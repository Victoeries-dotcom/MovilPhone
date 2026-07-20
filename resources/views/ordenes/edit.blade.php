@extends('layout')

@section('content')
<style>
    /* Separa visualmente los grupos sin crear tarjetas anidadas dentro del formulario. */
    .os-edit-section {
        grid-column: 1 / -1;
        margin: .5rem 0 .15rem;
        padding-bottom: .5rem;
        border-bottom: 1px solid #e2e8f0;
        color: #0f1f3d;
        font-size: 14px;
        font-weight: 800;
    }
</style>

<div class="page-header">
    <h1>Editar {{ $ordenServicio->numero_os }}</h1>
    <a href="{{ route('ordenes.index') }}" class="btn">← Volver</a>
</div>

<div class="card">
    {{-- Formulario principal: actualiza cliente, orden de servicio y el cobro conectado con Caja. --}}
    <form method="POST" action="{{ route('ordenes.update', $ordenServicio) }}">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="os-edit-section">Datos del cliente</div>

            <div class="form-group">
                <label>Nombre del cliente *</label>
                {{-- Se conecta con clientes.nombre mediante ordenes_servicio.cliente_id. --}}
                <input
                    type="text"
                    name="cliente_nombre"
                    required
                    value="{{ old('cliente_nombre', $ordenServicio->cliente->nombre ?? '') }}"
                >
            </div>

            <div class="form-group">
                <label>Teléfono principal *</label>
                {{-- Se conecta con clientes.telefono_principal y conserva el dato del paso 2 de Nueva OS. --}}
                <input
                    type="tel"
                    name="cliente_telefono"
                    required
                    value="{{ old('cliente_telefono', $ordenServicio->cliente->telefono_principal ?? '') }}"
                >
            </div>

            <div class="form-group">
                <label>Teléfono extra</label>
                {{-- Actualiza clientes.telefono_alternativo y ordenes_servicio.cliente_telefono_extra. --}}
                <input
                    type="tel"
                    name="cliente_telefono_extra"
                    value="{{ old('cliente_telefono_extra', $ordenServicio->cliente_telefono_extra ?: ($ordenServicio->cliente->telefono_alternativo ?? '')) }}"
                >
            </div>

            <div class="form-group">
                <label>Sucursal</label>
                {{-- La sucursal es informativa y permanece conectada con ordenes_servicio.sucursal_id. --}}
                <input type="text" value="{{ $ordenServicio->sucursal->nombre ?? 'SIN SUCURSAL' }}" readonly>
            </div>

            <div class="os-edit-section">Datos del dispositivo</div>

            <div class="form-group">
                <label>Tipo de dispositivo *</label>
                {{-- Muestra exactamente ordenes_servicio.tipo_dispositivo capturado en Nueva OS. --}}
                <input
                    type="text"
                    name="tipo_dispositivo"
                    required
                    value="{{ old('tipo_dispositivo', $ordenServicio->tipo_dispositivo) }}"
                >
            </div>

            <div class="form-group">
                <label>Marca *</label>
                {{-- Es texto libre como Nueva OS; así conserva marcas no incluidas en una lista, por ejemplo ASUSVIVOBOOK. --}}
                <input
                    type="text"
                    name="marca"
                    required
                    value="{{ old('marca', $ordenServicio->marca) }}"
                >
            </div>

            <div class="form-group">
                <label>Modelo *</label>
                {{-- Se conecta directamente con ordenes_servicio.modelo. --}}
                <input type="text" name="modelo" required value="{{ old('modelo', $ordenServicio->modelo) }}">
            </div>

            <div class="form-group">
                <label>IMEI / Serie</label>
                {{-- Guarda un identificador adicional en ordenes_servicio.imei cuando el equipo lo tenga. --}}
                <input type="text" name="imei" value="{{ old('imei', $ordenServicio->imei) }}">
            </div>

            <div class="form-group">
                <label>Técnico asignado</label>
                {{-- Se conecta con users mediante ordenes_servicio.tecnico_id. --}}
                <select name="tecnico_id">
                    <option value="">— Sin asignar —</option>
                    @foreach($tecnicos as $tecnico)
                        <option
                            value="{{ $tecnico->id }}"
                            {{ (string) old('tecnico_id', $ordenServicio->tecnico_id) === (string) $tecnico->id ? 'selected' : '' }}
                        >
                            {{ $tecnico->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group full-width">
                <label>Problema reportado *</label>
                {{-- Conserva la falla descrita en el paso 7 de Nueva OS. --}}
                <textarea name="problema_reportado" required rows="3">{{ old('problema_reportado', $ordenServicio->problema_reportado) }}</textarea>
            </div>

            <div class="form-group full-width">
                <label>Diagnóstico técnico</label>
                {{-- Se completa durante la revisión y se conecta con ordenes_servicio.problema_diagnosticado. --}}
                <textarea name="problema_diagnosticado" rows="3">{{ old('problema_diagnosticado', $ordenServicio->problema_diagnosticado) }}</textarea>
            </div>

            <div class="form-group full-width">
                <label>Estado físico *</label>
                {{-- Conserva el estado físico capturado en el último paso de Nueva OS. --}}
                <textarea name="estado_fisico" required rows="2">{{ old('estado_fisico', $ordenServicio->estado_fisico) }}</textarea>
            </div>

            <div class="form-group full-width">
                <label>Patrón, PIN o contraseña del dispositivo</label>
                {{-- Se conserva exactamente porque una contraseña puede distinguir mayúsculas de minúsculas. --}}
                <input
                    type="text"
                    name="contrasena_dispositivo"
                    value="{{ old('contrasena_dispositivo', $ordenServicio->contrasena_dispositivo) }}"
                    data-no-mayusculas
                    placeholder="Ej. PATRÓN: 1-2-5-8 o PIN 1234"
                >
            </div>

            <div class="form-group full-width">
                <label>Accesorios entregados</label>
                {{-- Se conecta con ordenes_servicio.accesorios_entregados y muestra el dato de Nueva OS. --}}
                <input
                    type="text"
                    name="accesorios_entregados"
                    value="{{ old('accesorios_entregados', $ordenServicio->accesorios_entregados) }}"
                >
            </div>

            <div class="os-edit-section">Cobros y entrega</div>

            <div class="form-group">
                <label>Anticipo ($)</label>
                {{-- Se conecta con ordenes_servicio.anticipo y actualiza la misma fila financiera en Caja. --}}
                <input type="number" name="anticipo" value="{{ old('anticipo', $ordenServicio->anticipo ?? 0) }}" min="0" step="0.01">
            </div>

            <div class="form-group">
                <label>Método de anticipo</label>
                {{-- Se conecta con movimientos_caja.metodo_pago mediante la sincronización de la OS. --}}
                @php
                    $metodoSeleccionado = strtolower(old('metodo_pago_anticipo', $ordenServicio->metodo_pago_anticipo ?? 'efectivo'));
                @endphp
                <select name="metodo_pago_anticipo">
                    @foreach(['efectivo', 'transferencia', 'tarjeta'] as $metodo)
                        <option value="{{ $metodo }}" {{ $metodoSeleccionado === $metodo ? 'selected' : '' }}>
                            {{ ucfirst($metodo) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Cobro de diagnóstico ($)</label>
                {{-- Se conecta con ordenes_servicio.cobro_diagnostico y con el total mostrado en Caja. --}}
                <input type="number" name="cobro_diagnostico" value="{{ old('cobro_diagnostico', $ordenServicio->cobro_diagnostico ?? 0) }}" min="0" step="0.01">
            </div>

            <div class="form-group">
                <label>Mano de obra ($)</label>
                {{-- Guarda el costo técnico en ordenes_servicio.mano_obra. --}}
                <input type="number" name="mano_obra" value="{{ old('mano_obra', $ordenServicio->mano_obra ?? 0) }}" min="0" step="0.01">
            </div>

            <div class="form-group">
                <label>Presupuesto total ($)</label>
                {{-- Se conecta con ordenes_servicio.presupuesto_total para calcular el saldo pendiente. --}}
                <input type="number" name="presupuesto_total" value="{{ old('presupuesto_total', $ordenServicio->presupuesto_total ?? 0) }}" min="0" step="0.01">
            </div>

            <div class="form-group">
                <label>Fecha estimada de entrega</label>
                {{-- Guarda la fecha prevista en ordenes_servicio.fecha_entrega_estimada. --}}
                <input
                    type="date"
                    name="fecha_entrega_estimada"
                    value="{{ old('fecha_entrega_estimada', $ordenServicio->fecha_entrega_estimada ? \Carbon\Carbon::parse($ordenServicio->fecha_entrega_estimada)->format('Y-m-d') : '') }}"
                >
            </div>
        </div>

        @if($errors->any())
            {{-- Presenta errores de validación sin borrar los valores enviados por el usuario. --}}
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem">
            <a href="{{ route('ordenes.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection
