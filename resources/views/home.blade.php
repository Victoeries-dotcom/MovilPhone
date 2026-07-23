@extends('layout')

@section('content')
{{-- Cabecera operativa: resume la sucursal activa y conecta las acciones con los modulos autorizados. --}}
<div class="dashboard-header">
    <div>
        <span class="dashboard-eyebrow">RESUMEN OPERATIVO</span>
        <h1 id="dashboardGreeting">Panel de {{ $sucursal->nombre ?? 'MovilPhone' }}</h1>
        <p>{{ now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}</p>
    </div>
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
    {{-- Indicadores: comparan la actividad de hoy con ayer usando DashboardController. --}}
    <section class="dashboard-kpis" aria-label="Indicadores de hoy">
        @php
            $tarjetas = [
                ['label' => 'Ventas', 'key' => 'ventas', 'icon' => 'shopping-bag', 'tone' => 'blue', 'money' => false],
                ['label' => 'Total vendido', 'key' => 'vendido', 'icon' => 'badge-dollar-sign', 'tone' => 'green', 'money' => true],
                ['label' => 'Ingresos de caja', 'key' => 'ingresos', 'icon' => 'wallet-cards', 'tone' => 'cyan', 'money' => true],
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
                <small>Comparado con ayer</small>
            </article>
        @endforeach
    </section>

    <div class="dashboard-grid-main">
        {{-- Tendencia financiera: usa la serie de siete dias calculada desde movimientos_caja. --}}
        <section class="dashboard-section dashboard-trend-section">
            <div class="dashboard-section-header">
                <div>
                    <span class="dashboard-section-kicker">CAJA</span>
                    <h2>Ingresos de los ultimos 7 dias</h2>
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

        {{-- Pulso del taller: conecta los estados de OS, inventario y tecnicos disponibles. --}}
        <section class="dashboard-section">
            <div class="dashboard-section-header">
                <div>
                    <span class="dashboard-section-kicker">TALLER</span>
                    <h2>Pulso operativo</h2>
                </div>
                <span class="dashboard-team"><i data-lucide="users"></i>{{ $tecnicos }} tecnicos</span>
            </div>
            <div class="dashboard-pulse-list">
                @foreach([
                    ['En espera', $operacion['espera'], 'clock-3', 'blue'],
                    ['En diagnostico', $operacion['diagnostico'], 'scan-search', 'cyan'],
                    ['En reparacion', $operacion['reparacion'], 'hammer', 'amber'],
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
        {{-- Tabla reciente: abre directamente la ficha de cada orden de la sucursal activa. --}}
        <section class="dashboard-section dashboard-orders">
            <div class="dashboard-section-header">
                <div>
                    <span class="dashboard-section-kicker">SEGUIMIENTO</span>
                    <h2>Ordenes recientes</h2>
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
                <div class="dashboard-inline-empty">No hay ordenes registradas en esta sucursal.</div>
            @endforelse
        </section>

        {{-- Actividad reciente: permite al superusuario revisar acciones sin abrir el panel completo. --}}
        <section class="dashboard-section">
            <div class="dashboard-section-header">
                <div>
                    <span class="dashboard-section-kicker">AUDITORIA</span>
                    <h2>Actividad reciente</h2>
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
                            <small>{{ $actividad->usuario->name ?? 'SISTEMA' }} · {{ $actividad->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <div class="dashboard-inline-empty">La actividad aparecera conforme el equipo use el sistema.</div>
                @endforelse
            </div>
        </section>
    </div>
@endif
@endsection
