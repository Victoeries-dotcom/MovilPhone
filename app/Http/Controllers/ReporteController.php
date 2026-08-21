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
            // La lista permite combinar cualquier cantidad de sucursales actuales o creadas después.
            'sucursales' => 'nullable|array',
            'sucursales.*' => 'integer|distinct|exists:sucursales,id',
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

        // La lista se consulta en cada carga para incluir automáticamente las sucursales futuras.
        $sucursalesDisponibles = Sucursal::query()->orderBy('nombre')->get();
        $sucursalesSeleccionadasIds = collect($request->input('sucursales', []))
            ->map(fn ($sucursalId) => (int) $sucursalId)
            ->unique()
            ->values()
            ->all();
        $seleccionPersonalizada = $sucursalesSeleccionadasIds !== [];

        // Usa primero la sucursal elegida en el módulo Sucursales y después la asignada al usuario.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        $sucursalActiva = $sucursalActivaId
            ? $sucursalesDisponibles->firstWhere('id', (int) $sucursalActivaId)
            : null;

        /*
         * El alcance se representa con IDs: null significa todas, una lista filtra esas sedes
         * y una lista vacía evita mostrar datos cuando todavía no existe un alcance válido.
         */
        $todasSucursales = ! $seleccionPersonalizada && $request->boolean('todas_sucursales');
        $sucursalIdsAlcance = match (true) {
            $seleccionPersonalizada => $sucursalesSeleccionadasIds,
            $todasSucursales => null,
            $sucursalActiva !== null => [(int) $sucursalActiva->id],
            default => [],
        };
        $hayAlcanceReporte = $sucursalIdsAlcance === null || $sucursalIdsAlcance !== [];
        $nombresSucursalesSeleccionadas = collect($sucursalesSeleccionadasIds)
            ->map(fn (int $sucursalId) => $sucursalesDisponibles->firstWhere('id', $sucursalId)?->nombre)
            ->filter()
            ->values();
        $alcanceReporte = match (true) {
            $seleccionPersonalizada => $nombresSucursalesSeleccionadas->implode(', '),
            $todasSucursales => 'Todas las sucursales',
            default => $sucursalActiva?->nombre ?? 'Sin sucursal',
        };

        /*
         * El rango personalizado usa los límites elegidos por el administrador.
         * Los demás accesos rápidos conservan el cálculo histórico de rangoPeriodo().
         */
        [$inicio, $fin] = $periodo === 'rango'
            ? $this->rangoPersonalizado($tipoRango, $rangoDesde, $rangoHasta)
            : $this->rangoPeriodo(
                $periodo,
                $fechaSeleccionada,
                $sucursalIdsAlcance
            );

        // Fecha y rango personalizados son consultas estrictas: nunca muestran acumulados ajenos al intervalo.
        $permiteRespaldoAcumulado = ! in_array($periodo, ['fecha', 'rango'], true);

        // Ventas del periodo: alimentan tarjetas, tabla de ventas y reporte por cliente.
        $ventasQuery = Venta::with(['cliente', 'sucursal', 'usuario', 'detalles'])
            ->whereBetween('created_at', [$inicio, $fin]);
        $this->filtrarSucursales($ventasQuery, $sucursalIdsAlcance);
        $ventas = $ventasQuery->latest()->get();

        // Productos del periodo: alimentan tablas y gráficas cuando existen resultados.
        $productosMasVendidos = $this->consultarProductosVendidos(
            $sucursalIdsAlcance,
            $inicio,
            $fin
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
                $sucursalIdsAlcance,
                null,
                null
            );

            if ($productosAcumulados->sum('total_vendido') > 0) {
                $productosGraficas = $productosAcumulados;
                $productosGraficasAcumuladas = true;
            }
        }

        // Existencias actuales: usa la sucursal activa o todas las sucursales, según el alcance solicitado.
        $productosExistenciaQuery = Inventario::with('sucursal')
            ->orderBy('cantidad_disponible');
        $this->filtrarSucursales(
            $productosExistenciaQuery,
            $sucursalIdsAlcance
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
        $this->filtrarSucursales(
            $reporteProveedoresQuery,
            $sucursalIdsAlcance
        );
        $reporteProveedores = $reporteProveedoresQuery
            ->orderByDesc('productos')
            ->get();

        // Consultas generales: todas comparten el mismo periodo y alcance para mantener cifras consistentes.
        $ordenesQuery = OrdenServicio::whereBetween('created_at', [$inicio, $fin]);
        $this->filtrarSucursales($ordenesQuery, $sucursalIdsAlcance);

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
            $this->filtrarSucursales(
                $ordenesAcumuladasQuery,
                $sucursalIdsAlcance
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
        $this->filtrarSucursales(
            $clientesQuery,
            $sucursalIdsAlcance,
            'sucursal_habitual_id'
        );

        $cajaQuery = MovimientoCaja::whereBetween('created_at', [$inicio, $fin]);
        $this->filtrarSucursales($cajaQuery, $sucursalIdsAlcance);

        $stockBajoQuery = Inventario::whereColumn('cantidad_disponible', '<=', 'stock_minimo');
        $this->filtrarSucursales(
            $stockBajoQuery,
            $sucursalIdsAlcance
        );

        /*
         * El resumen financiero usa el mismo periodo y alcance que el resto de Reportes.
         * Estos valores provienen de movimientos_caja y sustituyen las tarjetas retiradas de Caja.
         */
        $ingresosCaja = (clone $cajaQuery)->where('tipo', 'INGRESO')->sum('monto');
        $egresosCaja = (clone $cajaQuery)->where('tipo', 'EGRESO')->sum('monto');

        $general = [
            'ordenes' => $ordenesQuery->count(),
            'clientes' => $clientesQuery->count(),
            'ventas' => $ventas->count(),
            'total_ventas' => $ventas->sum('total'),
            'movimientos_caja' => (clone $cajaQuery)->count(),
            'ingresos_caja' => $ingresosCaja,
            'egresos_caja' => $egresosCaja,
            'balance_caja' => $ingresosCaja - $egresosCaja,
            'anticipos_caja' => (clone $cajaQuery)->sum('anticipo'),
            'productos_bajo_stock' => $stockBajoQuery->count(),
        ];

        // Sincroniza "Clientes nuevos" con los registros históricos de la sucursal.
        // Solo usa el acumulado cuando el periodo está vacío y existen clientes anteriores.
        $clientesMostrados = $general['clientes'];
        $clientesEsAcumulado = false;
        if ($permiteRespaldoAcumulado && $clientesMostrados === 0 && $hayAlcanceReporte) {
            $clientesAcumuladosQuery = Cliente::query();
            $this->filtrarSucursales(
                $clientesAcumuladosQuery,
                $sucursalIdsAlcance,
                'sucursal_habitual_id'
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
            $this->filtrarSucursales(
                $ordenesAcumuladasQuery,
                $sucursalIdsAlcance
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
            $this->filtrarSucursales(
                $ventasAcumuladasQuery,
                $sucursalIdsAlcance
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
            'sucursalesDisponibles',
            'sucursalesSeleccionadasIds',
            'seleccionPersonalizada',
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
     * Con fechas consulta el periodo; sin fechas devuelve el acumulado del alcance elegido.
     */
    private function consultarProductosVendidos(
        ?array $sucursalIds,
        ?Carbon $inicio = null,
        ?Carbon $fin = null
    ) {
        $query = VentaDetalle::select('nombre_producto')
            ->selectRaw('SUM(cantidad) as total_vendido')
            ->selectRaw('SUM(subtotal) as total_ingresos')
            ->whereHas('venta', function (Builder $venta) use (
                $sucursalIds,
                $inicio,
                $fin
            ) {
                if ($inicio && $fin) {
                    $venta->whereBetween('created_at', [$inicio, $fin]);
                }

                $this->filtrarSucursales($venta, $sucursalIds);
            });

        return $query
            ->groupBy('nombre_producto')
            ->orderByDesc('total_vendido')
            ->get();
    }

    /**
     * Aplica el alcance solicitado: varias sucursales, todas las sucursales o cero filas sin selección.
     * El nombre de columna conecta Clientes mediante sucursal_habitual_id y los demás módulos por sucursal_id.
     */
    private function filtrarSucursales(
        Builder $query,
        ?array $sucursalIds,
        string $columna = 'sucursal_id'
    ): void {
        // null representa el botón "Todas las sucursales" y no agrega restricciones SQL.
        if ($sucursalIds === null) {
            return;
        }

        if ($sucursalIds !== []) {
            $query->whereIn($columna, $sucursalIds);
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
        ?array $sucursalIds
    ): array {
        if ($periodo === 'fecha') {
            $fecha = Carbon::createFromFormat('Y-m-d', $fechaSeleccionada);

            return [$fecha->copy()->startOfDay(), $fecha->copy()->endOfDay()];
        }

        if ($periodo === 'acumulado') {
            $primerRegistro = $this->fechaPrimerRegistro($sucursalIds);

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
     * Busca la fecha más antigua para que Acumulado cubra las sucursales elegidas o todo el sistema.
     * Se conecta con las cinco fuentes principales y respeta el alcance seleccionado.
     */
    private function fechaPrimerRegistro(?array $sucursalIds): ?string
    {
        if ($sucursalIds === []) {
            return null;
        }

        /*
         * Cada consulta usa filtrarSucursales(); en modo global no se agrega WHERE,
         * por lo que las nuevas sucursales quedan incluidas automáticamente.
         */
        $fechaMinima = function (Builder $query, string $columna = 'sucursal_id') use ($sucursalIds) {
            $this->filtrarSucursales($query, $sucursalIds, $columna);

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
