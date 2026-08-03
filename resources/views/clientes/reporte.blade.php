@extends('layout')

@section('content')
<div class="page-header">
    <div>
        <h1>Reporte por usuario</h1>
        <div class="page-title-sub">Compras y órdenes realizadas por cada cliente</div>
    </div>
    <a href="{{ route('clientes.index') }}" class="btn">← Volver a clientes</a>
</div>

<form method="GET" action="{{ route('clientes.reportes.usuarios') }}" class="card">
    <div class="form-grid">
        <div class="form-group">
            <label for="search">Cliente</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Nombre o teléfono">
        </div>
        <div class="form-group">
            <label for="usuario_id">Usuario que atendió</label>
            <select id="usuario_id" name="usuario_id">
                <option value="">Todos los usuarios</option>
                @foreach($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected((string) request('usuario_id') === (string) $usuario->id)>{{ $usuario->name }} — {{ $usuario->rol }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="fecha_inicio">Desde</label>
            <input id="fecha_inicio" type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}">
        </div>
        <div class="form-group">
            <label for="fecha_fin">Hasta</label>
            <input id="fecha_fin" type="date" name="fecha_fin" value="{{ request('fecha_fin') }}">
        </div>
        <div class="form-group full-width">
            <label for="sucursal_id">Sucursal</label>
            <select id="sucursal_id" name="sucursal_id" @disabled(auth()->user()->rol === 'usuario' && session('sucursal_id'))>
                <option value="">Todas las sucursales</option>
                @foreach($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected((string) request('sucursal_id') === (string) $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
            @if(auth()->user()->rol === 'usuario' && session('sucursal_id'))
                <small style="color:#64748b;">El rol usuario consulta únicamente la sucursal activa.</small>
            @endif
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit">Buscar</button>
        <a class="btn" href="{{ route('clientes.reportes.usuarios') }}">Limpiar</a>
        <a class="btn btn-danger" href="{{ route('clientes.reportes.usuarios.pdf', request()->query()) }}">Exportar PDF</a>
        <a class="btn btn-success" href="{{ route('clientes.reportes.usuarios.excel', request()->query()) }}">Exportar Excel</a>
    </div>
</form>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Clientes</div><div class="stat-num">{{ $totales['clientes'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Compras</div><div class="stat-num blue">{{ $totales['compras'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Total ventas</div><div class="stat-num green">${{ number_format($totales['ventas'], 2) }}</div></div>
    <div class="stat-card"><div class="stat-label">Órdenes</div><div class="stat-num amber">{{ $totales['ordenes'] }}</div></div>
</div>

<div style="overflow-x:auto;">
<table>
    <thead><tr><th>Cliente</th><th>Teléfono</th><th>Sucursal</th><th>Compras</th><th>Total ventas</th><th>Órdenes</th><th>Total órdenes</th></tr></thead>
    <tbody>
    @forelse($clientes as $cliente)
        <tr>
            <td><strong>{{ $cliente->nombre }}</strong></td>
            <td>{{ $cliente->telefono_principal }}</td>
            <td>{{ $cliente->sucursal?->nombre ?? 'Sin sucursal' }}</td>
            <td>{{ $cliente->compras_count }}</td>
            <td>${{ number_format($cliente->compras_total ?? 0, 2) }}</td>
            <td>{{ $cliente->ordenes_reporte_count }}</td>
            <td>${{ number_format($cliente->ordenes_total ?? 0, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="7" style="text-align:center;color:#888;padding:2rem;">No hay clientes que coincidan con los filtros.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
