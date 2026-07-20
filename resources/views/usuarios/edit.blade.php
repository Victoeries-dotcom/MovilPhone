@extends('layout')

@section('content')
<div class="page-header">
    <h1>Editar Usuario</h1>
    <a href="{{ route('usuarios.index') }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="name" required value="{{ $usuario->name }}"/>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="{{ $usuario->email }}"/>
            </div>
            <div class="form-group">
                <label>Número telefónico</label>
                <input type="text" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" placeholder="Ej. 9991234567"/>
            </div>
            <div class="form-group">
                <label>Rol *</label>
                <select name="rol" required>
                    <option value="usuario" {{ $usuario->rol == 'usuario' ? 'selected' : '' }}>Usuario</option>
                    <option value="superusuario" {{ $usuario->rol == 'superusuario' ? 'selected' : '' }}>Super Usuario</option>
                    <option value="capturista" {{ $usuario->rol == 'capturista' ? 'selected' : '' }}>Capturista</option>
                    <option value="vendedor" {{ $usuario->rol == 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                    <option value="tecnico" {{ $usuario->rol == 'tecnico' ? 'selected' : '' }}>Tecnico</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sucursal *</label>
                {{-- Se conecta con users.sucursal_id para cambiar la sucursal asignada al usuario. --}}
                <select name="sucursal_id" required>
                    <option value="">Selecciona una sucursal</option>
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" {{ old('sucursal_id', $usuario->sucursal_id) == $sucursal->id ? 'selected' : '' }}>
                            {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
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
            <a href="{{ route('usuarios.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection
