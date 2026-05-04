@extends('layout')

@section('content')
<div class="page-header">
    <h1>{{ $cliente->nombre }}</h1>
    <a href="{{ route('clientes.index') }}" class="btn">← Volver</a>
</div>

<div class="card" style="margin-bottom:10px">
    <div class="form-grid">
        <div>
            <div style="font-size:11px;color:#888">Teléfono principal</div>
            <div style="font-size:14px">{{ $cliente->telefono_principal }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Teléfono alternativo</div>
            <div style="font-size:14px">{{ $cliente->telefono_alternativo ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Sucursal habitual</div>
            <div style="font-size:14px">{{ $cliente->sucursal->nombre ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Dirección</div>
            <div style="font-size:14px">{{ $cliente->direccion ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Cliente desde</div>
            <div style="font-size:14px">{{ $cliente->created_at->format('d/m/Y') }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Total de visitas</div>
            <div style="font-size:14px">{{ $cliente->ordenes->count() }}</div>
        </div>
    </div>
</div>

<div style="font-size:14px;font-weight:600;margin-bottom:10px">Historial de órdenes</div>

<table>
    <thead>
        <tr>
            <th>Número OS</th>
            <th>Equipo</th>
            <th>Problema</th>
            <th>Estado</th>
            <th>Sucursal</th>
            <th>Monto</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @forelse($cliente->ordenes as $orden)
        <tr>
            <td><a href="{{ route('ordenes.show', $orden) }}" style="color:#2563eb">{{ $orden->numero_os }}</a></td>
            <td>{{ $orden->marca }} {{ $orden->modelo }}</td>
            <td>{{ Str::limit($orden->problema_reportado, 40) }}</td>
            <td><span class="badge badge-recibido">{{ $orden->estado }}</span></td>
            <td>{{ $orden->sucursal->nombre }}</td>
            <td>${{ number_format($orden->presupuesto_total, 2) }}</td>
            <td>{{ $orden->created_at->format('d/m/Y') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;color:#888;padding:2rem">Sin órdenes registradas</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div style="display:flex;gap:8px;margin-top:1rem">
    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-primary">Editar cliente</a>
    <a href="{{ route('ordenes.create') }}" class="btn btn-success">+ Nueva OS para este cliente</a>
</div>
@endsection