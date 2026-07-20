<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\OrdenServicio;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Busca registros autorizados de la sucursal activa desde la barra superior.
     * Se conecta con Clientes, Ordenes, Inventario y Ventas y devuelve JSON para el buscador global.
     */
    public function index(Request $request)
    {
        $datos = $request->validate(['q' => 'required|string|min:2|max:80']);
        $termino = trim($datos['q']);
        $sucursalId = $this->sucursalActivaId();
        $rol = auth()->user()->rol;
        $resultados = collect();

        if (in_array($rol, ['superusuario', 'usuario', 'tecnico', 'vendedor'], true)) {
            Cliente::query()
                ->where('sucursal_habitual_id', $sucursalId)
                ->where(fn (Builder $query) => $query
                    ->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('telefono_principal', 'like', "%{$termino}%"))
                ->limit(5)
                ->get()
                ->each(fn (Cliente $cliente) => $resultados->push([
                    'tipo' => 'cliente',
                    'icono' => 'contact',
                    'titulo' => $cliente->nombre,
                    'detalle' => $cliente->telefono_principal,
                    'url' => route('clientes.show', $cliente),
                    'id' => $cliente->id,
                ]));
        }

        if (in_array($rol, ['superusuario', 'usuario', 'tecnico'], true)) {
            OrdenServicio::with('cliente')
                ->where('sucursal_id', $sucursalId)
                ->where(fn (Builder $query) => $query
                    ->where('numero_os', 'like', "%{$termino}%")
                    ->orWhere('marca', 'like', "%{$termino}%")
                    ->orWhere('modelo', 'like', "%{$termino}%")
                    ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->where('nombre', 'like', "%{$termino}%")))
                ->limit(5)
                ->get()
                ->each(fn (OrdenServicio $orden) => $resultados->push([
                    'tipo' => 'orden',
                    'icono' => 'wrench',
                    'titulo' => $orden->numero_os,
                    'detalle' => ($orden->cliente->nombre ?? 'SIN CLIENTE').' · '.$orden->estado,
                    'url' => route('ordenes.show', $orden),
                    'id' => $orden->id,
                ]));
        }

        if (in_array($rol, ['superusuario', 'capturista'], true)) {
            Inventario::query()
                ->where('sucursal_id', $sucursalId)
                ->where(fn (Builder $query) => $query
                    ->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('categoria', 'like', "%{$termino}%")
                    ->orWhere('dispositivo_compatible', 'like', "%{$termino}%"))
                ->limit(5)
                ->get()
                ->each(fn (Inventario $pieza) => $resultados->push([
                    'tipo' => 'inventario',
                    'icono' => 'package-open',
                    'titulo' => $pieza->nombre,
                    'detalle' => $pieza->categoria.' · '.$pieza->cantidad_disponible.' disponibles',
                    'url' => route('inventario.edit', $pieza),
                    'id' => $pieza->id,
                ]));
        }

        if (in_array($rol, ['superusuario', 'vendedor'], true)) {
            Venta::with('cliente')
                ->where('sucursal_id', $sucursalId)
                ->where(fn (Builder $query) => $query
                    ->where('id', 'like', "%{$termino}%")
                    ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->where('nombre', 'like', "%{$termino}%")))
                ->limit(5)
                ->get()
                ->each(fn (Venta $venta) => $resultados->push([
                    'tipo' => 'venta',
                    'icono' => 'shopping-cart',
                    'titulo' => 'VENTA #'.$venta->id,
                    'detalle' => ($venta->cliente->nombre ?? 'SIN CLIENTE').' · $'.number_format($venta->total, 2),
                    'url' => route('ventas.show', $venta),
                    'id' => $venta->id,
                ]));
        }

        return response()->json(['resultados' => $resultados->take(15)->values()]);
    }

    /**
     * Devuelve un resumen para el panel lateral sin exponer datos sensibles.
     * Se conecta con el resultado seleccionado y conserva la autorizacion por sucursal.
     */
    public function quickView(string $tipo, int $id)
    {
        $sucursalId = $this->sucursalActivaId();

        // Esta matriz replica los permisos del buscador para impedir consultar un tipo por URL directa.
        $rolesPorTipo = [
            'cliente' => ['superusuario', 'usuario', 'tecnico', 'vendedor'],
            'orden' => ['superusuario', 'usuario', 'tecnico'],
            'inventario' => ['superusuario', 'capturista'],
            'venta' => ['superusuario', 'vendedor'],
        ];
        abort_unless(
            isset($rolesPorTipo[$tipo]) && in_array(auth()->user()->rol, $rolesPorTipo[$tipo], true),
            403
        );

        $datos = match ($tipo) {
            'cliente' => $this->clienteQuickView($id, $sucursalId),
            'orden' => $this->ordenQuickView($id, $sucursalId),
            'inventario' => $this->inventarioQuickView($id, $sucursalId),
            'venta' => $this->ventaQuickView($id, $sucursalId),
            default => abort(404),
        };

        return response()->json($datos);
    }

    /** Prepara la ficha resumida de un cliente y se conecta con su historial de ordenes. */
    private function clienteQuickView(int $id, ?int $sucursalId): array
    {
        $cliente = Cliente::withCount('ordenes')
            ->where('sucursal_habitual_id', $sucursalId)
            ->findOrFail($id);

        return [
            'titulo' => $cliente->nombre,
            'subtitulo' => 'CLIENTE',
            'campos' => [
                ['etiqueta' => 'Telefono', 'valor' => $cliente->telefono_principal],
                ['etiqueta' => 'Telefono alternativo', 'valor' => $cliente->telefono_alternativo ?: 'NO REGISTRADO'],
                ['etiqueta' => 'Servicios', 'valor' => $cliente->ordenes_count],
            ],
            'url' => route('clientes.show', $cliente),
        ];
    }

    /** Prepara la ficha resumida de una OS y se conecta con cliente y tecnico. */
    private function ordenQuickView(int $id, ?int $sucursalId): array
    {
        $orden = OrdenServicio::with(['cliente', 'tecnico'])
            ->where('sucursal_id', $sucursalId)
            ->findOrFail($id);

        return [
            'titulo' => $orden->numero_os,
            'subtitulo' => $orden->estado,
            'campos' => [
                ['etiqueta' => 'Cliente', 'valor' => $orden->cliente->nombre ?? 'SIN CLIENTE'],
                ['etiqueta' => 'Equipo', 'valor' => trim($orden->marca.' '.$orden->modelo)],
                ['etiqueta' => 'Tecnico', 'valor' => $orden->tecnico->name ?? 'SIN ASIGNAR'],
                ['etiqueta' => 'Problema', 'valor' => $orden->problema_reportado],
            ],
            'url' => route('ordenes.show', $orden),
        ];
    }

    /** Prepara la ficha de inventario y se conecta con existencias y precios actuales. */
    private function inventarioQuickView(int $id, ?int $sucursalId): array
    {
        $pieza = Inventario::where('sucursal_id', $sucursalId)->findOrFail($id);

        return [
            'titulo' => $pieza->nombre,
            'subtitulo' => $pieza->categoria,
            'campos' => [
                ['etiqueta' => 'Disponible', 'valor' => $pieza->cantidad_disponible],
                ['etiqueta' => 'Stock minimo', 'valor' => $pieza->stock_minimo],
                ['etiqueta' => 'Precio de venta', 'valor' => '$'.number_format($pieza->precio_venta, 2)],
                ['etiqueta' => 'Compatible', 'valor' => $pieza->dispositivo_compatible ?: 'NO REGISTRADO'],
            ],
            'url' => route('inventario.edit', $pieza),
        ];
    }

    /** Prepara la ficha de venta y se conecta con cliente, vendedor y detalles. */
    private function ventaQuickView(int $id, ?int $sucursalId): array
    {
        $venta = Venta::with(['cliente', 'usuario'])->withCount('detalles')
            ->where('sucursal_id', $sucursalId)
            ->findOrFail($id);

        return [
            'titulo' => 'VENTA #'.$venta->id,
            'subtitulo' => $venta->estado,
            'campos' => [
                ['etiqueta' => 'Cliente', 'valor' => $venta->cliente->nombre ?? 'SIN CLIENTE'],
                ['etiqueta' => 'Vendedor', 'valor' => $venta->usuario->name ?? 'SIN REGISTRO'],
                ['etiqueta' => 'Productos', 'valor' => $venta->detalles_count],
                ['etiqueta' => 'Total', 'valor' => '$'.number_format($venta->total, 2)],
            ],
            'url' => route('ventas.show', $venta),
        ];
    }

    /** Obtiene la sucursal elegida y evita mezclar resultados entre sedes. */
    private function sucursalActivaId(): ?int
    {
        $id = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        return $id ? (int) $id : null;
    }
}
