<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; }
        h1 { color: #0f1f3d; margin-bottom: 3px; }
        .resumen { margin: 12px 0; padding: 8px; background: #eef4ff; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0f1f3d; color: white; padding: 6px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #d8dee9; }
        .numero { text-align: right; }
    </style>
</head>
<body>
    <h1>MovilPhone — Reporte por usuario</h1>
    <div>Compras y órdenes realizadas por cada cliente · Generado: {{ now()->format('d/m/Y H:i') }}</div>
    <div class="resumen">
        Clientes: {{ $totales['clientes'] }} · Compras: {{ $totales['compras'] }} ·
        Total ventas: ${{ number_format($totales['ventas'], 2) }} · Órdenes: {{ $totales['ordenes'] }}
    </div>
    <table>
        <thead><tr><th>Cliente</th><th>Teléfono</th><th>Sucursal</th><th>Compras</th><th>Total ventas</th><th>Órdenes</th><th>Total órdenes</th></tr></thead>
        <tbody>
        @forelse($clientes as $cliente)
            <tr>
                <td>{{ $cliente->nombre }}</td><td>{{ $cliente->telefono_principal }}</td><td>{{ $cliente->sucursal?->nombre ?? 'Sin sucursal' }}</td>
                <td class="numero">{{ $cliente->compras_count }}</td><td class="numero">${{ number_format($cliente->compras_total ?? 0, 2) }}</td>
                <td class="numero">{{ $cliente->ordenes_reporte_count }}</td><td class="numero">${{ number_format($cliente->ordenes_total ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No hay clientes que coincidan con los filtros.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
