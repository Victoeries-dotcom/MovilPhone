<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\HistorialEstado;
use App\Models\User;
use Illuminate\Http\Request;

class OrdenServicioController extends Controller
{
    // Mostrar lista de órdenes
    public function index(Request $request)
    {
        $query = OrdenServicio::with(['cliente', 'sucursal', 'tecnico']);

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }
        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        }
        if ($request->search) {
            $query->whereHas('cliente', function($q) use ($request) {
                $q->where('nombre', 'like', '%'.$request->search.'%')
                  ->orWhere('telefono_principal', 'like', '%'.$request->search.'%');
            })->orWhere('numero_os', 'like', '%'.$request->search.'%');
        }

        $ordenes = $query->latest()->get();
        $sucursales = Sucursal::all();
        $tecnicos = User::all();
        $stats = [
            'recibidos'   => OrdenServicio::where('estado', 'RECIBIDO')->count(),
            'en_proceso'  => OrdenServicio::whereIn('estado', ['EN DIAGNÓSTICO', 'EN REPARACIÓN', 'ESPERANDO REFACCIÓN'])->count(),
            'listos'      => OrdenServicio::whereIn('estado', ['TERMINADO', 'NOTIFICADO'])->count(),
            'esperando'   => OrdenServicio::where('estado', 'ESPERANDO AUTORIZACIÓN')->count(),
        ];

        return view('ordenes.index', compact('ordenes', 'sucursales', 'tecnicos', 'stats'));
    }

    // Mostrar formulario de nueva OS
    public function create()
    {
        $sucursales = Sucursal::all();
        $tecnicos = User::all();
        $clientes = Cliente::all();
        return view('ordenes.create', compact('sucursales', 'tecnicos', 'clientes'));
    }

    // Guardar nueva OS
    public function store(Request $request)
    {
        $request->validate([
            'cliente_nombre'       => 'required|string',
            'cliente_telefono'     => 'required|string',
            'sucursal_id'          => 'required|exists:sucursales,id',
            'marca'                => 'required|string',
            'modelo'               => 'required|string',
            'problema_reportado'   => 'required|string',
            'accesorios_entregados'=> 'required|string',
            'estado_fisico'        => 'required|string',
        ]);

        // Buscar o crear cliente
        $cliente = Cliente::firstOrCreate(
            ['telefono_principal' => $request->cliente_telefono],
            ['nombre' => $request->cliente_nombre, 'sucursal_habitual_id' => $request->sucursal_id]
        );

        // Generar número de OS
        $sucursal = Sucursal::find($request->sucursal_id);
        $prefix = $sucursal->nombre === 'Izamal' ? 'IZA' : 'BUC';
        $year = date('Y');
        $ultimo = OrdenServicio::where('sucursal_id', $request->sucursal_id)->count() + 1;
        $numero_os = $prefix . '-' . $year . '-' . str_pad($ultimo, 4, '0', STR_PAD_LEFT);

        $orden = OrdenServicio::create([
            'numero_os'             => $numero_os,
            'cliente_id'            => $cliente->id,
            'sucursal_id'           => $request->sucursal_id,
            'tecnico_id'            => $request->tecnico_id,
            'marca'                 => $request->marca,
            'modelo'                => $request->modelo,
            'imei'                  => $request->imei,
            'problema_reportado'    => $request->problema_reportado,
            'accesorios_entregados' => $request->accesorios_entregados,
            'estado_fisico'         => $request->estado_fisico,
            'cobro_diagnostico'     => $request->cobro_diagnostico ?? 0,
        ]);

        // Registrar en historial
        HistorialEstado::create([
            'os_id'  => $orden->id,
            'estado' => 'RECIBIDO',
        ]);

        return redirect()->route('ordenes.index')->with('success', 'Orden '.$numero_os.' creada correctamente.');
    }

    // Ver detalle de una OS
    public function show(OrdenServicio $ordenServicio)
    {
        $ordenServicio->load(['cliente', 'sucursal', 'tecnico', 'historial']);
        $transiciones = OrdenServicio::TRANSICIONES[$ordenServicio->estado] ?? [];
        return view('ordenes.show', compact('ordenServicio', 'transiciones'));
    }

    // Mostrar formulario de edición
    public function edit(OrdenServicio $ordenServicio)
    {
        $sucursales = Sucursal::all();
        $tecnicos = User::all();
        return view('ordenes.edit', compact('ordenServicio', 'sucursales', 'tecnicos'));
    }

    // Guardar edición
    public function update(Request $request, OrdenServicio $ordenServicio)
    {
        $request->validate([
            'marca'                  => 'required|string',
            'modelo'                 => 'required|string',
            'problema_reportado'     => 'required|string',
            'accesorios_entregados'  => 'required|string',
            'estado_fisico'          => 'required|string',
        ]);

        $ordenServicio->update($request->only([
            'tecnico_id', 'marca', 'modelo', 'imei',
            'problema_reportado', 'problema_diagnosticado',
            'accesorios_entregados', 'estado_fisico',
            'cobro_diagnostico', 'presupuesto_total',
            'mano_obra', 'fecha_entrega_estimada'
        ]));

        return redirect()->route('ordenes.show', $ordenServicio)->with('success', 'Orden actualizada correctamente.');
    }

    // Avanzar estado
    public function avanzarEstado(Request $request, OrdenServicio $ordenServicio)
    {
        $nuevoEstado = $request->estado;

        if (!$ordenServicio->puedeAvanzarA($nuevoEstado)) {
            return back()->with('error', 'Transición de estado no permitida.');
        }

        $ordenServicio->update(['estado' => $nuevoEstado]);

        HistorialEstado::create([
            'os_id'  => $ordenServicio->id,
            'estado' => $nuevoEstado,
            'nota'   => $request->nota,
        ]);

        return back()->with('success', 'Estado actualizado a: '.$nuevoEstado);
    }

    // Eliminar OS
    public function destroy(OrdenServicio $ordenServicio)
    {
        $ordenServicio->delete();
        return redirect()->route('ordenes.index')->with('success', 'Orden eliminada.');
    }
}