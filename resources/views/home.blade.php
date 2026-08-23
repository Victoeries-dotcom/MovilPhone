@extends('layout')

@section('content')
@php
    /*
     * Saludo contextual: adapta el mensaje a la hora configurada por Laravel y usa solamente
     * el primer nombre para que la bienvenida sea personal sin ocupar demasiado espacio.
     */
    $saludoPanel = match (true) {
        now()->hour < 12 => 'Buenos días',
        now()->hour < 19 => 'Buenas tardes',
        default => 'Buenas noches',
    };
    $primerNombre = \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ');
    // La fecha visible y los indicadores comparten el mismo día calculado por DashboardController.
    $fechaPanel = ucfirst($diaPanel->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'));
    $fechaSupervision = $diaPanel->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    $alcancePanel = $sucursal ? 'la sucursal '.$sucursal->nombre : 'MovilPhone';
    $descripcionPanel = $supervisionActiva
        ? 'Estás supervisando la información del '.$fechaSupervision.' de '.$alcancePanel.'.'
        : 'Aquí tienes el resumen operativo de '.$alcancePanel.'.';
@endphp
{{-- Cabecera operativa: recibe al usuario, identifica la sucursal y conserva las acciones autorizadas. --}}
<div class="dashboard-header">
    <div class="dashboard-welcome">
        <span class="dashboard-eyebrow">RESUMEN OPERATIVO · {{ mb_strtoupper($sucursal->nombre ?? 'MOVILPHONE') }}</span>
        <h1 id="dashboardGreeting">{{ $saludoPanel }}, {{ $primerNombre }}</h1>
        <p>{{ $descripcionPanel }}</p>

        {{-- Supervisión diaria: abre un selector limitado al mes actual y conserva la sucursal activa. --}}
        @if($sucursal)
            <div class="dashboard-supervision-row">
                <details class="dashboard-supervision-picker" @if($errors->has('fecha_supervision')) open @endif>
                    <summary class="dashboard-supervision-button">
                        <i data-lucide="calendar-search" aria-hidden="true"></i>
                        <span>Supervisión por día</span>
                        <i data-lucide="chevron-down" class="dashboard-supervision-chevron" aria-hidden="true"></i>
                    </summary>
                    <div class="dashboard-supervision-popover">
                        <form method="GET" action="{{ route('home') }}" class="dashboard-supervision-form">
                            <label for="fecha_supervision">Día del mes actual</label>
                            <input
                                type="date"
                                id="fecha_supervision"
                                name="fecha_supervision"
                                value="{{ old('fecha_supervision', $diaPanel->toDateString()) }}"
                                min="{{ $inicioMesActual->toDateString() }}"
                                max="{{ $hoy->toDateString() }}"
                                required
                            >
                            <small>Consulta desde el primer día del mes hasta hoy.</small>
                            @error('fecha_supervision')
                                <span class="dashboard-supervision-error" role="alert">{{ $message }}</span>
                            @enderror
                            <div class="dashboard-supervision-actions">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i data-lucide="search" aria-hidden="true"></i><span>Consultar día</span>
                                </button>
                                @if($supervisionActiva)
                                    <a href="{{ route('home') }}" class="btn btn-sm">Volver a hoy</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </details>

                @if($supervisionActiva)
                    <span class="dashboard-supervision-active">
                        <i data-lucide="badge-check" aria-hidden="true"></i>
                        Día seleccionado: {{ $fechaSupervision }}
                    </span>
                @endif
            </div>
        @endif
    </div>
    <div class="dashboard-header-side">
        <span class="dashboard-date"><i data-lucide="calendar-days"></i>{{ $fechaPanel }}</span>
        <div class="dashboard-actions">
            @if(in_array(auth()->user()->rol, ['superusuario', 'usuario', 'tecnico']))
                <a href="{{ route('ordenes.create') }}" class="btn btn-primary"><i data-lucide="plus"></i><span>Nueva OS</span></a>
            @endif
            {{-- Nueva venta: conecta el panel con Ventas para los tres roles autorizados por routes/web.php. --}}
            @if(in_array(auth()->user()->rol, ['superusuario', 'vendedor', 'usuario']))
                <a href="{{ route('ventas.create') }}" class="btn"><i data-lucide="shopping-cart"></i><span>Nueva venta</span></a>
            @endif
        </div>
    </div>
</div>

@if(!$sucursal)
    {{-- Estado vacío: centra la orientación y usa la garza animada como fondo sin alterar la selección de sucursal. --}}
    <section class="dashboard-branch-empty" aria-labelledby="dashboardBranchEmptyTitle">
        {{-- La imagen se sirve desde public/images y su movimiento es solamente visual mediante CSS. --}}
        <div class="dashboard-branch-empty-visual" aria-hidden="true">
            <img src="{{ asset('images/dashboard-heron.jpg') }}" alt="">
        </div>

        {{-- El contenido permanece sobre la animación y guía al usuario hacia el selector existente del menú. --}}
        <div class="dashboard-branch-empty-content">
            <span class="dashboard-branch-empty-icon"><i data-lucide="store"></i></span>
            <span class="dashboard-branch-empty-kicker">CONFIGURACIÓN INICIAL</span>
            <h2 id="dashboardBranchEmptyTitle">Selecciona una sucursal</h2>
            <p>Los indicadores aparecerán cuando el sistema tenga una sucursal activa.</p>
        </div>
    </section>
@else
    {{-- Indicadores: comparan el día visible con el inmediatamente anterior usando DashboardController. --}}
    <section class="dashboard-kpis" aria-label="Indicadores del {{ $fechaSupervision }}">
        @php
            $tarjetas = [
                ['label' => 'Ventas', 'key' => 'ventas', 'icon' => 'shopping-bag', 'tone' => 'blue', 'money' => false],
                ['label' => 'Total vendido', 'key' => 'vendido', 'icon' => 'badge-dollar-sign', 'tone' => 'green', 'money' => true],
                // Los tres métodos de cobro usan indicadores independientes del día supervisado.
                ['label' => 'Efectivo en caja', 'key' => 'efectivo', 'icon' => 'banknote', 'tone' => 'green', 'money' => true],
                ['label' => 'Transferencias recibidas', 'key' => 'transferencias', 'icon' => 'landmark', 'tone' => 'cyan', 'money' => true],
                ['label' => 'Pagos con tarjeta', 'key' => 'tarjeta', 'icon' => 'credit-card', 'tone' => 'violet', 'money' => true],
                ['label' => 'Total egresos', 'key' => 'egresos', 'icon' => 'circle-minus', 'tone' => 'red', 'money' => true],
                // El color del balance comunica si el resultado diario es positivo o negativo.
                ['label' => 'Balance', 'key' => 'balance', 'icon' => 'scale', 'tone' => $indicadores['balance']['actual'] >= 0 ? 'green' : 'red', 'money' => true],
                ['label' => 'Ordenes nuevas', 'key' => 'ordenes', 'icon' => 'wrench', 'tone' => 'amber', 'money' => false],
                ['label' => 'Clientes nuevos', 'key' => 'clientes', 'icon' => 'user-plus', 'tone' => 'violet', 'money' => false],
            ];
        @endphp
        @foreach($tarjetas as $tarjeta)
            @php $dato = $indicadores[$tarjeta['key']]; @endphp
            <article class="dashboard-kpi dashboard-kpi-{{ $tarjeta['tone'] }}">
                <div class="dashboard-kpi-top">
                    <span class="dashboard-kpi-icon"><i data-lucide="{{ $tarjeta['icon'] }}"></i></span>
                    <span class="dashboard-delta {{ $dato['variacion'] < 0 ? 'is-negative' : 'is-positive' }}">
                        <i data-lucide="{{ $dato['variacion'] < 0 ? 'trending-down' : 'trending-up' }}"></i>
                        {{ abs($dato['variacion']) }}%
                    </span>
                </div>
                <span class="dashboard-kpi-label">{{ $tarjeta['label'] }}</span>
                <strong>{{ $tarjeta['money'] ? '$'.number_format($dato['actual'], 2) : number_format($dato['actual']) }}</strong>
                <small>Comparado con el día anterior</small>
            </article>
        @endforeach

    </section>

    <div class="dashboard-grid-main">
        {{-- Tendencia financiera: termina en el día supervisado y usa movimientos_caja. --}}
        <section class="dashboard-section dashboard-trend-section">
            <div class="dashboard-section-header">
                <div>
                    <span class="dashboard-section-kicker">CAJA</span>
                    <h2>{{ $supervisionActiva ? 'Ingresos hasta el '.$fechaSupervision : 'Ingresos de los últimos 7 días' }}</h2>
                </div>
                <a href="{{ route('caja.index') }}" class="btn btn-sm"><i data-lucide="arrow-up-right"></i><span>Ver caja</span></a>
            </div>
            @php $maxTendencia = max(1, (float) $tendencia->max('valor')); @endphp
            <div class="dashboard-bars" role="img" aria-label="Grafica de ingresos de los ultimos siete dias">
                @foreach($tendencia as $punto)
                    <div class="dashboard-bar-item" title="{{ $punto['etiqueta'] }}: ${{ number_format($punto['valor'], 2) }}">
                        <span class="dashboard-bar-value">${{ number_format($punto['valor'], 0) }}</span>
                        <span class="dashboard-bar-track">
                            <span class="dashboard-bar-fill" style="height: {{ max(4, ($punto['valor'] / $maxTendencia) * 100) }}%"></span>
                        </span>
                        <span class="dashboard-bar-label">{{ $punto['etiqueta'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Pulso del taller: se identifica como actual porque inventario no conserva una fotografía histórica diaria. --}}
        <section class="dashboard-section">
            <div class="dashboard-section-header">
                <div>
                    <span class="dashboard-section-kicker">TALLER</span>
                    <h2>Pulso operativo actual</h2>
                </div>
                <span class="dashboard-team"><i data-lucide="users"></i>{{ $tecnicos }} técnicos</span>
            </div>
            <div class="dashboard-pulse-list">
                @foreach([
                    ['En espera', $operacion['espera'], 'clock-3', 'blue'],
                    ['En diagnóstico', $operacion['diagnostico'], 'scan-search', 'cyan'],
                    ['En reparación', $operacion['reparacion'], 'hammer', 'amber'],
                    ['Listos para recoger', $operacion['listos'], 'circle-check-big', 'green'],
                    ['Stock critico', $operacion['stock_bajo'], 'triangle-alert', 'red'],
                ] as [$label, $value, $icon, $tone])
                    <div class="dashboard-pulse-row">
                        <span class="dashboard-pulse-icon is-{{ $tone }}"><i data-lucide="{{ $icon }}"></i></span>
                        <span>{{ $label }}</span>
                        <strong>{{ $value }}</strong>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="dashboard-grid-lower">
        {{-- Tabla de seguimiento: con filtro activo muestra solo las órdenes creadas durante el día. --}}
        <section class="dashboard-section dashboard-orders">
            <div class="dashboard-section-header">
                <div>
                    <span class="dashboard-section-kicker">SEGUIMIENTO</span>
                    <h2>{{ $supervisionActiva ? 'Órdenes del día' : 'Órdenes recientes' }}</h2>
                </div>
                @if(in_array(auth()->user()->rol, ['superusuario', 'usuario', 'tecnico']))
                    <a href="{{ route('ordenes.index') }}" class="dashboard-link">Ver todas <i data-lucide="arrow-right"></i></a>
                @endif
            </div>
            @forelse($ordenesRecientes as $orden)
                <a href="{{ route('ordenes.show', $orden) }}" class="dashboard-order-row">
                    <span class="dashboard-order-mark"><i data-lucide="smartphone"></i></span>
                    <span class="dashboard-order-main">
                        <strong>{{ $orden->numero_os }}</strong>
                        <small>{{ $orden->cliente->nombre ?? 'SIN CLIENTE' }} · {{ trim($orden->marca.' '.$orden->modelo) }}</small>
                    </span>
                    <span class="ui-status-badge ui-status-info">{{ $orden->estado }}</span>
                    <span class="dashboard-order-tech">{{ $orden->tecnico->name ?? 'SIN ASIGNAR' }}</span>
                </a>
            @empty
                <div class="dashboard-inline-empty">
                    {{ $supervisionActiva ? 'No se registraron órdenes durante el día seleccionado.' : 'No hay órdenes registradas en esta sucursal.' }}
                </div>
            @endforelse
        </section>

        {{-- Actividad reciente: permite al superusuario revisar acciones sin abrir el panel completo. --}}
        <section class="dashboard-section">
            <div class="dashboard-section-header">
                <div>
                    <span class="dashboard-section-kicker">AUDITORIA</span>
                    <h2>{{ $supervisionActiva ? 'Actividad del día' : 'Actividad reciente' }}</h2>
                </div>
                @if(auth()->user()->rol === 'superusuario')
                    <a href="{{ route('actividad.index') }}" class="dashboard-link">Ver actividad <i data-lucide="arrow-right"></i></a>
                @endif
            </div>
            <div class="dashboard-activity-list">
                @forelse($actividadReciente as $actividad)
                    <div class="dashboard-activity-item">
                        <span class="dashboard-activity-dot"></span>
                        <div>
                            <strong>{{ $actividad->accion }} · {{ $actividad->modulo }}</strong>
                            <p>{{ $actividad->descripcion }}</p>
                            <small>
                                {{ $actividad->usuario->name ?? 'SISTEMA' }} ·
                                {{ $supervisionActiva ? $actividad->created_at->locale('es')->isoFormat('D MMM · HH:mm') : $actividad->created_at->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                @empty
                    <div class="dashboard-inline-empty">
                        {{ $supervisionActiva ? 'No se registró actividad durante el día seleccionado.' : 'La actividad aparecerá conforme el equipo use el sistema.' }}
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endif

@endsection
