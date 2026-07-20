@extends('layout')

@section('content')

<style>
    /* Capa tipo modal: se conecta con el asistente de registro para centrarlo como en Ventas. */
    .usuario-wizard-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
    }

    /* Caja principal del asistente: contiene pasos, errores y formulario de usuario. */
    .usuario-wizard-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 2.5rem;
        width: 100%;
        max-width: 560px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: usuarioSlideUp 0.25s ease;
    }

    @keyframes usuarioSlideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    /* Cada paso se oculta y solo se muestra el paso activo. */
    .usuario-step { display: none; }
    .usuario-step.active { display: block; }

    /* Barra de avance: marca visualmente en qué paso va el registro. */
    .usuario-progress {
        display: flex;
        gap: 8px;
        margin-bottom: 2rem;
    }

    .usuario-dot {
        height: 4px;
        flex: 1;
        border-radius: 4px;
        background: #e2e8f0;
    }

    .usuario-dot.done { background: #0f1f3d; }

    .usuario-label {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 0.5rem;
    }

    .usuario-title {
        font-size: 22px;
        font-weight: 700;
        color: #0f1f3d;
        margin-bottom: 1.5rem;
    }

    /* Inputs del wizard: se conectan con UsuarioController para guardar users. */
    .usuario-input {
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

    .usuario-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }

    .usuario-btn {
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

    .usuario-btn:hover { background: #1e3a8a; }

    .usuario-back {
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

    .usuario-back:hover { color: #0f1f3d; }

    /* Resumen final: confirma lo que se enviará al controlador de usuarios. */
    .usuario-resumen {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 14px;
        margin-bottom: 1rem;
    }
</style>

<div class="page-header">
    <div>
        <h1>Nuevo Usuario</h1>
        @if(session('sucursal_nombre'))
            <div class="page-title-sub">Sucursal activa: {{ session('sucursal_nombre') }}</div>
        @endif
    </div>
    <a href="{{ route('usuarios.index') }}" class="btn">← Volver</a>
</div>

<div class="usuario-wizard-overlay">
    <div class="usuario-wizard-box">

        {{-- Muestra errores enviados por UsuarioController, como email duplicado o sucursal faltante. --}}
        @if($errors->any())
            <div class="alert alert-error" style="margin-bottom:1rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
            <a href="{{ route('usuarios.index') }}"
               style="background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;text-decoration:none;line-height:1;"
               title="Cerrar">×</a>
        </div>

        <div class="usuario-progress" id="usuarioProgress"></div>

        <form method="POST" action="{{ route('usuarios.store') }}" id="usuarioForm">
            @csrf

            {{-- PASO 1: Nombre del usuario; se guarda en users.name. --}}
            <div class="usuario-step active" id="usuario-step-1">
                <div class="usuario-label">Paso 1 de 4</div>
                <div class="usuario-title">¿Nombre del usuario?</div>
                <input class="usuario-input" type="text" name="name" id="u_name"
                    value="{{ old('name') }}" placeholder="Nombre completo" autocomplete="off" required>
                <button type="button" class="usuario-btn" onclick="usuarioNext(1)">Continuar →</button>
            </div>

            {{-- PASO 2: Contacto; email se conecta con users.email y telefono con users.telefono. --}}
            <div class="usuario-step" id="usuario-step-2">
                <div class="usuario-label">Paso 2 de 4</div>
                <div class="usuario-title">Datos de contacto</div>
                <input class="usuario-input" type="email" name="email" id="u_email"
                    value="{{ old('email') }}" placeholder="correo@movilphone.com" autocomplete="off" required>
                <input class="usuario-input" type="text" name="telefono" id="u_telefono"
                    value="{{ old('telefono') }}" placeholder="Número telefónico">
                <button type="button" class="usuario-btn" onclick="usuarioNext(2)">Continuar →</button>
                <button type="button" class="usuario-back" onclick="usuarioPrev(2)">← Atrás</button>
            </div>

            {{-- PASO 3: Rol y sucursal; se conectan con users.rol y users.sucursal_id. --}}
            <div class="usuario-step" id="usuario-step-3">
                <div class="usuario-label">Paso 3 de 4</div>
                <div class="usuario-title">Rol y sucursal</div>
                <select class="usuario-input" name="rol" id="u_rol" required>
                    <option value="usuario" {{ old('rol') == 'usuario' ? 'selected' : '' }}>Usuario</option>
                    <option value="superusuario" {{ old('rol') == 'superusuario' ? 'selected' : '' }}>Super Usuario</option>
                    <option value="capturista" {{ old('rol') == 'capturista' ? 'selected' : '' }}>Capturista</option>
                    <option value="vendedor" {{ old('rol') == 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                    <option value="tecnico" {{ old('rol') == 'tecnico' ? 'selected' : '' }}>Tecnico</option>
                </select>
                <select class="usuario-input" name="sucursal_id" id="u_sucursal" required>
                    <option value="">Selecciona una sucursal</option>
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" {{ old('sucursal_id', session('sucursal_id')) == $sucursal->id ? 'selected' : '' }}>
                            {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="usuario-btn" onclick="usuarioPrepararResumen()">Continuar →</button>
                <button type="button" class="usuario-back" onclick="usuarioPrev(3)">← Atrás</button>
            </div>

            {{-- PASO 4: Confirmación final antes de guardar en UsuarioController@store. --}}
            <div class="usuario-step" id="usuario-step-4">
                <div class="usuario-label">Paso 4 de 4</div>
                <div class="usuario-title">Confirmar registro</div>
                <div class="usuario-resumen">
                    <div><strong>Nombre:</strong> <span id="r_name"></span></div>
                    <div><strong>Email:</strong> <span id="r_email"></span></div>
                    <div><strong>Teléfono:</strong> <span id="r_telefono"></span></div>
                    <div><strong>Rol:</strong> <span id="r_rol"></span></div>
                    <div><strong>Sucursal:</strong> <span id="r_sucursal"></span></div>
                </div>
                <button type="submit" class="usuario-btn">Guardar usuario</button>
                <button type="button" class="usuario-back" onclick="usuarioPrev(4)">← Atrás</button>
            </div>
        </form>
    </div>
</div>

<script>
const usuarioTotalSteps = 4;

/* Dibuja la barra de avance del registro de usuario. */
function usuarioBuildProgress(current) {
    const progress = document.getElementById('usuarioProgress');
    progress.innerHTML = '';

    for (let i = 1; i <= usuarioTotalSteps; i++) {
        const dot = document.createElement('div');
        dot.className = 'usuario-dot' + (i < current ? ' done' : '');
        progress.appendChild(dot);
    }
}

/* Valida campos obligatorios del paso actual antes de avanzar. */
function usuarioValidarPaso(stepNumber) {
    const step = document.getElementById('usuario-step-' + stepNumber);
    const fields = step.querySelectorAll('input[required], select[required]');

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

/* Avanza al siguiente paso y enfoca el primer campo disponible. */
function usuarioNext(current) {
    if (!usuarioValidarPaso(current)) return;

    document.getElementById('usuario-step-' + current).classList.remove('active');
    document.getElementById('usuario-step-' + (current + 1)).classList.add('active');
    usuarioBuildProgress(current + 1);

    const next = document.getElementById('usuario-step-' + (current + 1));
    const firstInput = next.querySelector('input, select');
    if (firstInput) setTimeout(() => firstInput.focus(), 100);
}

/* Regresa un paso sin perder los datos escritos. */
function usuarioPrev(current) {
    document.getElementById('usuario-step-' + current).classList.remove('active');
    document.getElementById('usuario-step-' + (current - 1)).classList.add('active');
    usuarioBuildProgress(current - 1);
}

/* Llena el resumen final con los datos que se enviarán al backend. */
function usuarioPrepararResumen() {
    if (!usuarioValidarPaso(3)) return;

    const rol = document.getElementById('u_rol');
    const sucursal = document.getElementById('u_sucursal');

    document.getElementById('r_name').textContent = document.getElementById('u_name').value || '-';
    document.getElementById('r_email').textContent = document.getElementById('u_email').value || '-';
    document.getElementById('r_telefono').textContent = document.getElementById('u_telefono').value || '-';
    document.getElementById('r_rol').textContent = rol.options[rol.selectedIndex].text;
    document.getElementById('r_sucursal').textContent = sucursal.options[sucursal.selectedIndex].text;

    usuarioNext(3);
}

document.addEventListener('keydown', function(event) {
    if (event.key !== 'Enter') return;

    const active = document.querySelector('.usuario-step.active');
    if (!active) return;

    const stepNumber = parseInt(active.id.replace('usuario-step-', ''), 10);
    if (stepNumber < usuarioTotalSteps) {
        event.preventDefault();
        stepNumber === 3 ? usuarioPrepararResumen() : usuarioNext(stepNumber);
    }
});

usuarioBuildProgress(1);
</script>

@endsection
