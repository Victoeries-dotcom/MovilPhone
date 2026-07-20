<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\HistorialEstado;
use App\Models\User;
use App\Models\MovimientoCaja;
use App\Support\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrdenServicioController extends Controller
{
    /**
     * Muestra únicamente las órdenes de la sucursal activa.
     * Se conecta con la selección guardada en sesión y con
     * ordenes_servicio.sucursal_id para filtrar listado y estadísticas.
     */
    public function index(Request $request)
    {
        // Usa la sucursal elegida en el menú; la asignada al usuario funciona como respaldo.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        $sucursalActiva = $sucursalActivaId ? Sucursal::find($sucursalActivaId) : null;

        $query = OrdenServicio::with(['cliente', 'sucursal', 'tecnico']);

        // El filtro se aplica siempre desde el servidor y no puede cambiarse mediante la URL.
        if ($sucursalActiva) {
            $query->where('sucursal_id', $sucursalActiva->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->search) {
            // Agrupa nombre, teléfono y folio para conservar el filtro de sucursal.
            $query->where(function ($busqueda) use ($request) {
                $busqueda
                    ->whereHas('cliente', function ($cliente) use ($request) {
                        $cliente->where('nombre', 'like', '%'.$request->search.'%')
                            ->orWhere('telefono_principal', 'like', '%'.$request->search.'%');
                    })
                    ->orWhere('numero_os', 'like', '%'.$request->search.'%');
            });
        }

        $ordenes = $query->latest()->get();
        $sucursales = $sucursalActiva ? collect([$sucursalActiva]) : collect();
        // Carga únicamente usuarios con rol Técnico pertenecientes a la sucursal activa.
        // Esta colección alimenta el selector del modal Entregar equipo.
        $tecnicos = User::query()
            ->where('rol', 'tecnico')
            ->when(
                $sucursalActiva,
                fn ($usuarios) => $usuarios->where('sucursal_id', $sucursalActiva->id),
                fn ($usuarios) => $usuarios->whereRaw('1 = 0')
            )
            ->orderBy('name')
            ->get();

        // Esta consulta base conecta todas las tarjetas con la misma sucursal activa.
        $statsQuery = OrdenServicio::query();
        if ($sucursalActiva) {
            $statsQuery->where('sucursal_id', $sucursalActiva->id);
        } else {
            $statsQuery->whereRaw('1 = 0');
        }

        $stats = [
            'recibidos' => (clone $statsQuery)->whereIn('estado', ['RECIBIDO', 'ESPERANDO AUTORIZACIÓN', 'AUTORIZADO'])->count(),
            'diagnostico' => (clone $statsQuery)->where('estado', 'EN DIAGNÓSTICO')->count(),
            'reparacion' => (clone $statsQuery)->whereIn('estado', ['EN REPARACIÓN', 'ESPERANDO REFACCIÓN'])->count(),
            'listos' => (clone $statsQuery)->whereIn('estado', ['TERMINADO', 'NOTIFICADO'])->count(),
            'rechazado' => (clone $statsQuery)->where('estado', 'RECHAZADO')->count(),
            'entregado' => (clone $statsQuery)->where('estado', 'ENTREGADO')->count(),
            'garantia' => (clone $statsQuery)->where('estado', 'GARANTÍA')->count(),
        ];

        $estados = ['RECIBIDO', 'EN DIAGNÓSTICO', 'ESPERANDO AUTORIZACIÓN', 'AUTORIZADO', 'RECHAZADO', 'EN REPARACIÓN', 'ESPERANDO REFACCIÓN', 'TERMINADO', 'NOTIFICADO', 'ENTREGADO', 'GARANTÍA'];

        $contadorEstados = [];
        foreach ($estados as $e) {
            // Los contadores auxiliares también respetan ordenes_servicio.sucursal_id.
            $contadorEstados[$e] = (clone $statsQuery)->where('estado', $e)->count();
        }

        return view('ordenes.index', compact(
            'ordenes',
            'sucursales',
            'tecnicos',
            'stats',
            'contadorEstados',
            'sucursalActiva'
        ));
    }

    /**
     * Busca un cliente anterior usando su teléfono como identificador único.
     * Se conecta con clientes.telefono_normalizado y devuelve los datos que
     * el asistente de Nueva OS usa para autocompletar sus primeros tres pasos.
     */
    public function buscarClientePorTelefono(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string|max:50',
        ]);

        $telefonoNormalizado = Cliente::normalizarTelefono($request->telefono);

        if ($telefonoNormalizado === '') {
            return response()->json([
                'message' => 'Ingresa un número telefónico válido.',
            ], 422);
        }

        $cliente = Cliente::withCount('ordenes')
            ->where('telefono_normalizado', $telefonoNormalizado)
            ->first();

        if (!$cliente) {
            return response()->json([
                'message' => 'No se encontró un cliente anterior con ese teléfono.',
            ], 404);
        }

        return response()->json([
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'telefono_principal' => $cliente->telefono_principal,
                'telefono_alternativo' => $cliente->telefono_alternativo,
                'servicios_anteriores' => $cliente->ordenes_count,
            ],
        ]);
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
        // Crea la llave telefónica canónica que conecta la OS con un solo cliente.
        $telefonoNormalizado = Cliente::normalizarTelefono($request->cliente_telefono);
        $request->merge([
            'cliente_telefono_normalizado' => $telefonoNormalizado,
        ]);

        $request->validate([
            'cliente_id'           => 'nullable|integer|exists:clientes,id',
            'cliente_nombre'       => 'required|string',
            'cliente_telefono'     => 'required|string|max:50',
            'cliente_telefono_normalizado' => 'required|string|max:80',
            'cliente_telefono_extra'=> 'nullable|string',
            'sucursal_id'          => 'required|exists:sucursales,id',
            'tipo_dispositivo'     => 'required|string',
            'marca'                => 'required|string',
            'modelo'               => 'required|string',
            'problema_reportado'   => 'required|string',
            'accesorios_entregados'=> 'nullable|string',
            'estado_fisico'        => 'required|string',
            'contrasena_dispositivo'=> 'nullable|string|max:255',
            'anticipo'             => 'nullable|numeric|min:0',
            'metodo_pago_anticipo' => 'nullable|string',
        ]);

        // Si el asistente seleccionó un cliente anterior, confirma que su ID y teléfono coincidan.
        $cliente = null;
        if ($request->filled('cliente_id')) {
            $cliente = Cliente::whereKey($request->cliente_id)
                ->where('telefono_normalizado', $telefonoNormalizado)
                ->first();

            if (!$cliente) {
                throw ValidationException::withMessages([
                    'cliente_telefono' => 'El teléfono fue modificado y ya no coincide con el cliente anterior seleccionado.',
                ]);
            }
        }

        // Reutiliza el registro existente por teléfono o crea uno solamente si realmente es nuevo.
        $cliente ??= Cliente::where('telefono_normalizado', $telefonoNormalizado)->first();

        if ($cliente) {
            $cliente->update([
                'nombre' => Str::upper($request->cliente_nombre),
                'telefono_principal' => $request->cliente_telefono,
                'telefono_alternativo' => $request->filled('cliente_telefono_extra')
                    ? $request->cliente_telefono_extra
                    : $cliente->telefono_alternativo,
                'sucursal_habitual_id' => $cliente->sucursal_habitual_id ?: $request->sucursal_id,
            ]);
        } else {
            $cliente = Cliente::create([
                'nombre' => Str::upper($request->cliente_nombre),
                'telefono_principal' => $request->cliente_telefono,
                'telefono_normalizado' => $telefonoNormalizado,
                'telefono_alternativo' => $request->cliente_telefono_extra,
                'sucursal_habitual_id' => $request->sucursal_id,
            ]);
        }

        // Genera el número de OS según la sucursal y lo conecta con el folio visible del servicio.
        $sucursal = Sucursal::find($request->sucursal_id);
        $prefix = $sucursal->nombre === 'Izamal' ? 'IZA' : 'BUC';
        $year = date('Y');
        $ultimo = OrdenServicio::where('sucursal_id', $request->sucursal_id)->count() + 1;
        $numero_os = $prefix . '-' . $year . '-' . str_pad($ultimo, 4, '0', STR_PAD_LEFT);

        // Crea la orden con los datos solicitados en Nueva OS y los conecta con ordenes_servicio.
        $orden = OrdenServicio::create([
            'numero_os'             => $numero_os,
            'cliente_id'            => $cliente->id,
            'cliente_telefono_extra' => $request->cliente_telefono_extra,
            'sucursal_id'           => $request->sucursal_id,
            'tecnico_id'            => $request->tecnico_id,
            'tipo_dispositivo'      => $request->tipo_dispositivo,
            'marca'                 => $request->marca,
            'modelo'                => $request->modelo,
            'imei'                  => $request->imei,
            'problema_reportado'    => $request->problema_reportado,
            'accesorios_entregados' => $request->accesorios_entregados ?: 'NINGUNO',
            'estado_fisico'         => $request->estado_fisico,
            // Guarda el patrón, PIN o contraseña del equipo y se conecta con el detalle de la orden.
            'contrasena_dispositivo'=> $request->contrasena_dispositivo,
            'cobro_diagnostico'     => 0,
            'anticipo'              => $request->anticipo ?? 0,
            'metodo_pago_anticipo'  => $request->metodo_pago_anticipo ?? 'efectivo',
        ]);

        // Registrar en historial
        HistorialEstado::create([
            'os_id'  => $orden->id,
            'estado' => 'RECIBIDO',
        ]);

        // Sincroniza el anticipo con Caja y conecta el cobro con la orden mediante os_id.
        $this->sincronizarCobroOrdenEnCaja($orden);

        // Registra la nueva orden para que el admin vea quién capturó el servicio.
        AdminActivityLogger::registrar(
            'ÓRDENES',
            'CREADA',
            'Orden '.$numero_os.' creada para '.$cliente->nombre,
            $orden->sucursal_id,
            $orden
        );

        return redirect()->route('ordenes.index')->with('success', 'Orden '.$numero_os.' creada correctamente.');
    }

    // Ver detalle de una OS
    public function show(OrdenServicio $ordenServicio)
    {
        $ordenServicio->load(['cliente', 'sucursal', 'tecnico', 'historial']);
        $transiciones = OrdenServicio::TRANSICIONES[$ordenServicio->estado] ?? [];
        return view('ordenes.show', compact('ordenServicio', 'transiciones'));
    }

    /**
     * Muestra el sticker imprimible de la orden.
     * Se conecta con resources/views/ordenes/sticker.blade.php y usa los datos guardados de la OS.
     */
    public function sticker(OrdenServicio $ordenServicio)
    {
        $ordenServicio->load(['cliente', 'sucursal']);
        return view('ordenes.sticker', compact('ordenServicio'));
    }

    // Mostrar formulario de edición
    public function edit(OrdenServicio $ordenServicio)
    {
        // Carga cliente y sucursal para mostrar exactamente los datos capturados en Nueva OS.
        $ordenServicio->load(['cliente', 'sucursal']);

        // Muestra técnicos de la misma sucursal y conserva al técnico asignado aunque sea un usuario antiguo.
        $tecnicos = User::where(function ($query) use ($ordenServicio) {
            $query->where('sucursal_id', $ordenServicio->sucursal_id)
                ->orWhereNull('sucursal_id')
                ->when($ordenServicio->tecnico_id, function ($usuarios) use ($ordenServicio) {
                    $usuarios->orWhere('id', $ordenServicio->tecnico_id);
                });
        })->orderBy('name')->get();

        return view('ordenes.edit', compact('ordenServicio', 'tecnicos'));
    }

    // Guardar edición
    public function update(Request $request, OrdenServicio $ordenServicio)
    {
        // Normaliza el teléfono editado para conservar la identidad única del cliente.
        $telefonoNormalizado = Cliente::normalizarTelefono($request->cliente_telefono);
        $request->merge([
            'cliente_telefono_normalizado' => $telefonoNormalizado,
        ]);

        $request->validate([
            'cliente_nombre'          => 'required|string|max:255',
            'cliente_telefono'        => 'required|string|max:30',
            'cliente_telefono_normalizado' => 'required|string|max:80',
            'marca'                  => 'required|string',
            'modelo'                 => 'required|string',
            'tipo_dispositivo'       => 'required|string',
            'cliente_telefono_extra' => 'nullable|string|max:30',
            'imei'                   => 'nullable|string|max:255',
            'tecnico_id'             => 'nullable|exists:users,id',
            'problema_reportado'     => 'required|string',
            'problema_diagnosticado' => 'nullable|string',
            'accesorios_entregados'  => 'nullable|string',
            'estado_fisico'          => 'required|string',
            'contrasena_dispositivo' => 'nullable|string|max:255',
            'cobro_diagnostico'      => 'nullable|numeric|min:0',
            'mano_obra'              => 'nullable|numeric|min:0',
            'presupuesto_total'      => 'nullable|numeric|min:0',
            'anticipo'               => 'nullable|numeric|min:0',
            'metodo_pago_anticipo'   => 'nullable|in:efectivo,transferencia,tarjeta',
            'fecha_entrega_estimada' => 'nullable|date',
        ]);

        // Impide asignar a este cliente el teléfono único que ya identifica a otro registro.
        $telefonoPerteneceAOtroCliente = Cliente::where(
            'telefono_normalizado',
            $telefonoNormalizado
        )
            ->where('id', '!=', $ordenServicio->cliente_id)
            ->exists();

        if ($telefonoPerteneceAOtroCliente) {
            throw ValidationException::withMessages([
                'cliente_telefono' => 'Ese teléfono ya identifica a otro cliente registrado.',
            ]);
        }

        // Actualiza los datos personales en clientes; la relación se mantiene mediante cliente_id.
        $ordenServicio->cliente->update([
            'nombre' => Str::upper($request->cliente_nombre),
            'telefono_principal' => $request->cliente_telefono,
            'telefono_normalizado' => $telefonoNormalizado,
            'telefono_alternativo' => $request->cliente_telefono_extra,
        ]);

        // Actualiza el equipo con los mismos campos de Nueva OS y normaliza textos descriptivos en mayúsculas.
        $ordenServicio->update([
            'tecnico_id' => $request->tecnico_id,
            'marca' => Str::upper($request->marca),
            'modelo' => Str::upper($request->modelo),
            'tipo_dispositivo' => Str::upper($request->tipo_dispositivo),
            'cliente_telefono_extra' => $request->cliente_telefono_extra,
            'imei' => $request->filled('imei') ? Str::upper($request->imei) : null,
            'problema_reportado' => Str::upper($request->problema_reportado),
            'problema_diagnosticado' => $request->filled('problema_diagnosticado')
                ? Str::upper($request->problema_diagnosticado)
                : null,
            'accesorios_entregados' => $request->filled('accesorios_entregados')
                ? Str::upper($request->accesorios_entregados)
                : 'NINGUNO',
            'estado_fisico' => Str::upper($request->estado_fisico),
            // La contraseña se conserva exactamente porque puede distinguir mayúsculas y minúsculas.
            'contrasena_dispositivo' => $request->contrasena_dispositivo,
            'cobro_diagnostico' => $request->cobro_diagnostico ?? 0,
            'presupuesto_total' => $request->presupuesto_total ?? 0,
            'mano_obra' => $request->mano_obra ?? 0,
            'fecha_entrega_estimada' => $request->fecha_entrega_estimada,
            'anticipo' => $request->anticipo ?? 0,
            'metodo_pago_anticipo' => $request->metodo_pago_anticipo ?? 'efectivo',
        ]);

        // Actualiza la misma fila de Caja cuando cambian el anticipo o el cobro de la orden.
        $this->sincronizarCobroOrdenEnCaja($ordenServicio->fresh());

        // Registra la edición para que el admin vea qué orden fue modificada.
        AdminActivityLogger::registrar(
            'ÓRDENES',
            'ACTUALIZADA',
            'Orden '.$ordenServicio->numero_os.' actualizada',
            $ordenServicio->sucursal_id,
            $ordenServicio
        );

        return redirect()->route('ordenes.show', $ordenServicio)->with('success', 'Orden actualizada correctamente.');
    }

    // Cambiar estado de una orden desde la lista o desde el detalle de la OS.
    public function avanzarEstado(Request $request, OrdenServicio $ordenServicio)
    {
        // Estados permitidos: se conectan con ordenes_servicio.estado y evitan guardar textos no válidos.
        $estadosPermitidos = [
            'RECIBIDO',
            'EN DIAGNÓSTICO',
            'ESPERANDO AUTORIZACIÓN',
            'AUTORIZADO',
            'RECHAZADO',
            'EN REPARACIÓN',
            'ESPERANDO REFACCIÓN',
            'TERMINADO',
            'NOTIFICADO',
            'ENTREGADO',
            'GARANTÍA',
        ];

        $nuevoEstado = $request->estado;

        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            return back()->with('error', 'Estado no válido para la orden.');
        }

        if ($ordenServicio->estado === $nuevoEstado) {
            return back()->with('success', 'La orden ya está en: '.$nuevoEstado);
        }

        $ordenServicio->update(['estado' => $nuevoEstado]);

        // HistorialEstado guarda la evidencia del cambio y se conecta con el detalle de la orden.
        HistorialEstado::create([
            'os_id'  => $ordenServicio->id,
            'estado' => $nuevoEstado,
            'nota'   => $request->nota ?: 'Cambio manual desde el menú de órdenes.',
        ]);

        // Registra el cambio de estado para que el admin siga el avance del servicio en actividad.
        AdminActivityLogger::registrar(
            'ÓRDENES',
            'ESTADO',
            'Orden '.$ordenServicio->numero_os.' cambió a '.$nuevoEstado,
            $ordenServicio->sucursal_id,
            $ordenServicio
        );

        return back()->with('success', 'Estado actualizado a: '.$nuevoEstado);
    }

    /**
     * Entrega el equipo al cliente y cierra la orden como ENTREGADO.
     * Se conecta con el modal de resources/views/ordenes/index.blade.php, historial_estados y movimientos_caja.
     */
    public function entregar(Request $request, OrdenServicio $ordenServicio)
    {
        $request->validate([
            // Valida en MySQL que el usuario sea técnico y pertenezca a la sucursal de la OS.
            'tecnico_entrega_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($ordenServicio) {
                    $query->where('rol', 'tecnico')
                        ->where('sucursal_id', $ordenServicio->sucursal_id);
                }),
            ],
            'cobro_final' => 'nullable|numeric|min:0',
        ]);

        $tecnicoEntrega = User::findOrFail($request->tecnico_entrega_id);
        $cobroFinal = (float) ($request->cobro_final ?? 0);
        $anticipo = (float) ($ordenServicio->anticipo ?? 0);
        $totalRegistrado = $anticipo + $cobroFinal;

        // Guarda tecnico_id y conecta permanentemente la orden con el técnico seleccionado.
        $ordenServicio->update([
            'estado' => 'ENTREGADO',
            'fecha_entrega_real' => now(),
            'cobro_diagnostico' => $cobroFinal,
            'tecnico_id' => $tecnicoEntrega->id,
        ]);

        // Guarda evidencia del técnico que realizó la reparación dentro del historial de la orden.
        HistorialEstado::create([
            'os_id' => $ordenServicio->id,
            'estado' => 'ENTREGADO',
            'nota' => 'Equipo entregado. Técnico que realizó la reparación: '.$tecnicoEntrega->name,
        ]);

        // Actualiza una sola fila financiera para evitar contar dos veces el anticipo al entregar.
        $this->sincronizarCobroOrdenEnCaja($ordenServicio->fresh());

        // Registra la entrega para que el admin vea el cierre de la orden en el panel de actividad.
        AdminActivityLogger::registrar(
            'ÓRDENES',
            'ENTREGADA',
            'Orden '.$ordenServicio->numero_os.' entregada por '.$tecnicoEntrega->name,
            $ordenServicio->sucursal_id,
            $ordenServicio
        );

        return redirect()->route('ordenes.ticketEntrega', $ordenServicio)
            ->with('tecnico_entrega', $tecnicoEntrega->name)
            ->with('cobro_final', $cobroFinal)
            ->with('total_registrado', $totalRegistrado);
    }

    /**
     * Muestra el ticket final de entrega.
     * Se conecta con resources/views/ordenes/ticket-entrega.blade.php y usa los datos cerrados de la OS.
     */
    public function ticketEntrega(OrdenServicio $ordenServicio)
    {
        $ordenServicio->load(['cliente', 'sucursal', 'tecnico']);
        $tecnicoEntrega = session('tecnico_entrega', $ordenServicio->tecnico->name ?? '—');
        $cobroFinal = session('cobro_final', $ordenServicio->cobro_diagnostico ?? 0);
        $totalRegistrado = session('total_registrado', ($ordenServicio->anticipo ?? 0) + ($ordenServicio->cobro_diagnostico ?? 0));

        // La política se conecta con ConfiguracionController y aparece al final del ticket.
        $politica = Schema::hasTable('configuraciones')
            ? DB::table('configuraciones')->where('clave', 'politica_garantia')->value('valor')
            : null;

        return view('ordenes.ticket-entrega', compact(
            'ordenServicio',
            'tecnicoEntrega',
            'cobroFinal',
            'totalRegistrado',
            'politica'
        ));
    }

    /**
     * Rechaza una OS, guarda el motivo y registra la devolución como egreso de Caja.
     * Se conecta con historial_estados, movimientos_caja y la sucursal activa.
     */
    public function rechazar(Request $request, OrdenServicio $ordenServicio)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
            'devolucion' => 'nullable|numeric|min:0',
        ]);

        $sucursalId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        abort_if(!$sucursalId || (int) $ordenServicio->sucursal_id !== (int) $sucursalId, 403);

        if ($ordenServicio->estado === 'RECHAZADO') {
            return redirect()->route('ordenes.index')->with('error', 'La orden ya está marcada como RECHAZADA.');
        }

        $anticipo = (float) ($ordenServicio->anticipo ?? 0);
        $devolucion = (float) ($request->devolucion ?? 0);
        if ($devolucion > $anticipo) {
            throw ValidationException::withMessages([
                'devolucion' => 'La devolución no puede ser mayor al anticipo de $'.number_format($anticipo, 2).'.',
            ]);
        }

        $motivo = Str::upper(trim($request->motivo));

        DB::transaction(function () use ($ordenServicio, $motivo, $devolucion) {
            $ordenServicio->update(['estado' => 'RECHAZADO']);

            HistorialEstado::create([
                'os_id' => $ordenServicio->id,
                'estado' => 'RECHAZADO',
                'nota' => 'MOTIVO: '.$motivo.($devolucion > 0
                    ? ' | DEVOLUCIÓN: $'.number_format($devolucion, 2)
                    : ' | SIN DEVOLUCIÓN'),
            ]);

            $consultaDevolucion = MovimientoCaja::where('os_id', $ordenServicio->id)
                ->where('categoria', 'DEVOLUCIÓN DE ANTICIPO');

            if ($devolucion <= 0) {
                $consultaDevolucion->delete();
                return;
            }

            // Un egreso independiente conserva el anticipo original y permite calcular el balance neto.
            MovimientoCaja::updateOrCreate(
                [
                    'os_id' => $ordenServicio->id,
                    'categoria' => 'DEVOLUCIÓN DE ANTICIPO',
                ],
                [
                    'sucursal_id' => $ordenServicio->sucursal_id,
                    'tipo' => 'EGRESO',
                    'monto' => $devolucion,
                    'metodo_pago' => strtolower($ordenServicio->metodo_pago_anticipo ?: 'efectivo'),
                    'anticipo' => 0,
                    'saldo_pendiente' => 0,
                    'es_anticipo' => false,
                    'es_pago_final' => false,
                    'descripcion' => 'RECHAZO '.$ordenServicio->numero_os.': '.$motivo,
                    'user_id' => auth()->id(),
                ]
            );
        });

        AdminActivityLogger::registrar(
            'ÓRDENES',
            'RECHAZADA',
            'Orden '.$ordenServicio->numero_os.' rechazada'.($devolucion > 0
                ? ' con devolución de $'.number_format($devolucion, 2)
                : ' sin devolución'),
            $ordenServicio->sucursal_id,
            $ordenServicio
        );

        return redirect()->route('ordenes.index')
            ->with('success', 'Orden '.$ordenServicio->numero_os.' marcada como RECHAZADA.');
    }

    /**
     * Crea o actualiza el cobro acumulado de una orden en una sola fila de Caja.
     * Se conecta con anticipo, cobro_diagnostico y metodo_pago_anticipo de ordenes_servicio.
     */
    private function sincronizarCobroOrdenEnCaja(OrdenServicio $ordenServicio): void
    {
        $anticipo = (float) ($ordenServicio->anticipo ?? 0);
        $diagnostico = (float) ($ordenServicio->cobro_diagnostico ?? 0);
        $total = $anticipo + $diagnostico;

        $movimiento = MovimientoCaja::where('os_id', $ordenServicio->id)
            ->where('categoria', 'Orden de Servicio')
            ->first();

        if ($total <= 0) {
            // Si la orden ya no tiene cobros, elimina únicamente su fila financiera vacía.
            $movimiento?->delete();
            return;
        }

        // Construye una descripción legible igual a la mostrada en la tabla de Caja.
        if ($anticipo > 0 && $diagnostico > 0) {
            $descripcion = 'Anticipo $'.number_format($anticipo, 2).' + Diagnóstico $'.number_format($diagnostico, 2);
        } elseif ($anticipo > 0) {
            $descripcion = 'Anticipo $'.number_format($anticipo, 2);
        } else {
            $descripcion = 'Diagnóstico $'.number_format($diagnostico, 2);
        }

        $datos = [
            'sucursal_id' => $ordenServicio->sucursal_id,
            'tipo' => 'INGRESO',
            'categoria' => 'Orden de Servicio',
            'monto' => $total,
            'metodo_pago' => strtolower($ordenServicio->metodo_pago_anticipo ?: 'efectivo'),
            // Conserva el anticipo por separado para calcular la tarjeta Total Anticipos.
            'anticipo' => $anticipo,
            'saldo_pendiente' => max(0, (float) ($ordenServicio->presupuesto_total ?? 0) - $total),
            'es_anticipo' => $anticipo > 0,
            'es_pago_final' => $ordenServicio->estado === 'ENTREGADO',
            'descripcion' => $descripcion,
            'os_id' => $ordenServicio->id,
            'user_id' => auth()->id(),
        ];

        if ($movimiento) {
            $movimiento->update($datos);
        } else {
            MovimientoCaja::create($datos);
        }
    }

    // Eliminar OS
    public function destroy(OrdenServicio $ordenServicio)
    {
        $numeroOs = $ordenServicio->numero_os;
        $sucursalId = $ordenServicio->sucursal_id;

        $ordenServicio->delete();

        // Registra la eliminación para que el admin sepa qué orden salió del sistema.
        AdminActivityLogger::registrar(
            'ÓRDENES',
            'ELIMINADA',
            'Orden '.$numeroOs.' eliminada',
            $sucursalId
        );

        return redirect()->route('ordenes.index')->with('success', 'Orden eliminada.');
    }
}



