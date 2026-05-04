@extends('layout')

@section('content')
<div class="page-header">
    <h1>Clientes</h1>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">+ Nuevo cliente</a>
</div>

<form method="GET" class="toolbar">
    <input type="text" name="search" placeholder="Buscar por nombre o teléfono…" value="{{ request('search') }}"/>
    <button type="submit" class="btn">Buscar</button>
</form>

<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Sucursal</th>
            <th>Visitas</th>
            <th>Total gastado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($clientes as $cliente)
        <tr>
            <td><strong>{{ $cliente->nombre }}</strong></td>
            <td>{{ $cliente->telefono_principal }}</td>
            <td>{{ $cliente->sucursal->nombre ?? '—' }}</td>
            <td>{{ $cliente->ordenes_count }}</td>
            <td>${{ number_format($cliente->ordenes_sum_presupuesto_total ?? 0, 2) }}</td>
            <td>
                <div style="display:flex;gap:6px">
                    <a href="{{ route('clientes.show', $cliente) }}" class="btn">Ver</a>
                    <a href="{{ route('clientes.edit', $cliente) }}" class="btn">Editar</a>
                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirm('¿Eliminar este cliente?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center;color:#888;padding:2rem">No hay clientes registrados</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection