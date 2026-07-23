@extends('layout')

@section('content')
{{-- Cabecera operativa: presenta el módulo y conecta el acceso rápido con el alta de productos. --}}
<section class="inventory-hero" aria-labelledby="inventory-title">
    <div class="inventory-hero-copy">
        <span class="inventory-eyebrow">Control de productos</span>
        <h1 id="inventory-title">Inventario</h1>
        <p>
            Consulta existencias y administra los productos disponibles
            @if($sucursalActiva)
                en {{ $sucursalActiva->nombre }}.
            @else
                en el taller.
            @endif
        </p>
    </div>

    <a href="{{ route('inventario.create') }}" class="inventory-add-button">
        <i data-lucide="circle-plus" aria-hidden="true"></i>
        <span>Agregar producto</span>
    </a>
</section>

{{-- Aviso de contexto: evita mezclar datos cuando todavía no existe una sucursal activa en sesión. --}}
@if(!$sucursalActiva)
    <div class="alert alert-error inventory-branch-alert" role="alert">
        <span>Selecciona una sucursal para consultar su inventario.</span>
        <a href="{{ route('sucursales.index') }}" class="btn">Ir a Sucursales</a>
    </div>
@endif

{{-- Confirmación del servidor: comunica altas, ediciones o eliminaciones completadas por InventarioController. --}}
@if(session('success'))
    <div class="alert alert-success inventory-success-alert">
        <i data-lucide="circle-check" aria-hidden="true"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- Indicadores: resumen los datos de inventario calculados para la sucursal activa. --}}
<section class="inventory-summary-grid" aria-label="Resumen del inventario">
    <article class="inventory-summary-card">
        <span class="inventory-summary-icon is-purple">
            <i data-lucide="package" aria-hidden="true"></i>
        </span>
        <div>
            <span class="inventory-summary-label">Productos registrados</span>
            <strong>{{ $stats['total'] }}</strong>
        </div>
    </article>

    <article class="inventory-summary-card">
        <span class="inventory-summary-icon is-green">
            <i data-lucide="check" aria-hidden="true"></i>
        </span>
        <div>
            <span class="inventory-summary-label">Unidades en existencia</span>
            <strong>{{ $stats['unidades'] }}</strong>
        </div>
    </article>

    <article class="inventory-summary-card">
        <span class="inventory-summary-icon is-red">
            <i data-lucide="circle-minus" aria-hidden="true"></i>
        </span>
        <div>
            <span class="inventory-summary-label">Productos con bajo stock</span>
            <strong>{{ $stats['bajo'] }}</strong>
        </div>
    </article>

    <article class="inventory-summary-card">
        <span class="inventory-summary-icon is-gold">
            <i data-lucide="wallet-cards" aria-hidden="true"></i>
        </span>
        <div>
            <span class="inventory-summary-label">Valor del inventario</span>
            <strong>${{ number_format($stats['valor'], 2) }}</strong>
        </div>
    </article>
</section>

{{-- Categorías: filtra la consulta del controlador sin perder la búsqueda ni el filtro de stock actuales. --}}
<section class="inventory-categories" aria-labelledby="inventory-categories-title">
    <div class="inventory-section-heading">
        <span class="inventory-section-icon">
            <i data-lucide="tag" aria-hidden="true"></i>
        </span>
        <div>
            <h2 id="inventory-categories-title">Categorías</h2>
            <p>Selecciona una para ver sus productos</p>
        </div>
    </div>

    <nav class="inventory-category-list" aria-label="Filtrar por categoría">
        <a
            href="{{ route('inventario.index', request()->except('categoria')) }}"
            class="inventory-category-chip {{ request('categoria') ? '' : 'is-active' }}"
            @if(!request('categoria')) aria-current="page" @endif
        >
            Todos
            <span>{{ $stats['total'] }}</span>
        </a>

        @foreach($categorias as $categoria)
            <a
                href="{{ route('inventario.index', array_merge(request()->except('categoria'), ['categoria' => $categoria])) }}"
                class="inventory-category-chip {{ request('categoria') === $categoria ? 'is-active' : '' }}"
                @if(request('categoria') === $categoria) aria-current="page" @endif
            >
                {{ $categoria }}
            </a>
        @endforeach
    </nav>
</section>

{{-- Buscador: envía los criterios a InventarioController y mantiene la categoría seleccionada. --}}
<form method="GET" action="{{ route('inventario.index') }}" class="inventory-filter-panel">
    @if(request('categoria'))
        <input type="hidden" name="categoria" value="{{ request('categoria') }}">
    @endif

    <label class="inventory-search-field">
        <span>
            <i data-lucide="search" aria-hidden="true"></i>
            Buscar producto
        </span>
        <input
            type="search"
            name="buscar"
            value="{{ request('buscar') }}"
            placeholder="Nombre, categoría o proveedor..."
            autocomplete="off"
        >
    </label>

    <label class="inventory-low-stock-toggle">
        <input type="checkbox" name="bajo_stock" value="1" {{ request('bajo_stock') ? 'checked' : '' }}>
        <span>Solo bajo stock</span>
    </label>

    <button type="submit" class="inventory-search-button">
        <i data-lucide="search" aria-hidden="true"></i>
        <span>Buscar</span>
    </button>

    @if(request()->hasAny(['buscar', 'bajo_stock']))
        {{-- Restablece búsqueda y stock sin perder la categoría activa, igual que el módulo del archivo externo. --}}
        <a href="{{ route('inventario.index', request()->only('categoria')) }}" class="btn inventory-clear-button">
            <i data-lucide="rotate-ccw" aria-hidden="true"></i>
            <span>Limpiar</span>
        </a>
    @endif
</form>

{{-- Tabla principal: muestra los productos filtrados y conecta cada acción con las rutas de Inventario. --}}
<section class="ui-table-panel inventory-table-panel" aria-label="Productos del inventario">
    <div class="ui-table-shell inventory-table-shell">
        <table class="inventory-table" data-workspace-ready="true">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Existencia</th>
                    <th>Stock mínimo</th>
                    <th>Precio de venta</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventario as $pieza)
                    @php
                        // Clasifica la pieza para mantener disponibles arriba y vendidos claramente identificados abajo.
                        $piezaVendida = $pieza->cantidad_disponible <= 0;
                        $piezaBajoStock = !$piezaVendida && $pieza->cantidad_disponible <= $pieza->stock_minimo;
                    @endphp
                    <tr class="inventory-product-row {{ $piezaVendida ? 'is-sold' : '' }}">
                        <td>
                            <div class="inventory-product">
                                <span class="inventory-product-icon">
                                    <i data-lucide="package" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <strong>{{ $pieza->nombre }}</strong>
                                    <small>{{ $pieza->proveedor ?: 'Proveedor no registrado' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="inventory-product-category">{{ $pieza->categoria }}</span>
                            <small class="inventory-compatible">{{ $pieza->dispositivo_compatible ?: 'Sin dispositivo especificado' }}</small>
                        </td>
                        <td>
                            <strong class="inventory-quantity {{ $piezaVendida ? 'is-zero' : '' }}">
                                {{ max(0, $pieza->cantidad_disponible) }}
                            </strong>
                            <span>unidades</span>
                        </td>
                        <td>{{ $pieza->stock_minimo }} unidades</td>
                        <td><strong>${{ number_format($pieza->precio_venta, 2) }}</strong></td>
                        <td>
                            @if($piezaVendida)
                                <span class="inventory-status is-sold">Vendido</span>
                            @elseif($piezaBajoStock)
                                <span class="inventory-status is-low">Bajo stock</span>
                            @else
                                <span class="inventory-status is-available">Disponible</span>
                            @endif
                        </td>
                        <td>
                            <div class="inventory-actions">
                                <a href="{{ route('inventario.edit', $pieza) }}" class="btn inventory-edit-button">
                                    <i data-lucide="pencil" aria-hidden="true"></i>
                                    <span>Editar</span>
                                </a>
                                <form
                                    method="POST"
                                    action="{{ route('inventario.destroy', $pieza) }}"
                                    onsubmit="return confirmarEliminacionSistema(event, 'la pieza', '{{ addslashes($pieza->nombre) }}');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger inventory-delete-button">
                                        <i data-lucide="circle-x" aria-hidden="true"></i>
                                        <span>Eliminar</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="inventory-empty-state">
                                <i data-lucide="package-open" aria-hidden="true"></i>
                                <strong>No hay productos para mostrar</strong>
                                <span>Ajusta los filtros o agrega el primer producto de esta sucursal.</span>
                                <a href="{{ route('inventario.create') }}" class="btn btn-primary">Agregar producto</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
