@extends('layout')

@section('content')
<div class="page-header">
    <h1>Clientes</h1>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">+ Nuevo cliente</a>
</div>

{{-- Buscador de clientes: se conecta con ClienteController::index usando el parámetro search. --}}
<form method="GET" class="toolbar">
    <input type="text" name="search" placeholder="Buscar por nombre o teléfono…" value="{{ request('search') }}"/>
    <button type="submit" class="btn">Buscar</button>
</form>

{{-- Tabla principal: se conecta con ClienteController::index y separa el historial de las acciones administrativas. --}}
<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Servicios anteriores</th>
            <th>Historial</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($clientes as $cliente)
        <tr>
            {{-- Nombre del cliente: se muestra en negritas para identificarlo rápidamente. --}}
            <td><strong style="text-transform:uppercase;">{{ $cliente->nombre }}</strong></td>
            <td>{{ $cliente->telefono_principal }}</td>
            {{-- Cuenta únicamente servicios cerrados como ENTREGADO o RECHAZADO. --}}
            <td><strong>{{ $cliente->servicios_anteriores_count ?? 0 }}</strong> servicio(s)</td>
            <td>
                {{-- El botón Historial abre ClienteController::show con las órdenes activas y anteriores del cliente. --}}
                <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-primary">Ver</a>
            </td>
            <td>
                {{-- Acciones administrativas: conectan con edit y destroy del recurso clientes. --}}
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <a href="{{ route('clientes.edit', $cliente) }}" class="btn">Editar</a>
                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}"
                        onsubmit="return confirmarEliminacionSistema(event, 'el cliente', '{{ addslashes($cliente->nombre) }}');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align:center;color:#888;padding:2rem">No hay clientes registrados</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
