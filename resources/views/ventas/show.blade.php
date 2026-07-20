@extends('layout')

@section('content')
<div class="page-header">
    <h1>Venta #{{ $venta->id }}</h1>
    <div style="display:flex;gap:6px;">
        <a href="{{ route('ventas.index') }}" class="btn">← Volver</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

    <div>
        <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:1.5rem;">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0;background:#f8fafc;font-weight:600;">Productos vendidos</div>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->nombre_producto }}</td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>${{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td>${{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr style="background:#f8fafc;">
                        <td colspan="3" style="text-align:right;font-weight:700;">Total:</td>
                        <td style="font-weight:700;color:#0f1f3d;">${{ number_format($venta->total, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:1rem;">
            <p style="font-size:11px;color:#888;font-weight:700;text-transform:uppercase;margin-bottom:.5rem;">Cliente</p>
            <p style="font-weight:600;">{{ $venta->cliente->nombre ?? 'Sin cliente' }}</p>
            <p style="color:#64748b;font-size:13px;">{{ $venta->cliente->telefono_principal ?? '' }}</p>
        </div>
        <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:1rem;">
            <p style="font-size:11px;color:#888;font-weight:700;text-transform:uppercase;margin-bottom:.5rem;">Sucursal</p>
            <p style="font-weight:600;">{{ $venta->sucursal->nombre ?? '—' }}</p>
        </div>
        <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:1rem;">
            <p style="font-size:11px;color:#888;font-weight:700;text-transform:uppercase;margin-bottom:.5rem;">Vendedor</p>
            <p style="font-weight:600;">{{ $venta->usuario->name ?? '—' }}</p>
        </div>
        <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:11px;color:#888;font-weight:700;text-transform:uppercase;margin-bottom:.5rem;">Fecha</p>
            <p style="font-weight:600;">{{ $venta->created_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }}</p>
        </div>
        @if($venta->notas)
        <div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-top:1rem;">
            <p style="font-size:11px;color:#888;font-weight:700;text-transform:uppercase;margin-bottom:.5rem;">Notas</p>
            <p>{{ $venta->notas }}</p>
        </div>
        @endif
    </div>

</div>
@endsection
