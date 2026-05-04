@extends('layout')

@section('content')
<div class="page-header">
    <h1>Inventario de Refacciones</h1>
    <a href="{{ route('inventario.create') }}" class="btn btn-primary">+ Agregar pieza</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total piezas</div>
        <div class="stat-num">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Stock bajo</div>
        <div class="stat-num red">{{ $stats['bajo'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Valor en inventario</div>
        <div class="stat-num green">${{ number_format($stats['valor'], 2) }}</div>
    </div>
</div>

<form method="GET" class="toolbar">
    <select name="sucursal_id" onchange="this.form.submit()">
        <option value="">Todas las sucursales</option>
        @foreach($sucursales as $s)
            <option value="{{ $s->id }}" {{ request('sucursal_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
        @endforeach
    </select>
    <label style="display:flex;align-items:center;gap:6px;font-size:13px">
        <input type="checkbox" name="bajo_stock" value="1" {{ request('bajo_stock') ? 'checked' : '' }} onchange="this.form.submit()"/>
        Solo stock bajo
    </label>
</form>

<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Sucursal</th>
            <th>Cantidad</th>
            <th>Stock mínimo</th>
            <th>Precio venta</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inventario as $pieza)
        <tr>
            <td><strong>{{ $pieza->nombre }}</strong><br><span style="font-size:11px;color:#888">{{ $pieza->dispositivo_compatible }}</span></td>
            <td>{{ $pieza->categoria }}</td>
            <td>{{ $pieza->sucursal->nombre }}</td>
            <td>
                <span style="font-weight:600;color:{{ $pieza->cantidad_disponible <= $pieza->stock_minimo ? '#dc2626' : '#16a34a' }}">
                    {{ $pieza->cantidad_disponible }}
                </span>
            </td>
            <td>{{ $pieza->stock_minimo }}</td>
            <td>${{ number_format($pieza->precio_venta, 2) }}</td>
            <td>
                <div style="display:flex;gap:6px">
                    <a href="{{ route('inventario.edit', $pieza) }}" class="btn">Editar</a>
                    <form method="POST" action="{{ route('inventario.destroy', $pieza) }}" onsubmit="return confirm('¿Eliminar esta pieza?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;color:#888;padding:2rem">No hay piezas registradas</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection