@extends('layout')

@section('content')
<div class="page-header">
    <h1>Agregar Pieza</h1>
    <a href="{{ route('inventario.index') }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('inventario.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Nombre de la pieza *</label>
                <input type="text" name="nombre" required value="{{ old('nombre') }}" placeholder="Ej: Pantalla iPhone 13 OLED"/>
            </div>
            <div class="form-group">
                <label>Categoría *</label>
                <select name="categoria" required>
                    @foreach(['Pantalla','Batería','Flex','Puerto de carga','Placa','Accesorio','Otro'] as $cat)
                        <option {{ old('categoria') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Sucursal *</label>
                <select name="sucursal_id" required>
                    <option value="">— Selecciona —</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}" {{ old('sucursal_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Cantidad disponible *</label>
                <input type="number" name="cantidad_disponible" required value="{{ old('cantidad_disponible', 0) }}" min="0"/>
            </div>
            <div class="form-group">
                <label>Stock mínimo *</label>
                <input type="number" name="stock_minimo" required value="{{ old('stock_minimo', 2) }}" min="0"/>
            </div>
            <div class="form-group">
                <label>Precio de costo ($) *</label>
                <input type="number" name="precio_costo" required value="{{ old('precio_costo') }}" min="0" step="0.01"/>
            </div>
            <div class="form-group">
                <label>Precio de venta ($) *</label>
                <input type="number" name="precio_venta" required value="{{ old('precio_venta') }}" min="0" step="0.01"/>
            </div>
            <div class="form-group">
                <label>Calidad</label>
                <select name="calidad">
                    @foreach(['Original','Compatible','Remanufacturada'] as $cal)
                        <option {{ old('calidad') == $cal ? 'selected' : '' }}>{{ $cal }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Proveedor</label>
                <input type="text" name="proveedor" value="{{ old('proveedor') }}" placeholder="Nombre del proveedor"/>
            </div>
            <div class="form-group full-width">
                <label>Dispositivo compatible</label>
                <input type="text" name="dispositivo_compatible" value="{{ old('dispositivo_compatible') }}" placeholder="Ej: iPhone 13, Galaxy A54"/>
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
            <button type="submit" class="btn btn-primary">Guardar pieza</button>
        </div>
    </form>
</div>
@endsection