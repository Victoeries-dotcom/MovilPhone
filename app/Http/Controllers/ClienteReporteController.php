<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteReporteController extends Controller
{
    /**
     * Muestra el reporte de compras y ordenes realizadas por cada cliente.
     */
    public function index(Request $request)
    {
        $this->autorizar($request);
        $filtros = $this->validarFiltros($request);

        return view('clientes.reporte', $this->datosReporte($filtros));
    }

    /**
     * Descarga el resultado filtrado como un PDF listo para imprimir o archivar.
     */
    public function pdf(Request $request)
    {
        $this->autorizar($request);
        $filtros = $this->validarFiltros($request);

        return Pdf::loadView('clientes.reporte-pdf', $this->datosReporte($filtros))
            ->setPaper('letter', 'landscape')
            ->download('reporte-clientes-'.now()->format('Y-m-d-His').'.pdf');
    }

    /**
     * Genera un CSV con BOM UTF-8, formato que Excel abre conservando acentos.
     */
    public function excel(Request $request): StreamedResponse
    {
        $this->autorizar($request);
        $filtros = $this->validarFiltros($request);
        $datos = $this->datosReporte($filtros);

        return response()->streamDownload(function () use ($datos): void {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Cliente', 'Telefono', 'Sucursal', 'Compras', 'Total en ventas', 'Ordenes', 'Total en ordenes']);

            foreach ($datos['clientes'] as $cliente) {
                fputcsv($salida, [
                    $cliente->nombre,
                    $cliente->telefono_principal,
                    $cliente->sucursal?->nombre ?? 'Sin sucursal',
                    $cliente->compras_count,
                    number_format((float) $cliente->compras_total, 2, '.', ''),
                    $cliente->ordenes_reporte_count,
                    number_format((float) $cliente->ordenes_total, 2, '.', ''),
                ]);
            }

            fclose($salida);
        }, 'reporte-clientes-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Valida los filtros antes de usarlos en las consultas del reporte.
     */
    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }

    /**
     * Construye una sola fuente de datos para pantalla, PDF y Excel.
     */
    private function datosReporte(array $filtros): array
    {
        $consulta = Cliente::query()->with('sucursal');

        if (! empty($filtros['search'])) {
            $busqueda = $filtros['search'];
            $consulta->where(function (Builder $query) use ($busqueda): void {
                $query->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('telefono_principal', 'like', "%{$busqueda}%");
            });
        }

        $sucursalId = $filtros['sucursal_id'] ?? null;
        $usuarioId = $filtros['usuario_id'] ?? null;
        $inicio = $filtros['fecha_inicio'] ?? null;
        $fin = $filtros['fecha_fin'] ?? null;

        // El usuario comun solo puede consultar la sucursal activa de su sesion.
        if (auth()->user()->rol === 'usuario' && session('sucursal_id')) {
            $sucursalId = (int) session('sucursal_id');
        }

        if ($sucursalId) {
            $consulta->where(function (Builder $query) use ($sucursalId): void {
                $query->where('sucursal_habitual_id', $sucursalId)
                    ->orWhereHas('ventas', fn (Builder $venta) => $venta->where('sucursal_id', $sucursalId))
                    ->orWhereHas('ordenes', fn (Builder $orden) => $orden->where('sucursal_id', $sucursalId));
            });
        }

        $aplicarFiltros = function (Builder $query, string $tipo) use ($inicio, $fin, $sucursalId, $usuarioId): void {
            if ($inicio) {
                $query->whereDate('created_at', '>=', $inicio);
            }
            if ($fin) {
                $query->whereDate('created_at', '<=', $fin);
            }
            if ($sucursalId) {
                $query->where('sucursal_id', $sucursalId);
            }
            if ($usuarioId) {
                // ventas.usuario_id identifica al vendedor; ordenes_servicio.tecnico_id identifica al tecnico.
                $query->where($tipo === 'venta' ? 'usuario_id' : 'tecnico_id', $usuarioId);
            }
        };

        // Si se eligio un periodo, sucursal o usuario, se muestran solo clientes con actividad coincidente.
        if ($inicio || $fin || $sucursalId || $usuarioId) {
            $consulta->where(function (Builder $query) use ($aplicarFiltros): void {
                $query->whereHas('ventas', fn (Builder $venta) => $aplicarFiltros($venta, 'venta'))
                    ->orWhereHas('ordenes', fn (Builder $orden) => $aplicarFiltros($orden, 'orden'));
            });
        }

        $clientes = $consulta
            ->withCount(['ventas as compras_count' => fn (Builder $query) => $aplicarFiltros($query, 'venta')])
            ->withSum(['ventas as compras_total' => fn (Builder $query) => $aplicarFiltros($query, 'venta')], 'total')
            ->withCount(['ordenes as ordenes_reporte_count' => fn (Builder $query) => $aplicarFiltros($query, 'orden')])
            ->withSum(['ordenes as ordenes_total' => fn (Builder $query) => $aplicarFiltros($query, 'orden')], 'presupuesto_total')
            ->orderBy('nombre')
            ->get();

        return [
            'clientes' => $clientes,
            'sucursales' => Sucursal::query()->orderBy('nombre')->get(),
            'usuarios' => User::query()->whereIn('rol', ['usuario', 'vendedor', 'tecnico', 'superusuario'])->orderBy('name')->get(),
            'filtros' => $filtros,
            'totales' => [
                'clientes' => $clientes->count(),
                'compras' => $clientes->sum('compras_count'),
                'ventas' => $clientes->sum('compras_total'),
                'ordenes' => $clientes->sum('ordenes_reporte_count'),
                'ordenes_total' => $clientes->sum('ordenes_total'),
            ],
        ];
    }

    /**
     * Protege pantalla y exportaciones contra accesos directos por URL.
     */
    private function autorizar(Request $request): void
    {
        abort_unless(in_array($request->user()->rol, ['superusuario', 'usuario'], true), 403);
    }
}
