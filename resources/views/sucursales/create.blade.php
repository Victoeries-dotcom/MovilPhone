@extends('layout')

@section('content')

<style>
    /* Capa tipo modal: se conecta con el formulario de sucursales para dar la misma experiencia que Ventas. */
    .sucursal-wizard-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
    }

    /* Caja del asistente: contiene pasos, errores y campos que guarda SucursalController. */
    .sucursal-wizard-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 2.5rem;
        width: 100%;
        max-width: 560px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: sucursalSlideUp 0.25s ease;
    }

    @keyframes sucursalSlideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    /* Cada paso se oculta hasta que el JavaScript lo marca como activo. */
    .sucursal-step { display: none; }
    .sucursal-step.active { display: block; }

    /* Barra de avance: indica visualmente en qué paso va el registro. */
    .sucursal-progress {
        display: flex;
        gap: 8px;
        margin-bottom: 2rem;
    }

    .sucursal-dot {
        height: 4px;
        flex: 1;
        border-radius: 4px;
        background: #e2e8f0;
    }

    .sucursal-dot.done { background: #0f1f3d; }

    .sucursal-label {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 0.5rem;
    }

    .sucursal-title {
        font-size: 22px;
        font-weight: 700;
        color: #0f1f3d;
        margin-bottom: 1.5rem;
    }

    /* Campos del asistente: se conectan con la tabla sucursales. */
    .sucursal-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 16px;
        font-family: inherit;
        outline: none;
        box-sizing: border-box;
        margin-bottom: 0.85rem;
    }

    .sucursal-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }

    .sucursal-help {
        color: #64748b;
        font-size: 13px;
        margin: -0.35rem 0 0.85rem;
    }

    .sucursal-btn {
        width: 100%;
        padding: 12px;
        background: #0f1f3d;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 0.5rem;
        font-family: inherit;
    }

    .sucursal-btn:hover { background: #1e3a8a; }

    .sucursal-back {
        background: none;
        border: none;
        color: #64748b;
        font-size: 13px;
        cursor: pointer;
        margin-top: 0.75rem;
        width: 100%;
        padding: 6px;
        font-family: inherit;
    }

    .sucursal-back:hover { color: #0f1f3d; }

    /* Resumen final: muestra lo que se enviará antes de guardar la sucursal. */
    .sucursal-resumen {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 14px;
        margin-bottom: 1rem;
    }
</style>

<div class="page-header">
    <h1>Nueva Sucursal</h1>
    <a href="{{ route('sucursales.index') }}" class="btn">← Volver</a>
</div>

<div class="sucursal-wizard-overlay">
    <div class="sucursal-wizard-box">
        <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
            <a href="{{ route('sucursales.index') }}"
               style="background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;text-decoration:none;line-height:1;"
               title="Cerrar">×</a>
        </div>

        <div class="sucursal-progress" id="sucursalProgress"></div>

        <form method="POST" action="{{ route('sucursales.store') }}" id="sucursalForm">
            @csrf

            {{-- PASO 1: Nombre de sucursal; se guarda en sucursales.nombre. --}}
            <div class="sucursal-step active" id="sucursal-step-1">
                <div class="sucursal-label">Paso 1 de 4</div>
                <div class="sucursal-title">¿Nombre de la sucursal?</div>
                <input class="sucursal-input" type="text" name="nombre" id="s_nombre"
                    value="{{ old('nombre') }}" placeholder="Ej. Valladolid, Yucatán" autocomplete="off" required>
                <button type="button" class="sucursal-btn" onclick="sucursalNext(1)">Continuar →</button>
            </div>

            {{-- PASO 2: Ubicación física y URL; se conectan con sucursales.ubicacion y sucursales.ubicacion_url. --}}
            <div class="sucursal-step" id="sucursal-step-2">
                <div class="sucursal-label">Paso 2 de 4</div>
                <div class="sucursal-title">Ubicación</div>
                <input class="sucursal-input" type="text" name="ubicacion" id="s_ubicacion"
                    value="{{ old('ubicacion') }}" placeholder="Ej. Calle 27 x 34 x 35, Izamal">
                <input class="sucursal-input" type="url" name="ubicacion_url" id="s_ubicacion_url"
                    value="{{ old('ubicacion_url') }}" placeholder="Ej. https://maps.app.goo.gl/..." data-no-mayusculas>
                <div class="sucursal-help">La URL no se convierte a mayúsculas para conservar el enlace exacto de Google Maps.</div>
                <button type="button" class="sucursal-btn" onclick="sucursalNext(2)">Continuar →</button>
                <button type="button" class="sucursal-back" onclick="sucursalPrev(2)">← Atrás</button>
            </div>

            {{-- PASO 3: Encargado; se conecta con nombre_encargado, telefono_encargado y horario. --}}
            <div class="sucursal-step" id="sucursal-step-3">
                <div class="sucursal-label">Paso 3 de 4</div>
                <div class="sucursal-title">Datos del encargado</div>
                <input class="sucursal-input" type="text" name="nombre_encargado" id="s_encargado"
                    value="{{ old('nombre_encargado') }}" placeholder="Ej. Alex Pérez">
                <input class="sucursal-input" type="tel" name="telefono_encargado" id="s_telefono"
                    value="{{ old('telefono_encargado') }}" placeholder="Ej. 999-123-4567">
                <input class="sucursal-input" type="text" name="horario" id="s_horario"
                    value="{{ old('horario') }}" placeholder="Ej. Lun-Vie 9:00am - 7:00pm">
                <button type="button" class="sucursal-btn" onclick="sucursalPrepararResumen()">Continuar →</button>
                <button type="button" class="sucursal-back" onclick="sucursalPrev(3)">← Atrás</button>
            </div>

            {{-- PASO 4: Confirmación final antes de guardar en SucursalController@store. --}}
            <div class="sucursal-step" id="sucursal-step-4">
                <div class="sucursal-label">Paso 4 de 4</div>
                <div class="sucursal-title">Confirmar sucursal</div>
                <div class="sucursal-resumen">
                    <div><strong>Sucursal:</strong> <span id="r_nombre"></span></div>
                    <div><strong>Dirección:</strong> <span id="r_ubicacion"></span></div>
                    <div><strong>Maps:</strong> <span id="r_url"></span></div>
                    <div><strong>Encargado:</strong> <span id="r_encargado"></span></div>
                    <div><strong>Teléfono:</strong> <span id="r_telefono"></span></div>
                    <div><strong>Horario:</strong> <span id="r_horario"></span></div>
                </div>
                <button type="submit" class="sucursal-btn">Guardar sucursal</button>
                <button type="button" class="sucursal-back" onclick="sucursalPrev(4)">← Atrás</button>
            </div>
        </form>
    </div>
</div>

<script>
const sucursalTotalSteps = 4;

/* Dibuja la barra de progreso del registro de sucursal. */
function sucursalBuildProgress(current) {
    const progress = document.getElementById('sucursalProgress');
    progress.innerHTML = '';

    for (let i = 1; i <= sucursalTotalSteps; i++) {
        const dot = document.createElement('div');
        dot.className = 'sucursal-dot' + (i < current ? ' done' : '');
        progress.appendChild(dot);
    }
}

/* Revisa campos obligatorios antes de avanzar de paso. */
function sucursalValidarPaso(stepNumber) {
    const step = document.getElementById('sucursal-step-' + stepNumber);
    const fields = step.querySelectorAll('input[required], select[required], textarea[required]');

    for (const field of fields) {
        if (!field.value.trim()) {
            field.focus();
            field.style.borderColor = '#dc2626';
            setTimeout(() => field.style.borderColor = '', 1500);
            return false;
        }
    }

    return true;
}

/* Avanza al siguiente paso y mantiene el flujo tipo Ventas. */
function sucursalNext(current) {
    if (!sucursalValidarPaso(current)) return;

    document.getElementById('sucursal-step-' + current).classList.remove('active');
    document.getElementById('sucursal-step-' + (current + 1)).classList.add('active');
    sucursalBuildProgress(current + 1);

    const next = document.getElementById('sucursal-step-' + (current + 1));
    const firstInput = next.querySelector('input, select, textarea');
    if (firstInput) setTimeout(() => firstInput.focus(), 100);
}

/* Regresa al paso anterior sin borrar lo capturado. */
function sucursalPrev(current) {
    document.getElementById('sucursal-step-' + current).classList.remove('active');
    document.getElementById('sucursal-step-' + (current - 1)).classList.add('active');
    sucursalBuildProgress(current - 1);
}

/* Llena el resumen con los datos que se enviarán al controlador. */
function sucursalPrepararResumen() {
    if (!sucursalValidarPaso(3)) return;

    document.getElementById('r_nombre').textContent = document.getElementById('s_nombre').value || '-';
    document.getElementById('r_ubicacion').textContent = document.getElementById('s_ubicacion').value || '-';
    document.getElementById('r_url').textContent = document.getElementById('s_ubicacion_url').value || '-';
    document.getElementById('r_encargado').textContent = document.getElementById('s_encargado').value || '-';
    document.getElementById('r_telefono').textContent = document.getElementById('s_telefono').value || '-';
    document.getElementById('r_horario').textContent = document.getElementById('s_horario').value || '-';

    sucursalNext(3);
}

document.addEventListener('keydown', function(event) {
    if (event.key !== 'Enter') return;

    const active = document.querySelector('.sucursal-step.active');
    if (!active) return;

    const stepNumber = parseInt(active.id.replace('sucursal-step-', ''), 10);
    if (stepNumber < sucursalTotalSteps) {
        event.preventDefault();
        stepNumber === 3 ? sucursalPrepararResumen() : sucursalNext(stepNumber);
    }
});

sucursalBuildProgress(1);
</script>

@endsection
