@extends('layout')
@section('title', 'Nueva Sucursal')

@section('content')

<div class="page-header">
    <div>
        <h1>Nueva Sucursal</h1>
    </div>
    <a href="{{ route('sucursales.index') }}" class="btn btn-secondary">← Volver</a>
</div>

<div style="background:white;border-radius:10px;border:1px solid #e2e8f0;padding:1.75rem;max-width:640px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <form method="POST" action="{{ route('sucursales.store') }}">
        @csrf

        <div class="form-group">
            <label>Nombre de la sucursal *</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Ej. Sucursal Centro">
            @error('nombre')<span style="color:#dc2626;font-size:12px;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label>Ubicación</label>
            <input type="text" name="ubicacion" value="{{ old('ubicacion') }}" placeholder="Ej. Calle 60 #123, Mérida, Yucatán">
        </div>

        <div class="form-group">
            <label>Teléfono del encargado</label>
            <input type="tel" name="telefono_encargado" value="{{ old('telefono_encargado') }}" placeholder="Ej. 999-123-4567">
        </div>

        <div class="form-group">
            <label>Horario</label>
            <input type="text" name="horario" value="{{ old('horario') }}" placeholder="Ej. Lun-Vie 9:00am - 7:00pm">
        </div>

        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #e2e8f0;">
            <button type="submit" class="btn btn-primary">Guardar sucursal</button>
            <a href="{{ route('sucursales.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

@endsection