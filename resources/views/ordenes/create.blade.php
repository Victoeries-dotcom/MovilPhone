@extends('layout')

@section('content')

<style>
    /* Limita el ancho del registro para reproducir la interfaz centrada del documento de referencia. */
    .os-page-shell {
        width: 100%;
        max-width: 620px;
        margin: 0 auto;
        font-family: system-ui, sans-serif;
    }

    .os-page-shell .page-header {
        margin-bottom: 1.25rem;
    }

    .os-page-shell .page-header h1 {
        font-size: 20px;
    }

    /* Contenedor principal: integra el asistente en la página y deja visible el menú del sistema. */
    .os-wizard-overlay {
        position: static;
        display: block;
        width: 100%;
        padding: 0;
        background: transparent;
        overflow: visible;
    }

    /* Caja estructural: permite que la barra quede arriba y la pregunta dentro de una tarjeta independiente. */
    .os-wizard-box {
        width: 100%;
        max-width: none;
        padding: 0;
        background: transparent;
        overflow: visible;
    }

    /* Barra superior: combina el número de paso y el avance continuo del registro. */
    .os-progress-row {
        display: grid;
        grid-template-columns: auto minmax(120px, 1fr);
        align-items: center;
        gap: 12px;
        margin-bottom: 1.25rem;
    }

    .os-progress-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .os-progress {
        height: 5px;
        overflow: hidden;
        border-radius: 5px;
        background: #e2e8f0;
    }

    .os-progress-fill {
        width: 0;
        height: 100%;
        border-radius: inherit;
        background: #0f1f3d;
        transition: width 180ms ease;
    }

    /* Cada paso representa una pregunta y se muestra como una tarjeta blanca dentro de la página. */
    .os-step {
        display: none;
        min-height: 275px;
        padding: 2rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 10px 28px rgba(15, 31, 61, 0.08);
    }

    .os-step.active {
        display: block;
    }

    .os-step-label {
        display: none;
    }

    .os-step-title {
        font-size: 24px;
        font-weight: 800;
        color: #0f1f3d;
        margin-bottom: 1.5rem;
    }

    /* Entrada estándar; se conecta con los name="" que lee OrdenServicioController::store. */
    .os-input,
    .os-textarea,
    .os-select {
        width: 100%;
        padding: 13px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 16px;
        font-family: inherit;
        outline: none;
        box-sizing: border-box;
    }

    .os-input:focus,
    .os-textarea:focus,
    .os-select:focus {
        border-color: #3b82f6;
    }

    .os-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .os-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
    }

    .os-btn {
        border: none;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 15px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
    }

    .os-btn-primary {
        flex: 1;
        background: #0f1f3d;
        color: #ffffff;
    }

    .os-btn-secondary {
        background: #ffffff;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .os-btn-save {
        flex: 1;
        background: #16a34a;
        color: #ffffff;
    }

    /* Botón de cliente anterior; abre la búsqueda conectada con clientes.telefono_normalizado. */
    .os-btn-returning {
        width: 100%;
        margin-top: 0.75rem;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .os-btn-returning:hover {
        background: #dbeafe;
    }

    /* Panel de búsqueda por teléfono; llena los primeros datos sin crear otro cliente. */
    .returning-client-panel {
        display: none;
        margin-top: 0.9rem;
        padding: 1rem;
        border: 1px solid #dbe3ef;
        border-radius: 8px;
        background: #f8fafc;
    }

    .returning-client-panel.visible {
        display: block;
    }

    .returning-client-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 8px;
        margin-top: 0.5rem;
    }

    .returning-client-status {
        min-height: 18px;
        margin-top: 0.6rem;
        font-size: 12px;
        font-weight: 700;
    }

    .returning-client-status.error {
        color: #dc2626;
    }

    .returning-client-status.loading {
        color: #64748b;
    }

    /* Confirma en el paso del equipo qué cliente anterior fue recuperado. */
    .returning-client-confirmation {
        display: none;
        margin-bottom: 1rem;
        padding: 0.75rem 0.9rem;
        border: 1px solid #86efac;
        border-radius: 8px;
        background: #f0fdf4;
        color: #166534;
        font-size: 13px;
        font-weight: 700;
    }

    .returning-client-confirmation.visible {
        display: block;
    }

    @media (max-width: 520px) {
        .returning-client-row {
            grid-template-columns: 1fr;
        }
    }

    /* Puntos de patrón; se conectan con contrasena_dispositivo por JavaScript. */
    .pattern-grid {
        display: grid;
        grid-template-columns: repeat(3, 52px);
        justify-content: center;
        gap: 12px;
        margin: 0.75rem 0 1rem;
    }

    .pattern-dot {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        border: 2px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-size: 16px;
        font-weight: 800;
        cursor: pointer;
    }

    .pattern-dot.selected {
        background: #0f1f3d;
        border-color: #0f1f3d;
        color: #ffffff;
    }

    .os-hint {
        font-size: 12px;
        color: #64748b;
        margin-top: 0.5rem;
    }

    /* Botones de método de pago; se conectan con metodo_pago_anticipo antes de guardar la OS. */
    .payment-options {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: 0.75rem;
    }

    .payment-btn {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        color: #334155;
        padding: 10px 14px;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
    }

    .payment-btn.selected {
        background: #0f1f3d;
        border-color: #0f1f3d;
        color: #ffffff;
    }

    /* Resumen final; muestra lo capturado antes de guardar y se alimenta con updateResumen(). */
    .os-summary {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        font-size: 13px;
        color: #334155;
    }

    .os-summary-title {
        font-weight: 800;
        color: #0f1f3d;
        margin-bottom: 0.5rem;
    }

    @media (max-width: 640px) {
        .os-step {
            min-height: 0;
            padding: 1.25rem;
        }

        .os-step-title {
            font-size: 22px;
        }

        .os-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .os-btn {
            width: 100%;
        }
    }
</style>

@php
    // Abre el paso relacionado con el primer error devuelto por OrdenServicioController::store.
    $camposPorPaso = [
        1 => ['cliente_nombre', 'cliente_id'],
        2 => ['cliente_telefono'],
        3 => ['cliente_telefono_extra'],
        4 => ['tipo_dispositivo'],
        5 => ['marca'],
        6 => ['modelo'],
        7 => ['problema_reportado'],
        8 => ['contrasena_dispositivo'],
        9 => ['estado_fisico', 'accesorios_entregados', 'anticipo', 'metodo_pago_anticipo', 'sucursal_id'],
    ];
    $pasoInicialOs = 1;
    foreach ($camposPorPaso as $numeroPaso => $campos) {
        if ($errors->hasAny($campos)) {
            $pasoInicialOs = $numeroPaso;
            break;
        }
    }
@endphp

<div class="os-page-shell">
    <div class="page-header">
        <h1>Nueva Orden de Servicio</h1>
        <a href="{{ route('ordenes.index') }}" class="btn">Volver</a>
    </div>

    <div class="os-wizard-overlay">
        <div class="os-wizard-box">
            <div class="os-progress-row">
                <span class="os-progress-label" id="progress-label">Paso 1 de 9</span>
                <div class="os-progress">
                    <div class="os-progress-fill" id="progress"></div>
                </div>
            </div>

            @if($errors->any())
                {{-- Muestra la validación sin ocultar la pregunta que necesita corrección. --}}
                <div class="alert alert-error" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
            @endif

            {{-- Formulario principal: envía todos los datos de Nueva OS a OrdenServicioController::store. --}}
            <form method="POST" action="{{ route('ordenes.store') }}" id="osForm">
            @csrf

            {{-- Conserva el cliente anterior seleccionado y lo conecta con ordenes_servicio.cliente_id. --}}
            <input type="hidden" name="cliente_id" id="cliente_id" value="{{ old('cliente_id') }}">

            {{-- La sucursal activa conecta la OS con reportes, caja y filtros por sucursal. --}}
            <input type="hidden" name="sucursal_id" value="{{ session('sucursal_id') ?? old('sucursal_id') ?? ($sucursales->first()->id ?? '') }}">

            {{-- Paso 1: guarda cliente_nombre para crear o localizar al cliente. --}}
            <div class="os-step active" id="step-1">
                <div class="os-step-label">Paso 1 de 9</div>
                <div class="os-step-title">¿Cuál es el nombre del cliente?</div>
                <input class="os-input" type="text" name="cliente_nombre" id="cliente_nombre" value="{{ old('cliente_nombre') }}" placeholder="Nombre completo" required>
                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-primary" onclick="nextStep(1)">Continuar</button>
                </div>
                <button type="button" class="os-btn os-btn-returning" onclick="toggleClienteAnterior()">
                    Cliente anterior
                </button>

                {{-- Busca por teléfono sin enviar el formulario principal y recupera un único cliente existente. --}}
                <div class="returning-client-panel" id="returning-client-panel">
                    <label class="os-hint" for="buscar_cliente_telefono" style="display:block;font-weight:800;text-transform:uppercase;">
                        Teléfono principal del cliente
                    </label>
                    <div class="returning-client-row">
                        <input
                            class="os-input"
                            type="tel"
                            id="buscar_cliente_telefono"
                            placeholder="999-000-0000"
                            data-no-mayusculas
                        >
                        <button type="button" class="os-btn os-btn-secondary" id="buscar-cliente-btn" onclick="buscarClienteAnterior()">
                            Buscar
                        </button>
                    </div>
                    <div class="returning-client-status" id="returning-client-status"></div>
                </div>
            </div>

            {{-- Paso 2: guarda cliente_telefono y lo conecta con clientes.telefono_principal. --}}
            <div class="os-step" id="step-2">
                <div class="os-step-label">Paso 2 de 9</div>
                <div class="os-step-title">¿Cuál es su teléfono?</div>
                <input class="os-input" type="tel" name="cliente_telefono" id="cliente_telefono" value="{{ old('cliente_telefono') }}" placeholder="999-000-0000" required>
                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-secondary" onclick="prevStep(2)">Atrás</button>
                    <button type="button" class="os-btn os-btn-primary" onclick="nextStep(2)">Continuar</button>
                </div>
            </div>

            {{-- Paso 3: guarda teléfono extra para avisos y lo conecta con cliente_telefono_extra. --}}
            <div class="os-step" id="step-3">
                <div class="os-step-label">Paso 3 de 9</div>
                <div class="os-step-title">¿Tiene un teléfono extra para avisarle?</div>
                <input class="os-input" type="tel" name="cliente_telefono_extra" id="cliente_telefono_extra" value="{{ old('cliente_telefono_extra') }}" placeholder="Número alternativo opcional">
                <div class="os-hint">Si no tiene, deja este campo vacío y continúa.</div>
                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-secondary" onclick="prevStep(3)">Atrás</button>
                    <button type="button" class="os-btn os-btn-primary" onclick="nextStep(3, false)">Continuar</button>
                </div>
            </div>

            {{-- Paso 4: guarda tipo_dispositivo en ordenes_servicio para identificar el equipo. --}}
            <div class="os-step" id="step-4">
                <div class="os-step-label">Paso 4 de 9</div>
                <div class="returning-client-confirmation" id="returning-client-confirmation"></div>
                <div class="os-step-title">¿Qué tipo de dispositivo es?</div>
                <input class="os-input" type="text" name="tipo_dispositivo" id="tipo_dispositivo" value="{{ old('tipo_dispositivo') }}" placeholder="Teléfono, tablet, computadora, consola..." required>
                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-secondary" onclick="prevStep(4)">Atrás</button>
                    <button type="button" class="os-btn os-btn-primary" onclick="nextStep(4)">Continuar</button>
                </div>
            </div>

            {{-- Paso 5: guarda marca en la orden y se muestra en listas/detalle. --}}
            <div class="os-step" id="step-5">
                <div class="os-step-label">Paso 5 de 9</div>
                <div class="os-step-title">¿Cuál es la marca?</div>
                <input class="os-input" type="text" name="marca" id="marca" value="{{ old('marca') }}" placeholder="Apple, Samsung, Xiaomi..." required>
                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-secondary" onclick="prevStep(5)">Atrás</button>
                    <button type="button" class="os-btn os-btn-primary" onclick="nextStep(5)">Continuar</button>
                </div>
            </div>

            {{-- Paso 6: guarda modelo para identificar con precisión el dispositivo. --}}
            <div class="os-step" id="step-6">
                <div class="os-step-label">Paso 6 de 9</div>
                <div class="os-step-title">¿Cuál es su modelo?</div>
                <input class="os-input" type="text" name="modelo" id="modelo" value="{{ old('modelo') }}" placeholder="iPhone 13, Galaxy A52..." required>
                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-secondary" onclick="prevStep(6)">Atrás</button>
                    <button type="button" class="os-btn os-btn-primary" onclick="nextStep(6)">Continuar</button>
                </div>
            </div>

            {{-- Paso 7: guarda problema_reportado como descripción inicial de la falla. --}}
            <div class="os-step" id="step-7">
                <div class="os-step-label">Paso 7 de 9</div>
                <div class="os-step-title">¿Qué problema tiene el dispositivo?</div>
                <textarea class="os-textarea" name="problema_reportado" id="problema_reportado" placeholder="Describe el problema reportado por el cliente..." required>{{ old('problema_reportado') }}</textarea>
                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-secondary" onclick="prevStep(7)">Atrás</button>
                    <button type="button" class="os-btn os-btn-primary" onclick="nextStep(7)">Continuar</button>
                </div>
            </div>

            {{-- Paso 8: guarda contrasena_dispositivo para acceso técnico al equipo. --}}
            <div class="os-step" id="step-8">
                <div class="os-step-label">Paso 8 de 9</div>
                <div class="os-step-title">¿El dispositivo tiene PIN, patrón o contraseña?</div>
                <input type="hidden" name="contrasena_dispositivo" id="contrasena_dispositivo" value="{{ old('contrasena_dispositivo') }}">
                <div class="os-hint">Si tiene patrón, toca los puntos en orden.</div>
                <div class="pattern-grid">
                    @foreach([1,2,3,4,5,6,7,8,9] as $punto)
                        <button type="button" class="pattern-dot" id="pattern-dot-{{ $punto }}" onclick="togglePatternDot({{ $punto }})">{{ $punto }}</button>
                    @endforeach
                </div>
                <div class="os-hint" style="text-align:center;margin-bottom:0.75rem;">
                    Patrón: <strong id="pattern-display">—</strong>
                    <button type="button" onclick="clearPattern()" style="border:none;background:none;color:#dc2626;cursor:pointer;margin-left:8px;text-decoration:underline;">Limpiar</button>
                </div>
                <input class="os-input" type="text" id="pin_manual" placeholder="PIN o contraseña escrita opcional" oninput="setManualPassword(this.value)">
                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-secondary" onclick="prevStep(8)">Atrás</button>
                    <button type="button" class="os-btn os-btn-primary" onclick="nextStep(8, false)">Continuar</button>
                </div>
            </div>

            {{-- Paso 9: guarda estado_fisico, accesorios_entregados, anticipo y metodo_pago_anticipo en la orden. --}}
            <div class="os-step" id="step-9">
                <div class="os-step-label">Paso 9 de 9</div>
                <div class="os-step-title">Estado físico y anticipo</div>
                <label class="os-hint" for="estado_fisico" style="display:block;text-transform:uppercase;font-weight:800;margin-bottom:0.4rem;">Estado físico del dispositivo *</label>
                <textarea class="os-textarea" name="estado_fisico" id="estado_fisico" placeholder="Golpes, rayones, pantalla rota..." required>{{ old('estado_fisico') }}</textarea>
                <label class="os-hint" for="accesorios_entregados" style="display:block;text-transform:uppercase;font-weight:800;margin:0.75rem 0 0.4rem;">Accesorios que entrega</label>
                <input class="os-input" type="text" name="accesorios_entregados" id="accesorios_entregados" value="{{ old('accesorios_entregados') }}" placeholder="Cargador, funda, audífonos... (opcional)">
                <label class="os-hint" for="anticipo" style="display:block;text-transform:uppercase;font-weight:800;margin:0.75rem 0 0.4rem;">Anticipo recibido ($)</label>
                <input class="os-input" type="number" name="anticipo" id="anticipo" value="{{ old('anticipo', 0) }}" min="0" step="0.01" placeholder="0.00" oninput="toggleMetodoAnticipo(); updateResumen();">

                {{-- Este campo oculto guarda el método seleccionado y se conecta con ordenes_servicio.metodo_pago_anticipo. --}}
                <input type="hidden" name="metodo_pago_anticipo" id="metodo_pago_anticipo" value="{{ old('metodo_pago_anticipo', 'efectivo') }}">

                <div id="metodo_pago_wrap" style="display:none;">
                    <div class="os-hint" style="text-transform:uppercase;font-weight:800;margin-top:0.75rem;">Método de pago</div>
                    <div class="payment-options">
                        <button type="button" class="payment-btn selected" id="metodo-efectivo" onclick="selectMetodoAnticipo('efectivo')">Efectivo</button>
                        <button type="button" class="payment-btn" id="metodo-transferencia" onclick="selectMetodoAnticipo('transferencia')">Transferencia</button>
                        <button type="button" class="payment-btn" id="metodo-tarjeta" onclick="selectMetodoAnticipo('tarjeta')">Tarjeta</button>
                    </div>
                </div>

                <div class="os-summary">
                    <div class="os-summary-title">Resumen</div>
                    <div>Cliente: <strong id="resumen_cliente">—</strong></div>
                    <div>Teléfono: <strong id="resumen_telefono">—</strong></div>
                    <div>Tipo: <strong id="resumen_tipo">—</strong></div>
                    <div>Marca / Modelo: <strong id="resumen_equipo">—</strong></div>
                    <div>Anticipo: <strong id="resumen_anticipo">$0.00</strong></div>
                </div>

                <div class="os-actions">
                    <button type="button" class="os-btn os-btn-secondary" onclick="prevStep(9)">Atrás</button>
                    <button type="submit" class="os-btn os-btn-save">Guardar Orden de Servicio</button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
    const totalSteps = 9;
    const buscarClienteUrl = @json(route('ordenes.buscarClientePorTelefono'));
    let patternSequence = [];
    let telefonoClienteCargado = '';

    /*
     * Abre o cierra la búsqueda de Cliente anterior.
     * Se conecta con el botón del primer paso sin abandonar la Nueva OS.
     */
    function toggleClienteAnterior() {
        const panel = document.getElementById('returning-client-panel');
        panel.classList.toggle('visible');

        if (panel.classList.contains('visible')) {
            document.getElementById('buscar_cliente_telefono').focus();
        }
    }

    /*
     * Convierte el teléfono al mismo formato canónico utilizado por Laravel.
     * Sirve para detectar si el usuario modifica el teléfono después de cargar al cliente.
     */
    function normalizarTelefonoCliente(telefono) {
        return (telefono || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
    }

    /*
     * Muestra un paso específico y actualiza la barra del asistente.
     * Se usa para saltar los datos personales cuando ya pertenecen a un cliente anterior.
     */
    function mostrarPaso(step) {
        document.querySelectorAll('.os-step').forEach(function(item) {
            item.classList.remove('active');
        });
        document.getElementById('step-' + step).classList.add('active');
        buildProgress(step);
    }

    /*
     * Busca al cliente por teléfono, llena nombre y contactos, y conserva su cliente_id.
     * Se conecta con OrdenServicioController::buscarClientePorTelefono.
     */
    async function buscarClienteAnterior() {
        const telefono = document.getElementById('buscar_cliente_telefono').value.trim();
        const estado = document.getElementById('returning-client-status');
        const boton = document.getElementById('buscar-cliente-btn');

        if (!telefono) {
            estado.className = 'returning-client-status error';
            estado.textContent = 'Ingresa el teléfono principal del cliente.';
            return;
        }

        estado.className = 'returning-client-status loading';
        estado.textContent = 'Buscando cliente...';
        boton.disabled = true;

        try {
            const respuesta = await fetch(buscarClienteUrl + '?' + new URLSearchParams({ telefono }), {
                headers: {
                    'Accept': 'application/json',
                },
            });
            const datos = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(datos.message || 'No fue posible buscar al cliente.');
            }

            const cliente = datos.cliente;
            document.getElementById('cliente_id').value = cliente.id;
            document.getElementById('cliente_nombre').value = cliente.nombre || '';
            document.getElementById('cliente_telefono').value = cliente.telefono_principal || '';
            document.getElementById('cliente_telefono_extra').value = cliente.telefono_alternativo || '';
            telefonoClienteCargado = normalizarTelefonoCliente(cliente.telefono_principal);

            const confirmacion = document.getElementById('returning-client-confirmation');
            confirmacion.textContent = 'Cliente anterior: ' + cliente.nombre
                + ' · ' + cliente.servicios_anteriores + ' servicio(s) registrado(s).';
            confirmacion.classList.add('visible');

            estado.className = 'returning-client-status';
            estado.textContent = '';
            mostrarPaso(4);
            document.getElementById('tipo_dispositivo').focus();
        } catch (error) {
            document.getElementById('cliente_id').value = '';
            estado.className = 'returning-client-status error';
            estado.textContent = error.message;
        } finally {
            boton.disabled = false;
        }
    }

    /*
     * Construye la barra de progreso del asistente.
     * Se conecta con nextStep() y prevStep() para indicar en qué pregunta va la Nueva OS.
     */
    function buildProgress(currentStep) {
        const progress = document.getElementById('progress');
        const label = document.getElementById('progress-label');
        progress.style.width = ((currentStep / totalSteps) * 100) + '%';
        label.textContent = 'Paso ' + currentStep + ' de ' + totalSteps;
    }

    /*
     * Avanza al siguiente paso validando campos obligatorios.
     * Se conecta con los botones Continuar de cada pregunta.
     */
    function nextStep(currentStep, required = true) {
        const current = document.getElementById('step-' + currentStep);
        const fields = current.querySelectorAll('input[required], textarea[required], select[required]');

        if (required) {
            for (const field of fields) {
                if (!field.value.trim()) {
                    field.focus();
                    field.style.borderColor = '#dc2626';
                    setTimeout(() => field.style.borderColor = '', 1200);
                    return;
                }
            }
        }

        current.classList.remove('active');
        const next = document.getElementById('step-' + (currentStep + 1));
        next.classList.add('active');
        buildProgress(currentStep + 1);

        if (currentStep + 1 === 9) {
            updateResumen();
            toggleMetodoAnticipo();
        }

        // Coloca el cursor en el primer campo del nuevo paso para continuar sin usar el mouse.
        const nextField = next.querySelector('input:not([type="hidden"]), textarea, select');
        if (nextField) {
            nextField.focus();
        }
    }

    /*
     * Regresa al paso anterior sin borrar la información capturada.
     * Se conecta con los botones Atrás del asistente de Nueva OS.
     */
    function prevStep(currentStep) {
        document.getElementById('step-' + currentStep).classList.remove('active');
        const previous = document.getElementById('step-' + (currentStep - 1));
        previous.classList.add('active');
        buildProgress(currentStep - 1);

        // Devuelve también el cursor al campo anterior para mantener la navegación por teclado.
        const previousField = previous.querySelector('input:not([type="hidden"]), textarea, select');
        if (previousField) {
            previousField.focus();
        }
    }

    /*
     * Guarda el patrón tocado en el campo contrasena_dispositivo.
     * Se conecta con OrdenServicioController::store para guardar el acceso del dispositivo.
     */
    function togglePatternDot(value) {
        const dot = document.getElementById('pattern-dot-' + value);
        const position = patternSequence.indexOf(value);

        if (position === -1) {
            patternSequence.push(value);
            dot.classList.add('selected');
        } else {
            patternSequence.splice(position, 1);
            dot.classList.remove('selected');
        }

        const password = patternSequence.length ? 'PATRÓN: ' + patternSequence.join('-') : '';
        document.getElementById('contrasena_dispositivo').value = password;
        document.getElementById('pattern-display').textContent = password || '—';
    }

    /*
     * Limpia el patrón capturado.
     * Se conecta con el campo oculto contrasena_dispositivo para evitar guardar un patrón incorrecto.
     */
    function clearPattern() {
        patternSequence = [];
        document.querySelectorAll('.pattern-dot').forEach(dot => dot.classList.remove('selected'));
        document.getElementById('contrasena_dispositivo').value = '';
        document.getElementById('pattern-display').textContent = '—';
    }

    /*
     * Guarda PIN o contraseña escrita cuando no es patrón.
     * Se conecta con el mismo campo contrasena_dispositivo usado por la base de datos.
     */
    function setManualPassword(value) {
        if (value.trim()) {
            clearPattern();
            document.getElementById('contrasena_dispositivo').value = value.trim();
        }
    }

    /*
     * Muestra método de pago solo cuando hay anticipo.
     * Se conecta con anticipo y metodo_pago_anticipo en ordenes_servicio.
     */
    function toggleMetodoAnticipo() {
        const anticipo = Number(document.getElementById('anticipo').value || 0);
        document.getElementById('metodo_pago_wrap').style.display = anticipo > 0 ? 'block' : 'none';
    }

    /*
     * Selecciona el método de pago del anticipo.
     * Se conecta con el campo oculto metodo_pago_anticipo que guarda OrdenServicioController::store.
     */
    function selectMetodoAnticipo(method) {
        document.getElementById('metodo_pago_anticipo').value = method;
        ['efectivo', 'transferencia', 'tarjeta'].forEach(function(item) {
            document.getElementById('metodo-' + item).classList.toggle('selected', item === method);
        });
    }

    /*
     * Actualiza el resumen final del servicio antes de guardarlo.
     * Se conecta con los campos capturados en pasos anteriores para verificar la OS completa.
     */
    function updateResumen() {
        const cliente = document.getElementById('cliente_nombre').value || '—';
        const telefono = document.getElementById('cliente_telefono').value || '—';
        const tipo = document.getElementById('tipo_dispositivo').value || '—';
        const marca = document.getElementById('marca').value || '';
        const modelo = document.getElementById('modelo').value || '';
        const anticipo = Number(document.getElementById('anticipo').value || 0);

        document.getElementById('resumen_cliente').textContent = cliente;
        document.getElementById('resumen_telefono').textContent = telefono;
        document.getElementById('resumen_tipo').textContent = tipo;
        document.getElementById('resumen_equipo').textContent = [marca, modelo].filter(Boolean).join(' ') || '—';
        document.getElementById('resumen_anticipo').textContent = '$' + anticipo.toFixed(2);
    }

    /*
     * Permite buscar con Enter sin enviar accidentalmente toda la Orden de Servicio.
     */
    document.getElementById('buscar_cliente_telefono').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            buscarClienteAnterior();
        }
    });

    /*
     * Avanza con Enter en los pasos 1 al 8 y se conecta con nextStep().
     * En campos de texto largo, Shift + Enter conserva la función de salto de línea.
     * El paso 9 no se envía automáticamente para evitar guardar una OS por accidente.
     */
    document.getElementById('osForm').addEventListener('keydown', function(event) {
        if (event.key !== 'Enter' || event.isComposing || event.defaultPrevented) {
            return;
        }

        const field = event.target;
        if (!field.matches('input:not([type="hidden"]), textarea, select')) {
            return;
        }

        // La búsqueda de Cliente anterior tiene su propia acción al presionar Enter.
        if (field.id === 'buscar_cliente_telefono') {
            return;
        }

        const activeStep = field.closest('.os-step.active');
        if (!activeStep) {
            return;
        }

        const currentStep = Number(activeStep.id.replace('step-', ''));
        if (!currentStep || currentStep >= totalSteps) {
            return;
        }

        if (field.tagName === 'TEXTAREA' && event.shiftKey) {
            return;
        }

        event.preventDefault();
        const optionalStep = currentStep === 3 || currentStep === 8;
        nextStep(currentStep, !optionalStep);
    });

    /*
     * Si cambia el teléfono recuperado, elimina cliente_id para impedir una relación equivocada.
     * El servidor volverá a buscar por el nuevo teléfono normalizado al guardar.
     */
    document.getElementById('cliente_telefono').addEventListener('input', function() {
        if (telefonoClienteCargado
            && normalizarTelefonoCliente(this.value) !== telefonoClienteCargado) {
            document.getElementById('cliente_id').value = '';
            document.getElementById('returning-client-confirmation').classList.remove('visible');
            telefonoClienteCargado = '';
        }
    });

    mostrarPaso(@json($pasoInicialOs));
    selectMetodoAnticipo(document.getElementById('metodo_pago_anticipo').value || 'efectivo');
    toggleMetodoAnticipo();
</script>

@endsection
