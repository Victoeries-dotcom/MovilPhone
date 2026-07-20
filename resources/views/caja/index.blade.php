@extends('layout')

@section('content')
{{-- Encabezado: identifica el módulo y conecta con el formulario de movimientos manuales. --}}
<div class="page-header caja-header">
    <h1>Caja y Finanzas</h1>
    <div style="display:flex;gap:.65rem;flex-wrap:wrap;">
        @if(auth()->user()?->rol === 'superusuario')
            {{-- Corte diario: resume exclusivamente la sucursal activa y la fecha elegida. --}}
            <a href="{{ route('caja.corte') }}" class="btn">Corte de caja</a>
        @endif
        <a href="{{ route('caja.create') }}" class="btn btn-primary">+ Registrar movimiento</a>
    </div>
</div>

{{-- Indicadores: resumen todos los movimientos de la sucursal activa, sin depender de los filtros. --}}
<div class="caja-stats">
    <div class="stat-card">
        <div class="stat-label">Total Ingresos</div>
        <div class="stat-num green">${{ number_format($stats['ingresos'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Egresos</div>
        <div class="stat-num red">${{ number_format($stats['egresos'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Balance</div>
        <div class="stat-num {{ $stats['balance'] >= 0 ? 'green' : 'red' }}">
            ${{ number_format($stats['balance'], 2) }}
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Anticipos</div>
        <div class="stat-num amber">${{ number_format($stats['anticipos'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Movimientos</div>
        <div class="stat-num blue">{{ $stats['movimientos'] }}</div>
    </div>
</div>

{{-- Filtros: envían parámetros GET al index para buscar por OS, método de pago o tipo de movimiento. --}}
<form method="GET" action="{{ route('caja.index') }}" class="caja-toolbar">
    <input
        type="search"
        name="search"
        value="{{ request('search') }}"
        placeholder="Buscar por número de orden..."
        aria-label="Buscar movimiento por número de orden"
    >
    <select name="metodo_pago" onchange="this.form.submit()" aria-label="Filtrar por método de pago">
        <option value="">Todos los métodos</option>
        <option value="efectivo" {{ request('metodo_pago') === 'efectivo' ? 'selected' : '' }}>💵 Efectivo</option>
        <option value="transferencia" {{ request('metodo_pago') === 'transferencia' ? 'selected' : '' }}>🏦 Transferencia</option>
        <option value="tarjeta" {{ request('metodo_pago') === 'tarjeta' ? 'selected' : '' }}>💳 Tarjeta</option>
    </select>
    <select name="tipo" onchange="this.form.submit()" aria-label="Filtrar por tipo de movimiento">
        <option value="">Todos los movimientos</option>
        <option value="INGRESO" {{ request('tipo') === 'INGRESO' ? 'selected' : '' }}>Solo ingresos</option>
        <option value="EGRESO" {{ request('tipo') === 'EGRESO' ? 'selected' : '' }}>Solo egresos</option>
        {{-- Rechazos muestra las devoluciones de anticipo registradas desde una Orden de Servicio. --}}
        <option value="RECHAZO" {{ request('tipo') === 'RECHAZO' ? 'selected' : '' }}>Solo rechazos</option>
    </select>
    <button type="submit" class="btn btn-primary">Buscar</button>
    @if(request()->hasAny(['search', 'metodo_pago', 'tipo']))
        <a href="{{ route('caja.index') }}" class="btn">Limpiar</a>
    @endif
</form>

{{-- Tabla financiera: cada fila conecta el movimiento con su orden o con su folio manual. --}}
<div class="caja-table-wrap">
    <table class="caja-table">
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
                    // Distingue visualmente los registros manuales de los cobros automáticos de una OS.
                    $esManual = !$mov->os_id;
                    $esRechazo = $mov->tipo === 'EGRESO'
                        && ($mov->categoria === 'DEVOLUCIÓN DE ANTICIPO'
                            || str_starts_with((string) $mov->descripcion, 'RECHAZO '));
                    $claseFila = $esRechazo
                        ? 'caja-row-egreso'
                        : ($esManual ? ($mov->tipo === 'INGRESO' ? 'caja-row-ingreso' : 'caja-row-egreso') : '');
                    $folioManual = $foliosManuales[$mov->id] ?? $mov->tipo.'-'.str_pad((string) $mov->id, 4, '0', STR_PAD_LEFT);
                @endphp
                <tr class="{{ $claseFila }}">
                    <td>{{ $mov->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>
                        @if($mov->orden)
                            <a class="caja-folio caja-folio-os" href="{{ route('ordenes.show', $mov->orden) }}">
                                🔧 {{ $mov->orden->numero_os }}
                            </a>
                        @else
                            <span class="caja-folio {{ $mov->tipo === 'INGRESO' ? 'caja-folio-ingreso' : 'caja-folio-egreso' }}">
                                {{ $mov->tipo === 'INGRESO' ? '🟢' : '🔴' }} {{ $folioManual }}
                            </span>
                        @endif
                    </td>
                    {{-- Presenta una etiqueta simple sin cambiar la categoría financiera guardada en MySQL. --}}
                    <td>{{ $esRechazo ? 'RECHAZO' : $mov->categoria }}</td>
                    <td class="caja-metodo">
                        @if($mov->metodo_pago === 'transferencia') 🏦
                        @elseif($mov->metodo_pago === 'tarjeta') 💳
                        @else 💵
                        @endif
                        {{ ucfirst($mov->metodo_pago ?? 'efectivo') }}
                    </td>
                    <td>{{ $mov->descripcion ?: '—' }}</td>
                    <td class="caja-monto {{ $mov->tipo === 'INGRESO' ? 'caja-monto-ingreso' : 'caja-monto-egreso' }}">
                        {{ $mov->tipo === 'INGRESO' ? '+' : '-' }}${{ number_format($mov->monto, 2) }}
                    </td>
                    <td>
                        <div class="caja-actions">
                            {{-- Ticket conecta el movimiento con su OS, cliente y pagos relacionados. --}}
                            <a href="{{ route('caja.ticket', $mov) }}" class="btn btn-sm">Ticket</a>
                            @if($esManual || $esRechazo)
                                {{-- Detalle abre un modal local para ingresos, egresos y devoluciones sin alterar datos. --}}
                                <button
                                    type="button"
                                    class="btn btn-sm"
                                    data-detalle-movimiento
                                    data-tipo="{{ strtolower($mov->tipo) }}"
                                    data-fecha="{{ $mov->created_at?->format('d/m/Y H:i') }}"
                                    data-monto="${{ number_format($mov->monto, 2) }}"
                                    data-concepto="{{ $mov->descripcion ?: $mov->categoria }}"
                                >
                                    🔍 Detalle
                                </button>
                            @endif
                            @if($esManual)
                                {{-- La doble confirmación protege solo movimientos manuales; una devolución se administra desde su OS. --}}
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
                    <td colspan="7" class="caja-empty">No hay movimientos registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal de detalle: presenta fecha, monto y concepto del ingreso o egreso seleccionado. --}}
<div id="modal-detalle-caja" class="caja-modal" role="dialog" aria-modal="true" aria-labelledby="detalle-titulo">
    <div class="caja-modal-card">
        <h2 id="detalle-titulo">Detalle del movimiento</h2>
        <div id="detalle-panel" class="caja-detalle-panel">
            <div><strong>Fecha:</strong> <span id="detalle-fecha"></span></div>
            <div><strong>Monto:</strong> <span id="detalle-monto"></span></div>
            <div><strong>Concepto:</strong> <span id="detalle-concepto"></span></div>
        </div>
        <div class="caja-modal-actions">
            <button type="button" class="btn" id="cerrar-detalle-caja">Cerrar</button>
        </div>
    </div>
</div>

<style>
    /* Organiza los cinco indicadores en una sola fila y se adapta a pantallas pequeñas. */
    .caja-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .caja-stats .stat-card { min-width:0; }
    .stat-num.amber { color:#d97706; }
    .stat-num.blue { color:#2563eb; }

    /* Mantiene buscador y filtros alineados sin modificar el ancho de la tabla. */
    .caja-toolbar { display:flex; gap:.65rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem; }
    .caja-toolbar input, .caja-toolbar select { min-height:38px; padding:.45rem .75rem; border:1px solid #dbe2ea; border-radius:6px; background:#fff; font-size:13.5px; }
    .caja-toolbar input { min-width:260px; }

    /* Permite desplazamiento horizontal controlado cuando la tabla no cabe en móviles. */
    .caja-table-wrap { width:100%; overflow-x:auto; border-radius:8px; }
    .caja-table { min-width:980px; }
    .caja-row-ingreso { background:#f0fdf4; }
    .caja-row-egreso { background:#fff5f5; }
    .caja-folio { font-size:12px; font-weight:700; text-decoration:none; white-space:nowrap; }
    .caja-folio-os { color:#2563eb; }
    .caja-folio-ingreso { color:#15803d; }
    .caja-folio-egreso { color:#dc2626; }
    .caja-metodo { text-transform:capitalize; white-space:nowrap; }
    .caja-monto { font-weight:800; white-space:nowrap; }
    .caja-monto-ingreso { color:#16a34a; }
    .caja-monto-egreso { color:#dc2626; }
    .caja-actions { display:flex; gap:6px; align-items:center; }
    .caja-actions form { margin:0; }
    .caja-sin-accion { color:#94a3b8; }
    .caja-empty { text-align:center; color:#888; padding:2rem; }

    /* El modal se conecta con los botones Detalle mediante atributos data del movimiento. */
    .caja-modal { display:none; position:fixed; inset:0; z-index:1200; align-items:center; justify-content:center; padding:1rem; background:rgba(15,23,42,.55); }
    .caja-modal.is-open { display:flex; }
    .caja-modal-card { width:min(440px,100%); background:#fff; border-radius:8px; padding:1.5rem; box-shadow:0 20px 60px rgba(15,23,42,.25); }
    .caja-modal-card h2 { margin:0 0 1rem; font-size:18px; color:#0f1f3d; }
    .caja-detalle-panel { display:grid; gap:.65rem; border:1px solid #dbe2ea; border-radius:6px; padding:1rem; background:#f8fafc; font-size:13px; }
    .caja-detalle-panel.ingreso { border-color:#bbf7d0; background:#f0fdf4; }
    .caja-detalle-panel.egreso { border-color:#fecaca; background:#fff5f5; }
    .caja-modal-actions { display:flex; justify-content:flex-end; margin-top:1rem; }

    @media (max-width:1200px) {
        .caja-stats { grid-template-columns:repeat(3,minmax(0,1fr)); }
    }
    @media (max-width:700px) {
        .caja-stats { grid-template-columns:1fr 1fr; }
        .caja-toolbar { align-items:stretch; }
        .caja-toolbar input, .caja-toolbar select { width:100%; min-width:0; }
    }
</style>

<script>
    /* Abre el detalle y conecta el botón seleccionado con los campos visibles del modal. */
    document.querySelectorAll('[data-detalle-movimiento]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const tipo = boton.dataset.tipo;
            const panel = document.getElementById('detalle-panel');
            document.getElementById('detalle-titulo').textContent = tipo === 'ingreso' ? 'Detalle del Ingreso' : 'Detalle del Egreso';
            document.getElementById('detalle-fecha').textContent = boton.dataset.fecha;
            document.getElementById('detalle-monto').textContent = boton.dataset.monto;
            document.getElementById('detalle-concepto').textContent = boton.dataset.concepto;
            panel.className = 'caja-detalle-panel ' + tipo;
            document.getElementById('modal-detalle-caja').classList.add('is-open');
        });
    });

    /* Cierra el detalle sin recargar ni alterar información de Caja. */
    function cerrarDetalleCaja() {
        document.getElementById('modal-detalle-caja').classList.remove('is-open');
    }

    document.getElementById('cerrar-detalle-caja').addEventListener('click', cerrarDetalleCaja);
    document.getElementById('modal-detalle-caja').addEventListener('click', (evento) => {
        if (evento.target.id === 'modal-detalle-caja') cerrarDetalleCaja();
    });
</script>
@endsection
