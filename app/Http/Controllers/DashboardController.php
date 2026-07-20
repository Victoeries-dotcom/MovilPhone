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

class DashboardController extends Controller
{
    /**
     * Construye el panel operativo de la sucursal activa.
     * Se conecta con Ventas, Caja, Ordenes, Clientes, Inventario, Usuarios y Actividad.
     */
    public function index()
    {
        $sucursalId = $this->sucursalActivaId();
        $sucursal = $sucursalId ? Sucursal::find($sucursalId) : null;
        $hoy = CarbonImmutable::today();
        $ayer = $hoy->subDay();

        $ventasHoy = $this->ventasEnRango($sucursalId, $hoy, $hoy->endOfDay());
        $ventasAyer = $this->ventasEnRango($sucursalId, $ayer, $ayer->endOfDay());
        $ingresosHoy = $this->movimientosEnRango($sucursalId, $hoy, $hoy->endOfDay())
            ->where('tipo', 'INGRESO')
            ->sum('monto');
        $ingresosAyer = $this->movimientosEnRango($sucursalId, $ayer, $ayer->endOfDay())
            ->where('tipo', 'INGRESO')
            ->sum('monto');

        // Las tarjetas comparan hoy contra ayer y conservan el mismo filtro de sucursal.
        $indicadores = [
            'ventas' => $this->indicador($ventasHoy->count(), $ventasAyer->count()),
            'vendido' => $this->indicador((float) $ventasHoy->sum('total'), (float) $ventasAyer->sum('total')),
            'ingresos' => $this->indicador((float) $ingresosHoy, (float) $ingresosAyer),
            'ordenes' => $this->indicador(
                $this->ordenesEnRango($sucursalId, $hoy, $hoy->endOfDay())->count(),
                $this->ordenesEnRango($sucursalId, $ayer, $ayer->endOfDay())->count()
            ),
            'clientes' => $this->indicador(
                $this->clientesEnRango($sucursalId, $hoy, $hoy->endOfDay())->count(),
                $this->clientesEnRango($sucursalId, $ayer, $ayer->endOfDay())->count()
            ),
        ];

        // Los estados alimentan el resumen de carga de trabajo del taller.
        $estados = OrdenServicio::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(!$sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
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
                ->when(!$sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->whereColumn('cantidad_disponible', '<=', 'stock_minimo')
                ->count(),
        ];

        // La serie de siete dias se conecta con Caja para mostrar tendencia real de ingresos.
        $tendencia = collect(range(6, 0))->map(function (int $dias) use ($hoy, $sucursalId) {
            $fecha = $hoy->subDays($dias);

            return [
                'etiqueta' => $fecha->locale('es')->isoFormat('dd D'),
                'valor' => (float) $this->movimientosEnRango($sucursalId, $fecha, $fecha->endOfDay())
                    ->where('tipo', 'INGRESO')
                    ->sum('monto'),
            ];
        });

        $ordenesRecientes = OrdenServicio::with(['cliente', 'tecnico'])
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(!$sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->latest()
            ->limit(6)
            ->get();

        $actividadReciente = AdminActivity::with(['usuario', 'sucursal'])
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
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
            'tecnicos'
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
            ->when(!$sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Consulta Caja dentro de un rango y se conecta con movimientos_caja.sucursal_id. */
    private function movimientosEnRango(?int $sucursalId, CarbonImmutable $inicio, CarbonImmutable $fin): Builder
    {
        return MovimientoCaja::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(!$sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Consulta Ordenes dentro de un rango y se conecta con ordenes_servicio.sucursal_id. */
    private function ordenesEnRango(?int $sucursalId, CarbonImmutable $inicio, CarbonImmutable $fin): Builder
    {
        return OrdenServicio::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when(!$sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Consulta Clientes dentro de un rango y usa su sucursal habitual. */
    private function clientesEnRango(?int $sucursalId, CarbonImmutable $inicio, CarbonImmutable $fin): Builder
    {
        return Cliente::query()
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_habitual_id', $sucursalId))
            ->when(!$sucursalId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Devuelve la sucursal de sesion o la asignada al usuario autenticado. */
    private function sucursalActivaId(): ?int
    {
        $id = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        return $id ? (int) $id : null;
    }
}
