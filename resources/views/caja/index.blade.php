@extends('layout')

@section('content')
{{-- Encabezado financiero: reúne la captura manual y el Corte de caja en un solo punto de trabajo. --}}
<header class="cash-page-header">
    <div>
        <span class="cash-page-eyebrow">Control financiero</span>
        <h1>Caja y Finanzas</h1>
    </div>

    <div class="cash-header-actions">
        {{-- Estos botones abren una captura manual; el servidor asigna la sucursal activa y el usuario responsable. --}}
        <button
            type="button"
            class="cash-manual-button is-income"
            data-cash-movement-open
            data-type="INGRESO"
            data-action="{{ route('caja.ingreso') }}"
        >
            <i data-lucide="circle-plus" aria-hidden="true"></i>
            <span>Ingreso</span>
        </button>
        <button
            type="button"
            class="cash-manual-button is-expense"
            data-cash-movement-open
            data-type="EGRESO"
            data-action="{{ route('caja.egreso') }}"
        >
            <i data-lucide="circle-minus" aria-hidden="true"></i>
            <span>Egreso</span>
        </button>

        @if(auth()->user()?->rol === 'superusuario')
            <a href="{{ route('caja.corte') }}" class="cash-cut-button">
                <i data-lucide="calendar-check" aria-hidden="true"></i>
                <span>Corte de caja</span>
            </a>
        @endif
    </div>
</header>

{{-- Mensajes del servidor: comunican operaciones realizadas por MovimientoCajaController. --}}
@if(session('success'))
    <div class="alert alert-success cash-alert">
        <i data-lucide="circle-check" aria-hidden="true"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-error cash-alert">
        <i data-lucide="circle-alert" aria-hidden="true"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-error cash-alert">
        <i data-lucide="circle-alert" aria-hidden="true"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

{{-- Filtros: envían criterios GET al controlador sin modificar ningún movimiento almacenado. --}}
<form method="GET" action="{{ route('caja.index') }}" class="cash-filter-panel">
    <label class="cash-filter-field is-search">
        <span>
            <i data-lucide="search" aria-hidden="true"></i>
            Buscar movimiento
        </span>
        <input
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Número de orden..."
            autocomplete="off"
        >
    </label>

    <label class="cash-filter-field">
        <span>
            <i data-lucide="credit-card" aria-hidden="true"></i>
            Método de pago
        </span>
        <select name="metodo_pago">
            <option value="">Todos los métodos</option>
            <option value="efectivo" @selected(request('metodo_pago') === 'efectivo')>Efectivo</option>
            <option value="transferencia" @selected(request('metodo_pago') === 'transferencia')>Transferencia</option>
            <option value="tarjeta" @selected(request('metodo_pago') === 'tarjeta')>Tarjeta</option>
        </select>
    </label>

    <label class="cash-filter-field">
        <span>
            <i data-lucide="list-filter" aria-hidden="true"></i>
            Tipo de movimiento
        </span>
        <select name="tipo">
            <option value="">Todos los movimientos</option>
            <option value="INGRESO" @selected(request('tipo') === 'INGRESO')>Solo ingresos</option>
            <option value="EGRESO" @selected(request('tipo') === 'EGRESO')>Solo egresos</option>
            <option value="RECHAZO" @selected(request('tipo') === 'RECHAZO')>Solo rechazos</option>
        </select>
    </label>

    <div class="cash-filter-actions">
        <button type="submit" class="cash-search-button">
            <i data-lucide="search" aria-hidden="true"></i>
            <span>Buscar</span>
        </button>
        @if(request()->hasAny(['search', 'metodo_pago', 'tipo']))
            <a href="{{ route('caja.index') }}" class="btn cash-clear-button">
                <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                <span>Limpiar</span>
            </a>
        @endif
    </div>
</form>

{{-- Tabla financiera: conecta cobros con su OS y distingue ventas, ingresos y egresos mediante color y folio. --}}
<section class="ui-table-panel cash-table-panel" aria-label="Movimientos de Caja">
    <div class="ui-table-shell cash-table-shell">
        <table class="cash-table" data-workspace-ready="true">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Orden</th>
                    <th>Categoría</th>
                    <th>Método</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                    @php
                        // Clasifica cada registro sin cambiar sus datos para reproducir los colores del archivo externo.
                        // Reconoce ventas nuevas y registros históricos que usaban la categoría abreviada.
                        $esVenta = in_array($mov->categoria, ['Venta de productos', 'Venta'], true);
                        $esRechazo = $mov->tipo === 'EGRESO'
                            && ($mov->categoria === 'DEVOLUCIÓN DE ANTICIPO'
                                || str_starts_with((string) $mov->descripcion, 'RECHAZO '));
                        $esManual = !$mov->os_id && !$esVenta;
                        $esIngresoDestacado = $mov->tipo === 'INGRESO' && ($esManual || $esVenta);
                        $claseFila = $esRechazo || $mov->tipo === 'EGRESO'
                            ? 'is-expense'
                            : ($esIngresoDestacado ? 'is-income' : '');
                        $folioManual = $foliosManuales[$mov->id]
                            ?? $mov->tipo.'-'.str_pad((string) $mov->id, 4, '0', STR_PAD_LEFT);
                        $montoVisible = $esRechazo && (float) $mov->devolucion > 0
                            ? (float) $mov->devolucion
                            : (float) $mov->monto;
                    @endphp
                    <tr class="cash-movement-row {{ $claseFila }}">
                        <td>{{ $mov->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            @if($mov->orden)
                                <a class="cash-reference is-order" href="{{ route('ordenes.show', $mov->orden) }}">
                                    <i data-lucide="wrench" aria-hidden="true"></i>
                                    {{ $mov->orden->numero_os }}
                                </a>
                            @elseif($esVenta)
                                <span class="cash-reference is-sale">
                                    <i data-lucide="package" aria-hidden="true"></i>
                                    {{ $mov->referencia_pago ?: 'VENTA-'.str_pad((string) $mov->id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            @else
                                <span class="cash-reference {{ $mov->tipo === 'INGRESO' ? 'is-income' : 'is-expense' }}">
                                    <i data-lucide="{{ $mov->tipo === 'INGRESO' ? 'circle-plus' : 'circle-minus' }}" aria-hidden="true"></i>
                                    {{ $folioManual }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $esRechazo ? 'Rechazo' : $mov->categoria }}</td>
                        <td>
                            <span class="cash-payment-method is-{{ $mov->metodo_pago ?: 'efectivo' }}">
                                @if($mov->metodo_pago === 'transferencia')
                                    <i data-lucide="landmark" aria-hidden="true"></i>
                                @elseif($mov->metodo_pago === 'tarjeta')
                                    <i data-lucide="credit-card" aria-hidden="true"></i>
                                @else
                                    <i data-lucide="banknote" aria-hidden="true"></i>
                                @endif
                                {{ ucfirst($mov->metodo_pago ?: 'efectivo') }}
                            </span>
                        </td>
                        <td>{{ $mov->descripcion ?: '—' }}</td>
                        <td>
                            <strong class="cash-amount {{ $mov->tipo === 'INGRESO' ? 'is-income' : 'is-expense' }}">
                                {{ $mov->tipo === 'INGRESO' ? '+' : '-' }}${{ number_format($montoVisible, 2) }}
                            </strong>
                        </td>
                        <td>
                            <div class="cash-actions">
                                @if($mov->tipo === 'INGRESO')
                                    {{-- Todo dinero recibido en Caja conserva un comprobante imprimible. --}}
                                    <a href="{{ route('caja.ticket', $mov) }}" class="btn btn-sm" title="Ver ticket del ingreso">
                                        <i data-lucide="receipt-text" aria-hidden="true"></i>
                                        Ticket
                                    </a>
                                @endif

                                @if($esManual || $esVenta || $esRechazo)
                                    {{-- Detalle presenta la información existente en un modal y no escribe en la base de datos. --}}
                                    <button
                                        type="button"
                                        class="btn btn-sm"
                                        data-cash-detail
                                        data-type="{{ $mov->tipo === 'INGRESO' ? 'income' : 'expense' }}"
                                        data-date="{{ $mov->created_at?->format('d/m/Y H:i') }}"
                                        data-amount="${{ number_format($montoVisible, 2) }}"
                                        data-description="{{ $mov->descripcion ?: $mov->categoria }}"
                                    >
                                        Detalle
                                    </button>
                                @endif

                                @if($esManual && in_array(auth()->user()?->rol, ['superusuario', 'tecnico'], true))
                                    {{-- Solo el personal autorizado recibe la acción; el rol Usuario no puede eliminar movimientos financieros. --}}
                                    <form
                                        method="POST"
                                        action="{{ route('caja.destroy', $mov) }}"
                                        onsubmit="return confirmarEliminacionSistema(event, 'el movimiento', '{{ $folioManual }}');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="cash-empty-state">
                                <i data-lucide="receipt-text" aria-hidden="true"></i>
                                <strong>No hay movimientos registrados</strong>
                                <span>Los cobros, ventas, ingresos y egresos de esta sucursal aparecerán aquí.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Captura manual: un mismo formulario cambia entre ingreso y egreso sin duplicar reglas financieras. --}}
<div id="cash-movement-modal" class="cash-modal" role="dialog" aria-modal="true" aria-labelledby="cash-movement-title">
    <form id="cash-movement-form" method="POST" class="cash-modal-card">
        @csrf
        <input type="hidden" name="origen" value="caja">

        <div class="cash-modal-heading">
            <span id="cash-movement-icon" class="cash-modal-icon"><i data-lucide="circle-plus" aria-hidden="true"></i></span>
            <div>
                <span>Movimiento manual</span>
                <h2 id="cash-movement-title">Registrar ingreso</h2>
            </div>
        </div>

        <div class="cash-manual-form">
            <label>
                <span>Concepto o descripción *</span>
                <textarea name="concepto" id="cash-movement-concept" rows="3" maxlength="500" required>{{ old('concepto') }}</textarea>
            </label>
            <label>
                <span>Monto ($) *</span>
                <input type="number" name="monto" value="{{ old('monto') }}" min="0.01" step="0.01" inputmode="decimal" required>
            </label>
            <label>
                <span>Método de pago *</span>
                <select name="metodo_pago" required>
                    <option value="efectivo" @selected(old('metodo_pago', 'efectivo') === 'efectivo')>Efectivo</option>
                    <option value="transferencia" @selected(old('metodo_pago') === 'transferencia')>Transferencia</option>
                    <option value="tarjeta" @selected(old('metodo_pago') === 'tarjeta')>Tarjeta</option>
                </select>
            </label>
        </div>

        <div class="cash-modal-actions">
            <button type="button" class="btn" id="cash-movement-close">Cancelar</button>
            <button type="submit" class="btn btn-success" id="cash-movement-submit">Registrar ingreso</button>
        </div>
    </form>
</div>

{{-- Modal de detalle: muestra fecha, monto y concepto del movimiento seleccionado. --}}
<div id="cash-detail-modal" class="cash-modal" role="dialog" aria-modal="true" aria-labelledby="cash-detail-title">
    <div class="cash-modal-card">
        <div class="cash-modal-heading">
            <span class="cash-modal-icon"><i data-lucide="receipt-text" aria-hidden="true"></i></span>
            <div>
                <span>Información financiera</span>
                <h2 id="cash-detail-title">Detalle del movimiento</h2>
            </div>
        </div>
        <div id="cash-detail-panel" class="cash-detail-panel">
            <div><strong>Fecha</strong><span id="cash-detail-date"></span></div>
            <div><strong>Monto</strong><span id="cash-detail-amount"></span></div>
            <div class="is-wide"><strong>Concepto</strong><span id="cash-detail-description"></span></div>
        </div>
        <div class="cash-modal-actions">
            <button type="button" class="btn" id="cash-detail-close">Cerrar</button>
        </div>
    </div>
</div>

<script>
    /*
     * Configura el formulario con la ruta y el estilo del tipo elegido.
     * El servidor vuelve a validar concepto, monto, método y sucursal antes de guardar.
     */
    function openCashMovement(type, action) {
        const isIncome = type === 'INGRESO';
        const modal = document.getElementById('cash-movement-modal');
        const form = document.getElementById('cash-movement-form');
        const icon = document.getElementById('cash-movement-icon');
        form.action = action;
        document.getElementById('cash-movement-title').textContent = isIncome ? 'Registrar ingreso' : 'Registrar egreso';
        document.getElementById('cash-movement-submit').textContent = isIncome ? 'Registrar ingreso' : 'Registrar egreso';
        icon.className = 'cash-modal-icon ' + (isIncome ? 'is-income' : 'is-expense');
        icon.innerHTML = `<i data-lucide="${isIncome ? 'circle-plus' : 'circle-minus'}" aria-hidden="true"></i>`;
        modal.classList.add('is-open');
        if (window.lucide) window.lucide.createIcons();
        setTimeout(() => document.getElementById('cash-movement-concept').focus(), 50);
    }

    document.querySelectorAll('[data-cash-movement-open]').forEach((button) => {
        button.addEventListener('click', () => openCashMovement(button.dataset.type, button.dataset.action));
    });

    function closeCashMovement() {
        document.getElementById('cash-movement-modal').classList.remove('is-open');
    }

    document.getElementById('cash-movement-close').addEventListener('click', closeCashMovement);
    document.getElementById('cash-movement-modal').addEventListener('click', (event) => {
        if (event.target.id === 'cash-movement-modal') closeCashMovement();
    });

    /*
     * Abre el modal con los atributos del movimiento seleccionado.
     * Se conecta únicamente con la tabla renderizada por Laravel y no modifica registros.
     */
    document.querySelectorAll('[data-cash-detail]').forEach((button) => {
        button.addEventListener('click', () => {
            const type = button.dataset.type;
            const panel = document.getElementById('cash-detail-panel');
            document.getElementById('cash-detail-title').textContent =
                type === 'income' ? 'Detalle del ingreso' : 'Detalle del egreso';
            document.getElementById('cash-detail-date').textContent = button.dataset.date;
            document.getElementById('cash-detail-amount').textContent = button.dataset.amount;
            document.getElementById('cash-detail-description').textContent = button.dataset.description;
            panel.className = 'cash-detail-panel ' + type;
            document.getElementById('cash-detail-modal').classList.add('is-open');
        });
    });

    /*
     * Cierra el detalle con el botón o al seleccionar el fondo.
     * Se conecta con el modal local y mantiene intactos filtros y movimientos.
     */
    function closeCashDetail() {
        document.getElementById('cash-detail-modal').classList.remove('is-open');
    }

    document.getElementById('cash-detail-close').addEventListener('click', closeCashDetail);
    document.getElementById('cash-detail-modal').addEventListener('click', (event) => {
        if (event.target.id === 'cash-detail-modal') closeCashDetail();
    });
</script>
@endsection
