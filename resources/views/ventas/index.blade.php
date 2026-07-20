@extends('layout')

@section('content')
<div class="page-header">
    <div>
        <h1>Ventas</h1>
        <p style="color:#64748b;font-size:14px;margin:0;">Resumen y registro de ventas realizadas</p>
    </div>
    <a href="{{ route('ventas.create') }}" class="btn btn-primary">+ Nueva venta</a>
</div>

{{-- Informa qué sucursal controla la consulta; se conecta con la selección guardada en la sesión. --}}
<div style="display:flex;align-items:center;margin-bottom:1.5rem;">
    <span style="background:#dbeafe;color:#1d4ed8;border-radius:999px;padding:7px 14px;font-size:13px;font-weight:700;">
        Sucursal: {{ $sucursalActiva?->nombre ?? 'Sin seleccionar' }}
    </span>
</div>

{{-- Cards resumen --}}
@php
    // Las tarjetas usan la misma colección ya filtrada por el controlador.
    $totalMonto = $ventas->sum('total');
    $completadas = $ventas->where('estado','completada')->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
    <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;">
        <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Ventas registradas</div>
        <div style="font-size:2rem;font-weight:700;color:#3b82f6;margin-top:.25rem;">{{ $ventas->count() }}</div>
    </div>
    <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;">
        <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Total vendido</div>
        <div style="font-size:2rem;font-weight:700;color:#16a34a;margin-top:.25rem;">${{ number_format($totalMonto,2) }}</div>
    </div>
    <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;">
        <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Completadas</div>
        <div style="font-size:2rem;font-weight:700;color:#f59e0b;margin-top:.25rem;">{{ $completadas }}</div>
    </div>
</div>

{{-- Tabla --}}
<div style="background:white;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                <th style="padding:12px 16px;text-align:left;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">#</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Cliente</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Sucursal</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Vendedor</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Total</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Estado</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Fecha</th>
                <th style="padding:12px 16px;text-align:left;font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $venta)
            <tr style="border-bottom:1px solid #f1f5f9;transition:background 0.15s;"
                onmouseover="this.style.background='#f8fafc'"
                onmouseout="this.style.background=''">
                <td style="padding:12px 16px;font-weight:700;color:#0f1f3d;">#{{ $venta->id }}</td>
                <td style="padding:12px 16px;">{{ $venta->cliente->nombre ?? 'Sin cliente' }}</td>
                <td style="padding:12px 16px;">{{ $venta->sucursal->nombre ?? '—' }}</td>
                <td style="padding:12px 16px;">{{ $venta->usuario->name ?? '—' }}</td>
                <td style="padding:12px 16px;font-weight:600;">${{ number_format($venta->total, 2) }}</td>
                <td style="padding:12px 16px;">
                    <span class="badge badge-autorizado">{{ $venta->estado }}</span>
                </td>
                <td style="padding:12px 16px;color:#64748b;font-size:14px;">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                <td style="padding:12px 16px;">
                    <div style="display:flex;gap:6px;">
                        <a href="{{ route('ventas.show', $venta) }}" class="btn btn-sm">Ver</a>
                        <form method="POST" action="{{ route('ventas.destroy', $venta) }}"
                            onsubmit="return confirmarEliminacionSistema(event, 'la venta', '#{{ $venta->id }}');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;color:#94a3b8;padding:2.5rem;font-size:14px;">
                    No hay ventas registradas para {{ $sucursalActiva?->nombre ?? 'la sucursal seleccionada' }}.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
