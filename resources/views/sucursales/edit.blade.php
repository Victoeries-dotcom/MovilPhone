@extends('layout')

@section('content')
{{-- Encabezado del editor conectado con el registro de sucursal seleccionado. --}}
<div class="page-header">
    <div>
        <h1>Editar Sucursal</h1>
        <p>Actualiza los datos operativos de {{ $sucursal->nombre }}.</p>
    </div>
    <a href="{{ route('sucursales.index') }}" class="btn">
        <i data-lucide="arrow-left" aria-hidden="true"></i>
        <span>Volver</span>
    </a>
</div>

{{-- Muestra los errores enviados por la validación de SucursalController@update. --}}
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Revisa la información:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    {{-- El formulario PUT actualiza la misma fila mediante la ruta sucursales.update. --}}
    <form method="POST" action="{{ route('sucursales.update', $sucursal) }}">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-group">
                <label for="nombre">Nombre de la sucursal *</label>
                <input id="nombre" name="nombre" type="text"
                       value="{{ old('nombre', $sucursal->nombre) }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="ubicacion">Ubicación (dirección)</label>
                <input id="ubicacion" name="ubicacion" type="text"
                       value="{{ old('ubicacion', $sucursal->ubicacion) }}">
            </div>

            <div class="form-group">
                <label for="ubicacion_url">Ubicación URL (Google Maps)</label>
                {{-- La URL no se convierte a mayúsculas porque debe conservar el enlace exacto. --}}
                <input id="ubicacion_url" name="ubicacion_url" type="url"
                       data-no-mayusculas
                       value="{{ old('ubicacion_url', $sucursal->ubicacion_url) }}">
            </div>

            <div class="form-group">
                <label for="nombre_encargado">Nombre del encargado</label>
                <input id="nombre_encargado" name="nombre_encargado" type="text"
                       value="{{ old('nombre_encargado', $sucursal->nombre_encargado) }}">
            </div>

            <div class="form-group">
                <label for="telefono_encargado">Teléfono del encargado</label>
                <input id="telefono_encargado" name="telefono_encargado" type="text"
                       value="{{ old('telefono_encargado', $sucursal->telefono_encargado) }}">
            </div>

            <div class="form-group">
                <label for="horario">Horario</label>
                <input id="horario" name="horario" type="text"
                       value="{{ old('horario', $sucursal->horario) }}">
            </div>
        </div>

        {{-- Acciones finales: cancelar regresa al listado y guardar persiste los cambios. --}}
        <div class="form-actions">
            <a href="{{ route('sucursales.index') }}" class="btn">
                <i data-lucide="x" aria-hidden="true"></i>
                <span>Cancelar</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" aria-hidden="true"></i>
                <span>Guardar cambios</span>
            </button>
        </div>
    </form>
</div>
@endsection
