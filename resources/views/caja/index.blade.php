@extends('layout')

@section('content')
<div class="page-header">
    <h1>Caja y Finanzas</h1>
    <a href="{{ route('caja.create') }}" class="btn btn-primary">+ Registrar movimiento</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Ingresos del día</div>
        <div class="stat-num green">${{ number_format($stats['ingresos'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Egresos del día</div>
        <div class="stat-num red">${{ number_format($stats['egresos'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Balance del día</div>
        <div class="stat-num {{ $stats['balance'] >= 0 ? 'green' : 'red' }}">${{ number_format($stats['balance'], 2) }}</div>
    </div>
</div>

<form method="GET" class="toolbar">
    <input type="date" name="fecha" value="{{ request('fecha') }}" onchange="this.form.submit()"/>
    <select name="tipo" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option {{ request('tipo') == 'INGRESO' ? 'selected' : '' }}>INGRESO</option>
        <option {{ request('tipo') == 'EGRESO' ? 'selected' : '' }}>EGRESO</option>
    </select>
    <select name="sucursal_id" onchange="this.form.submit()">
        <option value="">Todas las sucursales</option>
        @foreach($sucursales as $s)
            <option value="{{ $s->id }}" {{ request('sucursal_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
        @endforeach
    </select>
</form>

<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Categoría</th>
            <th>Descripción</th>
            <th>Sucursal</th>
            <th>Monto</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movimientos as $mov)
        <tr>
            <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
            <td>
                <span class="badge {{ $mov->tipo == 'INGRESO' ? 'badge-autorizado' : 'badge-rechazado' }}">
                    {{ $mov->tipo }}
                </span>
            </td>
            <td>{{ $mov->categoria }}</td>
            <td>{{ $mov->descripcion ?? '—' }}</td>
            <td>{{ $mov->sucursal->nombre }}</td>
            <td style="font-weight:600;color:{{ $mov->tipo == 'INGRESO' ? '#16a34a' : '#dc2626' }}">
                {{ $mov->tipo == 'INGRESO' ? '+' : '-' }}${{ number_format($mov->monto, 2) }}
            </td>
            <td>
                <form method="POST" action="{{ route('caja.destroy', $mov) }}" onsubmit="return confirm('¿Eliminar este movimiento?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;color:#888;padding:2rem">No hay movimientos registrados</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection