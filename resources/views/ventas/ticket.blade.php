@extends('layout')

@section('content')
<div class="page-header ticket-actions">
    <h1>🧾 Ticket de Venta</h1>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir ticket</button>
        <a href="{{ route('ventas.index') }}" class="btn">← Volver a Ventas</a>
    </div>
</div>

@php
    // La fecha, el folio y el total provienen de la venta registrada y no se recalculan desde Caja.
    $fechaEmision = $venta->created_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
@endphp

{{-- Mantiene la proporción visual del ticket de entrega mostrado como referencia. --}}
<div class="ticket-page">
    <div class="ticket-card">
        {{-- La venta genera únicamente el comprobante que se entrega al cliente. --}}
        <div class="ticket-copy-label">COPIA CLIENTE</div>
        {{-- Identidad comercial compartida por ConfiguracionController y AppServiceProvider. --}}
        <header class="ticket-header">
            <div class="ticket-brand"><span class="ticket-icon">📱</span><span>{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }}</span></div>
            <div class="ticket-subtitle">{{ $configuracionGlobal['negocio_subtitulo'] ?? 'Sistema de Taller' }}</div>
        </header>

        {{-- Identifica de manera inequívoca cuándo y cuál venta generó el comprobante. --}}
        <div class="ticket-meta">
            <div>
                <div class="ticket-meta-title">FECHA DE EMISIÓN</div>
                <div class="ticket-meta-value">{{ $fechaEmision }}</div>
            </div>
            <div class="ticket-meta-right">
                <div class="ticket-meta-title">NO. VENTA</div>
                <div class="ticket-meta-value">VEN-{{ str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>

        <div class="ticket-status">VENTA REGISTRADA</div>

        {{-- Datos operativos: se conectan con ventas.usuario_id y ventas.sucursal_id. --}}
        <section class="ticket-section">
            <div class="ticket-section-title">---DATOS DE VENTA---</div>
            <div class="ticket-row">
                <span>VENDEDOR:</span>
                <strong>{{ $venta->usuario->name ?? '—' }}</strong>
            </div>
            <div class="ticket-row">
                <span>SUCURSAL:</span>
                <strong>{{ $venta->sucursal->nombre ?? '—' }}</strong>
            </div>
            <div class="ticket-row">
                <span>MÉTODO DE PAGO:</span>
                <strong>EFECTIVO</strong>
            </div>
        </section>

        {{-- Productos: cada renglón conserva cantidad, precio unitario y subtotal de venta_detalles. --}}
        <section class="ticket-section ticket-products">
            <div class="ticket-section-title">---PRODUCTOS / SERVICIOS---</div>
            @forelse($venta->detalles as $detalle)
                <article class="ticket-product">
                    <strong class="ticket-product-name">{{ $detalle->nombre_producto }}</strong>
                    <div class="ticket-product-calculation">
                        <span>{{ $detalle->cantidad }} × ${{ number_format($detalle->precio_unitario, 2) }}</span>
                        <strong>${{ number_format($detalle->subtotal, 2) }}</strong>
                    </div>
                </article>
            @empty
                <div class="ticket-empty">Sin productos registrados</div>
            @endforelse
        </section>

        @if($venta->notas)
            {{-- Las notas son las capturadas al registrar la venta; no se agregan datos de cliente. --}}
            <section class="ticket-section ticket-notes">
                <div class="ticket-section-title">---NOTAS---</div>
                <div>{{ $venta->notas }}</div>
            </section>
        @endif

        <div class="ticket-total">
            <span>TOTAL DE LA VENTA</span>
            <strong>${{ number_format($venta->total, 2) }}</strong>
        </div>

        @if($politica)
            {{-- La garantía global aparece completa en ambas copias y antes del folio interno. --}}
            <section class="ticket-warranty">
                <div class="ticket-warranty-title">POLÍTICA DE GARANTÍA</div>
                <div class="ticket-warranty-text">{{ $politica }}</div>
            </section>
        @endif

        {{-- El pie conserva el folio interno y los datos configurables del negocio. --}}
        <footer class="ticket-footer">
            <div>Folio: #{{ $venta->id }}</div>
            @if(!empty($configuracionGlobal['negocio_telefono']))<div>{{ $configuracionGlobal['negocio_telefono'] }}</div>@endif
            @if(!empty($configuracionGlobal['negocio_direccion']))<div>{{ $configuracionGlobal['negocio_direccion'] }}</div>@endif
            <div>Gracias por su preferencia</div>
            <div>{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }} — {{ $configuracionGlobal['negocio_subtitulo'] ?? 'Sistema de Taller' }}</div>
        </footer>
    </div>
</div>

<style>
/* Centra el comprobante y conserva el fondo neutro de la referencia. */
.ticket-page { min-height:calc(100vh - 150px);display:flex;justify-content:center;align-items:flex-start;flex-wrap:wrap;gap:2rem;padding:2.2rem 1rem 3rem;background:#eef2f7; }
.ticket-card { width:100%;max-width:450px;background:#fff;border:1px solid #e2e8f0;border-radius:9px;padding:30px;box-shadow:0 2px 10px rgba(15,23,42,.10);font-family:Arial,Helvetica,sans-serif;color:#000; }
.ticket-copy-label { margin:-12px 0 16px;text-align:center;font-size:11px;font-weight:900;letter-spacing:.12em; }
.ticket-header { text-align:center;border-bottom:3px solid #000;padding-bottom:17px;margin-bottom:20px; }
.ticket-brand { display:inline-flex;align-items:center;justify-content:center;gap:9px;font-size:25px;font-weight:900;line-height:1; }
.ticket-icon { font-size:21px; }
.ticket-subtitle { margin-top:9px;font-size:13px;font-weight:800; }
.ticket-meta { display:flex;justify-content:space-between;gap:1rem;margin-bottom:20px; }
.ticket-meta-right { text-align:right; }
.ticket-meta-title { font-size:15px;font-weight:900;line-height:1.1; }
.ticket-meta-value { font-size:12px;font-weight:800;margin-top:3px; }
.ticket-status { border:2px solid #000;border-radius:4px;text-align:center;padding:8px 10px;margin-bottom:22px;font-size:17px;font-weight:900;letter-spacing:.4px; }
.ticket-section { border-bottom:3px solid #000;padding-bottom:18px;margin-bottom:17px; }
.ticket-section-title { text-align:center;font-size:15px;font-weight:900;margin-bottom:13px; }
.ticket-row { display:grid;grid-template-columns:minmax(160px,1fr) minmax(90px,auto);gap:12px;align-items:start;margin-bottom:8px;font-size:14px;line-height:1.2; }
.ticket-row span,.ticket-product-name { font-weight:900; }
.ticket-row strong { font-weight:800;text-align:right;overflow-wrap:anywhere; }
.ticket-product { padding:8px 0;border-bottom:1px solid #cbd5e1; }
.ticket-product:last-child { border-bottom:0; }
.ticket-product-name { display:block;font-size:14px;line-height:1.25; }
.ticket-product-calculation { display:flex;justify-content:space-between;gap:1rem;margin-top:5px;font-size:13px; }
.ticket-product-calculation span { font-weight:700; }
.ticket-product-calculation strong { font-weight:900; }
.ticket-empty,.ticket-notes { font-size:13px;font-weight:700;line-height:1.45; }
.ticket-total { border:3px solid #000;border-radius:4px;padding:14px 16px;margin:16px 0 14px;display:flex;justify-content:space-between;align-items:center;gap:1rem; }
.ticket-total span { font-size:15px;font-weight:900; }
.ticket-total strong { font-size:24px;font-weight:900; }
.ticket-warranty { border-top:3px solid #000;padding-top:12px;margin-top:12px;font-size:11px;font-weight:700;line-height:1.6; }
.ticket-warranty-title { font-size:12px;font-weight:900;margin-bottom:7px; }
.ticket-warranty-text { white-space:pre-line; }
.ticket-footer { text-align:center;margin-top:14px;padding-top:12px;border-top:3px solid #000;font-size:11px;font-weight:800;line-height:1.55; }
/* Imprime solamente el recibo y elimina menú, botones y sombras de pantalla. */
@media print {
    nav,.ticket-actions,footer:not(.ticket-footer),.btn,.sidebar,.topbar { display:none !important; }
    .main { margin-left:0 !important; }
    .content { padding:0 !important; }
    .ticket-page { min-height:auto !important;padding:0 !important;background:#fff !important; }
    .ticket-card { border:0 !important;box-shadow:none !important;max-width:100% !important;border-radius:0 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;page-break-after:always;break-after:page; }
    .ticket-card:last-child { page-break-after:auto;break-after:auto; }
    body { background:#fff !important; }
}
</style>
@endsection
