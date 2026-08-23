<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Construye el panel operativo de la sucursal activa.
     * Se conecta con Ventas, Caja, Ordenes, Clientes, Inventario, Usuarios y Actividad.
     */
    public function index(Request $request)
    {
        $sucursalId = $this->sucursalActivaId();
        $sucursal = $sucursalId ? Sucursal::find($sucursalId) : null;
        $hoy = CarbonImmutable::today();
        $inicioMesActual = $hoy->startOfMonth();

        // El filtro solo admite días transcurridos del mes actual para evitar consultas ambiguas o futuras.
        $datosFiltro = $request->validate([
            'fecha_supervision' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:'.$inicioMesActual->toDateString(),
                'before_or_equal:'.$hoy->toDateString(),
            ],
        ], [
            'fecha_supervision.date_format' => 'Selecciona una fecha válida.',
            'fecha_supervision.after_or_equal' => 'Selecciona un día del mes actual.',
            'fecha_supervision.before_or_equal' => 'Solo puedes supervisar hasta el día de hoy.',
        ]);

        $supervisionActiva = filled($datosFiltro['fecha_supervision'] ?? null);
        $diaPanel = $supervisionActiva
            ? CarbonImmutable::parse($datosFiltro['fecha_supervision'])->startOfDay()
            : $hoy;
        $diaAnterior = $diaPanel->subDay();

        $ventasDia = $this->ventasEnRango($sucursalId, $diaPanel, $diaPanel->endOfDay());
        $ventasDiaAnterior = $this->ventasEnRango($sucursalId, $diaAnterior, $diaAnterior->endOfDay());
        // Las cifras de Caja comparten fecha y sucursal para que ingresos, egresos y balance sean consistentes.
        $movimientosDia = $this->movimientosEnRango($sucursalId, $diaPanel, $diaPanel->endOfDay());
        $movimientosDiaAnterior = $this->movimientosEnRango($sucursalId, $diaAnterior, $diaAnterior->endOfDay());
        $ingresosDia = (clone $movimientosDia)
            ->where('tipo', 'INGRESO')
            ->sum('monto');
        $ingresosDiaAnterior = (clone $movimientosDiaAnterior)
            ->where('tipo', 'INGRESO')
            ->sum('monto');
        // Separa los tres métodos de ingreso sin alterar el total usado por el balance.
        $efectivoDia = (clone $movimientosDia)
            ->where('tipo', 'INGRESO')
            ->where('metodo_pago', 'efectivo')
            ->sum('monto');
        $efectivoDiaAnterior = (clone $movimientosDiaAnterior)
            ->where('tipo', 'INGRESO')
            ->where('metodo_pago', 'efectivo')
            ->sum('monto');
        $transferenciasDia = (clone $movimientosDia)
            ->where('tipo', 'INGRESO')
            ->where('metodo_pago', 'transferencia')
            ->sum('monto');
        $transferenciasDiaAnterior = (clone $movimientosDiaAnterior)
            ->where('tipo', 'INGRESO')
            ->where('metodo_pago', 'transferencia')
            ->sum('monto');
        $tarjetaDia = (clone $movimientosDia)
            ->where('tipo', 'INGRESO')
            ->where('metodo_pago', 'tarjeta')
            ->sum('monto');
        $tarjetaDiaAnterior = (clone $movimientosDiaAnterior)
            ->where('tipo', 'INGRESO')
            ->where('metodo_pago', 'tarjeta')
            ->sum('monto');
        $egresosDia = (clone $movimientosDia)
            ->where('tipo', 'EGRESO')
            ->sum('monto');
        $egresosDiaAnterior = (clone $movimientosDiaAnterior)
            ->where('tipo', 'EGRESO')
            ->sum('monto');

        // Las tarjetas comparan el día supervisado contra su día anterior y conservan la sucursal activa.
        $indicadores = [
            'ventas' => $this->indicador($ventasDia->count(), $ventasDiaAnterior->count()),
            'vendido' => $this->indicador((float) $ventasDia->sum('total'), (float) $ventasDiaAnterior->sum('total')),
            'ingresos' => $this->indicador((float) $ingresosDia, (float) $ingresosDiaAnterior),
            'efectivo' => $this->indicador((float) $efectivoDia, (float) $efectivoDiaAnterior),
            'transferencias' => $this->indicador((float) $transferenciasDia, (float) $transferenciasDiaAnterior),
            'tarjeta' => $this->indicador((float) $tarjetaDia, (float) $tarjetaDiaAnterior),
            'egresos' => $this->indicador((float) $egresosDia, (float) $egresosDiaAnterior),
            // El balance financiero siempre corresponde a ingresos menos egresos del mismo día.
            'balance' => $this->indicador(
                (float) $ingresosDia - (float) $egresosDia,
                (float) $ingresosDiaAnterior - (float) $egresosDiaAnterior
            ),
            'ordenes' => $this->indicador(
                $this->ordenesEnRango($sucursalId, $diaPanel, $diaPanel->endOfDay())->count(),
                $this->ordenesEnRango($sucursalId, $diaAnterior, $diaAnterior->endOfDay())->count()
            ),
            'clientes' => $this->indicador(
                $this->clientesEnRango($sucursalId, $diaPanel, $diaPanel->endOfDay())->count(),
                $this->clientesEnRango($sucursalId, $diaAnterior, $diaAnterior->endOfDay())->count()
            ),
        ];

        // Los estados alimentan el resumen de carga de trabajo del taller.
        $estados = OrdenServicio::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(! $sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->select('estado')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $operacion = [
            'espera' => collect(['RECIBIDO', 'ESPERANDO AUTORIZACION', 'ESPERANDO AUTORIZACIÓN'])->sum(fn ($estado) => (int) ($estados[$estado] ?? 0)),
            'diagnostico' => (int) ($estados['EN DIAGNÓSTICO'] ?? 0),
            'reparacion' => collect(['AUTORIZADO', 'EN REPARACIÓN', 'ESPERANDO REFACCIÓN'])->sum(fn ($estado) => (int) ($estados[$estado] ?? 0)),
            'listos' => collect(['TERMINADO', 'NOTIFICADO'])->sum(fn ($estado) => (int) ($estados[$estado] ?? 0)),
            'entregados' => (int) ($estados['ENTREGADO'] ?? 0),
            'stock_bajo' => Inventario::query()
                ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
                ->when(! $sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->whereColumn('cantidad_disponible', '<=', 'stock_minimo')
                ->count(),
        ];

        // La serie termina en el día supervisado y se conecta con Caja para mostrar su contexto inmediato.
        $tendencia = collect(range(6, 0))->map(function (int $dias) use ($diaPanel, $sucursalId) {
            $fecha = $diaPanel->subDays($dias);

            return [
                'etiqueta' => $fecha->locale('es')->isoFormat('dd D'),
                'valor' => (float) $this->movimientosEnRango($sucursalId, $fecha, $fecha->endOfDay())
                    ->where('tipo', 'INGRESO')
                    ->sum('monto'),
            ];
        });

        $ordenesRecientes = OrdenServicio::with(['cliente', 'tecnico'])
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(! $sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            // Al supervisar una fecha, muestra únicamente las órdenes registradas durante ese día.
            ->when($supervisionActiva, fn (Builder $query) => $query->whereBetween('created_at', [$diaPanel, $diaPanel->endOfDay()]))
            ->latest()
            ->limit(6)
            ->get();

        $actividadReciente = AdminActivity::with(['usuario', 'sucursal'])
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            // La auditoría sí conserva fecha, por eso puede limitarse de forma precisa al día elegido.
            ->when($supervisionActiva, fn (Builder $query) => $query->whereBetween('created_at', [$diaPanel, $diaPanel->endOfDay()]))
            ->latest()
            ->limit(6)
            ->get();

        $tecnicos = User::query()
            ->where('rol', 'tecnico')
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->count();

        return view('home', compact(
            'sucursal',
            'indicadores',
            'operacion',
            'tendencia',
            'ordenesRecientes',
            'actividadReciente',
            'tecnicos',
            'diaPanel',
            'hoy',
            'inicioMesActual',
            'supervisionActiva'
        ));
    }

    /** Convierte dos valores en una variacion porcentual para las tarjetas del dashboard. */
    private function indicador(float|int $actual, float|int $anterior): array
    {
        $variacion = $anterior == 0
            ? ($actual > 0 ? 100 : 0)
            : (($actual - $anterior) / abs($anterior)) * 100;

        return ['actual' => $actual, 'anterior' => $anterior, 'variacion' => round($variacion, 1)];
    }

    /** Consulta Ventas dentro de un rango y se conecta con ventas.sucursal_id. */
    private function ventasEnRango(?int $sucursalId, CarbonImmutable $inicio, CarbonImmutable $fin): Builder
    {
        return Venta::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(! $sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Consulta Caja dentro de un rango y se conecta con movimientos_caja.sucursal_id. */
    private function movimientosEnRango(?int $sucursalId, CarbonImmutable $inicio, CarbonImmutable $fin): Builder
    {
        return MovimientoCaja::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(! $sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Consulta Ordenes dentro de un rango y se conecta con ordenes_servicio.sucursal_id. */
    private function ordenesEnRango(?int $sucursalId, CarbonImmutable $inicio, CarbonImmutable $fin): Builder
    {
        return OrdenServicio::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(! $sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Consulta Clientes dentro de un rango y usa su sucursal habitual. */
    private function clientesEnRango(?int $sucursalId, CarbonImmutable $inicio, CarbonImmutable $fin): Builder
    {
        return Cliente::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_habitual_id', $sucursalId))
            ->when(! $sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Devuelve la sucursal de sesion o la asignada al usuario autenticado. */
    private function sucursalActivaId(): ?int
    {
        $id = session('sucursal_id') ?: auth()->user()?->sucursal_id;

        return $id ? (int) $id : null;
    }
}
