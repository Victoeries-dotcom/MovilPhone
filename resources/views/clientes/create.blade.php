@extends('layout')

@section('content')
<div class="page-header">
    <h1>Nuevo Cliente</h1>
    <a href="{{ route('clientes.index') }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('clientes.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nombre" required value="{{ old('nombre') }}" placeholder="Nombre completo"/>
            </div>
            <div class="form-group">
                <label>Teléfono principal *</label>
                <input type="text" name="telefono_principal" required value="{{ old('telefono_principal') }}" placeholder="999-000-0000"/>
            </div>
            <div class="form-group">
                <label>Teléfono alternativo</label>
                <input type="text" name="telefono_alternativo" value="{{ old('telefono_alternativo') }}" placeholder="Opcional"/>
            </div>
            <div class="form-group">
                <label>Sucursal habitual</label>
                <select name="sucursal_habitual_id">
                    <option value="">— Sin definir —</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}" {{ old('sucursal_habitual_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group full-width">
                <label>Dirección</label>
                <input type="text" name="direccion" value="{{ old('direccion') }}" placeholder="Opcional"/>
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
            <a href="{{ route('clientes.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cliente</button>
        </div>
    </form>
</div>
@endsection