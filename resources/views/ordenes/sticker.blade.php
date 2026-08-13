@extends('layout')

@section('content')
<div class="page-header">
    <h1>Sticker de Orden</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button onclick="window.print()" class="btn btn-primary">Imprimir sticker</button>
        <a href="{{ route('ordenes.index') }}" class="btn">Volver a Órdenes</a>
    </div>
</div>

{{-- Sticker imprimible: se conecta con OrdenServicioController::sticker y toma datos de ordenes_servicio. --}}
<div id="sticker" style="width:300px;margin:0 auto;padding:12px;font-family:Arial,sans-serif;text-transform:uppercase;border:2px solid #000;background:white;color:#000;">
    <div style="text-align:center;border-bottom:2px solid #000;padding-bottom:6px;margin-bottom:8px;">
        <div style="font-size:22px;font-weight:900;">MovilPhone</div>
        <div style="font-size:19px;font-weight:900;">{{ $ordenServicio->numero_os }}</div>
        {{-- Contacto del local: queda debajo del folio dinámico para que el cliente pueda comunicarse desde la etiqueta. --}}
        <div style="font-size:14px;font-weight:900;margin-top:3px;">CONTÁCTANOS: 9911098036</div>
    </div>

    {{-- Datos principales: aumentan su tamaño y permiten saltos para conservar textos largos dentro del sticker. --}}
    <div class="sticker-detail"><strong>Cliente:</strong> {{ $ordenServicio->cliente->nombre ?? '—' }}</div>
    <div class="sticker-detail"><strong>Tel:</strong> {{ $ordenServicio->cliente->telefono_principal ?? '—' }}</div>
    <div class="sticker-detail"><strong>Tipo:</strong> {{ $ordenServicio->tipo_dispositivo ?? '—' }}</div>
    <div class="sticker-detail"><strong>Equipo:</strong> {{ $ordenServicio->marca }} {{ $ordenServicio->modelo }}</div>
    <div class="sticker-detail"><strong>Problema:</strong> {{ $ordenServicio->problema_reportado }}</div>
    <div class="sticker-detail"><strong>Acceso:</strong> {{ $ordenServicio->contrasena_dispositivo ?: '—' }}</div>

    <div style="text-align:center;border-top:2px solid #000;padding-top:7px;margin-top:9px;font-size:14px;font-weight:900;">
        {{ $ordenServicio->sucursal->nombre ?? 'MovilPhone' }}
    </div>
</div>

<style>
    /* Texto legible: conserva el ancho físico de la etiqueta y envuelve datos extensos sin recortarlos. */
    .sticker-detail {
        margin-bottom: 6px;
        font-size: 15px;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    /* Ajustes de impresión: ocultan el sistema y dejan solo la etiqueta del equipo. */
    @media print {
        .sidebar, .topbar, .page-header, .btn { display: none !important; }
        .main { margin-left: 0 !important; }
        .content { padding: 0 !important; }
        body { background: white !important; }
        #sticker { margin: 0 !important; box-shadow: none !important; }
    }
</style>
@endsection
