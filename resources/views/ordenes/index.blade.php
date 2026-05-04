@extends('layout')

@section('content')
<div class="page-header">
    <h1>Órdenes de Servicio</h1>
    <a href="{{ route('ordenes.create') }}" class="btn btn-primary">+ Nueva OS</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Recibidos</div>
        <div class="stat-num blue">{{ $stats['recibidos'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">En proceso</div>
        <div class="stat-num amber">{{ $stats['en_proceso'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Listos para entregar</div>
        <div class="stat-num green">{{ $stats['listos'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Esperando autorización</div>
        <div class="stat-num red">{{ $stats['esperando'] }}</div>
    </div>
</div>

<form method="GET" class="toolbar">
    <input type="text" name="search" placeholder="Buscar cliente o número OS…" value="{{ request('search') }}"/>
    <select name="estado" onchange="this.form.submit()">
        <option value="">Todos los estados</option>
        @foreach(['RECIBIDO','EN DIAGNÓSTICO','ESPERANDO AUTORIZACIÓN','AUTORIZADO','RECHAZADO','EN REPARACIÓN','ESPERANDO REFACCIÓN','TERMINADO','NOTIFICADO','ENTREGADO','GARANTÍA'] as $e)
            <option {{ request('estado') == $e ? 'selected' : '' }}>{{ $e }}</option>
        @endforeach
    </select>
    <select name="sucursal_id" onchange="this.form.submit()">
        <option value="">Todas las sucursales</option>
        @foreach($sucursales as $s)
            <option value="{{ $s->id }}" {{ request('sucursal_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn">Buscar</button>
</form>

@forelse($ordenes as $orden)
<div class="card">
    <div class="card-header">
        <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <strong>{{ $orden->numero_os }}</strong>
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
                    ][$orden->estado] ?? 'badge-recibido';
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $orden->estado }}</span>
                <span style="font-size:11px;color:#888">{{ $orden->sucursal->nombre }}</span>
            </div>
            <div style="font-size:13px;color:#555">{{ $orden->cliente->nombre }} · {{ $orden->cliente->telefono_principal }}</div>
            <div style="font-size:12px;color:#888;margin-top:2px">{{ $orden->marca }} {{ $orden->modelo }} {{ $orden->tecnico ? '· Técnico: '.$orden->tecnico->name : '' }}</div>
            <div style="font-size:12px;color:#888;margin-top:6px;padding-top:6px;border-top:1px solid #f0f0f0">{{ $orden->problema_reportado }}</div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="{{ route('ordenes.show', $orden) }}" class="btn">Ver detalle</a>
            <a href="{{ route('ordenes.edit', $orden) }}" class="btn">Editar</a>
            <form method="POST" action="{{ route('ordenes.destroy', $orden) }}" onsubmit="return confirm('¿Eliminar esta OS?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </div>
</div>
@empty
<div style="text-align:center;color:#888;padding:3rem;font-size:14px">No hay órdenes que coincidan</div>
@endforelse
@endsection