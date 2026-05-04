@extends('layout')

@section('content')
<div class="page-header">
    <h1>Editar Cliente</h1>
    <a href="{{ route('clientes.show', $cliente) }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('clientes.update', $cliente) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nombre" required value="{{ $cliente->nombre }}"/>
            </div>
            <div class="form-group">
                <label>Teléfono principal *</label>
                <input type="text" name="telefono_principal" required value="{{ $cliente->telefono_principal }}"/>
            </div>
            <div class="form-group">
                <label>Teléfono alternativo</label>
                <input type="text" name="telefono_alternativo" value="{{ $cliente->telefono_alternativo }}"/>
            </div>
            <div class="form-group">
                <label>Sucursal habitual</label>
                <select name="sucursal_habitual_id">
                    <option value="">— Sin definir —</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}" {{ $cliente->sucursal_habitual_id == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group full-width">
                <label>Dirección</label>
                <input type="text" name="direccion" value="{{ $cliente->direccion }}"/>
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
            <a href="{{ route('clientes.show', $cliente) }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection