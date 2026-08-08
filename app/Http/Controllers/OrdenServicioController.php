<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DeviceCatalogEntry;
use App\Models\HistorialEstado;
use App\Models\MovimientoCaja;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Support\AdminActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Filtra por created_at dentro del día, semana ISO o mes que eligió el usuario.
        if ($rangoPeriodo = $this->rangoPeriodoSeleccionado($request)) {
            $query->whereBetween('created_at', $rangoPeriodo);
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
     * Convierte el periodo elegido en límites completos para ordenes_servicio.created_at.
     *
     * @return array{CarbonImmutable, CarbonImmutable}|null
     */
    private function rangoPeriodoSeleccionado(Request $request): ?array
    {
        $request->validate([
            'periodo' => ['nullable', Rule::in(['dia', 'semana', 'mes'])],
            'periodo_valor' => ['nullable', 'required_with:periodo', 'string', 'max:10'],
        ]);

        if (! $request->filled('periodo') || ! $request->filled('periodo_valor')) {
            return null;
        }

        $zonaHoraria = config('app.timezone');
        $valor = $request->string('periodo_valor')->toString();

        try {
            if ($request->periodo === 'dia' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $fecha)) {
                $inicio = CarbonImmutable::create((int) $fecha[1], (int) $fecha[2], (int) $fecha[3], 0, 0, 0, $zonaHoraria);

                if ($inicio->format('Y-m-d') === $valor) {
                    return [$inicio->startOfDay(), $inicio->endOfDay()];
                }
            }

            if ($request->periodo === 'semana' && preg_match('/^(\d{4})-W(\d{2})$/', $valor, $semana)) {
                $inicio = CarbonImmutable::create((int) $semana[1], 1, 4, 0, 0, 0, $zonaHoraria)
                    ->setISODate((int) $semana[1], (int) $semana[2]);

                if ($inicio->isoWeekYear === (int) $semana[1] && $inicio->isoWeek === (int) $semana[2]) {
                    return [$inicio->startOfWeek(), $inicio->endOfWeek()];
                }
            }

            if ($request->periodo === 'mes' && preg_match('/^(\d{4})-(\d{2})$/', $valor, $mes)) {
                $inicio = CarbonImmutable::create((int) $mes[1], (int) $mes[2], 1, 0, 0, 0, $zonaHoraria);

                if ($inicio->format('Y-m') === $valor) {
                    return [$inicio->startOfMonth(), $inicio->endOfMonth()];
                }
            }
        } catch (\Throwable) {
            // El mensaje uniforme evita aceptar fechas que PHP normalice silenciosamente.
        }

        throw ValidationException::withMessages([
            'periodo_valor' => 'Selecciona un día, semana o mes válido.',
        ]);
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
        $sucursalId = $this->sucursalActivaId();

        if ($telefonoNormalizado === '') {
            return response()->json([
                'message' => 'Ingresa un número telefónico válido.',
            ], 422);
        }

        $cliente = Cliente::withCount('ordenes')
            ->where('telefono_normalizado', $telefonoNormalizado)
            // El autocompletado solo consulta clientes visibles en la sucursal del usuario.
            ->when($sucursalId, fn ($clientes) => $clientes->where('sucursal_habitual_id', $sucursalId))
            ->first();

        if (! $cliente) {
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
        // El formulario recibe únicamente datos de la sucursal activa para evitar opciones cruzadas.
        $sucursalId = $this->sucursalActivaId();
        $sucursales = Sucursal::whereKey($sucursalId)->get();
        $tecnicos = User::where('rol', 'tecnico')->where('sucursal_id', $sucursalId)->orderBy('name')->get();
        $clientes = Cliente::where('sucursal_habitual_id', $sucursalId)->orderBy('nombre')->get();

        // Combina el catálogo inicial con los equipos escritos manualmente en órdenes anteriores.
        $deviceCatalog = $this->catalogoDispositivosActualizado();

        return view('ordenes.create', compact('sucursales', 'tecnicos', 'clientes', 'deviceCatalog'));
    }

    // Guardar nueva OS
    public function store(Request $request)
    {
        // Crea la llave telefónica solo cuando el cliente proporciona un número de contacto.
        $telefonoNormalizado = $request->filled('cliente_telefono')
            ? Cliente::normalizarTelefono($request->cliente_telefono)
            : null;
        $request->merge([
            'cliente_telefono_normalizado' => $telefonoNormalizado,
        ]);

        // Resuelve primero la sucursal para usarla también en las reglas de cliente y técnico.
        $sucursalId = $this->sucursalActivaId();
        abort_if(! $sucursalId || (int) $request->sucursal_id !== $sucursalId, 403);

        $request->validate([
            'cliente_id' => [
                'nullable',
                'integer',
                // El cliente anterior debe pertenecer a la misma sede que registra la nueva OS.
                Rule::exists('clientes', 'id')->where(
                    fn ($clientes) => $clientes->where('sucursal_habitual_id', $sucursalId)
                ),
            ],
            'cliente_nombre' => 'required|string|max:255',
            // El contacto es opcional; cuando se captura conserva exactamente 10 dígitos.
            'cliente_telefono' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'cliente_telefono_normalizado' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'cliente_telefono_extra' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'sucursal_id' => 'required|exists:sucursales,id',
            'tecnico_id' => [
                'nullable',
                // Evita asignar por petición manual un técnico de otra sucursal.
                Rule::exists('users', 'id')->where(
                    fn ($usuarios) => $usuarios
                        ->where('rol', 'tecnico')
                        ->where('sucursal_id', $sucursalId)
                ),
            ],
            'tipo_dispositivo' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'imei' => 'nullable|string|max:255',
            'problema_reportado' => 'required|string|max:5000',
            'accesorios_entregados' => 'nullable|string|max:2000',
            'estado_fisico' => 'required|string|max:5000',
            'contrasena_dispositivo' => 'nullable|string|max:255',
            'anticipo' => 'nullable|numeric|min:0',
            // El importe capturado en el asistente se guarda en ordenes_servicio.cobro_diagnostico.
            'cobro_diagnostico' => 'nullable|numeric|min:0',
            'metodo_pago_anticipo' => ['nullable', Rule::in(['efectivo', 'transferencia', 'tarjeta'])],
        ], [
            'cliente_telefono.regex' => 'El teléfono principal debe contener exactamente 10 dígitos.',
            'cliente_telefono_normalizado.regex' => 'El teléfono principal debe contener exactamente 10 dígitos.',
            'cliente_telefono_extra.regex' => 'El teléfono extra debe contener exactamente 10 dígitos.',
        ]);

        /**
         * Guarda Cliente, OS, Historial y Caja como una sola operación.
         * La transacción evita datos incompletos si alguno de esos módulos falla.
         */
        [$orden, $cliente] = DB::transaction(function () use ($request, $telefonoNormalizado) {
            // Si se eligió un cliente anterior, confirma dentro de la transacción que ID y teléfono coincidan.
            $cliente = null;
            if ($request->filled('cliente_id')) {
                if ($telefonoNormalizado === null) {
                    throw ValidationException::withMessages([
                        'cliente_telefono' => 'El cliente anterior seleccionado debe conservar su teléfono.',
                    ]);
                }

                $cliente = Cliente::whereKey($request->cliente_id)
                    ->where('telefono_normalizado', $telefonoNormalizado)
                    ->lockForUpdate()
                    ->first();

                if (! $cliente) {
                    throw ValidationException::withMessages([
                        'cliente_telefono' => 'El teléfono fue modificado y ya no coincide con el cliente anterior seleccionado.',
                    ]);
                }
            }

            // Solo reutiliza por una llave telefónica real; sin teléfono crea un historial independiente.
            if (! $cliente && $telefonoNormalizado !== null) {
                $cliente = Cliente::where('telefono_normalizado', $telefonoNormalizado)
                    ->lockForUpdate()
                    ->first();
            }

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

            /**
             * Bloquea brevemente la sucursal para que dos capturas simultáneas no reciban el mismo folio.
             * Se conecta con sucursales.id y con la secuencia visible en ordenes_servicio.numero_os.
             */
            $sucursal = Sucursal::whereKey($request->sucursal_id)->lockForUpdate()->firstOrFail();
            $prefix = $this->generarPrefijoSucursal($sucursal->nombre);
            $numeroOs = $this->generarNumeroOs($prefix, (int) date('Y'));

            // Crea la orden con los datos del asistente y la conecta con Cliente, Sucursal y Técnico.
            $orden = OrdenServicio::create([
                'numero_os' => $numeroOs,
                'cliente_id' => $cliente->id,
                'cliente_telefono_extra' => $request->cliente_telefono_extra,
                'sucursal_id' => $request->sucursal_id,
                'tecnico_id' => $request->tecnico_id,
                'tipo_dispositivo' => $request->tipo_dispositivo,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'imei' => $request->imei,
                'problema_reportado' => $request->problema_reportado,
                'accesorios_entregados' => $request->accesorios_entregados ?: 'NINGUNO',
                'estado_fisico' => $request->estado_fisico,
                // Guarda el patrón, PIN o contraseña del equipo y se conecta con el detalle de la orden.
                'contrasena_dispositivo' => $request->contrasena_dispositivo,
                'cobro_diagnostico' => $request->cobro_diagnostico ?? 0,
                'anticipo' => $request->anticipo ?? 0,
                'metodo_pago_anticipo' => $request->metodo_pago_anticipo ?? 'efectivo',
                // Al crear la OS, el diagnóstico monetario funciona como precio inicial del servicio.
                'presupuesto_total' => $request->cobro_diagnostico ?? 0,
            ]);

            // Aprende la combinación capturada para ofrecerla en futuras órdenes, incluso si se escribió en "Otro".
            DeviceCatalogEntry::firstOrCreate([
                'device_type' => trim($request->tipo_dispositivo),
                'brand' => trim($request->marca),
                'model' => trim($request->modelo),
            ]);

            // Crea el primer estado y lo conecta con la línea de tiempo de la nueva OS.
            HistorialEstado::create([
                'os_id' => $orden->id,
                'estado' => 'RECIBIDO',
            ]);

            // Sincroniza el anticipo con Caja y conecta el cobro con la orden mediante os_id.
            $this->sincronizarCobroOrdenEnCaja($orden);

            return [$orden, $cliente];
        }, 3);

        /**
         * La auditoría es informativa: una falla en Actividad no debe convertir una OS ya guardada en error 500.
         * El aviso queda en laravel.log para que pueda revisarse sin afectar Ordenes, Cliente o Caja.
         */
        try {
            AdminActivityLogger::registrar(
                'ÓRDENES',
                'CREADA',
                'Orden '.$orden->numero_os.' creada para '.$cliente->nombre,
                $orden->sucursal_id,
                $orden
            );
        } catch (\Throwable $exception) {
            Log::warning('No fue posible registrar la creación de la OS en Actividad.', [
                'orden_id' => $orden->id,
                'numero_os' => $orden->numero_os,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('ordenes.index')->with('success', 'Orden '.$orden->numero_os.' creada correctamente.');
    }

    // Ver detalle de una OS
    public function show(OrdenServicio $ordenServicio)
    {
        $this->asegurarSucursalActiva($ordenServicio);
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
        $this->asegurarSucursalActiva($ordenServicio);
        $ordenServicio->load(['cliente', 'sucursal']);

        return view('ordenes.sticker', compact('ordenServicio'));
    }

    // Mostrar formulario de edición
    public function edit(OrdenServicio $ordenServicio)
    {
        $this->asegurarSucursalActiva($ordenServicio);
        // Carga cliente y sucursal para mostrar exactamente los datos capturados en Nueva OS.
        $ordenServicio->load(['cliente', 'sucursal']);

        // El selector solo permite tecnicos que pertenecen a la sucursal de esta orden.
        $tecnicos = User::query()
            ->where('rol', 'tecnico')
            ->where('sucursal_id', $ordenServicio->sucursal_id)
            ->orderBy('name')
            ->get();

        // El mismo catálogo alimenta el selector para superusuario, usuario y cualquier sucursal futura.
        $estados = OrdenServicio::ESTADOS;
        $puedeCambiarEstado = in_array(auth()->user()?->rol, ['superusuario', 'usuario'], true);

        return view('ordenes.edit', compact('ordenServicio', 'tecnicos', 'estados', 'puedeCambiarEstado'));
    }

    // Guardar edición
    public function update(Request $request, OrdenServicio $ordenServicio)
    {
        $this->asegurarSucursalActiva($ordenServicio);
        // Normaliza el teléfono editado únicamente cuando el cliente proporciona uno.
        $telefonoNormalizado = $request->filled('cliente_telefono')
            ? Cliente::normalizarTelefono($request->cliente_telefono)
            : null;
        $request->merge([
            'cliente_telefono_normalizado' => $telefonoNormalizado,
        ]);

        $request->validate([
            'cliente_nombre' => 'required|string|max:255',
            // El contacto sigue siendo opcional al editar; si existe, mantiene sus 10 dígitos.
            'cliente_telefono' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'cliente_telefono_normalizado' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'marca' => 'required|string',
            'modelo' => 'required|string',
            'tipo_dispositivo' => 'required|string',
            'cliente_telefono_extra' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'imei' => 'nullable|string|max:255',
            // Solo superusuario y usuario pueden enviar uno de los estados oficiales.
            'estado' => in_array(auth()->user()?->rol, ['superusuario', 'usuario'], true)
                ? ['required', Rule::in(array_keys(OrdenServicio::ESTADOS))]
                : ['prohibited'],
            // Repite la restriccion del selector para impedir asignaciones manipuladas desde el navegador.
            'tecnico_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($usuarios) => $usuarios
                    ->where('rol', 'tecnico')
                    ->where('sucursal_id', $ordenServicio->sucursal_id)),
            ],
            'problema_reportado' => 'required|string',
            'problema_diagnosticado' => 'nullable|string',
            'accesorios_entregados' => 'nullable|string',
            'estado_fisico' => 'required|string',
            'contrasena_dispositivo' => 'nullable|string|max:255',
            'cobro_diagnostico' => 'nullable|numeric|min:0',
            'mano_obra' => 'nullable|numeric|min:0',
            'presupuesto_total' => 'nullable|numeric|min:0',
            'anticipo' => 'nullable|numeric|min:0',
            'metodo_pago_anticipo' => 'nullable|in:efectivo,transferencia,tarjeta',
            'fecha_entrega_estimada' => 'nullable|date',
        ], [
            'cliente_telefono.regex' => 'El teléfono principal debe contener exactamente 10 dígitos.',
            'cliente_telefono_normalizado.regex' => 'El teléfono principal debe contener exactamente 10 dígitos.',
            'cliente_telefono_extra.regex' => 'El teléfono extra debe contener exactamente 10 dígitos.',
        ]);

        // Impide asignar a este cliente el teléfono único que ya identifica a otro registro.
        $telefonoPerteneceAOtroCliente = $telefonoNormalizado !== null
            && Cliente::where('telefono_normalizado', $telefonoNormalizado)
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

        $estadoAnterior = $ordenServicio->estado;
        // Los demás roles conservan el estado actual aunque manipulen manualmente la petición.
        $estadoEditado = in_array(auth()->user()?->rol, ['superusuario', 'usuario'], true)
            ? $request->estado
            : $estadoAnterior;

        // Actualiza el equipo con los mismos campos de Nueva OS y normaliza textos descriptivos en mayúsculas.
        $cobroDiagnostico = (float) ($request->cobro_diagnostico ?? 0);
        $presupuestoTotal = (float) ($request->presupuesto_total ?? 0);
        // Si no escribieron otro presupuesto, conserva el diagnóstico como precio efectivo del servicio.
        $presupuestoTotal = $presupuestoTotal > 0 ? $presupuestoTotal : $cobroDiagnostico;

        $ordenServicio->update([
            'tecnico_id' => $request->tecnico_id,
            'marca' => Str::upper($request->marca),
            'modelo' => Str::upper($request->modelo),
            'tipo_dispositivo' => Str::upper($request->tipo_dispositivo),
            'cliente_telefono_extra' => $request->cliente_telefono_extra,
            'imei' => $request->filled('imei') ? Str::upper($request->imei) : null,
            // Guarda la opción validada del selector en ordenes_servicio.estado.
            'estado' => $estadoEditado,
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
            'cobro_diagnostico' => $cobroDiagnostico,
            'presupuesto_total' => $presupuestoTotal,
            'mano_obra' => $request->mano_obra ?? 0,
            'fecha_entrega_estimada' => $request->fecha_entrega_estimada,
            'anticipo' => $request->anticipo ?? 0,
            'metodo_pago_anticipo' => $request->metodo_pago_anticipo ?? 'efectivo',
        ]);

        if ($estadoAnterior !== $estadoEditado) {
            // Conserva evidencia del cambio realizado desde Editar OS en historial_estados.
            HistorialEstado::create([
                'os_id' => $ordenServicio->id,
                'estado' => $estadoEditado,
                'nota' => 'Cambio de estado desde Editar OS.',
            ]);

            AdminActivityLogger::registrar(
                'ÓRDENES',
                'ESTADO',
                'Orden '.$ordenServicio->numero_os.' cambió de '.$estadoAnterior.' a '.$estadoEditado,
                $ordenServicio->sucursal_id,
                $ordenServicio
            );
        }

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
        $this->asegurarSucursalActiva($ordenServicio);
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

        if (! in_array($nuevoEstado, $estadosPermitidos, true)) {
            return back()->with('error', 'Estado no válido para la orden.');
        }

        if ($ordenServicio->estado === $nuevoEstado) {
            return back()->with('success', 'La orden ya está en: '.$nuevoEstado);
        }

        $ordenServicio->update(['estado' => $nuevoEstado]);

        // HistorialEstado guarda la evidencia del cambio y se conecta con el detalle de la orden.
        HistorialEstado::create([
            'os_id' => $ordenServicio->id,
            'estado' => $nuevoEstado,
            'nota' => $request->nota ?: 'Cambio manual desde el menú de órdenes.',
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
        $this->asegurarSucursalActiva($ordenServicio);
        if ($ordenServicio->estado !== 'TERMINADO') {
            throw ValidationException::withMessages([
                'cobro_final' => 'La orden debe estar lista para recoger antes de registrar el pago final.',
            ]);
        }

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
            'cobro_final' => 'required|numeric|min:0',
        ]);

        $tecnicoEntrega = User::findOrFail($request->tecnico_entrega_id);
        $cobroFinal = (float) ($request->cobro_final ?? 0);
        $precioServicio = $ordenServicio->precioServicio();
        $anticipo = (float) ($ordenServicio->anticipo ?? 0);

        if ($precioServicio <= 0) {
            throw ValidationException::withMessages([
                'cobro_final' => 'Registra el precio del servicio antes de entregar el equipo.',
            ]);
        }

        if ($anticipo - $precioServicio >= 0.01) {
            throw ValidationException::withMessages([
                'cobro_final' => 'El anticipo no puede superar el precio del servicio.',
            ]);
        }

        $saldoEsperado = max(0, $precioServicio - $anticipo);
        if (abs($cobroFinal - $saldoEsperado) >= 0.01) {
            throw ValidationException::withMessages([
                'cobro_final' => 'El pago final debe ser exactamente $'.number_format($saldoEsperado, 2).'.',
            ]);
        }

        DB::transaction(function () use ($ordenServicio, $tecnicoEntrega, $cobroFinal): void {
            // pago_final conserva la liquidación sin destruir ordenes_servicio.cobro_diagnostico.
            $ordenServicio->update([
                'estado' => 'ENTREGADO',
                'fecha_entrega_real' => now(),
                'pago_final' => $cobroFinal,
                'tecnico_id' => $tecnicoEntrega->id,
            ]);

            // Guarda evidencia del técnico que realizó la reparación dentro del historial de la orden.
            HistorialEstado::create([
                'os_id' => $ordenServicio->id,
                'estado' => 'ENTREGADO',
                'nota' => 'Equipo entregado. Técnico que realizó la reparación: '.$tecnicoEntrega->name,
            ]);

            // Orden, historial y Caja quedan cerrados juntos o se revierten si alguna escritura falla.
            $this->sincronizarCobroOrdenEnCaja($ordenServicio->fresh());
        });

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
            ->with('total_registrado', $precioServicio);
    }

    /**
     * Muestra el ticket final de entrega.
     * Se conecta con resources/views/ordenes/ticket-entrega.blade.php y usa los datos cerrados de la OS.
     */
    public function ticketEntrega(OrdenServicio $ordenServicio)
    {
        $this->asegurarSucursalActiva($ordenServicio);
        $ordenServicio->load(['cliente', 'sucursal', 'tecnico']);
        $tecnicoEntrega = session('tecnico_entrega', $ordenServicio->tecnico->name ?? '—');
        $cobroFinal = session('cobro_final', $ordenServicio->pago_final ?? 0);
        // El ticket conserva el precio autorizado aunque se vuelva a abrir después de la entrega.
        $totalRegistrado = session('total_registrado', $ordenServicio->precioServicio());

        // La política se conecta con ConfiguracionController y aparece al final del ticket.
        $politica = Schema::hasTable('configuraciones')
            ? DB::table('configuraciones')->where('clave', 'politica_garantia')->value('valor')
            : null;
        // La política inicial protege los tickets aun antes de la primera personalización del taller.
        $politica = $politica ?: config('warranty.default_policy');

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
        abort_if(! $sucursalId || (int) $ordenServicio->sucursal_id !== (int) $sucursalId, 403);

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
     * Se conecta con anticipo, pago_final y metodo_pago_anticipo de ordenes_servicio.
     */
    private function sincronizarCobroOrdenEnCaja(OrdenServicio $ordenServicio): void
    {
        $anticipo = (float) ($ordenServicio->anticipo ?? 0);
        $pagoFinal = (float) ($ordenServicio->pago_final ?? 0);
        $totalPagado = $anticipo + $pagoFinal;

        $movimiento = MovimientoCaja::where('os_id', $ordenServicio->id)
            ->where('categoria', 'Orden de Servicio')
            ->first();

        if ($totalPagado <= 0) {
            // Si la orden ya no tiene cobros, elimina únicamente su fila financiera vacía.
            $movimiento?->delete();

            return;
        }

        // Construye una descripción legible igual a la mostrada en la tabla de Caja.
        if ($anticipo > 0 && $pagoFinal > 0) {
            $descripcion = 'Anticipo $'.number_format($anticipo, 2).' + Pago final $'.number_format($pagoFinal, 2);
        } elseif ($anticipo > 0) {
            $descripcion = 'Anticipo $'.number_format($anticipo, 2);
        } else {
            $descripcion = 'Pago final $'.number_format($pagoFinal, 2);
        }

        $datos = [
            'sucursal_id' => $ordenServicio->sucursal_id,
            'tipo' => 'INGRESO',
            'categoria' => 'Orden de Servicio',
            'monto' => $totalPagado,
            'metodo_pago' => strtolower($ordenServicio->metodo_pago_anticipo ?: 'efectivo'),
            // Conserva el anticipo por separado para calcular la tarjeta Total Anticipos.
            'anticipo' => $anticipo,
            'saldo_pendiente' => $ordenServicio->saldoPendiente(),
            'es_anticipo' => $anticipo > 0,
            'es_pago_final' => $pagoFinal > 0 && $ordenServicio->estado === 'ENTREGADO',
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

    /**
     * Construye el prefijo del folio con las primeras letras de la sucursal activa.
     * Se conecta con sucursales.nombre para que Izamal, Buctzotz y futuras sucursales no compartan por error el mismo código.
     */
    private function generarPrefijoSucursal(string $nombreSucursal): string
    {
        $nombreNormalizado = preg_replace('/[^A-Z0-9]/', '', Str::upper(Str::ascii($nombreSucursal))) ?? '';

        return str_pad(substr($nombreNormalizado, 0, 3), 3, 'X');
    }

    /**
     * Obtiene el siguiente folio usando el número mayor realmente guardado, aunque existan órdenes eliminadas.
     * Se conecta globalmente con ordenes_servicio.numero_os para no repetir una clave única entre sucursales.
     */
    private function generarNumeroOs(string $prefix, int $year): string
    {
        $patron = '/^'.preg_quote($prefix, '/').'-'.$year.'-(\d+)$/';

        $ultimoNumero = OrdenServicio::query()
            ->where('numero_os', 'like', $prefix.'-'.$year.'-%')
            ->pluck('numero_os')
            ->reduce(function (int $mayor, string $folio) use ($patron): int {
                return preg_match($patron, $folio, $coincidencias)
                    ? max($mayor, (int) $coincidencias[1])
                    : $mayor;
            }, 0);

        return $prefix.'-'.$year.'-'.str_pad((string) ($ultimoNumero + 1), 4, '0', STR_PAD_LEFT);
    }

    // Eliminar OS
    public function destroy(OrdenServicio $ordenServicio)
    {
        $this->asegurarSucursalActiva($ordenServicio);
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

    /**
     * Protege cada acción individual de Ordenes contra accesos manuales de otra sucursal.
     * Se conecta con la sesión del Super Usuario y con users.sucursal_id para las cuentas de taller.
     */
    private function asegurarSucursalActiva(OrdenServicio $ordenServicio): void
    {
        $sucursalId = $this->sucursalActivaId();
        abort_if(! $sucursalId || (int) $ordenServicio->sucursal_id !== $sucursalId, 403);
    }

    /**
     * Agrega al catálogo configurado las combinaciones aprendidas al guardar órdenes de servicio.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    private function catalogoDispositivosActualizado(): array
    {
        $catalogo = config('device_catalog', []);

        DeviceCatalogEntry::query()
            ->orderBy('device_type')
            ->orderBy('brand')
            ->orderBy('model')
            ->each(function (DeviceCatalogEntry $entrada) use (&$catalogo): void {
                $catalogo[$entrada->device_type] ??= [];
                $catalogo[$entrada->device_type][$entrada->brand] ??= [];

                if (! in_array($entrada->model, $catalogo[$entrada->device_type][$entrada->brand], true)) {
                    $catalogo[$entrada->device_type][$entrada->brand][] = $entrada->model;
                    sort($catalogo[$entrada->device_type][$entrada->brand], SORT_NATURAL | SORT_FLAG_CASE);
                }
            });

        return $catalogo;
    }

    /**
     * Centraliza la sucursal usada por listados, formularios y acciones de Ordenes.
     * Se conecta con el selector lateral o con la sucursal fija de Buctzotz/Izamal del usuario.
     */
    private function sucursalActivaId(): ?int
    {
        $id = session('sucursal_id') ?: auth()->user()?->sucursal_id;

        return $id ? (int) $id : null;
    }
}
