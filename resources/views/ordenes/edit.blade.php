@extends('layout')

@section('content')
{{-- Contenedor visual exclusivo de Editar OS; no modifica rutas, permisos ni datos del formulario. --}}
<div class="os-edit-page">
    {{-- Encabezado profesional: identifica la orden, su sucursal y su estado actual. --}}
    <header class="os-edit-hero">
        <div class="os-edit-hero-copy">
            <span class="os-edit-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M14.7 6.3a4.2 4.2 0 0 0-5.4 5.4L3.8 17.2a2.1 2.1 0 0 0 3 3l5.5-5.5a4.2 4.2 0 0 0 5.4-5.4l-2.5 2.5-3-3 2.5-2.5Z"/>
                </svg>
                Órdenes de servicio
            </span>
            <h1>Editar <span>{{ $ordenServicio->numero_os }}</span></h1>
            <p>Revisa y actualiza la información registrada del cliente, el dispositivo y la entrega.</p>

            <div class="os-edit-meta" aria-label="Resumen de la orden">
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 21h16M6 21V7l6-4 6 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01M10 21v-3h4v3"/>
                    </svg>
                    {{ $ordenServicio->sucursal->nombre ?? 'Sin sucursal' }}
                </span>
                <span class="os-edit-status">
                    <i aria-hidden="true"></i>
                    {{ $estados[$ordenServicio->estado] ?? ucfirst(strtolower($ordenServicio->estado)) }}
                </span>
            </div>
        </div>

        <a href="{{ route('ordenes.index') }}" class="os-edit-back-button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Volver a órdenes
        </a>
    </header>

    {{-- Formulario principal: conserva el método PUT y actualiza las mismas relaciones existentes. --}}
    <form method="POST" action="{{ route('ordenes.update', $ordenServicio) }}" class="os-edit-form">
        @csrf
        @method('PUT')

        @if($errors->any())
            {{-- Los errores conservan sus mensajes y ahora se muestran con mayor jerarquía visual. --}}
            <div class="alert alert-error os-edit-error" role="alert">
                <span class="os-edit-error-icon" aria-hidden="true">!</span>
                <div>
                    <strong>Revisa la información antes de guardar</strong>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Sección 1: mantiene los campos conectados con el cliente de la orden. --}}
        <section class="os-edit-card" aria-labelledby="os-edit-client-title">
            <header class="os-edit-section-header">
                <span class="os-edit-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/>
                    </svg>
                </span>
                <div>
                    <span class="os-edit-step">01</span>
                    <h2 id="os-edit-client-title">Datos del cliente</h2>
                    <p>Información de contacto asociada a esta orden.</p>
                </div>
            </header>

            <div class="os-edit-grid">
                <div class="form-group">
                    <label for="cliente_nombre">Nombre del cliente <span class="os-edit-required">Requerido</span></label>
                    {{-- Se conecta con clientes.nombre mediante ordenes_servicio.cliente_id. --}}
                    <input id="cliente_nombre" type="text" name="cliente_nombre" required value="{{ old('cliente_nombre', $ordenServicio->cliente->nombre ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="cliente_telefono">Teléfono principal <span class="os-edit-optional">Opcional</span></label>
                    {{-- Conserva en clientes.telefono_principal el contacto opcional capturado en Nueva OS. --}}
                    <input
                        id="cliente_telefono"
                        type="tel"
                        name="cliente_telefono"
                        inputmode="numeric"
                        minlength="10"
                        maxlength="10"
                        pattern="[0-9]{10}"
                        title="Si lo capturas, escribe exactamente 10 dígitos."
                        value="{{ old('cliente_telefono', $ordenServicio->cliente->telefono_principal ?? '') }}"
                    >
                </div>

                <div class="form-group">
                    <label for="cliente_telefono_extra">Teléfono extra <span class="os-edit-optional">Opcional</span></label>
                    {{-- Actualiza clientes.telefono_alternativo y ordenes_servicio.cliente_telefono_extra. --}}
                    <input id="cliente_telefono_extra" type="tel" name="cliente_telefono_extra" value="{{ old('cliente_telefono_extra', $ordenServicio->cliente_telefono_extra ?: ($ordenServicio->cliente->telefono_alternativo ?? '')) }}">
                </div>

                <div class="form-group">
                    <label for="sucursal_orden">Sucursal</label>
                    {{-- La sucursal es informativa y permanece conectada con ordenes_servicio.sucursal_id. --}}
                    <input id="sucursal_orden" type="text" value="{{ $ordenServicio->sucursal->nombre ?? 'SIN SUCURSAL' }}" readonly>
                </div>
            </div>
        </section>

        {{-- Sección 2: conserva todos los datos técnicos y operativos del dispositivo. --}}
        <section class="os-edit-card" aria-labelledby="os-edit-device-title">
            <header class="os-edit-section-header">
                <span class="os-edit-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="7" y="2" width="10" height="20" rx="2"/><path d="M10 5h4M11 18h2"/>
                    </svg>
                </span>
                <div>
                    <span class="os-edit-step">02</span>
                    <h2 id="os-edit-device-title">Datos del dispositivo</h2>
                    <p>Identificación, asignación y diagnóstico técnico.</p>
                </div>
            </header>

            <div class="os-edit-grid">
                <div class="form-group">
                    <label for="tipo_dispositivo">Tipo de dispositivo <span class="os-edit-required">Requerido</span></label>
                    {{-- Muestra exactamente ordenes_servicio.tipo_dispositivo capturado en Nueva OS. --}}
                    <input id="tipo_dispositivo" type="text" name="tipo_dispositivo" required value="{{ old('tipo_dispositivo', $ordenServicio->tipo_dispositivo) }}">
                </div>

                <div class="form-group">
                    <label for="marca">Marca <span class="os-edit-required">Requerido</span></label>
                    {{-- Es texto libre como Nueva OS y conserva marcas que no pertenecen a una lista fija. --}}
                    <input id="marca" type="text" name="marca" required value="{{ old('marca', $ordenServicio->marca) }}">
                </div>

                <div class="form-group">
                    <label for="modelo">Modelo <span class="os-edit-required">Requerido</span></label>
                    {{-- Se conecta directamente con ordenes_servicio.modelo. --}}
                    <input id="modelo" type="text" name="modelo" required value="{{ old('modelo', $ordenServicio->modelo) }}">
                </div>

                <div class="form-group">
                    <label for="imei">IMEI / Serie <span class="os-edit-optional">Opcional</span></label>
                    {{-- Guarda un identificador adicional en ordenes_servicio.imei cuando el equipo lo tenga. --}}
                    <input id="imei" type="text" name="imei" value="{{ old('imei', $ordenServicio->imei) }}">
                </div>

                <div class="form-group">
                    <label for="tecnico_id">Técnico asignado <span class="os-edit-optional">Opcional</span></label>
                    {{-- Se conecta con users mediante ordenes_servicio.tecnico_id. --}}
                    <select id="tecnico_id" name="tecnico_id">
                        <option value="">— Sin asignar —</option>
                        @foreach($tecnicos as $tecnico)
                            <option value="{{ $tecnico->id }}" {{ (string) old('tecnico_id', $ordenServicio->tecnico_id) === (string) $tecnico->id ? 'selected' : '' }}>
                                {{ $tecnico->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($puedeCambiarEstado)
                    <div class="form-group">
                        <label for="estado">Estado de la orden <span class="os-edit-required">Requerido</span></label>
                        {{-- Superusuario y usuario cambian ordenes_servicio.estado; el controlador registra historial y sucursal. --}}
                        <select id="estado" name="estado" required>
                            @foreach($estados as $valorEstado => $etiquetaEstado)
                                <option value="{{ $valorEstado }}" {{ old('estado', $ordenServicio->estado) === $valorEstado ? 'selected' : '' }}>
                                    {{ $etiquetaEstado }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="form-group os-edit-wide">
                    <label for="problema_reportado">Problema reportado <span class="os-edit-required">Requerido</span></label>
                    {{-- Conserva la falla descrita en el paso 7 de Nueva OS. --}}
                    <textarea id="problema_reportado" name="problema_reportado" required rows="3">{{ old('problema_reportado', $ordenServicio->problema_reportado) }}</textarea>
                </div>

                <div class="form-group os-edit-wide">
                    <label for="problema_diagnosticado">Diagnóstico técnico <span class="os-edit-optional">Opcional</span></label>
                    {{-- Se completa durante la revisión y se conecta con ordenes_servicio.problema_diagnosticado. --}}
                    <textarea id="problema_diagnosticado" name="problema_diagnosticado" rows="3">{{ old('problema_diagnosticado', $ordenServicio->problema_diagnosticado) }}</textarea>
                </div>

                <div class="form-group os-edit-wide">
                    <label for="estado_fisico">Estado físico <span class="os-edit-required">Requerido</span></label>
                    {{-- Conserva el estado físico capturado en el último paso de Nueva OS. --}}
                    <textarea id="estado_fisico" name="estado_fisico" required rows="2">{{ old('estado_fisico', $ordenServicio->estado_fisico) }}</textarea>
                </div>

                <div class="form-group os-edit-wide">
                    <label for="contrasena_dispositivo">Patrón, PIN o contraseña <span class="os-edit-optional">Opcional</span></label>
                    {{-- Se conserva exactamente porque una contraseña puede distinguir mayúsculas de minúsculas. --}}
                    <input
                        id="contrasena_dispositivo"
                        type="text"
                        name="contrasena_dispositivo"
                        value="{{ old('contrasena_dispositivo', $ordenServicio->contrasena_dispositivo) }}"
                        data-no-mayusculas
                        placeholder="Ej. PATRÓN: 1-2-5-8 o PIN 1234"
                    >
                </div>

                <div class="form-group os-edit-wide">
                    <label for="accesorios_entregados">Accesorios entregados <span class="os-edit-optional">Opcional</span></label>
                    {{-- Se conecta con ordenes_servicio.accesorios_entregados y muestra el dato de Nueva OS. --}}
                    <input id="accesorios_entregados" type="text" name="accesorios_entregados" value="{{ old('accesorios_entregados', $ordenServicio->accesorios_entregados) }}">
                </div>
            </div>
        </section>

        {{-- Sección 3: conserva los importes que se conectan con la orden y Caja. --}}
        <section class="os-edit-card" aria-labelledby="os-edit-payment-title">
            <header class="os-edit-section-header">
                <span class="os-edit-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 15h2"/>
                    </svg>
                </span>
                <div>
                    <span class="os-edit-step">03</span>
                    <h2 id="os-edit-payment-title">Cobros y entrega</h2>
                    <p>Importes registrados y fecha estimada de finalización.</p>
                </div>
            </header>

            <div class="os-edit-grid os-edit-grid-financial">
                <div class="form-group">
                    <label for="anticipo">Anticipo ($)</label>
                    {{-- Se conecta con ordenes_servicio.anticipo y actualiza la misma fila financiera en Caja. --}}
                    <input id="anticipo" type="number" name="anticipo" value="{{ old('anticipo', $ordenServicio->anticipo ?? 0) }}" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label for="metodo_pago_anticipo">Método de anticipo</label>
                    {{-- Se conecta con movimientos_caja.metodo_pago mediante la sincronización de la OS. --}}
                    @php
                        $metodoSeleccionado = strtolower(old('metodo_pago_anticipo', $ordenServicio->metodo_pago_anticipo ?? 'efectivo'));
                    @endphp
                    <select id="metodo_pago_anticipo" name="metodo_pago_anticipo">
                        @foreach(['efectivo', 'transferencia', 'tarjeta'] as $metodo)
                            <option value="{{ $metodo }}" {{ $metodoSeleccionado === $metodo ? 'selected' : '' }}>
                                {{ ucfirst($metodo) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="cobro_diagnostico">Diagnóstico del dispositivo ($)</label>
                    {{-- Es el precio diagnosticado y no se mezcla con el dinero recibido al entregar. --}}
                    <input id="cobro_diagnostico" type="number" name="cobro_diagnostico" value="{{ old('cobro_diagnostico', $ordenServicio->cobro_diagnostico ?? 0) }}" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label for="mano_obra">Mano de obra ($)</label>
                    {{-- Guarda el costo técnico en ordenes_servicio.mano_obra. --}}
                    <input id="mano_obra" type="number" name="mano_obra" value="{{ old('mano_obra', $ordenServicio->mano_obra ?? 0) }}" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label for="presupuesto_total">Presupuesto total ($)</label>
                    {{-- Se conecta con ordenes_servicio.presupuesto_total para calcular el saldo pendiente. --}}
                    <input id="presupuesto_total" type="number" name="presupuesto_total" value="{{ old('presupuesto_total', $ordenServicio->presupuesto_total ?? 0) }}" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label for="fecha_entrega_estimada">Fecha estimada de entrega</label>
                    {{-- Guarda la fecha prevista en ordenes_servicio.fecha_entrega_estimada. --}}
                    <input
                        id="fecha_entrega_estimada"
                        type="date"
                        name="fecha_entrega_estimada"
                        value="{{ old('fecha_entrega_estimada', $ordenServicio->fecha_entrega_estimada ? \Carbon\Carbon::parse($ordenServicio->fecha_entrega_estimada)->format('Y-m-d') : '') }}"
                    >
                </div>
            </div>
        </section>

        {{-- Barra final: conserva Cancelar y Guardar como únicas acciones del formulario. --}}
        <div class="os-edit-actions">
            <p>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M12 10v6M12 7h.01"/>
                </svg>
                Los cambios se aplicarán a esta orden de servicio.
            </p>
            <div>
                <a href="{{ route('ordenes.index') }}" class="btn os-edit-cancel-button">Cancelar</a>
                <button type="submit" class="btn btn-primary os-edit-save-button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                    Guardar cambios
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
