@extends('layout')
@section('content')

{{-- Encabezado de la página con el botón para crear una categoría nueva --}}
<div class="page-header">
    <h1>Categorías</h1>
    <a href="{{ route('categorias.create') }}" class="btn btn-primary">+ Nueva categoría</a>
</div>

{{-- BOTONES DE FILTRO: uno por cada categoría, con el conteo de piezas de inventario --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1.5rem;">
    <a href="{{ route('categorias.index') }}" 
       class="btn {{ !$filtro ? 'btn-primary' : '' }}">
        Todas
    </a>
    @foreach($categorias as $cat)
    <a href="{{ route('categorias.index', ['filtro' => $cat->nombre]) }}" 
       class="btn {{ $filtro === $cat->nombre ? 'btn-primary' : '' }}"
       style="display:flex;align-items:center;gap:6px;">
        {{ $cat->nombre }}
        {{-- productos_count: cuántas piezas de Inventario tienen esta categoría --}}
        <span style="background:rgba(0,0,0,0.1);padding:1px 7px;border-radius:20px;font-size:11px;font-weight:700;">
            {{ $cat->productos_count }}
        </span>
    </a>
    @endforeach
</div>

{{-- TABLA PRINCIPAL: se muestra solo cuando NO hay filtro activo (vista "Todas") --}}
@if(!$filtro)
<table>
    <thead>
        <tr>
            <th>Nombre</th>
            {{-- Pieza(s): muestra los productos de Inventario conectados con esta categoría. --}}
            <th>Pieza(s)</th>
            <th>Dispositivo compatible</th>
            <th>Calidad</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($categorias as $categoria)
        <tr>
            <td><strong>{{ $categoria->nombre }}</strong></td>

            {{-- Recorre TODAS las piezas de Inventario de esta categoría.
                 Si hay más de una, se listan una debajo de otra en la misma celda.
                 Si no hay ninguna, se muestra un guion. --}}
            <td>
                @forelse($categoria->productos as $producto)
                    <div>{{ $producto->nombre }}</div>
                @empty
                    —
                @endforelse
            </td>
            <td>
                @forelse($categoria->productos as $producto)
                    <div>{{ $producto->dispositivo_compatible ?? '—' }}</div>
                @empty
                    —
                @endforelse
            </td>
            <td>
                @forelse($categoria->productos as $producto)
                    <div>{{ $producto->calidad ?? '—' }}</div>
                @empty
                    —
                @endforelse
            </td>

            {{-- Botones para editar o eliminar la categoría --}}
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-sm">Editar</a>
                    <form method="POST" action="{{ route('categorias.destroy', $categoria) }}"
                        onsubmit="return confirmarEliminacionSistema(event, 'la categoría', '{{ addslashes($categoria->nombre) }}');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            {{-- colspan="5" cubre las columnas visibles: Nombre, Pieza(s), Dispositivo, Calidad y Acciones. --}}
            <td colspan="5" style="text-align:center;color:#888;padding:2rem">No hay categorías registradas.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- VISTA FILTRADA: se muestra cuando el usuario da clic en una categoría específica --}}
@else
<div style="margin-bottom:1rem;">
    <h2 style="font-size:16px;font-weight:600;color:#0f1f3d;">
        Mostrando: {{ $filtro }}
        <span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;margin-left:8px;">
            {{ $ordenes->count() }} órdenes
        </span>
    </h2>
</div>

{{-- Tabla de Órdenes de Servicio que pertenecen a esta categoría --}}
<table>
    <thead>
        <tr>
            <th>No. OS</th>
            <th>Cliente</th>
            <th>Marca / Modelo</th>
            <th>Estado</th>
            <th>Sucursal</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ordenes as $orden)
        <tr>
            <td><strong>{{ $orden->numero_os }}</strong></td>
            <td>{{ $orden->cliente->nombre ?? '—' }}</td>
            <td>{{ $orden->marca }} {{ $orden->modelo }}</td>
            <td><span class="badge badge-{{ strtolower(str_replace(' ', '-', $orden->estado)) }}">{{ $orden->estado }}</span></td>
            <td>{{ $orden->sucursal->nombre ?? '—' }}</td>
            <td>{{ $orden->created_at->format('d/m/Y') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center;color:#888;padding:2rem">No hay órdenes en esta categoría.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Tabla de piezas de Inventario que pertenecen a esta categoría --}}
<div style="margin:1.5rem 0 1rem;">
    <h2 style="font-size:16px;font-weight:600;color:#0f1f3d;">
        Piezas en inventario
        <span style="background:#dcfce7;color:#166534;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;margin-left:8px;">
            {{ $productos->count() }} piezas
        </span>
    </h2>
</div>
<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Dispositivo compatible</th>
            <th>Calidad</th>
            <th>Stock</th>
            <th>Sucursal</th>
        </tr>
    </thead>
    <tbody>
        @forelse($productos as $producto)
        <tr>
            <td><strong>{{ $producto->nombre }}</strong></td>
            <td>{{ $producto->dispositivo_compatible ?? '—' }}</td>
            <td>{{ $producto->calidad ?? '—' }}</td>
            <td>{{ $producto->cantidad_disponible }}</td>
            <td>{{ $producto->sucursal->nombre ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align:center;color:#888;padding:2rem">No hay piezas registradas en esta categoría.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endif
@endsection
