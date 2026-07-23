@extends('layout')

@section('content')
{{-- Cabecera del directorio: conecta el acceso Nuevo cliente con ClienteController::create. --}}
<section class="client-directory-hero">
    <div>
        <span>Directorio del taller</span>
        <h1>Clientes</h1>
        <p>Consulta contactos, servicios e historial de reparaciones de la sucursal activa.</p>
    </div>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">
        <i data-lucide="user-plus" aria-hidden="true"></i>
        Nuevo cliente
    </a>
</section>

{{-- Indicadores: usan los conteos calculados por ClienteController::index para resumir esta sucursal. --}}
<section class="client-directory-stats" aria-label="Resumen de clientes">
    <article>
        <span class="client-stat-icon is-neutral"><i data-lucide="users"></i></span>
        <p>Clientes registrados<strong>{{ $clientes->count() }}</strong></p>
    </article>
    <article>
        <span class="client-stat-icon is-success"><i data-lucide="wrench"></i></span>
        <p>Servicios activos<strong>{{ $clientes->sum('servicios_activos_count') }}</strong></p>
    </article>
    <article>
        <span class="client-stat-icon is-warning"><i data-lucide="refresh-cw"></i></span>
        <p>Clientes recurrentes<strong>{{ $clientes->filter(fn ($cliente) => $cliente->ordenes_count > 1)->count() }}</strong></p>
    </article>
</section>

{{-- Buscador: envía search al listado del controlador sin perder la sucursal autenticada. --}}
<form method="GET" class="client-directory-search">
    <label>
        <span><i data-lucide="search"></i> Buscar cliente</span>
        <input
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Nombre o número telefónico..."
            autocomplete="off"
        >
    </label>
    <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> Buscar</button>
    @if(request('search'))
        <a href="{{ route('clientes.index') }}" class="btn"><i data-lucide="x"></i> Limpiar</a>
    @endif
</form>

{{-- Tabla operativa: conecta historial, edición y eliminación con las rutas resource de Clientes. --}}
<div class="client-directory-table">
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Servicios</th>
                <th>Valor registrado</th>
                <th>Historial</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clientes as $cliente)
                <tr>
                    <td>
                        <div class="client-identity">
                            <span>{{ mb_strtoupper(mb_substr($cliente->nombre, 0, 1)) }}</span>
                            <div>
                                <strong>{{ $cliente->nombre }}</strong>
                                <small>Cliente desde {{ $cliente->created_at->format('d/m/Y') }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="client-contact">
                            <strong><i data-lucide="phone"></i> {{ $cliente->telefono_principal }}</strong>
                            @if($cliente->telefono_alternativo)
                                <small>Alternativo: {{ $cliente->telefono_alternativo }}</small>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="client-service-chip"><i data-lucide="wrench"></i> {{ $cliente->ordenes_count }} total</span>
                        <small class="client-secondary">{{ $cliente->servicios_anteriores_count }} finalizados</small>
                    </td>
                    <td><strong>${{ number_format($cliente->ordenes_sum_presupuesto_total ?? 0, 2) }}</strong></td>
                    <td>
                        <a href="{{ route('clientes.show', $cliente) }}" class="btn client-history-button">
                            <i data-lucide="clipboard-list"></i> Ver historial
                        </a>
                    </td>
                    <td>
                        <div class="client-actions">
                            <a href="{{ route('clientes.edit', $cliente) }}" class="btn" title="Editar cliente">
                                <i data-lucide="pencil"></i><span>Editar</span>
                            </a>
                            <form
                                method="POST"
                                action="{{ route('clientes.destroy', $cliente) }}"
                                onsubmit="return confirmarEliminacionSistema(event, 'el cliente', '{{ addslashes($cliente->nombre) }}');"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" title="Eliminar cliente">
                                    <i data-lucide="trash-2"></i><span>Eliminar</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        {{-- Estado vacío: orienta al usuario cuando el filtro o la sucursal no tienen clientes. --}}
                        <div class="client-directory-empty">
                            <i data-lucide="contact-round"></i>
                            <strong>No encontramos clientes</strong>
                            <p>Registra un cliente nuevo o cambia el término de búsqueda.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
