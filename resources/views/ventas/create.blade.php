@extends('layout')

@section('content')

<style>
.wizard-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; align-items: center;
    justify-content: center; z-index: 1000;
}
.wizard-box {
    background: white; border-radius: 16px; padding: 2.5rem;
    width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: slideUp 0.3s ease;
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

/* Categorías/Productos */
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

/* Lista inventario */
.inv-list {
    max-height: 260px; overflow-y: auto;
    display: flex; flex-direction: column; gap: 8px;
    margin-bottom: 0.75rem;
}
.inv-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 10px;
    cursor: pointer; transition: all 0.15s;
}
.inv-item:hover { border-color: #0f1f3d; background: #f8fafc; }
.inv-item.selected { border-color: #0f1f3d; background: #0f1f3d; color: white; }
.inv-item.selected .inv-price { color: #86efac; }
.inv-price { font-weight: 700; color: #16a34a; font-size: 15px; white-space: nowrap; margin-left: 1rem; }
</style>

<div class="page-header">
    <div>
        <h1>Nueva Venta</h1>
        @if(session('sucursal_nombre'))
            <div class="page-title-sub">Sucursal: {{ session('sucursal_nombre') }}</div>
        @endif
    </div>
    <a href="{{ route('ventas.index') }}" class="btn">← Volver</a>
</div>

<div class="wizard-overlay" id="wizard">
    <div class="wizard-box">

        {{-- Muestra errores enviados por VentaController, por ejemplo cuando la cantidad supera el stock disponible. --}}
        @if($errors->any())
            <div class="alert alert-error" style="margin-bottom:1rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
            <a href="{{ route('ventas.index') }}"
               style="background:none;border:none;font-size:20px;cursor:pointer;
               color:#64748b;text-decoration:none;line-height:1;" title="Cerrar">✕</a>
        </div>

        <div class="wizard-progress" id="progress"></div>

        <form method="POST" action="{{ route('ventas.store') }}" id="wizardForm">
            @csrf

            {{-- PASO 1: Cliente --}}
            <div class="wizard-step active" id="step-1">
                <div class="wizard-label">Paso 1 de 4</div>
                <div class="wizard-title">¿Nombre del cliente?</div>
                <input class="wizard-input" type="text" name="cliente_nombre" id="f_cliente"
                    placeholder="Nombre completo" autocomplete="off" required/>
                <button type="button" class="wizard-btn" onclick="nextStep(1)">Continuar →</button>
            </div>

            {{-- PASO 2: Tipo de producto --}}
            <div class="wizard-step" id="step-2">
                <div class="wizard-label">Paso 2 de 4</div>
                <div class="wizard-title">¿Qué tipo de producto?</div>
                <input type="hidden" name="productos[0][nombre]" id="f_tipo_producto"/>
                <input type="hidden" name="productos[0][inventario_id]" id="f_inventario_id"/>
                <div class="cat-grid">
                    <button type="button" class="cat-btn" onclick="selectTipo(this,'inventario')">
                        <span class="cat-icon">📦</span> Del inventario
                    </button>
                    <button type="button" class="cat-btn" onclick="selectTipo(this,'manual')">
                        <span class="cat-icon">✏️</span> Producto manual
                    </button>
                    <button type="button" class="cat-btn" onclick="selectTipo(this,'servicio')">
                        <span class="cat-icon">🔧</span> Servicio
                    </button>
                    <button type="button" class="cat-btn" onclick="selectTipo(this,'otro')">
                        <span class="cat-icon">➕</span> Otro
                    </button>
                </div>
                <button type="button" class="wizard-back" onclick="prevStep(2)">← Atrás</button>
            </div>

            {{-- PASO 3a: Seleccionar del inventario --}}
            <div class="wizard-step" id="step-3">
                <div class="wizard-label">Paso 3 de 4</div>
                <div class="wizard-title" id="step3-title">Selecciona el producto</div>

                {{-- Vista inventario --}}
                <div id="vista-inventario">
                    <input type="text" class="wizard-input" id="buscador-inv"
                        placeholder="🔍 Buscar…" oninput="filtrarInv()"
                        style="margin-bottom:0.75rem;"/>
                    <div class="inv-list" id="lista-inv">
                        @forelse($inventario ?? [] as $pieza)
                            @if($pieza->cantidad_disponible > 0)
                            <div class="inv-item"
                                onclick="elegirInventario({{ $pieza->id }}, '{{ addslashes($pieza->nombre) }}', {{ $pieza->precio_venta }}, {{ $pieza->cantidad_disponible }})"
                                data-nombre="{{ strtolower($pieza->nombre) }}"
                                data-cat="{{ strtolower($pieza->categoria ?? '') }}">
                                <div>
                                    <div style="font-weight:600;font-size:14px;">{{ $pieza->nombre }}</div>
                                    <div style="font-size:12px;color:#64748b;margin-top:2px;">
                                        {{ $pieza->categoria ?? '—' }}
                                        @if($pieza->dispositivo_compatible) · {{ $pieza->dispositivo_compatible }} @endif
                                        · Stock: {{ $pieza->cantidad_disponible }}
                                    </div>
                                </div>
                                <span class="inv-price">${{ number_format($pieza->precio_venta,2) }}</span>
                            </div>
                            @endif
                        @empty
                            <div style="text-align:center;color:#94a3b8;padding:2rem;font-size:14px;">
                                No hay piezas disponibles
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Vista manual / servicio / otro --}}
                <div id="vista-manual" style="display:none;">
                    <input class="wizard-input" type="text" id="f_nombre_manual"
                        placeholder="Nombre del producto o servicio" style="margin-bottom:0.75rem;"/>
                </div>

                <button type="button" class="wizard-btn" id="btn-continuar-3"
                    onclick="confirmarProducto()" style="display:none;">Continuar →</button>
                <button type="button" class="wizard-back" onclick="prevStep(3)">← Atrás</button>
            </div>

            {{-- PASO 4: Cantidad, precio y notas --}}
            <div class="wizard-step" id="step-4">
                <div class="wizard-label">Paso 4 de 4</div>
                <div class="wizard-title">Detalle de la venta</div>

                <div style="background:#f8fafc;border:2px solid #e2e8f0;border-radius:10px;
                    padding:10px 14px;margin-bottom:1rem;font-size:14px;">
                    <span style="color:#64748b;">Producto:</span>
                    <span id="resumen-producto" style="font-weight:700;color:#0f1f3d;margin-left:6px;"></span>
                </div>

                <label class="wizard-label">Cantidad</label>
                <input class="wizard-input" type="number" name="productos[0][cantidad]" id="f_cantidad"
                    value="1" min="1" required style="margin-bottom:0.75rem;"/>

                <label class="wizard-label">Precio unitario</label>
                <input class="wizard-input" type="number" name="productos[0][precio_unitario]" id="f_precio"
                    min="0" step="0.01" required placeholder="0.00" style="margin-bottom:0.75rem;"/>

                <label class="wizard-label">Notas (opcional)</label>
                <textarea class="wizard-input" name="notas" id="f_notas"
                    rows="2" placeholder="Notas de la venta…"></textarea>

                <button type="submit" class="wizard-btn" style="background:#16a34a;margin-top:1.25rem;">
                    ✅ Registrar venta
                </button>
                <button type="button" class="wizard-back" onclick="prevStep(4)">← Atrás</button>
            </div>

        </form>
    </div>
</div>

<script>
const totalSteps = 4;
let tipoSeleccionado = '';
let stockSeleccionado = null;

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
        if (!inp.value.trim()) {
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

function selectTipo(btn, tipo) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    tipoSeleccionado = tipo;

    const vistaInv = document.getElementById('vista-inventario');
    const vistaMan = document.getElementById('vista-manual');
    const btnCont  = document.getElementById('btn-continuar-3');
    const title    = document.getElementById('step3-title');

    if (tipo === 'inventario') {
        vistaInv.style.display = 'block';
        vistaMan.style.display = 'none';
        btnCont.style.display  = 'none';
        title.textContent = 'Selecciona del inventario';
    } else {
        vistaInv.style.display = 'none';
        vistaMan.style.display = 'block';
        btnCont.style.display  = 'block';
        title.textContent = tipo === 'servicio' ? '¿Qué servicio?' :
                            tipo === 'manual'   ? '¿Nombre del producto?' : '¿Qué producto?';
        document.getElementById('f_inventario_id').value = '';
        stockSeleccionado = null;
        document.getElementById('f_cantidad').removeAttribute('max');
        setTimeout(() => document.getElementById('f_nombre_manual').focus(), 100);
    }

    setTimeout(() => {
        document.getElementById('step-2').classList.remove('active');
        document.getElementById('step-3').classList.add('active');
        buildProgress(3);
    }, 300);
}

function filtrarInv() {
    const q = document.getElementById('buscador-inv').value.toLowerCase();
    document.querySelectorAll('#lista-inv .inv-item').forEach(item => {
        const n = item.dataset.nombre || '';
        const c = item.dataset.cat || '';
        item.style.display = (n.includes(q) || c.includes(q)) ? '' : 'none';
    });
}

function elegirInventario(id, nombre, precio, stock) {
    document.getElementById('f_tipo_producto').value  = nombre;
    document.getElementById('f_inventario_id').value  = id;
    document.getElementById('f_precio').value         = precio;
    document.getElementById('resumen-producto').textContent = nombre;
    stockSeleccionado = stock;

    // Limita la cantidad en pantalla al stock real de la sucursal seleccionada.
    const cantidad = document.getElementById('f_cantidad');
    cantidad.max = stock;
    cantidad.value = Math.min(parseInt(cantidad.value || '1', 10), stock);

    document.getElementById('step-3').classList.remove('active');
    document.getElementById('step-4').classList.add('active');
    buildProgress(4);
    setTimeout(() => document.getElementById('f_cantidad').focus(), 100);
}

function confirmarProducto() {
    const nombre = document.getElementById('f_nombre_manual').value.trim();
    if (!nombre) {
        const inp = document.getElementById('f_nombre_manual');
        inp.style.borderColor = '#dc2626';
        setTimeout(() => inp.style.borderColor = '', 1500);
        inp.focus();
        return;
    }
    document.getElementById('f_tipo_producto').value = nombre;
    document.getElementById('resumen-producto').textContent = nombre;

    document.getElementById('step-3').classList.remove('active');
    document.getElementById('step-4').classList.add('active');
    buildProgress(4);
    setTimeout(() => document.getElementById('f_cantidad').focus(), 100);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const active = document.querySelector('.wizard-step.active');
        if (!active) return;
        const stepNum = parseInt(active.id.replace('step-', ''));
        if (stepNum === 1) { e.preventDefault(); nextStep(1); }
        if (stepNum === 3 && tipoSeleccionado !== 'inventario') {
            e.preventDefault(); confirmarProducto();
        }
    }
});

document.getElementById('f_cantidad').addEventListener('input', function() {
    if (stockSeleccionado !== null && parseInt(this.value || '0', 10) > stockSeleccionado) {
        this.value = stockSeleccionado;
        this.style.borderColor = '#dc2626';
        setTimeout(() => this.style.borderColor = '', 1500);
    }
});

buildProgress(1);
</script>

@endsection
