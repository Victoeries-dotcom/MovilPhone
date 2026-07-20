@extends('layout')

@section('content')
<div class="page-header">
    <h1>Nueva Venta</h1>
    <a href="{{ route('ventas.index') }}" class="btn">← Volver</a>
</div>

<div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.75rem;max-width:900px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <form method="POST" action="{{ route('ventas.store') }}">
        @csrf

        <div class="form-grid">
            <div class="form-group">
                <label>Cliente</label>
                <select name="cliente_id">
                    <option value="">— Sin cliente —</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Sucursal *</label>
                <select name="sucursal_id" required>
                    <option value="">— Seleccionar —</option>
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" {{ old('sucursal_id') == $sucursal->id ? 'selected' : '' }}>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
                @error('sucursal_id')<span style="color:#dc2626;font-size:12px;">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-group">
            <label>Notas</label>
            <textarea name="notas" placeholder="Notas opcionales de la venta">{{ old('notas') }}</textarea>
        </div>

        <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #e2e8f0;">
            <h3 style="font-size:15px;font-weight:600;margin-bottom:1rem;color:#0f1f3d;">Productos</h3>

            <div id="productos-container">
                <div class="producto-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:end;">
                    <div class="form-group" style="margin:0">
                        <label>Producto *</label>
                        <select name="productos[0][id]" required onchange="actualizarPrecio(this)">
                            <option value="">— Seleccionar —</option>
                            @foreach($inventario as $item)
                                <option value="{{ $item->id }}" data-precio="{{ $item->precio_venta }}">{{ $item->nombre }} (Stock: {{ $item->cantidad_disponible }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Cantidad *</label>
                        <input type="number" name="productos[0][cantidad]" value="1" min="1" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Precio unitario</label>
                        <input type="text" class="precio-display" readonly style="background:#f8fafc;">
                    </div>
                    <div style="padding-bottom:2px;">
                        <button type="button" onclick="eliminarFila(this)" class="btn btn-danger btn-sm">✕</button>
                    </div>
                </div>
            </div>

            <button type="button" onclick="agregarProducto()" class="btn" style="margin-top:8px;">+ Agregar producto</button>
        </div>

        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #e2e8f0;">
            <button type="submit" class="btn btn-primary">Registrar venta</button>
            <a href="{{ route('ventas.index') }}" class="btn">Cancelar</a>
        </div>
    </form>
</div>

<script>
let contador = 1;

function actualizarPrecio(select) {
    const row = select.closest('.producto-row');
    const precio = select.options[select.selectedIndex].dataset.precio || '';
    row.querySelector('.precio-display').value = precio ? '$' + parseFloat(precio).toFixed(2) : '';
}

function agregarProducto() {
    const container = document.getElementById('productos-container');
    const div = document.createElement('div');
    div.className = 'producto-row';
    div.style = 'display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:end;';
    div.innerHTML = `
        <div class="form-group" style="margin:0">
            <select name="productos[${contador}][id]" required onchange="actualizarPrecio(this)">
                <option value="">— Seleccionar —</option>
                @foreach($inventario as $item)
                <option value="{{ $item->id }}" data-precio="{{ $item->precio_venta }}">{{ $item->nombre }} (Stock: {{ $item->cantidad_disponible }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <input type="number" name="productos[${contador}][cantidad]" value="1" min="1" required>
        </div>
        <div class="form-group" style="margin:0">
            <input type="text" class="precio-display" readonly style="background:#f8fafc;">
        </div>
        <div style="padding-bottom:2px;">
            <button type="button" onclick="eliminarFila(this)" class="btn btn-danger btn-sm">✕</button>
        </div>
    `;
    container.appendChild(div);
    contador++;
}

function eliminarFila(btn) {
    const rows = document.querySelectorAll('.producto-row');
    if (rows.length > 1) btn.closest('.producto-row').remove();
}
</script>
@endsection