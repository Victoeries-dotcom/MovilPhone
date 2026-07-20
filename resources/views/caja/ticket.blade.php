@extends('layout')

@section('content')
@php
    // El movimiento llega cargado con orden, cliente, sucursal y usuario desde CajaController::ticket.
    $orden = $movimientoCaja->orden;
@endphp
<div class="page-header">
    <h1>Ticket de Caja</h1>
    <div style="display:flex;gap:.75rem;">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir ticket</button>
        <a href="{{ route('caja.index') }}" class="btn">← Volver a Caja</a>
    </div>
</div>

<div id="ticket" style="max-width:400px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:2rem;box-shadow:0 2px 8px rgba(0,0,0,.08);">

    {{-- Encabezado imprimible conectado con la fecha y sucursal reales del movimiento. --}}
    <div style="text-align:center;border-bottom:2px dashed #e2e8f0;padding-bottom:1.25rem;margin-bottom:1.25rem;">
        <div style="font-size:22px;font-weight:800;color:#0f1f3d;">📱 {{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }}</div>
        <div style="font-size:12px;color:#6b7280;margin-top:4px;">{{ $configuracionGlobal['negocio_subtitulo'] ?? 'Sistema de Taller' }}</div>
        
        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
            {{ $movimientoCaja->created_at?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}
        </div>
        <div style="font-size:11px;color:#64748b;margin-top:2px;">
            {{ $movimientoCaja->sucursal->nombre ?? 'Sin sucursal' }}
        </div>
    </div>

    <div style="text-align:center;margin-bottom:1.25rem;">
        @if($movimientoCaja->es_pago_final)
            <span style="background:#dcfce7;color:#166534;padding:.4rem 1.2rem;border-radius:20px;font-size:13px;font-weight:700;">✅ PAGO FINAL</span>
        @elseif($movimientoCaja->es_anticipo)
            <span style="background:#fef9c3;color:#854d0e;padding:.4rem 1.2rem;border-radius:20px;font-size:13px;font-weight:700;">⏳ ANTICIPO</span>
        @else
            <span style="background:#dbeafe;color:#1e40af;padding:.4rem 1.2rem;border-radius:20px;font-size:13px;font-weight:700;">{{ $movimientoCaja->tipo }}</span>
        @endif
    </div>

    @if($orden)
    <div style="background:#f8fafc;border-radius:8px;padding:1rem;margin-bottom:1rem;font-size:13px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.5rem;">Orden de Servicio</div>
        <div style="display:flex;justify-content:space-between;margin-bottom:.35rem;">
            <span style="color:#6b7280;">No. OS</span><strong>#{{ $orden->numero_os }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:.35rem;">
            <span style="color:#6b7280;">Cliente</span><strong>{{ $orden->cliente->nombre ?? '—' }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:.35rem;">
            <span style="color:#6b7280;">Equipo</span>
            <strong>{{ $orden->tipo_dispositivo ?? '' }} {{ $orden->marca }} {{ $orden->modelo }}</strong>
        </div>
        @if($orden->imei)
        <div style="display:flex;justify-content:space-between;margin-bottom:.35rem;">
            <span style="color:#6b7280;">IMEI</span><strong>{{ $orden->imei }}</strong>
        </div>
        @endif
    </div>
    @endif

    {{-- Historial de pagos: aparece cuando la OS tiene más de un movimiento relacionado. --}}
    @if($pagosOrden->count() > 1)
    <div style="margin-bottom:1rem;font-size:13px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:.5rem;">Historial de pagos</div>
        @foreach($pagosOrden as $p)
        <div style="display:flex;justify-content:space-between;padding:.3rem 0;border-bottom:1px solid #f1f5f9;font-size:12px;">
            <span style="color:#6b7280;">
                {{ $p->created_at?->format('d/m/Y') ?? '—' }} —
                {{ $p->es_anticipo ? 'Anticipo' : ($p->es_pago_final ? 'Pago final' : $p->categoria) }}
            </span>
            <span style="font-weight:600;color:{{ $p->tipo === 'INGRESO' ? '#16a34a' : '#dc2626' }};">
                {{ $p->tipo === 'INGRESO' ? '+' : '-' }}${{ number_format($p->monto,2) }}
            </span>
        </div>
        @endforeach
    </div>
    @endif

    <div style="border-top:2px dashed #e2e8f0;padding-top:1rem;margin-top:.5rem;font-size:13px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:.4rem;">
            <span style="color:#6b7280;">Categoría</span>
            <span>{{ $movimientoCaja->categoria }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:.4rem;">
            <span style="color:#6b7280;">Método de pago</span>
            <span>
                @if($movimientoCaja->metodo_pago=='efectivo') 💵 Efectivo
                @elseif($movimientoCaja->metodo_pago=='transferencia') 🏦 Transferencia
                @else 💳 Tarjeta
                @endif
            </span>
        </div>
        @if($movimientoCaja->referencia_pago)
        <div style="display:flex;justify-content:space-between;margin-bottom:.4rem;">
            <span style="color:#6b7280;">Referencia</span>
            <span>{{ $movimientoCaja->referencia_pago }}</span>
        </div>
        @endif
        @if($movimientoCaja->descripcion)
        <div style="display:flex;justify-content:space-between;margin-bottom:.4rem;">
            <span style="color:#6b7280;">Descripción</span>
            <span>{{ $movimientoCaja->descripcion }}</span>
        </div>
        @endif
    </div>

    {{-- El color y texto distinguen visualmente ingresos de egresos. --}}
    <div style="background:{{ $movimientoCaja->tipo === 'INGRESO' ? '#0f1f3d' : '#991b1b' }};color:#fff;border-radius:10px;padding:1rem 1.25rem;margin-top:1rem;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:13px;opacity:.8;">{{ $movimientoCaja->tipo === 'INGRESO' ? 'Monto recibido' : 'Monto entregado' }}</span>
        <span style="font-size:24px;font-weight:800;">${{ number_format($movimientoCaja->monto,2) }}</span>
    </div>

    @if($movimientoCaja->saldo_pendiente > 0)
    <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:8px;padding:.75rem 1rem;margin-top:.75rem;display:flex;justify-content:space-between;">
        <span style="color:#dc2626;font-size:13px;font-weight:600;">Saldo pendiente</span>
        <span style="color:#dc2626;font-size:16px;font-weight:800;">${{ number_format($movimientoCaja->saldo_pendiente,2) }}</span>
    </div>
    @elseif($movimientoCaja->es_pago_final)
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.75rem 1rem;margin-top:.75rem;text-align:center;">
        <span style="color:#16a34a;font-size:13px;font-weight:700;">✅ Orden LIQUIDADA — Equipo entregado</span>
    </div>
    @endif

    <div style="text-align:center;margin-top:1.5rem;padding-top:1rem;border-top:2px dashed #e2e8f0;font-size:11px;color:#9ca3af;">
        <div>Folio: #{{ $movimientoCaja->id }}</div>
        {{-- Datos del negocio: se conectan con ConfiguracionController para personalizar el ticket. --}}
        @if(!empty($configuracionGlobal['negocio_telefono']))<div style="margin-top:3px;">{{ $configuracionGlobal['negocio_telefono'] }}</div>@endif
        @if(!empty($configuracionGlobal['negocio_direccion']))<div style="margin-top:2px;">{{ $configuracionGlobal['negocio_direccion'] }}</div>@endif
        <div style="margin-top:4px;">Gracias por su preferencia</div>
        <div style="margin-top:2px;">{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }} — {{ $configuracionGlobal['negocio_subtitulo'] ?? 'Sistema de Taller' }}</div>
    </div>
</div>

<style>
@media print {
    nav, .page-header, footer, .btn, .sidebar, .topbar { display:none !important; }
    .main { margin-left:0 !important; }
    .content { padding:0 !important; }
    #ticket { border:none !important; box-shadow:none !important; max-width:100% !important; }
    body { background:white !important; }
}
</style>
@endsection
