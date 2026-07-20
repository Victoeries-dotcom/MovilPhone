@extends('layout')

@section('content')
<div class="page-header">
    <h1>Sucursales</h1>
    <a href="{{ route('sucursales.create') }}" class="btn btn-primary">+ Nueva sucursal</a>
</div>

@if($sucursalSeleccionada)
{{-- Resumen activo: usa clases globales conectadas con el selector de tema claro u oscuro. --}}
<section class="branch-focus-card" aria-labelledby="branchFocusTitle">
    <div class="branch-focus-header">
        <div>
            <div class="branch-focus-label">Sucursal seleccionada</div>
            <h2 class="branch-focus-title" id="branchFocusTitle">{{ $sucursalSeleccionada->nombre }}</h2>
        </div>
        <a href="{{ route('sucursales.index') }}" class="btn">Ver todas</a>
    </div>

    {{-- Datos operativos: se conectan con el registro de la sucursal seleccionada. --}}
    <div class="branch-focus-grid">
        <div class="branch-focus-item">
            <span>Encargado</span>
            <strong>{{ $sucursalSeleccionada->nombre_encargado ?? '-' }}</strong>
        </div>
        <div class="branch-focus-item">
            <span>Ubicación</span>
            <strong>{{ $sucursalSeleccionada->ubicacion ?? '-' }}</strong>
        </div>
        <div class="branch-focus-item">
            <span>Teléfono</span>
            <strong>{{ $sucursalSeleccionada->telefono_encargado ?? '-' }}</strong>
        </div>
        <div class="branch-focus-item">
            <span>Horario</span>
            <strong>{{ $sucursalSeleccionada->horario ?? '-' }}</strong>
        </div>
    </div>
</section>
@endif

<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Encargado</th>
            <th>Ubicación</th>
            <th>Mapa</th>
            <th>Teléfono</th>
            <th>Horario</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sucursales as $sucursal)
        {{-- La fila abre su resumen y conserva acciones independientes mediante stopPropagation(). --}}
        <tr
            onclick="window.location='{{ route('sucursales.index', ['sucursal_id' => $sucursal->id]) }}'"
            class="branch-table-row {{ $sucursalSeleccionada && $sucursalSeleccionada->id === $sucursal->id ? 'is-selected' : '' }}"
        >
            <td>
                <a href="{{ route('sucursales.index', ['sucursal_id' => $sucursal->id]) }}" class="branch-table-link">
                    {{ $sucursal->nombre }}
                </a>
            </td>
            <td>{{ $sucursal->nombre_encargado ?? '-' }}</td>
            <td>{{ $sucursal->ubicacion ?? '-' }}</td>
            <td>
                @if($sucursal->ubicacion_url)
                <a href="{{ $sucursal->ubicacion_url }}"
                   target="_blank"
                   class="btn btn-sm branch-map-button"
                   onclick="event.stopPropagation();">
                   📍 Maps
                </a>
                @else
                <span class="branch-empty-value">-</span>
                @endif
            </td>
            <td>{{ $sucursal->telefono_encargado ?? '-' }}</td>
            <td>{{ $sucursal->horario ?? '-' }}</td>
            <td>
                <div class="branch-actions">
                    <a href="{{ route('sucursales.index', ['sucursal_id' => $sucursal->id]) }}" class="btn btn-sm" onclick="event.stopPropagation();">Ver datos</a>

                    @if(!$sucursalSeleccionada || $sucursalSeleccionada->id !== $sucursal->id)
                    <form method="POST" action="{{ route('sucursales.destroy', $sucursal) }}"
                        onsubmit="return confirmarEliminacionSistema(event, 'la sucursal', '{{ addslashes($sucursal->nombre) }}', 'se eliminaran todos sus datos en el sistema');"
                        onclick="event.stopPropagation();">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="event.stopPropagation();">
                            Eliminar
                        </button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="branch-empty-table">No hay sucursales</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
