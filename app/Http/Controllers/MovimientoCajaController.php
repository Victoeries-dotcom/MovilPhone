<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Support\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MovimientoCajaController extends Controller
{
    /**
     * Muestra el resumen financiero y los movimientos de la sucursal activa.
     * Se conecta con movimientos_caja, ordenes_servicio y los filtros GET de caja.index.
     */
    public function index(Request $request)
    {
        $sucursalId = $this->sucursalActivaId();

        // Esta consulta base limita la información a la sucursal elegida en el menú lateral.
        $consultaBase = MovimientoCaja::query()
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId));

        // Los indicadores usan todos los movimientos de la sucursal, aunque la tabla tenga filtros activos.
        $ingresos = (clone $consultaBase)->where('tipo', 'INGRESO')->sum('monto');
        $egresos = (clone $consultaBase)->where('tipo', 'EGRESO')->sum('monto');
        $stats = [
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'balance' => $ingresos - $egresos,
            'anticipos' => (clone $consultaBase)->sum('anticipo'),
            'movimientos' => (clone $consultaBase)->count(),
        ];

        // La tabla carga su orden y sucursal para mostrar el folio de OS y el origen del movimiento.
        $query = (clone $consultaBase)->with(['orden', 'sucursal']);

        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        if ($request->tipo === 'RECHAZO') {
            // El filtro Rechazos se conecta con los egresos creados al devolver un anticipo desde Órdenes.
            $query->where('tipo', 'EGRESO')
                ->where(function ($rechazos) {
                    $rechazos->where('categoria', 'DEVOLUCIÓN DE ANTICIPO')
                        ->orWhere('descripcion', 'like', 'RECHAZO %');
                });
        } elseif (in_array($request->tipo, ['INGRESO', 'EGRESO'], true)) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('search')) {
            $termino = trim($request->search);
            $query->where(function ($busqueda) use ($termino) {
                // Busca por número de orden, descripción o identificador del movimiento manual.
                $busqueda->where('descripcion', 'like', '%'.$termino.'%')
                    ->orWhere('id', 'like', '%'.$termino.'%')
                    ->orWhereHas('orden', function ($orden) use ($termino) {
                        $orden->where('numero_os', 'like', '%'.$termino.'%');
                    });
            });
        }

        $movimientos = $query->latest()->get();

        // Genera folios consecutivos para identificar ingresos y egresos que no pertenecen a una OS.
        $foliosManuales = [];
        $contadores = ['INGRESO' => 0, 'EGRESO' => 0];
        (clone $consultaBase)
            ->whereNull('os_id')
            ->oldest('id')
            ->get(['id', 'tipo'])
            ->each(function ($movimiento) use (&$foliosManuales, &$contadores) {
                $contadores[$movimiento->tipo]++;
                $foliosManuales[$movimiento->id] = $movimiento->tipo.'-'.str_pad(
                    (string) $contadores[$movimiento->tipo],
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            });

        return view('caja.index', compact('movimientos', 'stats', 'foliosManuales'));
    }

    /**
     * Abre el formulario de un movimiento manual.
     * Se conecta con sucursales para asignar correctamente el ingreso o egreso.
     */
    public function create()
    {
        // El asistente usa la sucursal activa de la sesión para impedir registros financieros en otra sede.
        return view('caja.create');
    }

    /**
     * Guarda un ingreso o egreso manual y lo reporta en la actividad del administrador.
     * Se conecta con movimientos_caja y AdminActivityLogger.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:INGRESO,EGRESO',
            'categoria' => 'required|string|max:100',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,transferencia,tarjeta',
            'descripcion' => 'nullable|string|max:500',
        ]);

        /*
         * La sucursal se obtiene de la sesión o del usuario autenticado.
         * Se conecta con el selector global y evita registrar dinero en otra sede
         * aunque el navegador envíe manualmente un sucursal_id diferente.
         */
        $sucursalId = $this->sucursalActivaId();
        if (! $sucursalId) {
            return redirect()->route('caja.index')
                ->with('error', 'Selecciona una sucursal antes de registrar el movimiento.');
        }

        $movimiento = MovimientoCaja::create([
            'sucursal_id' => $sucursalId,
            'tipo' => $request->tipo,
            // Normaliza textos capturados para mantener uniforme el registro de Caja.
            'categoria' => Str::upper($request->categoria),
            'monto' => $request->monto,
            'metodo_pago' => $request->metodo_pago,
            'descripcion' => $request->filled('descripcion') ? Str::upper($request->descripcion) : null,
            'user_id' => auth()->id(),
        ]);

        // Registra el movimiento para que el admin vea ingresos y egresos en tiempo real.
        AdminActivityLogger::registrar(
            'CAJA',
            $movimiento->tipo,
            $movimiento->categoria.' por $'.number_format($movimiento->monto, 2),
            $movimiento->sucursal_id,
            $movimiento
        );

        return redirect()->route('caja.index')->with('success', 'Movimiento registrado correctamente.');
    }

    /**
     * Registra un egreso desde el acceso rápido de Órdenes de Servicio.
     * Se conecta con movimientos_caja y usa obligatoriamente la sucursal activa.
     */
    public function registrarEgreso(Request $request)
    {
        return $this->registrarMovimientoRapido($request, 'EGRESO');
    }

    /**
     * Registra un ingreso desde el acceso rápido de Órdenes de Servicio.
     * Se conecta con movimientos_caja y usa obligatoriamente la sucursal activa.
     */
    public function registrarIngreso(Request $request)
    {
        return $this->registrarMovimientoRapido($request, 'INGRESO');
    }

    /**
     * Muestra un comprobante imprimible del movimiento seleccionado.
     * Se conecta con movimientos_caja, su OS, cliente, sucursal e historial de pagos.
     */
    public function ticket(MovimientoCaja $movimientoCaja)
    {
        $sucursalId = $this->sucursalActivaId();
        abort_if(! $sucursalId || (int) $movimientoCaja->sucursal_id !== $sucursalId, 403);

        $movimientoCaja->load(['orden.cliente', 'orden.sucursal', 'sucursal', 'usuario']);
        $pagosOrden = $movimientoCaja->os_id
            ? MovimientoCaja::where('os_id', $movimientoCaja->os_id)->oldest()->get()
            : collect();

        return view('caja.ticket', compact('movimientoCaja', 'pagosOrden'));
    }

    /**
     * Genera el corte diario de la sucursal activa.
     * Se conecta con los métodos de pago, anticipos, ingresos y egresos de movimientos_caja.
     */
    public function corteCaja(Request $request)
    {
        abort_unless(auth()->user()?->rol === 'superusuario', 403);

        $request->validate(['fecha' => 'nullable|date_format:Y-m-d']);
        $fecha = $request->get('fecha', now()->toDateString());
        $sucursalId = $this->sucursalActivaId();

        if (! $sucursalId) {
            return redirect()->route('caja.index')
                ->with('error', 'Selecciona una sucursal antes de consultar el corte.');
        }

        $sucursalActiva = Sucursal::findOrFail($sucursalId);
        $consulta = MovimientoCaja::with(['orden', 'sucursal'])
            ->where('sucursal_id', $sucursalId)
            ->whereDate('created_at', $fecha);

        // Cada cálculo usa clones para conservar el mismo día y sucursal sin mezclar consultas.
        $corte = [
            'fecha' => $fecha,
            'efectivo' => (clone $consulta)->where('tipo', 'INGRESO')->where('metodo_pago', 'efectivo')->sum('monto'),
            'transferencia' => (clone $consulta)->where('tipo', 'INGRESO')->where('metodo_pago', 'transferencia')->sum('monto'),
            'tarjeta' => (clone $consulta)->where('tipo', 'INGRESO')->where('metodo_pago', 'tarjeta')->sum('monto'),
            'total_ingresos' => (clone $consulta)->where('tipo', 'INGRESO')->sum('monto'),
            'total_egresos' => (clone $consulta)->where('tipo', 'EGRESO')->sum('monto'),
            'anticipos' => (clone $consulta)->sum('anticipo'),
            'pagos_finales' => (clone $consulta)->where('es_pago_final', true)->sum('monto'),
            'movimientos' => (clone $consulta)->latest()->get(),
        ];
        $corte['balance'] = $corte['total_ingresos'] - $corte['total_egresos'];

        return view('caja.corte', [
            'corte' => $corte,
            'horaCorte' => config('caja.hora_corte', '22:00'),
            'sucursalActiva' => $sucursalActiva,
        ]);
    }

    /**
     * Guarda la hora informativa del corte para el superusuario.
     * Se conecta con config/caja.php; no elimina ni modifica movimientos financieros.
     */
    public function guardarHoraCorte(Request $request)
    {
        abort_unless(auth()->user()?->rol === 'superusuario', 403);
        $request->validate(['hora_corte' => 'required|date_format:H:i']);

        $hora = $request->hora_corte;
        $contenido = "<?php\n\nreturn [\n    // Hora informativa usada por la pantalla Corte de Caja.\n    'hora_corte' => '{$hora}',\n];\n";
        File::put(config_path('caja.php'), $contenido);
        config(['caja.hora_corte' => $hora]);

        return redirect()->route('caja.corte')
            ->with('success', 'Hora de corte actualizada a '.$hora.'.');
    }

    /**
     * Elimina únicamente movimientos manuales para proteger los cobros ligados a órdenes.
     * Se conecta con la doble confirmación de caja.index y con AdminActivityLogger.
     */
    public function destroy(MovimientoCaja $movimientoCaja)
    {
        // Protege la eliminación manual para que Caja solo opere sobre la sucursal activa.
        $sucursalIdActivo = $this->sucursalActivaId();
        abort_if(! $sucursalIdActivo || (int) $movimientoCaja->sucursal_id !== $sucursalIdActivo, 403);

        if ($movimientoCaja->os_id) {
            return redirect()->route('caja.index')
                ->with('error', 'Los movimientos ligados a una orden se administran desde la orden de servicio.');
        }

        $descripcion = $movimientoCaja->categoria.' por $'.number_format($movimientoCaja->monto, 2);
        $sucursalId = $movimientoCaja->sucursal_id;
        $movimientoCaja->delete();

        // Notifica la eliminación para conservar trazabilidad en el panel del administrador.
        AdminActivityLogger::registrar(
            'CAJA',
            'ELIMINADO',
            'Movimiento eliminado: '.$descripcion,
            $sucursalId
        );

        return redirect()->route('caja.index')->with('success', 'Movimiento eliminado.');
    }

    public function show(MovimientoCaja $movimientoCaja) {}

    public function edit(MovimientoCaja $movimientoCaja) {}

    public function update(Request $request, MovimientoCaja $movimientoCaja) {}

    /**
     * Centraliza la validación y creación de ingresos/egresos rápidos.
     * Se conecta con el modal de ordenes.index y con el panel de actividad.
     */
    private function registrarMovimientoRapido(Request $request, string $tipo)
    {
        $request->validate([
            'concepto' => 'required|string|max:500',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,transferencia,tarjeta',
        ]);

        $sucursalId = $this->sucursalActivaId();
        if (! $sucursalId) {
            return redirect()->route('ordenes.index')
                ->with('error', 'Selecciona una sucursal antes de registrar el movimiento.');
        }

        $movimiento = MovimientoCaja::create([
            'sucursal_id' => $sucursalId,
            'tipo' => $tipo,
            'categoria' => $tipo.' MANUAL',
            'monto' => $request->monto,
            'metodo_pago' => $request->metodo_pago,
            'anticipo' => 0,
            'saldo_pendiente' => 0,
            'es_anticipo' => false,
            'es_pago_final' => false,
            'descripcion' => Str::upper(trim($request->concepto)),
            'user_id' => auth()->id(),
        ]);

        AdminActivityLogger::registrar(
            'CAJA',
            $tipo,
            $movimiento->descripcion.' por $'.number_format($movimiento->monto, 2),
            $sucursalId,
            $movimiento
        );

        return redirect()->route('ordenes.index')
            ->with('success', ucfirst(strtolower($tipo)).' registrado correctamente en Caja.');
    }

    /**
     * Devuelve la sucursal elegida en el menú o la asignada al usuario.
     * Centraliza el filtro usado por listado, ticket y corte de Caja.
     */
    private function sucursalActivaId(): ?int
    {
        $sucursalId = session('sucursal_id') ?: auth()->user()?->sucursal_id;

        return $sucursalId ? (int) $sucursalId : null;
    }
}
