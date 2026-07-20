@extends('layout')

@section('content')
<div class="page-header">
    <h1>Editar Categoría</h1>
    <a href="{{ route('categorias.index') }}" class="btn">← Volver</a>
</div>

<div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.75rem;max-width:640px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <form method="POST" action="{{ route('categorias.update', $categoria) }}">
        @csrf @method('PUT')

        <div class="form-group">
            <label>Nombre *</label>
            <input type="text" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" required>
            @error('nombre')<span style="color:#dc2626;font-size:12px;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Descripción</label>
            <input type="text" name="descripcion" value="{{ old('descripcion', $categoria->descripcion) }}">
        </div>

        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #e2e8f0;">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('categorias.index') }}" class="btn">Cancelar</a>
        </div>
    </form>
</div>
@endsection