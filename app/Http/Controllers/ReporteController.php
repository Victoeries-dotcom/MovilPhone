<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReporteController extends Controller
{
    /**
     * Genera todos los reportes del periodo y alcance solicitados.
     * Se conecta con Ventas, Caja, Órdenes, Clientes e Inventario por sucursal o de forma global.
     */
    public function index(Request $request)
    {
        $periodosPermitidos = ['dia', 'semana', 'mes', 'fecha', 'rango', 'acumulado'];
        $periodoSolicitado = $request->get('periodo', 'dia');
        $periodo = in_array($periodoSolicitado, $periodosPermitidos, true)
            ? $periodoSolicitado
            : 'dia';

        // Si el navegador envía una fecha, fuerza el modo calendario aunque falte el campo periodo.
        // Esto conecta directamente el input de la vista con rangoPeriodo() y evita regresar a "Por día".
        if ($request->filled('fecha')) {
            $periodo = 'fecha';
        }

        // El calendario acepta cualquier fecha válida y conserva el valor en la URL del reporte.
        $request->validate([
            'fecha' => 'nullable|date_format:Y-m-d',
            // El modo determina si los límites recibidos representan días, semanas ISO o meses.
            'tipo_rango' => 'nullable|in:dia,semana,mes',
            'desde' => 'nullable|string|max:10',
            'hasta' => 'nullable|string|max:10',
            // Activa la suma de todas las sucursales sin depender de una lista fija de sedes.
            'todas_sucursales' => 'nullable|boolean',
        ]);
        $fechaSeleccionada = $request->get('fecha', now()->toDateString());
        $tipoRango = $request->get('tipo_rango', 'dia');
        $rangoDesde = $request->get('desde', $this->valorActualRango($tipoRango));
        $rangoHasta = $request->get('hasta', $this->valorActualRango($tipoRango));

        // Conserva los tres selectores al cambiar entre días, semanas y meses en la misma pantalla.
        $valoresRango = [
            'dia' => [
                'desde' => $tipoRango === 'dia' ? $rangoDesde : now()->toDateString(),
                'hasta' => $tipoRango === 'dia' ? $rangoHasta : now()->toDateString(),
            ],
            'semana' => [
                'desde' => $tipoRango === 'semana' ? $rangoDesde : now()->format('o-\WW'),
                'hasta' => $tipoRango === 'semana' ? $rangoHasta : now()->format('o-\WW'),
            ],
            'mes' => [
                'desde' => $tipoRango === 'mes' ? $rangoDesde : now()->format('Y-m'),
                'hasta' => $tipoRango === 'mes' ? $rangoHasta : now()->format('Y-m'),
            ],
        ];

        // Usa primero la sucursal elegida en el módulo Sucursales y después la asignada al usuario.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        $sucursalActiva = $sucursalActivaId ? Sucursal::find($sucursalActivaId) : null;

        /*
         * El alcance global omite únicamente el filtro de sucursal.
         * Se conecta con todas las consultas del reporte e incluye automáticamente sucursales futuras.
         */
        $todasSucursales = $request->boolean('todas_sucursales');
        $hayAlcanceReporte = $todasSucursales || $sucursalActiva !== null;
        $alcanceReporte = $todasSucursales
            ? 'Todas las sucursales'
            : ($sucursalActiva?->nombre ?? 'Sin sucursal');

        /*
         * El rango personalizado usa los límites elegidos por el administrador.
         * Los demás accesos rápidos conservan el cálculo histórico de rangoPeriodo().
         */
        [$inicio, $fin] = $periodo === 'rango'
            ? $this->rangoPersonalizado($tipoRango, $rangoDesde, $rangoHasta)
            : $this->rangoPeriodo(
                $periodo,
                $fechaSeleccionada,
                $sucursalActiva?->id,
                $todasSucursales
            );

        // Fecha y rango personalizados son consultas estrictas: nunca muestran acumulados ajenos al intervalo.
        $permiteRespaldoAcumulado = ! in_array($periodo, ['fecha', 'rango'], true);

        // Ventas del periodo: alimentan tarjetas, tabla de ventas y reporte por cliente.
        $ventasQuery = Venta::with(['cliente', 'sucursal', 'usuario', 'detalles'])
            ->whereBetween('created_at', [$inicio, $fin]);
        $this->filtrarSucursal($ventasQuery, $sucursalActiva?->id, 'sucursal_id', $todasSucursales);
        $ventas = $ventasQuery->latest()->get();

        // Productos del periodo: alimentan tablas y gráficas cuando existen resultados.
        $productosMasVendidos = $this->consultarProductosVendidos(
            $sucursalActiva?->id,
            $inicio,
            $fin,
            $todasSucursales
        );

        // Si el periodo no tuvo ventas, las gráficas usan el historial completo de la sucursal.
        // Las tablas y tarjetas conservan el periodo solicitado para no alterar el reporte principal.
        $productosGraficas = $productosMasVendidos;
        $productosGraficasAcumuladas = false;

        if (
            $permiteRespaldoAcumulado
            && $productosGraficas->sum('total_vendido') <= 0
            && $hayAlcanceReporte
        ) {
            $productosAcumulados = $this->consultarProductosVendidos(
                $sucursalActiva?->id,
                null,
                null,
                $todasSucursales
            );

            if ($productosAcumulados->sum('total_vendido') > 0) {
                $productosGraficas = $productosAcumulados;
                $productosGraficasAcumuladas = true;
            }
        }

        // Existencias actuales: usa la sucursal activa o todas las sucursales, según el alcance solicitado.
        $productosExistenciaQuery = Inventario::with('sucursal')
            ->orderBy('cantidad_disponible');
        $this->filtrarSucursal(
            $productosExistenciaQuery,
            $sucursalActiva?->id,
            'sucursal_id',
            $todasSucursales
        );
        $productosExistencia = $productosExistenciaQuery->get();

        // Agrupa las ventas ya filtradas para mostrar compras y total por cliente.
        $reporteClientes = $ventas
            ->groupBy('cliente_id')
            ->map(function ($ventasCliente) {
                $primeraVenta = $ventasCliente->first();

                return [
                    'cliente' => $primeraVenta->cliente->nombre ?? 'Sin cliente',
                    'compras' => $ventasCliente->count(),
                    'total' => $ventasCliente->sum('total'),
                ];
            })
            ->sortByDesc('total');

        // Proveedores: obtiene el costo directamente del inventario actual de la sucursal.
        // El scope multiplica existencia por precio_costo y se actualiza con cada movimiento.
        $reporteProveedoresQuery = Inventario::query()->resumenPorProveedor();
        $this->filtrarSucursal(
            $reporteProveedoresQuery,
            $sucursalActiva?->id,
            'sucursal_id',
            $todasSucursales
        );
        $reporteProveedores = $reporteProveedoresQuery
            ->orderByDesc('productos')
            ->get();

        // Consultas generales: todas comparten el mismo periodo y alcance para mantener cifras consistentes.
        $ordenesQuery = OrdenServicio::whereBetween('created_at', [$inicio, $fin]);
        $this->filtrarSucursal($ordenesQuery, $sucursalActiva?->id, 'sucursal_id', $todasSucursales);

        // Agrupa los estados de las OS para alimentar la gráfica vertical de Órdenes.
        // Los grupos coinciden con las tarjetas utilizadas en el módulo Órdenes de Servicio.
        $conteosEstadosOrdenes = (clone $ordenesQuery)
            ->select('estado')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $gruposEstadosOrdenes = [
            'En espera' => ['RECIBIDO', 'ESPERANDO AUTORIZACIÓN', 'AUTORIZADO'],
            'Diagnóstico' => ['EN DIAGNÓSTICO'],
            'Reparación' => ['EN REPARACIÓN', 'ESPERANDO REFACCIÓN'],
            'Listo para recoger' => ['TERMINADO', 'NOTIFICADO'],
            'No quedó / Rechazado' => ['RECHAZADO'],
            'Entregado' => ['ENTREGADO'],
            'Garantía' => ['GARANTÍA'],
        ];

        $agruparOrdenesPorEstado = function ($conteos) use ($gruposEstadosOrdenes) {
            return collect($gruposEstadosOrdenes)
                ->map(function (array $estados, string $etiqueta) use ($conteos) {
                    return [
                        'estado' => $etiqueta,
                        'total' => collect($estados)->sum(
                            fn (string $estado) => (int) ($conteos[$estado] ?? 0)
                        ),
                    ];
                })
                ->values();
        };

        $ordenesPorEstado = $agruparOrdenesPorEstado($conteosEstadosOrdenes);
        $ordenesGraficas = $ordenesPorEstado;
        $ordenesGraficasAcumuladas = false;

        // Sin órdenes en el rango, la gráfica consulta el historial completo del alcance seleccionado.
        if (
            $permiteRespaldoAcumulado
            && $ordenesGraficas->sum('total') <= 0
            && $hayAlcanceReporte
        ) {
            $ordenesAcumuladasQuery = OrdenServicio::query();
            $this->filtrarSucursal(
                $ordenesAcumuladasQuery,
                $sucursalActiva?->id,
                'sucursal_id',
                $todasSucursales
            );
            $conteosOrdenesAcumuladas = $ordenesAcumuladasQuery
                ->select('estado')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado');
            $ordenesAcumuladas = $agruparOrdenesPorEstado($conteosOrdenesAcumuladas);

            if ($ordenesAcumuladas->sum('total') > 0) {
                $ordenesGraficas = $ordenesAcumuladas;
                $ordenesGraficasAcumuladas = true;
            }
        }

        $clientesQuery = Cliente::whereBetween('created_at', [$inicio, $fin]);
        $this->filtrarSucursal(
            $clientesQuery,
            $sucursalActiva?->id,
            'sucursal_habitual_id',
            $todasSucursales
        );

        $cajaQuery = MovimientoCaja::whereBetween('created_at', [$inicio, $fin]);
        $this->filtrarSucursal($cajaQuery, $sucursalActiva?->id, 'sucursal_id', $todasSucursales);

        $stockBajoQuery = Inventario::whereColumn('cantidad_disponible', '<=', 'stock_minimo');
        $this->filtrarSucursal(
            $stockBajoQuery,
            $sucursalActiva?->id,
            'sucursal_id',
            $todasSucursales
        );

        $general = [
            'ordenes' => $ordenesQuery->count(),
            'clientes' => $clientesQuery->count(),
            'ventas' => $ventas->count(),
            'total_ventas' => $ventas->sum('total'),
            'movimientos_caja' => (clone $cajaQuery)->count(),
            'ingresos_caja' => (clone $cajaQuery)->where('tipo', 'INGRESO')->sum('monto'),
            'egresos_caja' => (clone $cajaQuery)->where('tipo', 'EGRESO')->sum('monto'),
            'productos_bajo_stock' => $stockBajoQuery->count(),
        ];

        // Sincroniza "Clientes nuevos" con los registros históricos de la sucursal.
        // Solo usa el acumulado cuando el periodo está vacío y existen clientes anteriores.
        $clientesMostrados = $general['clientes'];
        $clientesEsAcumulado = false;
        if ($permiteRespaldoAcumulado && $clientesMostrados === 0 && $hayAlcanceReporte) {
            $clientesAcumuladosQuery = Cliente::query();
            $this->filtrarSucursal(
                $clientesAcumuladosQuery,
                $sucursalActiva?->id,
                'sucursal_habitual_id',
                $todasSucursales
            );
            $clientesAcumulados = $clientesAcumuladosQuery->count();

            if ($clientesAcumulados > 0) {
                $clientesMostrados = $clientesAcumulados;
                $clientesEsAcumulado = true;
            }
        }
        $general['clientes_mostrados'] = $clientesMostrados;
        $general['clientes_es_acumulado'] = $clientesEsAcumulado;

        // Sincroniza la tarjeta "Órdenes" con la gráfica de estados.
        // Cuando el periodo está vacío, cuenta todas las OS del alcance seleccionado.
        $ordenesMostradas = $general['ordenes'];
        if ($ordenesGraficasAcumuladas && $hayAlcanceReporte) {
            $ordenesAcumuladasQuery = OrdenServicio::query();
            $this->filtrarSucursal(
                $ordenesAcumuladasQuery,
                $sucursalActiva?->id,
                'sucursal_id',
                $todasSucursales
            );
            $ordenesMostradas = $ordenesAcumuladasQuery->count();
        }
        $general['ordenes_mostradas'] = $ordenesMostradas;
        $general['ordenes_es_acumulado'] = $ordenesGraficasAcumuladas;

        // Sincroniza el conteo de la tarjeta "Ventas" con las gráficas acumuladas.
        // Consulta la tabla ventas del alcance seleccionado para contar operaciones, no piezas.
        $ventasMostradas = $general['ventas'];
        if ($productosGraficasAcumuladas && $hayAlcanceReporte) {
            $ventasAcumuladasQuery = Venta::query();
            $this->filtrarSucursal(
                $ventasAcumuladasQuery,
                $sucursalActiva?->id,
                'sucursal_id',
                $todasSucursales
            );
            $ventasMostradas = $ventasAcumuladasQuery->count();
        }
        $general['ventas_mostradas'] = $ventasMostradas;
        $general['ventas_es_acumulado'] = $productosGraficasAcumuladas;

        // Sincroniza la tarjeta "Total vendido" con la gráfica de ingresos.
        // Si el periodo está vacío, ambas muestran el acumulado del alcance seleccionado.
        $general['total_ventas_mostrado'] = $productosGraficasAcumuladas
            ? $productosGraficas->sum('total_ingresos')
            : $general['total_ventas'];
        $general['total_ventas_es_acumulado'] = $productosGraficasAcumuladas;

        // Prepara arreglos simples y seguros para las gráficas Canvas de la vista.
        // Ventas usa cantidades, Total vendido usa ingresos y Órdenes usa estados.
        $graficas = [
            'productos' => [
                'etiquetas' => $productosGraficas->pluck('nombre_producto')->values(),
                'cantidades' => $productosGraficas
                    ->pluck('total_vendido')
                    ->map(fn ($cantidad) => (float) $cantidad)
                    ->values(),
                'ingresos' => $productosGraficas
                    ->pluck('total_ingresos')
                    ->map(fn ($ingreso) => (float) $ingreso)
                    ->values(),
                'es_acumulado' => $productosGraficasAcumuladas,
            ],
            'ordenes' => [
                'etiquetas' => $ordenesGraficas->pluck('estado')->values(),
                'cantidades' => $ordenesGraficas->pluck('total')->values(),
                'es_acumulado' => $ordenesGraficasAcumuladas,
            ],
        ];

        $periodoEtiqueta = match ($periodo) {
            'semana' => 'Semana',
            'mes' => 'Mes',
            'fecha' => 'Fecha seleccionada',
            'rango' => match ($tipoRango) {
                'semana' => 'Rango de semanas',
                'mes' => 'Rango de meses',
                default => 'Rango de días',
            },
            'acumulado' => 'Acumulado',
            default => 'Día',
        };

        return view('reportes.index', compact(
            'periodo',
            'periodoEtiqueta',
            'fechaSeleccionada',
            'tipoRango',
            'valoresRango',
            'inicio',
            'fin',
            'sucursalActiva',
            'todasSucursales',
            'hayAlcanceReporte',
            'alcanceReporte',
            'ventas',
            'productosMasVendidos',
            'productosExistencia',
            'reporteClientes',
            'reporteProveedores',
            'general',
            'ordenesPorEstado',
            'graficas'
        ));
    }

    /**
     * Devuelve el valor actual con el formato que necesita cada control HTML.
     * Se conecta con input date, week y month de la vista de Reportes.
     */
    private function valorActualRango(string $tipoRango): string
    {
        return match ($tipoRango) {
            'semana' => now()->format('o-\WW'),
            'mes' => now()->format('Y-m'),
            default => now()->toDateString(),
        };
    }

    /**
     * Convierte los límites escritos por el usuario en fechas completas para la base de datos.
     * Se conecta con created_at de Ventas, Caja, Órdenes y Clientes y evita rangos invertidos.
     */
    private function rangoPersonalizado(string $tipoRango, string $desde, string $hasta): array
    {
        try {
            $inicio = $this->convertirLimiteRango($tipoRango, $desde, true);
            $fin = $this->convertirLimiteRango($tipoRango, $hasta, false);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'desde' => 'El periodo seleccionado no tiene un formato válido.',
            ]);
        }

        if ($inicio->gt($fin)) {
            throw ValidationException::withMessages([
                'hasta' => 'El periodo final no puede ser anterior al periodo inicial.',
            ]);
        }

        return [$inicio, $fin];
    }

    /**
     * Interpreta un día, semana ISO o mes y devuelve su inicio o final natural.
     * Se conecta con rangoPersonalizado() para incluir completos los periodos seleccionados.
     */
    private function convertirLimiteRango(
        string $tipoRango,
        string $valor,
        bool $esInicio
    ): Carbon {
        if ($tipoRango === 'semana') {
            if (! preg_match('/^(\d{4})-W(\d{2})$/', $valor, $partes)) {
                throw new \InvalidArgumentException('Semana inválida.');
            }

            $fecha = Carbon::now()->setISODate((int) $partes[1], (int) $partes[2]);
            if ($fecha->format('o-\WW') !== $valor) {
                throw new \InvalidArgumentException('Semana fuera de rango.');
            }

            return $esInicio
                ? $fecha->startOfWeek()->startOfDay()
                : $fecha->endOfWeek()->endOfDay();
        }

        if ($tipoRango === 'mes') {
            if (! preg_match('/^(\d{4})-(\d{2})$/', $valor, $partes)) {
                throw new \InvalidArgumentException('Mes inválido.');
            }

            $mes = (int) $partes[2];
            if ($mes < 1 || $mes > 12) {
                throw new \InvalidArgumentException('Mes fuera de rango.');
            }

            $fecha = Carbon::create((int) $partes[1], $mes, 1);

            return $esInicio
                ? $fecha->startOfMonth()->startOfDay()
                : $fecha->endOfMonth()->endOfDay();
        }

        $fecha = Carbon::createFromFormat('!Y-m-d', $valor);
        if ($fecha->format('Y-m-d') !== $valor) {
            throw new \InvalidArgumentException('Día fuera de rango.');
        }

        return $esInicio ? $fecha->startOfDay() : $fecha->endOfDay();
    }

    /**
     * Resume productos vendidos por cantidad e ingresos.
     * Con fechas consulta el periodo; sin fechas devuelve el acumulado de la sucursal.
     */
    private function consultarProductosVendidos(
        ?int $sucursalId,
        ?Carbon $inicio = null,
        ?Carbon $fin = null,
        bool $todasSucursales = false
    ) {
        $query = VentaDetalle::select('nombre_producto')
            ->selectRaw('SUM(cantidad) as total_vendido')
            ->selectRaw('SUM(subtotal) as total_ingresos')
            ->whereHas('venta', function (Builder $venta) use (
                $sucursalId,
                $inicio,
                $fin,
                $todasSucursales
            ) {
                if ($inicio && $fin) {
                    $venta->whereBetween('created_at', [$inicio, $fin]);
                }

                $this->filtrarSucursal($venta, $sucursalId, 'sucursal_id', $todasSucursales);
            });

        return $query
            ->groupBy('nombre_producto')
            ->orderByDesc('total_vendido')
            ->get();
    }

    /**
     * Aplica el alcance solicitado: una sucursal, todas las sucursales o cero filas sin selección.
     * El nombre de columna conecta Clientes mediante sucursal_habitual_id y los demás módulos por sucursal_id.
     */
    private function filtrarSucursal(
        Builder $query,
        ?int $sucursalId,
        string $columna = 'sucursal_id',
        bool $todasSucursales = false
    ): void {
        if ($todasSucursales) {
            return;
        }

        if ($sucursalId) {
            $query->where($columna, $sucursalId);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Convierte el botón elegido en un rango de fechas para consultar los registros guardados.
     * Acumulado inicia en el primer dato real localizado dentro del alcance seleccionado.
     */
    private function rangoPeriodo(
        string $periodo,
        string $fechaSeleccionada,
        ?int $sucursalId,
        bool $todasSucursales = false
    ): array {
        if ($periodo === 'fecha') {
            $fecha = Carbon::createFromFormat('Y-m-d', $fechaSeleccionada);

            return [$fecha->copy()->startOfDay(), $fecha->copy()->endOfDay()];
        }

        if ($periodo === 'acumulado') {
            $primerRegistro = $this->fechaPrimerRegistro($sucursalId, $todasSucursales);

            return [
                $primerRegistro ? Carbon::parse($primerRegistro)->startOfDay() : now()->startOfDay(),
                now()->endOfDay(),
            ];
        }

        return match ($periodo) {
            'semana' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'mes' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    /**
     * Busca la fecha más antigua para que Acumulado cubra la sucursal o todo el sistema.
     * Se conecta con las cinco fuentes principales y respeta el alcance seleccionado.
     */
    private function fechaPrimerRegistro(?int $sucursalId, bool $todasSucursales = false): ?string
    {
        if (! $sucursalId && ! $todasSucursales) {
            return null;
        }

        /*
         * Cada consulta usa filtrarSucursal(); en modo global no se agrega WHERE,
         * por lo que las nuevas sucursales quedan incluidas automáticamente.
         */
        $fechaMinima = function (Builder $query, string $columna = 'sucursal_id') use (
            $sucursalId,
            $todasSucursales
        ) {
            $this->filtrarSucursal($query, $sucursalId, $columna, $todasSucursales);

            return $query->min('created_at');
        };

        return collect([
            $fechaMinima(Venta::query()),
            $fechaMinima(MovimientoCaja::query()),
            $fechaMinima(OrdenServicio::query()),
            $fechaMinima(Cliente::query(), 'sucursal_habitual_id'),
            $fechaMinima(Inventario::query()),
        ])->filter()->min();
    }
}
