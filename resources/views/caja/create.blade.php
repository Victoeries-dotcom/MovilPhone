@extends('layout')

@section('content')
@include('components.registro-wizard')

<div class="page-header">
    <h1>Registrar Movimiento</h1>
    <a href="{{ route('caja.index') }}" class="btn">← Volver</a>
</div>

@php
    // Coloca al usuario en el paso relacionado con el primer error devuelto por MovimientoCajaController::store.
    $pasosCaja = ['tipo' => 1, 'categoria' => 2, 'monto' => 3, 'metodo_pago' => 4, 'descripcion' => 5];
    $pasoInicialCaja = 1;
    foreach ($pasosCaja as $campo => $paso) {
        if ($errors->has($campo)) {
            $pasoInicialCaja = $paso;
            break;
        }
    }
@endphp

<div class="registro-wizard" data-registro-wizard data-initial-step="{{ $pasoInicialCaja }}">
    <div class="registro-wizard-progress">
        <span class="registro-wizard-progress-label" data-progress-label></span>
        <div class="registro-wizard-track"><div class="registro-wizard-fill" data-progress-fill></div></div>
    </div>

    @if($errors->any())
        {{-- Presenta los errores conectados con las reglas de MovimientoCajaController::store. --}}
        <div class="alert alert-error" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
    @endif

    <div class="registro-wizard-card">
        {{-- El formulario registra un ingreso o egreso manual en movimientos_caja. --}}
        <form method="POST" action="{{ route('caja.store') }}">
            @csrf

            {{-- La sede seleccionada en el menú se usa automáticamente para separar la información financiera. --}}
            <input type="hidden" name="sucursal_id" value="{{ session('sucursal_id') }}">

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Qué tipo de movimiento registrarás?</div>
                <select class="registro-input" name="tipo" required>
                    <option value="">SELECCIONA UNA OPCIÓN</option>
                    <option value="INGRESO" {{ old('tipo') === 'INGRESO' ? 'selected' : '' }}>INGRESO</option>
                    <option value="EGRESO" {{ old('tipo') === 'EGRESO' ? 'selected' : '' }}>EGRESO</option>
                </select>
                <div class="registro-actions">
                    <button type="button" class="registro-next" data-next>Siguiente</button>
                </div>
            </section>

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Cuál es la categoría del movimiento?</div>
                <select class="registro-input" name="categoria" required>
                    @foreach(['REPARACIÓN','DIAGNÓSTICO','VENTA ACCESORIO','COMPRA PIEZA','COMPRA DESHUESO','GASTO OPERATIVO','SUELDO','OTRO'] as $cat)
                        <option value="{{ $cat }}" {{ old('categoria') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
                <div class="registro-actions">
                    <button type="button" class="registro-prev" data-prev>Anterior</button>
                    <button type="button" class="registro-next" data-next>Siguiente</button>
                </div>
            </section>

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Cuál es el monto?</div>
                <input class="registro-input" type="number" name="monto" value="{{ old('monto') }}"
                    min="0.01" step="0.01" placeholder="0.00" required>
                <div class="registro-actions">
                    <button type="button" class="registro-prev" data-prev>Anterior</button>
                    <button type="button" class="registro-next" data-next>Siguiente</button>
                </div>
            </section>

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Cuál fue el método de pago?</div>
                <select class="registro-input" name="metodo_pago" required>
                    <option value="efectivo" {{ old('metodo_pago', 'efectivo') === 'efectivo' ? 'selected' : '' }}>EFECTIVO</option>
                    <option value="transferencia" {{ old('metodo_pago') === 'transferencia' ? 'selected' : '' }}>TRANSFERENCIA</option>
                    <option value="tarjeta" {{ old('metodo_pago') === 'tarjeta' ? 'selected' : '' }}>TARJETA</option>
                </select>
                <div class="registro-actions">
                    <button type="button" class="registro-prev" data-prev>Anterior</button>
                    <button type="button" class="registro-next" data-next>Siguiente</button>
                </div>
            </section>

            <section class="registro-step" aria-hidden="true">
                <div class="registro-question">¿Deseas agregar una descripción?</div>
                <textarea class="registro-input" name="descripcion" placeholder="DESCRIPCIÓN DEL MOVIMIENTO"
                    maxlength="500" data-uppercase>{{ old('descripcion') }}</textarea>
                <div class="registro-help">Sucursal activa: {{ session('sucursal_nombre', 'SIN SELECCIONAR') }}</div>
                <div class="registro-actions">
                    <button type="button" class="registro-prev" data-prev>Anterior</button>
                    <button type="submit" class="registro-save">Guardar movimiento</button>
                </div>
            </section>
        </form>
    </div>
</div>
@endsection
