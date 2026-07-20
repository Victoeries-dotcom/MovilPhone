@extends('layout')

@section('content')
<div class="page-header">
    <h1>{{ $ordenServicio->numero_os }}</h1>
    <a href="{{ route('ordenes.index') }}" class="btn">← Volver</a>
</div>

@php
$badgeClass = [
    'RECIBIDO' => 'badge-recibido',
    'EN DIAGNÓSTICO' => 'badge-diagnostico',
    'ESPERANDO AUTORIZACIÓN' => 'badge-espera',
    'AUTORIZADO' => 'badge-autorizado',
    'RECHAZADO' => 'badge-rechazado',
    'EN REPARACIÓN' => 'badge-reparacion',
    'ESPERANDO REFACCIÓN' => 'badge-espera',
    'TERMINADO' => 'badge-terminado',
    'NOTIFICADO' => 'badge-terminado',
    'ENTREGADO' => 'badge-entregado',
    'GARANTÍA' => 'badge-garantia',
][$ordenServicio->estado] ?? 'badge-recibido';

// Este importe conecta el modal de rechazo con el anticipo real guardado en la OS.
$anticipoDisponible = (float) ($ordenServicio->anticipo ?? 0);
@endphp

<div class="card" style="margin-bottom:10px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:1rem">
        <span class="badge {{ $badgeClass }}">{{ $ordenServicio->estado }}</span>
       <span style="font-size:12px;color:#888">{{ $ordenServicio->sucursal->nombre ?? '—' }}</span>
    </div>
    <div class="form-grid">
        <div>
            <div style="font-size:11px;color:#888">Cliente</div>
            <div style="font-size:14px">{{ $ordenServicio->cliente->nombre ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Teléfono</div>
            <div style="font-size:14px">{{ $ordenServicio->cliente->telefono_principal ?? '—' }}</div>
        </div>
        {{-- Muestra el teléfono extra capturado en Nueva OS para avisos adicionales. --}}
        <div>
            <div style="font-size:11px;color:#888">Teléfono extra</div>
            <div style="font-size:14px">{{ $ordenServicio->cliente_telefono_extra ?: ($ordenServicio->cliente->telefono_alternativo ?? '—') }}</div>
        </div>
        {{-- Muestra el tipo de dispositivo guardado desde Nueva OS. --}}
        <div>
            <div style="font-size:11px;color:#888">Tipo de dispositivo</div>
            <div style="font-size:14px">{{ $ordenServicio->tipo_dispositivo ?: '—' }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Equipo</div>
            <div style="font-size:14px">{{ $ordenServicio->marca }} {{ $ordenServicio->modelo }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Técnico</div>
            <div style="font-size:14px">{{ $ordenServicio->tecnico->name ?? 'Sin asignar' }}</div>
        </div>
        {{-- Muestra el acceso del dispositivo guardado desde la captura de la orden. --}}
        <div>
            <div style="font-size:11px;color:#888">Patrón / contraseña</div>
            <div style="font-size:14px">{{ $ordenServicio->contrasena_dispositivo ?: '—' }}</div>
        </div>
        <div style="grid-column:1/-1">
            <div style="font-size:11px;color:#888">Problema reportado</div>
            <div style="font-size:14px">{{ $ordenServicio->problema_reportado }}</div>
        </div>
        @if($ordenServicio->problema_diagnosticado)
        <div style="grid-column:1/-1">
            <div style="font-size:11px;color:#888">Diagnóstico técnico</div>
            <div style="font-size:14px">{{ $ordenServicio->problema_diagnosticado }}</div>
        </div>
        @endif
        <div>
            <div style="font-size:11px;color:#888">Accesorios</div>
            <div style="font-size:14px">{{ $ordenServicio->accesorios_entregados }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Presupuesto</div>
            <div style="font-size:14px">${{ number_format($ordenServicio->presupuesto_total, 2) }}</div>
        </div>
        {{-- Muestra el anticipo recibido al momento de crear la OS. --}}
        <div>
            <div style="font-size:11px;color:#888">Anticipo</div>
            <div style="font-size:14px">${{ number_format($ordenServicio->anticipo ?? 0, 2) }} · {{ $ordenServicio->metodo_pago_anticipo ?? 'efectivo' }}</div>
        </div>
    </div>
</div>

{{-- Avanzar estado --}}
@if(count($transiciones) > 0)
<div class="card" style="margin-bottom:10px">
    <div style="font-size:12px;color:#888;margin-bottom:8px">Avanzar estado</div>
    {{-- Este formulario conecta el detalle de la orden con OrdenServicioController::avanzarEstado. --}}
    <form method="POST" action="{{ route('ordenes.avanzarEstado', $ordenServicio) }}">
        @csrf
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            @foreach($transiciones as $t)
                @if($t === 'RECHAZADO')
                    {{-- Rechazar necesita motivo y posible devolución, por eso abre un modal separado. --}}
                    <button type="button" class="btn btn-danger" onclick="abrirRechazoDetalle()">→ {{ $t }}</button>
                @else
                    <button type="submit" name="estado" value="{{ $t }}" class="btn btn-success">→ {{ $t }}</button>
                @endif
            @endforeach
            <input type="text" name="nota" placeholder="Nota opcional…" style="flex:1;min-width:200px;padding:7px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px"/>
        </div>
    </form>
</div>
@endif

{{-- Modal de rechazo desde el detalle de la OS. --}}
<div id="modal-rechazo-detalle" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1100;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;padding:2rem;width:100%;max-width:520px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:22px;font-weight:800;color:#991b1b;margin:0 0 1.25rem;">Rechazar {{ $ordenServicio->numero_os }}</h2>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.45rem;">Motivo *</label>
        <textarea id="rechazo-detalle-motivo" rows="3" maxlength="500" style="width:100%;padding:12px;border:1px solid #dbe3ef;border-radius:8px;box-sizing:border-box;font:inherit;"></textarea>
        @if($anticipoDisponible > 0)
            {{-- Permite devolver como máximo el anticipo que realmente recibió la orden. --}}
            <label style="display:block;font-size:13px;font-weight:700;margin:.9rem 0 .45rem;">Devolución del anticipo ($)</label>
            <input type="number" id="rechazo-detalle-devolucion" min="0" max="{{ $anticipoDisponible }}" step="0.01" value="0" style="width:100%;padding:12px;border:1px solid #dbe3ef;border-radius:8px;box-sizing:border-box;">
            <div style="margin-top:.45rem;color:#64748b;font-size:12px;">Máximo: ${{ number_format($anticipoDisponible, 2) }}</div>
        @else
            {{-- Mantiene devolución en cero y explica por qué no aparece un campo editable. --}}
            <input type="hidden" id="rechazo-detalle-devolucion" value="0">
            <div style="margin-top:.9rem;padding:.8rem 1rem;background:#f1f5f9;border:1px solid #dbe3ef;border-radius:8px;color:#475569;font-size:13px;">
                Esta orden no tiene anticipo registrado. Se rechazará sin devolución.
            </div>
        @endif
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;">
            <button type="button" class="btn" onclick="cerrarRechazoDetalle()">Cancelar</button>
            <button type="button" class="btn btn-danger" onclick="confirmarRechazoDetalle()">Confirmar rechazo</button>
        </div>
    </div>
</div>
<form id="form-rechazo-detalle" method="POST" action="{{ route('ordenes.rechazar', $ordenServicio) }}" style="display:none;">
    @csrf
    <input type="hidden" name="motivo" id="rechazo-detalle-motivo-hidden">
    <input type="hidden" name="devolucion" id="rechazo-detalle-devolucion-hidden">
</form>

{{-- Historial --}}
<div class="card" style="margin-bottom:10px">
    <div style="font-size:12px;color:#888;margin-bottom:8px">Línea de tiempo</div>
    <div style="border-left:2px solid #e5e5e5;padding-left:14px">
        @foreach($ordenServicio->historial as $h)
        <div style="position:relative;margin-bottom:8px;font-size:13px">
            <div style="position:absolute;left:-19px;top:4px;width:8px;height:8px;border-radius:50%;background:{{ $loop->last ? '#1a1a1a' : '#ddd' }}"></div>
            <strong>{{ $h->estado }}</strong>
            <span style="color:#888;font-size:11px"> · {{ $h->created_at->format('d/m/Y H:i') }}</span>
            @if($h->nota) <div style="color:#888;font-size:12px">{{ $h->nota }}</div> @endif
        </div>
        @endforeach
    </div>
</div>

<div style="display:flex;gap:8px">
    <a href="{{ route('ordenes.edit', $ordenServicio) }}" class="btn btn-primary">Editar</a>
    <form method="POST" action="{{ route('ordenes.destroy', $ordenServicio) }}"
        onsubmit="return confirmarEliminacionSistema(event, 'la orden de servicio', '{{ addslashes($ordenServicio->numero_os) }}');">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">Eliminar</button>
    </form>
</div>

<script>
// Controla el rechazo desde el detalle y conecta los datos con OrdenServicioController::rechazar.
function abrirRechazoDetalle() {
    document.getElementById('modal-rechazo-detalle').style.display = 'flex';
    setTimeout(() => document.getElementById('rechazo-detalle-motivo').focus(), 100);
}

function cerrarRechazoDetalle() {
    document.getElementById('modal-rechazo-detalle').style.display = 'none';
}

function confirmarRechazoDetalle() {
    const motivo = document.getElementById('rechazo-detalle-motivo').value.trim();
    const devolucion = parseFloat(document.getElementById('rechazo-detalle-devolucion').value) || 0;
    const maximo = {{ $anticipoDisponible }};

    if (!motivo) {
        alert('Escribe el motivo del rechazo.');
        return;
    }
    if (devolucion < 0 || devolucion > maximo) {
        alert('La devolución no puede superar el anticipo disponible.');
        return;
    }

    document.getElementById('rechazo-detalle-motivo-hidden').value = motivo;
    document.getElementById('rechazo-detalle-devolucion-hidden').value = devolucion.toFixed(2);
    document.getElementById('form-rechazo-detalle').submit();
}
</script>
@endsection
