<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Support\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        // Separa el total de órdenes de los servicios ya cerrados.
        // La columna Servicios anteriores usa únicamente ENTREGADO o RECHAZADO.
        $query = Cliente::withCount('ordenes')
            ->withCount([
                'ordenes as servicios_anteriores_count' => function ($ordenes) {
                    $ordenes->whereIn('estado', ['ENTREGADO', 'RECHAZADO']);
                },
            ]);

        if (session('sucursal_id')) {
            $query->where('sucursal_habitual_id', session('sucursal_id'));
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%'.$request->search.'%')
                  ->orWhere('telefono_principal', 'like', '%'.$request->search.'%');
            });
        }

        $clientes = $query->latest()->get();
        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        // La sucursal se toma de la sesión, por lo que el asistente solo necesita mostrar la vista.
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        // Normaliza el teléfono antes de validar para impedir duplicados con guiones o espacios.
        $request->merge([
            'telefono_normalizado' => Cliente::normalizarTelefono($request->telefono_principal),
        ]);

        $request->validate([
            'nombre'             => 'required|string',
            'telefono_principal' => 'required|string|max:50',
            'telefono_normalizado' => 'required|string|max:80|unique:clientes,telefono_normalizado',
        ]);

        // Normaliza el registro antes de guardar: conecta el formulario con la tabla clientes.
        $cliente = Cliente::create([
            'nombre' => strtoupper($request->nombre),
            'telefono_principal' => $request->telefono_principal,
            'telefono_normalizado' => $request->telefono_normalizado,
            'telefono_alternativo' => $request->telefono_alternativo,
            'direccion' => $request->direccion,
            'sucursal_habitual_id' => $request->sucursal_habitual_id ?: session('sucursal_id'),
        ]);

        // Registra la captura para que el admin la vea en el panel de actividad.
        AdminActivityLogger::registrar(
            'CLIENTES',
            'CREADO',
            'Cliente creado: '.$cliente->nombre,
            $cliente->sucursal_habitual_id,
            $cliente
        );

        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    public function show(Cliente $cliente)
    {
        // Impide abrir desde la URL un cliente que pertenece a otra sucursal distinta de la seleccionada.
        $this->asegurarSucursalActiva($cliente);

        // Carga primero las órdenes recientes y su sucursal para dividir activas e históricas.
        $cliente->load([
            'sucursal',
            'ordenes' => fn ($ordenes) => $ordenes->with('sucursal')->latest(),
        ]);
        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        // Mantiene la edición conectada únicamente con la sucursal activa del sistema.
        $this->asegurarSucursalActiva($cliente);

        // El formulario solo necesita el cliente porque la sucursal ya no se modifica desde esta pantalla.
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        // Valida la sucursal antes de aceptar cualquier modificación del cliente.
        $this->asegurarSucursalActiva($cliente);

        // Usa el mismo identificador normalizado y permite conservar el teléfono del cliente editado.
        $request->merge([
            'telefono_normalizado' => Cliente::normalizarTelefono($request->telefono_principal),
        ]);

        $request->validate([
            'nombre'             => 'required|string',
            'telefono_principal' => 'required|string|max:50',
            'telefono_normalizado' => [
                'required',
                'string',
                'max:80',
                Rule::unique('clientes', 'telefono_normalizado')->ignore($cliente->id),
            ],
        ]);

        // Actualiza únicamente los campos visibles; dirección y sucursal habitual conservan su valor actual.
        $cliente->update([
            'nombre' => strtoupper($request->nombre),
            'telefono_principal' => $request->telefono_principal,
            'telefono_normalizado' => $request->telefono_normalizado,
            'telefono_alternativo' => $request->filled('telefono_alternativo')
                ? $request->telefono_alternativo
                : null,
        ]);

        // Registra la actualización para notificar al admin qué cliente cambió.
        AdminActivityLogger::registrar(
            'CLIENTES',
            'ACTUALIZADO',
            'Cliente actualizado: '.$cliente->nombre,
            $cliente->sucursal_habitual_id,
            $cliente
        );

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        // Protege la eliminación para que una sucursal no borre clientes de otra.
        $this->asegurarSucursalActiva($cliente);

        $nombre = $cliente->nombre;
        $sucursalId = $cliente->sucursal_habitual_id;
        $ordenesEliminadas = 0;

        // La transacción conecta clientes, órdenes, historial y caja: todo se elimina junto o no se elimina nada.
        DB::transaction(function () use ($cliente, &$ordenesEliminadas) {
            $cliente->load('ordenes');
            $ordenesEliminadas = $cliente->ordenes->count();

            foreach ($cliente->ordenes as $orden) {
                // Elimina los movimientos vinculados para no conservar cobros huérfanos en movimientos_caja.
                $orden->movimientosCaja()->delete();

                // Elimina los cambios de estado conectados con la orden en historial_estados.
                $orden->historial()->delete();

                // Elimina la orden antes del cliente para respetar la llave foránea cliente_id.
                $orden->delete();
            }

            $cliente->delete();
        });

        // Registra la eliminación para que el admin conozca qué cliente fue retirado.
        AdminActivityLogger::registrar(
            'CLIENTES',
            'ELIMINADO',
            'Cliente eliminado: '.$nombre.' junto con '.$ordenesEliminadas.' orden(es) relacionada(s).',
            $sucursalId
        );

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    /**
     * Verifica que el cliente pertenezca a la sucursal guardada en la sesión.
     * Se conecta con el selector global de sucursal para aislar los datos de cada taller.
     */
    private function asegurarSucursalActiva(Cliente $cliente): void
    {
        $sucursalActiva = session('sucursal_id');

        if ($sucursalActiva && (int) $cliente->sucursal_habitual_id !== (int) $sucursalActiva) {
            abort(403, 'El cliente no pertenece a la sucursal seleccionada.');
        }
    }
}


