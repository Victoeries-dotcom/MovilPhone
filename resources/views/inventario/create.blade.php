@extends('layout')

@section('content')

<style>
/* Permite desplazar el asistente cuando un paso supera la altura de la pantalla. */
.wizard-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; align-items: center;
    justify-content: center; z-index: 1000; overflow-y: auto;
    padding: 1rem; box-sizing: border-box;
}
.wizard-box {
    background: white; border-radius: 16px; padding: 2.5rem;
    width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: slideUp 0.3s ease; max-height: calc(100vh - 2rem);
    overflow-y: auto; box-sizing: border-box;
}
@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.wizard-step { display: none; }
.wizard-step.active { display: block; }
.wizard-label {
    font-size: 13px; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.5rem;
}
.wizard-title {
    font-size: 22px; font-weight: 700; color: #0f1f3d;
    margin-bottom: 1.5rem; letter-spacing: -0.02em;
}
.wizard-input {
    width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0;
    border-radius: 10px; font-size: 16px; font-family: inherit;
    transition: border-color 0.15s; outline: none; box-sizing: border-box;
}
.wizard-input:focus { border-color: #3b82f6; }
.wizard-btn {
    width: 100%; padding: 12px; background: #0f1f3d; color: white;
    border: none; border-radius: 10px; font-size: 15px; font-weight: 600;
    cursor: pointer; margin-top: 1rem; font-family: inherit;
    transition: background 0.15s;
}
.wizard-btn:hover { background: #1e3a8a; }
.wizard-back {
    background: none; border: none; color: #64748b; font-size: 13px;
    cursor: pointer; margin-top: 0.75rem; font-family: inherit;
    width: 100%; text-align: center; padding: 6px;
}
.wizard-back:hover { color: #0f1f3d; }
.wizard-progress {
    display: flex; gap: 6px; margin-bottom: 2rem;
}
.wizard-dot {
    height: 4px; flex: 1; border-radius: 4px; background: #e2e8f0;
    transition: background 0.3s;
}
.wizard-dot.done { background: #0f1f3d; }

/* Categorías */
.cat-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    margin-bottom: 0.5rem;
}
.cat-btn {
    padding: 1rem; border: 2px solid #e2e8f0; border-radius: 10px;
    background: white; cursor: pointer; font-family: inherit;
    font-size: 13.5px; font-weight: 600; color: #1e293b;
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    transition: all 0.15s;
}
.cat-btn:hover { border-color: #0f1f3d; background: #f8fafc; }
.cat-btn.selected { border-color: #0f1f3d; background: #0f1f3d; color: white; }
.cat-icon { font-size: 24px; }
/* Muestra el campo manual únicamente cuando la persona elige la categoría "Otro". */
.cat-custom { margin-top:1rem; padding-top:1rem; border-top:1px solid #e2e8f0; }
.cat-custom[hidden] { display:none; }
.cat-custom-label { display:block; margin-bottom:6px; color:#64748b; font-size:13px; font-weight:700; }

/* Calidad */
.cal-grid {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;
    margin-bottom: 0.5rem;
}
.cal-btn {
    padding: 0.85rem 0.5rem; border: 2px solid #e2e8f0; border-radius: 10px;
    background: white; cursor: pointer; font-family: inherit;
    font-size: 13px; font-weight: 600; color: #1e293b;
    display: flex; flex-direction: column; align-items: center; gap: 5px;
    transition: all 0.15s;
}
.cal-btn:hover { border-color: #0f1f3d; background: #f8fafc; }
.cal-btn.selected { border-color: #0f1f3d; background: #0f1f3d; color: white; }
</style>

<div class="page-header">
    <h1>Agregar Pieza</h1>
    <a href="{{ route('inventario.index') }}" class="btn">← Volver</a>
</div>

<div class="wizard-overlay" id="wizard">
    <div class="wizard-box">
        <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
            <a href="{{ route('inventario.index') }}" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;text-decoration:none;line-height:1;" title="Cerrar">✕</a>
        </div>

        <div class="wizard-progress" id="progress"></div>

        <form method="POST" action="{{ route('inventario.store') }}" id="wizardForm">
            @csrf

            {{-- PASO 1: Nombre de la pieza --}}
            <div class="wizard-step active" id="step-1">
                <div class="wizard-label">Paso 1 de 9</div>
                <div class="wizard-title">¿Nombre de la pieza?</div>
                <input class="wizard-input" type="text" name="nombre" id="f_nombre"
                    placeholder="Ej. Pantalla iPhone 13 OLED" autocomplete="off" required/>
                <button type="button" class="wizard-btn" onclick="nextStep(1)">Continuar →</button>
            </div>

            {{-- PASO 2: Categoría --}}
            <div class="wizard-step" id="step-2">
                <div class="wizard-label">Paso 2 de 9</div>
                <div class="wizard-title">¿Categoría de la pieza?</div>
                <input type="hidden" name="categoria" id="f_categoria"/>
                <div class="cat-grid">
                    <button type="button" class="cat-btn" onclick="selectCat(this, 'Telefono')">
                        <span class="cat-icon">📱</span> Teléfono
                    </button>
                    <button type="button" class="cat-btn" onclick="selectCat(this, 'Batería')">
                        <span class="cat-icon">🔋</span> Baterías
                    </button>
                    <button type="button" class="cat-btn" onclick="selectCat(this, 'Flex y Cargadores')">
                        <span class="cat-icon">🔌</span> Flex - Cargadores
                    </button>
                    <button type="button" class="cat-btn" onclick="selectCat(this, 'Piezas')">
                        <span class="cat-icon">⚡</span> Piezas
                    </button>
                    <button type="button" class="cat-btn" onclick="selectCat(this, 'Placa')">
                        <span class="cat-icon">🖥️</span> Placas
                    </button>
                    <button type="button" class="cat-btn" onclick="selectCat(this, 'Accesorio')">
                        <span class="cat-icon">🎧</span> Accesorio
                    </button>
                    <button type="button" class="cat-btn" onclick="selectCat(this, 'Estaño')">
                        <span class="cat-icon">🪄</span> Estaños
                    </button>
                    <button type="button" class="cat-btn" onclick="selectCat(this, 'Otro')" aria-controls="categoria-otro" aria-expanded="false">
                        <span class="cat-icon">📦</span> Otro
                    </button>
                </div>

                {{-- Al elegir "Otro", este campo reemplaza ese texto por la categoría escrita. --}}
                <div class="cat-custom" id="categoria-otro" hidden>
                    <label class="cat-custom-label" for="f_categoria_otro">Nombre de la categoría</label>
                    <input
                        class="wizard-input"
                        type="text"
                        id="f_categoria_otro"
                        placeholder="Ej. Herramientas"
                        maxlength="100"
                        autocomplete="off"
                    />
                    <button type="button" class="wizard-btn" onclick="confirmarCategoriaOtro()">Continuar →</button>
                </div>
                <button type="button" class="wizard-back" onclick="prevStep(2)">← Atrás</button>
            </div>

            {{-- PASO 3: Dispositivo compatible --}}
            <div class="wizard-step" id="step-3">
                <div class="wizard-label">Paso 3 de 9</div>
                <div class="wizard-title">¿Dispositivo compatible?</div>
                <input class="wizard-input" type="text" name="dispositivo_compatible" id="f_dispositivo"
                    placeholder="Ej. iPhone 13, Galaxy A54" autocomplete="off"/>
                <button type="button" class="wizard-btn" onclick="nextStep(3)">Continuar →</button>
                <button type="button" class="wizard-back" onclick="prevStep(3)">← Atrás</button>
            </div>

            {{-- PASO 4: Calidad --}}
            <div class="wizard-step" id="step-4">
                <div class="wizard-label">Paso 4 de 9</div>
                <div class="wizard-title">¿Calidad de la pieza?</div>
                <input type="hidden" name="calidad" id="f_calidad"/>
                <div class="cal-grid">
                    <button type="button" class="cal-btn" onclick="selectCal(this, 'Original')">
                        <span class="cat-icon">⭐</span> Original
                    </button>
                    <button type="button" class="cal-btn" onclick="selectCal(this, 'Compatible')">
                        <span class="cat-icon">✅</span> Compatible
                    </button>
                    <button type="button" class="cal-btn" onclick="selectCal(this, 'Remanufacturada')">
                        <span class="cat-icon">♻️</span> Remanufacturada
                    </button>
                </div>
                <button type="button" class="wizard-back" onclick="prevStep(4)">← Atrás</button>
            </div>

            {{-- PASO 5: Proveedor --}}
            <div class="wizard-step" id="step-5">
                <div class="wizard-label">Paso 5 de 9</div>
                <div class="wizard-title">¿Nombre del proveedor?</div>
                <input class="wizard-input" type="text" name="proveedor" id="f_proveedor"
                    placeholder="Opcional — presiona continuar si no aplica" autocomplete="off"/>
                <button type="button" class="wizard-btn" onclick="nextStep(5)">Continuar →</button>
                <button type="button" class="wizard-back" onclick="prevStep(5)">← Atrás</button>
            </div>

            {{-- PASO 6: Precio de costo --}}
            <div class="wizard-step" id="step-6">
                <div class="wizard-label">Paso 6 de 9</div>
                <div class="wizard-title">¿Precio de costo?</div>
                <input class="wizard-input" type="number" name="precio_costo" id="f_costo"
                    placeholder="0.00" min="0" step="0.01" required/>
                <button type="button" class="wizard-btn" onclick="nextStep(6)">Continuar →</button>
                <button type="button" class="wizard-back" onclick="prevStep(6)">← Atrás</button>
            </div>

            {{-- PASO 7: Precio de venta --}}
            <div class="wizard-step" id="step-7">
                <div class="wizard-label">Paso 7 de 9</div>
                <div class="wizard-title">¿Precio de venta?</div>
                <input class="wizard-input" type="number" name="precio_venta" id="f_venta"
                    placeholder="0.00" min="0" step="0.01" required/>
                <button type="button" class="wizard-btn" onclick="nextStep(7)">Continuar →</button>
                <button type="button" class="wizard-back" onclick="prevStep(7)">← Atrás</button>
            </div>

            {{-- PASO 8: Cantidad disponible --}}
            <div class="wizard-step" id="step-8">
                <div class="wizard-label">Paso 8 de 9</div>
                <div class="wizard-title">¿Cantidad disponible?</div>
                <input class="wizard-input" type="number" name="cantidad_disponible" id="f_cantidad"
                    placeholder="0" min="0" value="0" required/>
                <button type="button" class="wizard-btn" onclick="nextStep(8)">Continuar →</button>
                <button type="button" class="wizard-back" onclick="prevStep(8)">← Atrás</button>
            </div>

            {{-- PASO 9: Stock mínimo y sucursal --}}
            <div class="wizard-step" id="step-9">
                <div class="wizard-label">Paso 9 de 9</div>
                <div class="wizard-title">¿Stock mínimo y sucursal?</div>

                <label style="font-size:13px;font-weight:600;color:#64748b;display:block;margin-bottom:6px;">Stock mínimo</label>
                <input class="wizard-input" type="number" name="stock_minimo" id="f_stock"
                    placeholder="2" min="0" value="2" required style="margin-bottom:1rem;"/>

                <label style="font-size:13px;font-weight:600;color:#64748b;display:block;margin-bottom:6px;">Sucursal</label>
                <select class="wizard-input" name="sucursal_id" required>
                    <option value="">— Selecciona —</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}" {{ old('sucursal_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>

                <button type="submit" class="wizard-btn" style="background:#16a34a; margin-top:1.25rem;">
                    ✅ Guardar Pieza
                </button>
                <button type="button" class="wizard-back" onclick="prevStep(9)">← Atrás</button>
            </div>

        </form>
    </div>
</div>

<script>
const totalSteps = 9;

function buildProgress(current) {
    const p = document.getElementById('progress');
    p.innerHTML = '';
    for (let i = 1; i <= totalSteps; i++) {
        const d = document.createElement('div');
        d.className = 'wizard-dot' + (i < current ? ' done' : '');
        p.appendChild(d);
    }
}

function nextStep(current) {
    const step = document.getElementById('step-' + current);
    const inputs = step.querySelectorAll('input[required], select[required], textarea[required]');
    for (let inp of inputs) {
        if (!inp.value.toString().trim()) {
            inp.focus();
            inp.style.borderColor = '#dc2626';
            setTimeout(() => inp.style.borderColor = '', 1500);
            return;
        }
    }
    document.getElementById('step-' + current).classList.remove('active');
    document.getElementById('step-' + (current + 1)).classList.add('active');
    buildProgress(current + 1);
    const next = document.getElementById('step-' + (current + 1));
    const firstInput = next.querySelector('input:not([type=hidden]), select, textarea');
    if (firstInput) setTimeout(() => firstInput.focus(), 100);
}

function prevStep(current) {
    document.getElementById('step-' + current).classList.remove('active');
    document.getElementById('step-' + (current - 1)).classList.add('active');
    buildProgress(current - 1);
}

function selectCat(btn, value) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    const categoria = document.getElementById('f_categoria');
    const panelOtro = document.getElementById('categoria-otro');
    const campoOtro = document.getElementById('f_categoria_otro');
    const esOtro = value === 'Otro';

    // "Otro" abre la captura manual; las categorías existentes avanzan inmediatamente.
    document.querySelectorAll('.cat-btn[aria-controls="categoria-otro"]').forEach(b => {
        b.setAttribute('aria-expanded', esOtro ? 'true' : 'false');
    });
    panelOtro.hidden = !esOtro;

    if (esOtro) {
        categoria.value = '';
        setTimeout(() => campoOtro.focus(), 100);
        return;
    }

    campoOtro.value = '';
    categoria.value = value;
    setTimeout(() => nextStep(2), 300);
}

/*
 * Valida la categoría escrita y la copia al campo enviado al InventarioController.
 * El controlador también la sincroniza con la tabla categorias al guardar la pieza.
 */
function confirmarCategoriaOtro() {
    const campoOtro = document.getElementById('f_categoria_otro');
    const categoriaEscrita = campoOtro.value.trim();

    if (!categoriaEscrita) {
        campoOtro.focus();
        campoOtro.style.borderColor = '#dc2626';
        setTimeout(() => campoOtro.style.borderColor = '', 1500);
        return;
    }

    document.getElementById('f_categoria').value = categoriaEscrita;
    nextStep(2);
}

function selectCal(btn, value) {
    document.querySelectorAll('.cal-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('f_calidad').value = value;
    setTimeout(() => nextStep(4), 300);
}

// Enter para avanzar
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const active = document.querySelector('.wizard-step.active');
        if (!active) return;
        const stepNum = parseInt(active.id.replace('step-', ''));

        // En Categoría, Enter selecciona el botón enfocado o confirma el texto de "Otro".
        if (stepNum === 2) {
            e.preventDefault();
            if (e.target.classList.contains('cat-btn')) {
                e.target.click();
            } else if (!document.getElementById('categoria-otro').hidden) {
                confirmarCategoriaOtro();
            }
            return;
        }

        if (stepNum < totalSteps) {
            e.preventDefault();
            nextStep(stepNum);
        }
    }
});

buildProgress(1);
</script>

@endsection
