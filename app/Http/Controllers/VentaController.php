<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Support\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function index()
    {
        // Obtiene la sucursal seleccionada en el sistema; se conecta con sucursales.id
        // y usa la sucursal asignada al usuario solamente como respaldo.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        $sucursalActiva = $sucursalActivaId ? Sucursal::find($sucursalActivaId) : null;

        // Filtra desde la base de datos por ventas.sucursal_id para impedir que el
        // listado y sus estadísticas mezclen ventas de Buctzotz con las de Izamal.
        $ventas = Venta::with(['cliente', 'sucursal', 'usuario'])
            ->when(
                $sucursalActiva,
                fn ($query) => $query->where('sucursal_id', $sucursalActiva->id),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->latest()
            ->get();

        return view('ventas.index', compact('ventas', 'sucursalActiva'));
    }

    /**
     * Abre la venta con piezas disponibles de la sucursal activa.
     * Se conecta con inventario.sucursal_id y evita usar otra sucursal como respaldo silencioso.
     */
    public function create()
    {
        $sucursalId = $this->sucursalActivaId();
        abort_unless($sucursalId, 422, 'Selecciona una sucursal antes de registrar una venta.');

        $inventario = Inventario::where('cantidad_disponible', '>', 0)
            ->where('sucursal_id', $sucursalId)
            ->orderBy('nombre')
            ->get();

        return view('ventas.create', compact('inventario'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            'productos' => 'required|array|min:1',
            'productos.*.nombre' => 'required|string|max:255',
            'productos.*.otro_nombre' => 'nullable|string|max:255',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
            'productos.*.inventario_id' => 'nullable|integer|exists:inventario,id', // ✅ CAMBIO 2
        ]);

        // La venta, sus detalles y el ingreso de Caja comparten la sucursal autenticada.
        $sucursalId = $this->sucursalActivaId();
        abort_unless($sucursalId, 422, 'Selecciona una sucursal antes de registrar una venta.');

        foreach ($request->productos as $index => $prod) {
            $inventarioId = $prod['inventario_id'] ?? null;

            if ($inventarioId) {
                // Valida antes de guardar que la pieza exista en la sucursal actual
                // y que la cantidad solicitada no supere el stock disponible.
                $pieza = Inventario::where('id', $inventarioId)
                    ->where('sucursal_id', $sucursalId)
                    ->first();

                if (! $pieza) {
                    throw ValidationException::withMessages([
                        "productos.$index.inventario_id" => 'La pieza seleccionada no pertenece a esta sucursal.',
                    ]);
                }

                if ((int) $prod['cantidad'] > (int) $pieza->cantidad_disponible) {
                    throw ValidationException::withMessages([
                        "productos.$index.cantidad" => "No puedes vender {$prod['cantidad']} piezas de {$pieza->nombre}; solo hay {$pieza->cantidad_disponible} disponibles en esta sucursal.",
                    ]);
                }
            }
        }

        // La transaccion devuelve la venta para conectarla con auditoria al terminar correctamente.
        $venta = DB::transaction(function () use ($request, $sucursalId) {

            $cliente = Cliente::firstOrCreate(
                [
                    'nombre' => $request->cliente_nombre,
                    'sucursal_habitual_id' => $sucursalId,
                ],
                [
                    'telefono_principal' => 'VENTA-'.now()->format('YmdHis'),
                ]
            );

            $total = 0;
            $detalles = [];

            foreach ($request->productos as $prod) {
                $nombreProducto = $prod['nombre'] === 'otro'
                    ? ($prod['otro_nombre'] ?? 'Producto sin nombre')
                    : $prod['nombre'];

                $subtotal = $prod['precio_unitario'] * $prod['cantidad'];
                $total += $subtotal;

                // ✅ CAMBIO 3: lee inventario_id y descuenta stock
                $inventarioId = $prod['inventario_id'] ?? null;

                if ($inventarioId) {
                    // Bloquea la fila durante la venta para evitar que dos registros
                    // descuenten el mismo stock al mismo tiempo.
                    $pieza = Inventario::where('id', $inventarioId)
                        ->where('sucursal_id', $sucursalId)
                        ->lockForUpdate()
                        ->first();

                    if (! $pieza || (int) $prod['cantidad'] > (int) $pieza->cantidad_disponible) {
                        throw ValidationException::withMessages([
                            'productos' => 'El stock cambió antes de guardar la venta. Revisa la cantidad disponible e inténtalo de nuevo.',
                        ]);
                    }

                    $pieza->decrement('cantidad_disponible', $prod['cantidad']);
                }

                $detalles[] = [
                    'inventario_id' => $inventarioId,
                    'nombre_producto' => $nombreProducto,
                    'cantidad' => $prod['cantidad'],
                    'precio_unitario' => $prod['precio_unitario'],
                    'subtotal' => $subtotal,
                ];
            }

            $venta = Venta::create([
                'cliente_id' => $cliente->id,
                'usuario_id' => Auth::id(),
                'sucursal_id' => $sucursalId,
                'total' => $total,
                'estado' => 'completada',
                'notas' => $request->notas,
            ]);

            foreach ($detalles as $detalle) {
                $venta->detalles()->create($detalle);
            }

            MovimientoCaja::create([
                'tipo' => 'INGRESO',
                'categoria' => 'Venta',
                'monto' => $total,
                // Las ventas se registran inicialmente como efectivo y se conectan con el filtro de Caja.
                'metodo_pago' => 'efectivo',
                'sucursal_id' => $sucursalId,
                'descripcion' => 'Venta #'.$venta->id,
                'os_id' => null,
                'user_id' => Auth::id(),
            ]);

            return $venta;
        });

        AdminActivityLogger::registrar(
            'VENTAS',
            'CREAR',
            'Venta #'.$venta->id.' registrada por $'.number_format($venta->total, 2),
            $sucursalId,
            $venta
        );

        return redirect()->route('ventas.index')->with('success', 'Venta registrada correctamente.');
    }

    public function show(Venta $venta)
    {
        // Protege la ficha para que una URL manual no cruce la sucursal activa.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        abort_unless((int) $venta->sucursal_id === (int) $sucursalActivaId, 404);
        $venta->load(['cliente', 'sucursal', 'usuario', 'detalles.inventario']);

        return view('ventas.show', compact('venta'));
    }

    public function destroy(Venta $venta)
    {
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        abort_unless((int) $venta->sucursal_id === (int) $sucursalActivaId, 404);
        $descripcion = 'Venta #'.$venta->id.' eliminada por $'.number_format($venta->total, 2);
        $sucursalId = $venta->sucursal_id;
        AdminActivityLogger::registrar('VENTAS', 'ELIMINAR', $descripcion, $sucursalId, $venta);
        $venta->delete();

        return redirect()->route('ventas.index')->with('success', 'Venta eliminada.');
    }

    /**
     * Centraliza la sucursal de Ventas para index, create, store, show y destroy.
     * Se conecta con el selector administrativo y con users.sucursal_id para Buctzotz e Izamal.
     */
    private function sucursalActivaId(): ?int
    {
        $id = session('sucursal_id') ?: auth()->user()?->sucursal_id;

        return $id ? (int) $id : null;
    }
}
