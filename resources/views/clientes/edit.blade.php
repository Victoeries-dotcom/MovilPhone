@extends('layout')

@section('content')
<div class="page-header">
    <h1>Editar Cliente</h1>
    <a href="{{ route('clientes.show', $cliente) }}" class="btn">← Volver</a>
</div>

<div class="card">
    {{-- Formulario de edición: se conecta con ClienteController::update y modifica únicamente nombre y teléfonos. --}}
    <form method="POST" action="{{ route('clientes.update', $cliente) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre completo *</label>
                {{-- Conserva el texto escrito si la validación falla y lo presenta en mayúsculas. --}}
                <input type="text" name="nombre" required value="{{ old('nombre', $cliente->nombre) }}" style="text-transform:uppercase;"/>
            </div>
            <div class="form-group">
                <label>Teléfono principal *</label>
                <input type="text" name="telefono_principal" required value="{{ old('telefono_principal', $cliente->telefono_principal) }}"/>
            </div>
            <div class="form-group">
                <label>Teléfono alternativo</label>
                <input type="text" name="telefono_alternativo" value="{{ old('telefono_alternativo', $cliente->telefono_alternativo) }}"/>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Botones finales: Cancelar regresa al historial y Guardar cambios envía el formulario al controlador. --}}
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem">
            <a href="{{ route('clientes.show', $cliente) }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection
