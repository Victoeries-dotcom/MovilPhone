@extends('layout')

@section('content')
@include('components.registro-wizard')

<div class="page-header">
    <h1>Nueva Categoría</h1>
    <a href="{{ route('categorias.index') }}" class="btn">← Volver</a>
</div>

<div class="registro-wizard" data-registro-wizard data-initial-step="{{ $errors->has('descripcion') ? 2 : 1 }}">
    <div class="registro-wizard-progress">
        <span class="registro-wizard-progress-label" data-progress-label></span>
        <div class="registro-wizard-track"><div class="registro-wizard-fill" data-progress-fill></div></div>
    </div>

    @if($errors->any())
        {{-- Muestra la validación conectada con CategoriaController::store. --}}
        <div class="alert alert-error" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
    @endif

    <div class="registro-wizard-card">
        {{-- El formulario guarda nombre y descripción en la tabla categorias. --}}
        <form method="POST" action="{{ route('categorias.store') }}">
            @csrf

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Cuál es el nombre de la categoría?</div>
                <input class="registro-input" type="text" name="nombre" value="{{ old('nombre') }}"
                    placeholder="EJ. ELECTRÓNICA" autocomplete="off" required data-uppercase>
                <div class="registro-actions">
                    <button type="button" class="registro-next" data-next>Siguiente</button>
                </div>
            </section>

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Deseas agregar una descripción?</div>
                <textarea class="registro-input" name="descripcion" placeholder="DESCRIPCIÓN OPCIONAL"
                    maxlength="255" data-uppercase>{{ old('descripcion') }}</textarea>
                <div class="registro-help">Puede dejar este campo vacío.</div>
                <div class="registro-actions">
                    <button type="button" class="registro-prev" data-prev>Anterior</button>
                    <button type="submit" class="registro-save">Guardar categoría</button>
                </div>
            </section>
        </form>
    </div>
</div>
@endsection
