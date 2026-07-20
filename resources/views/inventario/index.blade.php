@extends('layout')

@section('content')
<style>
    /* Colores de piezas vendidas: se conectan con .inventario-agotado y con los estilos inline de cada celda vendida. */
    :root {
        --inventario-vendido-fondo: #525e63; /* Color principal de toda la fila cuando la pieza ya no tiene stock. */
        --inventario-vendido-hover: #185a72; /* Color de la fila vendida cuando pasas el mouse encima. */
        --inventario-vendido-texto: #ffffff; /* Color del texto dentro de una fila vendida. */
        --inventario-vendido-secundario: #d9f6ff; /* Color del texto pequeño, como dispositivo compatible, dentro de una fila vendida. */
        --inventario-vendido-linea: #7dd3fc; /* Línea izquierda que marca visualmente la pieza vendida. */
        --inventario-vendido-etiqueta: #0ea5e9; /* Fondo de la etiqueta VENDIDO dentro de la columna Cantidad. */
    }

    /* Identifica piezas agotadas para que toda la fila se vea como vendida. */
    .inventario-agotado td {
        background: var(--inventario-vendido-fondo) !important;
        color: var(--inventario-vendido-texto) !important;
    }

    /* Mantiene visible la fila vendida cuando el usuario pasa el mouse encima. */
    .inventario-agotado:hover td {
        background: var(--inventario-vendido-hover) !important;
    }

    /* Marca el inicio de la fila vendida para distinguirla de las piezas disponibles. */
    .inventario-agotado td:first-child {
        border-left: 7px solid var(--inventario-vendido-linea);
    }

    /* Fuerza texto blanco en nombre, cantidad y detalles de la pieza vendida. */
    .inventario-agotado td span,
    .inventario-agotado td strong {
        color: var(--inventario-vendido-texto) !important;
    }
</style>

<div class="page-header">
    <h1>Inventario de Refacciones</h1>
    <a href="{{ route('inventario.create') }}" class="btn btn-primary">+ Agregar pieza</a>
</div>

{{-- Aviso de seguridad: evita mezclar inventarios cuando todavía no se ha elegido una sucursal. --}}
@if(!$sucursalActiva)
    <div class="alert alert-error" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
        <span>Selecciona una sucursal para consultar su inventario.</span>
        <a href="{{ route('sucursales.index') }}" class="btn">Ir a Sucursales</a>
    </div>
@endif

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
    <input type="text" name="buscar" value="{{ request('buscar') }}"
        placeholder="🔍 Buscar por nombre o dispositivo…"
        style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;
        font-family:inherit;outline:none;min-width:240px;"
        oninput="this.form.submit()"/>
    {{-- Indicador de sucursal: muestra el contexto guardado por Sucursales y evita cambiarlo solo en esta tabla. --}}
    @if($sucursalActiva)
        <div style="display:flex;align-items:center;min-height:38px;padding:8px 12px;border:1px solid #bfdbfe;border-radius:6px;background:#eff6ff;color:#1e3a8a;font-size:13.5px;font-weight:700;">
            🏪 Sucursal: {{ $sucursalActiva->nombre }}
        </div>
    @endif
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
        {{-- Resalta en azul las piezas agotadas para identificar que ya fueron vendidas. --}}
        @php
            $piezaAgotada = $pieza->cantidad_disponible <= 0;
            // Aplica los colores de venta agotada definidos arriba; se conecta con la fila y cada celda.
            $estiloAgotado = $piezaAgotada ? 'background:var(--inventario-vendido-fondo) !important;color:var(--inventario-vendido-texto) !important;' : '';
        @endphp
        <tr class="{{ $piezaAgotada ? 'inventario-agotado' : '' }}">
            <td style="{{ $estiloAgotado }}{{ $piezaAgotada ? 'border-left:7px solid var(--inventario-vendido-linea);' : '' }}"><strong>{{ $pieza->nombre }}</strong><br><span style="font-size:11px;color:{{ $piezaAgotada ? 'var(--inventario-vendido-secundario)' : '#888' }}">{{ $pieza->dispositivo_compatible }}</span></td>
            <td style="{{ $estiloAgotado }}">{{ $pieza->categoria }}</td>
            <td style="{{ $estiloAgotado }}">{{ $pieza->sucursal->nombre }}</td>
            <td style="{{ $estiloAgotado }}">
                <span style="font-weight:800;color:{{ $piezaAgotada ? '#ffffff' : ($pieza->cantidad_disponible <= $pieza->stock_minimo ? '#dc2626' : '#16a34a') }}">
                    {{ $pieza->cantidad_disponible }}
                </span>
                @if($piezaAgotada)
                    <span style="display:inline-block;margin-left:8px;padding:3px 9px;border-radius:999px;background:var(--inventario-vendido-etiqueta);color:white;font-size:11px;font-weight:800;letter-spacing:.03em;">
                        VENDIDO
                    </span>
                @endif
            </td>
            <td style="{{ $estiloAgotado }}">{{ $pieza->stock_minimo }}</td>
            <td style="{{ $estiloAgotado }}">${{ number_format($pieza->precio_venta, 2) }}</td>
            <td style="{{ $estiloAgotado }}">
                <div style="display:flex;gap:6px">
                    <a href="{{ route('inventario.edit', $pieza) }}" class="btn">Editar</a>
                    <form method="POST" action="{{ route('inventario.destroy', $pieza) }}"
                        onsubmit="return confirmarEliminacionSistema(event, 'la pieza', '{{ addslashes($pieza->nombre) }}');">
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
