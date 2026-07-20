@extends('layout')

@section('content')
<div class="page-header ticket-actions">
    <h1>🧾 Ticket de Entrega</h1>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir ticket</button>
        <a href="{{ route('ordenes.index') }}" class="btn">← Volver a Órdenes</a>
    </div>
</div>

@php
    // Importes del ticket: se conectan con ordenes_servicio y con el cobro guardado al entregar.
    $anticipo = (float) ($ordenServicio->anticipo ?? 0);
    $reparacion = (float) ($cobroFinal ?? ($ordenServicio->cobro_diagnostico ?? 0));
    $total = (float) ($totalRegistrado ?? ($anticipo + $reparacion));

    // Contacto alternativo: primero usa el dato capturado en la OS y después el dato del cliente.
    $contactoAlternativo = $ordenServicio->cliente_telefono_extra ?: ($ordenServicio->cliente->telefono_alternativo ?? '—');

    // Fecha de emisión: usa la fecha real de entrega y la formatea para el recibo impreso.
    $fechaEmision = $ordenServicio->fecha_entrega_real
        ? \Carbon\Carbon::parse($ordenServicio->fecha_entrega_real)->format('d/m/Y H:i')
        : now()->format('d/m/Y H:i');

    // Política opcional: solo se consulta si existe la tabla configuraciones en esta instalación.
    $politica = \Illuminate\Support\Facades\Schema::hasTable('configuraciones')
        ? \Illuminate\Support\Facades\DB::table('configuraciones')->where('clave', 'politica_garantia')->value('valor')
        : null;
@endphp

{{-- Contenedor visual: centra el ticket y le da el tamaño del recibo mostrado en el archivo de referencia. --}}
<div class="ticket-page">
    {{-- Ticket final: formato tipo recibo conectado con la orden entregada, cliente, dispositivo y caja. --}}
    <div id="ticket" class="ticket-card">

        {{-- Encabezado del taller: identifica el comprobante impreso con marca y subtítulo. --}}
        <div class="ticket-header">
            <div class="ticket-brand"><span class="ticket-icon">📱</span><span>{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }}</span></div>
            <div class="ticket-subtitle">{{ $configuracionGlobal['negocio_subtitulo'] ?? 'Taller de Reparación' }}</div>
        </div>

        {{-- Fecha y folio: conectan el ticket con la orden de servicio entregada. --}}
        <div class="ticket-meta">
            <div>
                <div class="ticket-meta-title">FECHA DE EMISIÓN</div>
                <div class="ticket-meta-value">{{ $fechaEmision }}</div>
            </div>
            <div class="ticket-meta-right">
                <div class="ticket-meta-title">NO. ORDEN</div>
                <div class="ticket-meta-value">{{ $ordenServicio->numero_os }}</div>
            </div>
        </div>

        <div class="ticket-status">EQUIPO ENTREGADO</div>

        {{-- Datos del cliente: salen de clientes y del teléfono extra guardado en la OS. --}}
        <section class="ticket-section">
            <div class="ticket-section-title">---CLIENTE---</div>
            <div class="ticket-row">
                <span>NOMBRE:</span>
                <strong>{{ $ordenServicio->cliente->nombre ?? '—' }}</strong>
            </div>
            <div class="ticket-row">
                <span>CONTACTO:</span>
                <strong>{{ $ordenServicio->cliente->telefono_principal ?? '—' }}</strong>
            </div>
            <div class="ticket-row">
                <span>CONTACTO ALTERNATIVO:</span>
                <strong>{{ $contactoAlternativo }}</strong>
            </div>
        </section>

        {{-- Datos del dispositivo: resumen técnico conectado con ordenes_servicio. --}}
        <section class="ticket-section">
            <div class="ticket-section-title">---DISPOSITIVO---</div>
            <div class="ticket-row">
                <span>TIPO:</span>
                <strong>{{ $ordenServicio->tipo_dispositivo ?: '—' }}</strong>
            </div>
            <div class="ticket-row">
                <span>MODELO:</span>
                <strong>{{ $ordenServicio->marca }} {{ $ordenServicio->modelo }}</strong>
            </div>
            <div class="ticket-row">
                <span>QUÉ SE REPARÓ:</span>
                <strong>{{ $ordenServicio->problema_diagnosticado ?: $ordenServicio->problema_reportado }}</strong>
            </div>
            <div class="ticket-row">
                <span>TÉCNICO:</span>
                <strong>{{ $tecnicoEntrega }}</strong>
            </div>
        </section>

        {{-- Cobros: separa reparación y anticipo para que el cliente vea cómo se forma el total. --}}
        <section class="ticket-section ticket-section-cobros">
            <div class="ticket-section-title">---COBROS---</div>
            <div class="ticket-row">
                <span>REPARACIÓN:</span>
                <strong>${{ number_format($reparacion, 2) }}</strong>
            </div>
            <div class="ticket-row">
                <span>ANTICIPO PAGADO:</span>
                <strong>${{ number_format($anticipo, 2) }}</strong>
            </div>
        </section>

        {{-- Total final: suma anticipo y reparación registrados en la entrega. --}}
        <div class="ticket-total">
            <span>TOTAL DEL SERVICIO</span>
            <strong>${{ number_format($total, 2) }}</strong>
        </div>

        @if($politica)
            {{-- Política de garantía: se muestra solo si existe configuración guardada. --}}
            <section class="ticket-warranty">
                <div class="ticket-warranty-title">POLÍTICA DE GARANTÍA</div>
                <div>{{ $politica }}</div>
            </section>
        @endif

        {{-- Pie del ticket: identifica el registro interno y cierra el comprobante. --}}
        <div class="ticket-footer">
            <div>Folio: #{{ $ordenServicio->id }}</div>
            {{-- Contacto comercial: se conecta con ConfiguracionController y aparece en cada comprobante. --}}
            @if(!empty($configuracionGlobal['negocio_telefono']))<div>{{ $configuracionGlobal['negocio_telefono'] }}</div>@endif
            @if(!empty($configuracionGlobal['negocio_direccion']))<div>{{ $configuracionGlobal['negocio_direccion'] }}</div>@endif
            <div>Gracias por su preferencia</div>
            <div>{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }} — {{ $configuracionGlobal['negocio_subtitulo'] ?? 'Sistema de Taller' }}</div>
        </div>
    </div>
</div>

<style>
/* Pantalla del ticket: crea el fondo gris y centra el recibo como en el archivo de referencia. */
.ticket-page {
    min-height: calc(100vh - 150px);
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 2.2rem 1rem 3rem;
    background: #eef2f7;
}

/* Tarjeta principal del ticket: define ancho, aire interno y tipografía de recibo. */
.ticket-card {
    width: 100%;
    max-width: 450px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 30px 30px 28px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .10);
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
}

/* Encabezado del negocio: conecta visualmente el ticket con MovilPhone. */
.ticket-header {
    text-align: center;
    border-bottom: 3px solid #000;
    padding-bottom: 17px;
    margin-bottom: 20px;
}

.ticket-brand {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-size: 25px;
    font-weight: 900;
    line-height: 1;
}

.ticket-icon { font-size: 21px; }
.ticket-subtitle { margin-top: 9px; font-size: 13px; font-weight: 800; }

/* Fecha y orden: usa dos columnas como el ticket de referencia. */
.ticket-meta {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 20px;
}

.ticket-meta-right { text-align: right; }
.ticket-meta-title { font-size: 15px; font-weight: 900; line-height: 1.1; }
.ticket-meta-value { font-size: 12px; font-weight: 800; margin-top: 3px; }

/* Estado del comprobante: resalta que el equipo ya fue entregado. */
.ticket-status {
    border: 2px solid #000;
    border-radius: 4px;
    text-align: center;
    padding: 8px 10px;
    margin-bottom: 22px;
    font-size: 17px;
    font-weight: 900;
    letter-spacing: .4px;
}

/* Secciones del ticket: separan cliente, dispositivo y cobros con línea negra fuerte. */
.ticket-section {
    border-bottom: 3px solid #000;
    padding-bottom: 18px;
    margin-bottom: 17px;
}

.ticket-section-title {
    text-align: center;
    font-size: 15px;
    font-weight: 900;
    margin-bottom: 13px;
}

/* Filas etiqueta-valor: mantienen los datos alineados como en la captura. */
.ticket-row {
    display: grid;
    grid-template-columns: minmax(190px, 1fr) minmax(90px, auto);
    gap: 12px;
    align-items: start;
    margin-bottom: 8px;
    font-size: 14px;
    line-height: 1.2;
}

.ticket-row span { font-weight: 900; }
.ticket-row strong { font-weight: 800; text-align: right; overflow-wrap: anywhere; }
.ticket-section-cobros .ticket-row { grid-template-columns: 1fr auto; }

/* Total final: muestra el monto principal con el mismo peso visual del recibo. */
.ticket-total {
    border: 3px solid #000;
    border-radius: 4px;
    padding: 14px 16px;
    margin: 16px 0 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.ticket-total span { font-size: 15px; font-weight: 900; }
.ticket-total strong { font-size: 24px; font-weight: 900; }

/* Garantía opcional: solo aparece cuando existe una política guardada. */
.ticket-warranty {
    border-top: 3px solid #000;
    padding-top: 12px;
    margin-top: 12px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.6;
}

.ticket-warranty-title { font-size: 12px; font-weight: 900; margin-bottom: 7px; }

/* Pie del ticket: cierra el comprobante con folio interno y agradecimiento. */
.ticket-footer {
    text-align: center;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 3px solid #000;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.55;
}

/* Impresión del ticket: oculta la interfaz del sistema y deja solo el recibo. */
@media print {
    nav, .ticket-actions, footer, .btn, .sidebar, .topbar { display: none !important; }
    .main { margin-left: 0 !important; }
    .content { padding: 0 !important; }
    .ticket-page { min-height: auto !important; padding: 0 !important; background: #fff !important; }
    #ticket {
        border: none !important;
        box-shadow: none !important;
        max-width: 100% !important;
        border-radius: 0 !important;
        font-family: Arial, Helvetica, sans-serif !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    body { background: white !important; }
    * { color: #000 !important; }
}
</style>
@endsection
