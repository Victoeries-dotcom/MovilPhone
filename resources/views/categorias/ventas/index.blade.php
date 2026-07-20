@extends('layout')

@section('content')
<div class="page-header">
    <h1>Ventas</h1>
    <a href="{{ route('ventas.create') }}" class="btn btn-primary">+ Nueva venta</a>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Sucursal</th>
            <th>Vendedor</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ventas as $venta)
        <tr>
            <td><strong>#{{ $venta->id }}</strong></td>
            <td>{{ $venta->cliente->nombre ?? 'Sin cliente' }}</td>
            <td>{{ $venta->sucursal->nombre ?? '—' }}</td>
            <td>{{ $venta->usuario->name ?? '—' }}</td>
            <td>${{ number_format($venta->total, 2) }}</td>
            <td><span class="badge badge-autorizado">{{ $venta->estado }}</span></td>
            <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="{{ route('ventas.show', $venta) }}" class="btn btn-sm">Ver</a>
                    <form method="POST" action="{{ route('ventas.destroy', $venta) }}"
                        onsubmit="return confirmarEliminacionSistema(event, 'la venta', '#{{ $venta->id }}');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;color:#888;padding:2rem">No hay ventas registradas.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
