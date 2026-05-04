@extends('layout')

@section('content')
<div class="page-header">
    <h1>Registrar Movimiento</h1>
    <a href="{{ route('caja.index') }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('caja.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>Tipo *</label>
                <select name="tipo" required>
                    <option value="">— Selecciona —</option>
                    <option {{ old('tipo') == 'INGRESO' ? 'selected' : '' }}>INGRESO</option>
                    <option {{ old('tipo') == 'EGRESO' ? 'selected' : '' }}>EGRESO</option>
                </select>
            </div>
            <div class="form-group">
                <label>Categoría *</label>
                <select name="categoria" required>
                    @foreach(['Reparación','Diagnóstico','Venta accesorio','Compra_pieza','Compra deshueso','Gasto operativo','Sueldo','Otro'] as $cat)
                        <option {{ old('categoria') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Monto ($) *</label>
                <input type="number" name="monto" required value="{{ old('monto') }}" min="0" step="0.01" placeholder="0.00"/>
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
            <div class="form-group full-width">
                <label>Descripción</label>
                <input type="text" name="descripcion" value="{{ old('descripcion') }}" placeholder="Descripción del movimiento…"/>
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
            <a href="{{ route('caja.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar movimiento</button>
        </div>
    </form>
</div>
@endsection