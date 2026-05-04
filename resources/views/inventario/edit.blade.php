@extends('layout')

@section('content')
<div class="page-header">
    <h1>Editar Pieza</h1>
    <a href="{{ route('inventario.index') }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('inventario.update', $inventario) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Nombre de la pieza *</label>
                <input type="text" name="nombre" required value="{{ $inventario->nombre }}"/>
            </div>
            <div class="form-group">
                <label>Categoría *</label>
                <select name="categoria" required>
                    @foreach(['Pantalla','Batería','Flex','Puerto de carga','Placa','Accesorio','Otro'] as $cat)
                        <option {{ $inventario->categoria == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Sucursal *</label>
                <select name="sucursal_id" required>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}" {{ $inventario->sucursal_id == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Cantidad disponible *</label>
                <input type="number" name="cantidad_disponible" required value="{{ $inventario->cantidad_disponible }}" min="0"/>
            </div>
            <div class="form-group">
                <label>Stock mínimo *</label>
                <input type="number" name="stock_minimo" required value="{{ $inventario->stock_minimo }}" min="0"/>
            </div>
            <div class="form-group">
                <label>Precio de costo ($) *</label>
                <input type="number" name="precio_costo" required value="{{ $inventario->precio_costo }}" min="0" step="0.01"/>
            </div>
            <div class="form-group">
                <label>Precio de venta ($) *</label>
                <input type="number" name="precio_venta" required value="{{ $inventario->precio_venta }}" min="0" step="0.01"/>
            </div>
            <div class="form-group">
                <label>Calidad</label>
                <select name="calidad">
                    @foreach(['Original','Compatible','Remanufacturada'] as $cal)
                        <option {{ $inventario->calidad == $cal ? 'selected' : '' }}>{{ $cal }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Proveedor</label>
                <input type="text" name="proveedor" value="{{ $inventario->proveedor }}"/>
            </div>
            <div class="form-group full-width">
                <label>Dispositivo compatible</label>
                <input type="text" name="dispositivo_compatible" value="{{ $inventario->dispositivo_compatible }}"/>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem">
            <a href="{{ route('inventario.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection