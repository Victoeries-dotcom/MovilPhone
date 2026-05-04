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
@endphp

<div class="card" style="margin-bottom:10px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:1rem">
        <span class="badge {{ $badgeClass }}">{{ $ordenServicio->estado }}</span>
       <span style="font-size:12px;color:#888">{{ $ordenServicio->sucursal->nombre ?? '—' }}</span>
    </div>
    <div class="form-grid">
        <div>
            <div style="font-size:11px;color:#888">Cliente</div>
            <div style="font-size:14px">{{ $ordenServicio->cliente->nombre }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Teléfono</div>
            <div style="font-size:14px">{{ $ordenServicio->cliente->telefono_principal }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Equipo</div>
            <div style="font-size:14px">{{ $ordenServicio->marca }} {{ $ordenServicio->modelo }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Técnico</div>
            <div style="font-size:14px">{{ $ordenServicio->tecnico->name ?? 'Sin asignar' }}</div>
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
    </div>
</div>

{{-- Avanzar estado --}}
@if(count($transiciones) > 0)
<div class="card" style="margin-bottom:10px">
    <div style="font-size:12px;color:#888;margin-bottom:8px">Avanzar estado</div>
    <form method="POST" action="{{ route('ordenes.estado', $ordenServicio) }}">
        @csrf
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            @foreach($transiciones as $t)
                <button type="submit" name="estado" value="{{ $t }}" class="btn btn-success">→ {{ $t }}</button>
            @endforeach
            <input type="text" name="nota" placeholder="Nota opcional…" style="flex:1;min-width:200px;padding:7px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px"/>
        </div>
    </form>
</div>
@endif

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
    <form method="POST" action="{{ route('ordenes.destroy', $ordenServicio) }}" onsubmit="return confirm('¿Eliminar esta OS?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">Eliminar</button>
    </form>
</div>
@endsection