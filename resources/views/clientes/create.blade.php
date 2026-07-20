@extends('layout')

@section('content')
@include('components.registro-wizard')

<div class="page-header">
    <h1>Nuevo Cliente</h1>
    <a href="{{ route('clientes.index') }}" class="btn">← Volver</a>
</div>

@php
    // Abre el paso del teléfono cuando el servidor devuelve un error relacionado con ese identificador.
    $pasoInicial = $errors->has('telefono_principal') || $errors->has('telefono_normalizado') ? 2 : 1;
@endphp

<div class="registro-wizard" data-registro-wizard data-initial-step="{{ $pasoInicial }}">
    <div class="registro-wizard-progress">
        <span class="registro-wizard-progress-label" data-progress-label></span>
        <div class="registro-wizard-track"><div class="registro-wizard-fill" data-progress-fill></div></div>
    </div>

    @if($errors->any())
        {{-- Errores de validación: se conectan con ClienteController::store. --}}
        <div class="alert alert-error" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
    @endif

    <div class="registro-wizard-card">
        {{-- Este formulario guarda el cliente en la tabla clientes mediante ClienteController::store. --}}
        <form method="POST" action="{{ route('clientes.store') }}">
            @csrf

            {{-- La sucursal activa se conserva oculta para separar correctamente los clientes por sede. --}}
            <input type="hidden" name="sucursal_habitual_id" value="{{ session('sucursal_id') }}">

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Cuál es el nombre del cliente?</div>
                <input class="registro-input" type="text" name="nombre" value="{{ old('nombre') }}"
                    placeholder="NOMBRE COMPLETO" autocomplete="off" required data-uppercase>
                <div class="registro-help">El nombre se guardará en mayúsculas.</div>
                <div class="registro-actions">
                    <button type="button" class="registro-next" data-next>Siguiente</button>
                </div>
            </section>

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Cuál es su teléfono principal?</div>
                <input class="registro-input" type="tel" name="telefono_principal"
                    value="{{ old('telefono_principal') }}" placeholder="999-000-0000" required>
                <div class="registro-help">Este número identifica al cliente y evita registros duplicados.</div>
                <div class="registro-actions">
                    <button type="button" class="registro-prev" data-prev>Anterior</button>
                    <button type="button" class="registro-next" data-next>Siguiente</button>
                </div>
            </section>

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Tiene un teléfono alternativo?</div>
                <input class="registro-input" type="tel" name="telefono_alternativo"
                    value="{{ old('telefono_alternativo') }}" placeholder="OPCIONAL">
                <div class="registro-help">Puede dejar este campo vacío.</div>
                <div class="registro-actions">
                    <button type="button" class="registro-prev" data-prev>Anterior</button>
                    <button type="submit" class="registro-save">Guardar cliente</button>
                </div>
            </section>
        </form>
    </div>
</div>
@endsection
