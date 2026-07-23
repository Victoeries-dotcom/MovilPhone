@extends('layout')

@section('content')
<style>
/* Agrupa accesos rápidos y el rango personalizado que alimentan ReporteController por GET. */
.reporte-filtros { display:grid; gap:1rem; margin-bottom:1.5rem; }
.reporte-accesos { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; }
.reporte-rango {
    display:grid; gap:1rem; padding:1rem; border:1px solid var(--ui-border,#dfe6ef);
    border-radius:8px; background:var(--ui-surface,#fff); box-shadow:var(--ui-shadow-sm,0 8px 24px rgba(15,31,61,.06));
}
.reporte-rango-cabecera { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
.reporte-rango-titulo { display:flex; align-items:center; gap:.7rem; min-width:0; }
.reporte-rango-icono { display:grid; place-items:center; width:36px; height:36px; border-radius:7px; color:#6d4bf2; background:rgba(109,75,242,.11); }
.reporte-rango-icono svg { width:18px; height:18px; }
.reporte-rango-titulo strong { display:block; color:var(--ui-text,#0f1f3d); font-size:13px; }
.reporte-rango-titulo span { display:block; margin-top:2px; color:var(--ui-muted,#64748b); font-size:11px; }
.reporte-rango-modos { display:grid; grid-template-columns:repeat(3,minmax(94px,1fr)); gap:4px; padding:4px; border:1px solid var(--ui-border,#dfe6ef); border-radius:7px; background:var(--ui-surface-soft,#f8fafc); }
.reporte-rango-modo { min-height:36px; padding:.45rem .75rem; border:0; border-radius:5px; background:transparent; color:var(--ui-muted,#64748b); font:inherit; font-size:12px; font-weight:750; cursor:pointer; transition:background .18s ease,color .18s ease,box-shadow .18s ease; }
.reporte-rango-modo:hover { color:var(--ui-text,#0f1f3d); }
.reporte-rango-modo.is-active { color:#fff; background:#6d4bf2; box-shadow:0 6px 16px rgba(109,75,242,.22); }
.reporte-rango-panel { display:grid; grid-template-columns:minmax(180px,1fr) minmax(180px,1fr) auto; align-items:end; gap:.75rem; }
.reporte-rango-panel[hidden] { display:none; }
.reporte-rango-campo { display:grid; gap:5px; }
.reporte-rango-campo label { color:var(--ui-muted,#64748b); font-size:10px; font-weight:800; text-transform:uppercase; }
.reporte-rango-campo input { width:100%; min-height:42px; padding:.55rem .75rem; border:1px solid var(--ui-border-strong,#cbd7e6); border-radius:6px; background:var(--ui-surface,#fff); color:var(--ui-text,#0f1f3d); font:inherit; font-size:13px; }
.reporte-rango-campo input:focus { outline:2px solid rgba(109,75,242,.18); border-color:#6d4bf2; }
.reporte-rango-submit { min-height:42px; white-space:nowrap; }
.reporte-rango-error { margin:0; color:#dc2626; font-size:11px; font-weight:700; }
.reporte-sucursal { margin-top:4px; font-size:12px; color:#2563eb; font-weight:700; }
/*
 * Panel analítico de Reportes.
 * Organiza las gráficas conectadas con $graficas y $general sin modificar sus consultas.
 */
.reporte-graficas { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:1rem; margin:0 0 1.5rem; }
.reporte-grafica {
    position:relative; min-width:0; overflow:hidden; padding:0; border:1px solid #e2e8f0;
    border-radius:8px; background:#fff; box-shadow:0 10px 30px rgba(15,31,61,.06);
    break-inside:avoid; transition:box-shadow .22s ease, transform .22s ease;
}
.reporte-grafica:hover { transform:translateY(-2px); box-shadow:0 18px 42px rgba(15,31,61,.1); }
.reporte-grafica::before { content:''; position:absolute; inset:0 0 auto; height:3px; background:var(--grafica-acento,#2563eb); }
.reporte-grafica-ventas { --grafica-acento:#2563eb; }
.reporte-grafica-ingresos { --grafica-acento:#16a34a; }
.reporte-grafica-ordenes { --grafica-acento:#f59e0b; grid-column:1 / -1; }
.reporte-grafica-general { --grafica-acento:#0f766e; }
.reporte-grafica-cabecera { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.15rem 1.2rem .9rem; border-bottom:1px solid #eef2f7; }
.reporte-grafica-titulo { display:flex; align-items:center; gap:.7rem; min-width:0; }
.reporte-grafica-icono { display:grid; place-items:center; flex:0 0 36px; width:36px; height:36px; border-radius:7px; color:var(--grafica-acento); background:color-mix(in srgb, var(--grafica-acento) 10%, white); }
.reporte-grafica-icono svg { width:18px; height:18px; }
.reporte-grafica h2 { margin:0; color:#0f1f3d; font-size:15px; line-height:1.25; }
.reporte-grafica-subtitulo { margin:.24rem 0 0; color:#64748b; font-size:11.5px; }
.reporte-grafica-resumen { flex:0 0 auto; text-align:right; }
.reporte-grafica-resumen strong { display:block; color:#0f1f3d; font-size:16px; line-height:1.2; }
.reporte-grafica-resumen span { display:block; margin-top:3px; color:#94a3b8; font-size:9px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
.reporte-grafica-cuerpo { position:relative; padding:1rem 1.2rem 1.2rem; }
.grafica-alcance { display:inline-block; margin-left:6px; padding:3px 7px; border-radius:5px; background:#fef3c7; color:#92400e; font-size:9px; font-weight:800; text-transform:uppercase; }
/* Indica cuando la tarjeta usa el mismo acumulado por sucursal que la gráfica inferior. */
.stat-alcance { display:block; margin-top:5px; color:#92400e; font-size:9px; font-weight:800; text-transform:uppercase; }
/* Distingue datos actuales, como Bajo stock, de los valores acumulados del historial. */
.stat-alcance-actual { color:#475569; }
.grafica-pastel-wrap { position:relative; display:flex; align-items:center; justify-content:center; min-height:250px; }
.grafica-pastel { display:block; width:250px; max-width:100%; height:250px; cursor:crosshair; }
.grafica-leyenda { display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:6px 10px; margin-top:.75rem; }
.grafica-leyenda-item { display:grid; grid-template-columns:9px minmax(0,1fr) auto; align-items:center; gap:8px; min-width:0; padding:7px 8px; border:1px solid transparent; border-radius:6px; font-size:11.5px; color:#334155; transition:background .18s ease,border-color .18s ease; }
.grafica-leyenda-item:hover { background:#f8fafc; border-color:#e2e8f0; }
.grafica-leyenda-color { width:9px; height:9px; border-radius:50%; box-shadow:0 0 0 3px color-mix(in srgb,currentColor 10%,transparent); }
.grafica-leyenda-nombre { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.grafica-leyenda-valor { color:#0f1f3d; font-weight:800; }
.grafica-barras-scroll { position:relative; width:100%; overflow-x:auto; padding-bottom:.25rem; }
.grafica-barras { display:block; width:100%; min-width:720px; height:325px; cursor:crosshair; }
/* Tooltip común: recibe información del punto bajo el cursor desde el motor Canvas. */
.grafica-tooltip { position:absolute; z-index:5; min-width:135px; max-width:220px; padding:9px 11px; border:1px solid rgba(148,163,184,.25); border-radius:7px; background:rgba(15,31,61,.96); color:#fff; box-shadow:0 12px 30px rgba(15,23,42,.22); pointer-events:none; opacity:0; transform:translate(-50%,-100%) translateY(-10px); transition:opacity .12s ease; }
.grafica-tooltip.visible { opacity:1; }
.grafica-tooltip-label { display:block; color:#cbd5e1; font-size:10px; font-weight:700; }
.grafica-tooltip-value { display:block; margin-top:3px; font-size:13px; font-weight:800; }
/* Organiza la gráfica radial y su leyenda; se conecta con $general del periodo activo. */
.reporte-general-radial { display:grid; grid-template-columns:minmax(270px,360px) minmax(0,1fr); align-items:center; gap:1.5rem; }
.radial-canvas-wrap { position:relative; display:flex; align-items:center; justify-content:center; min-height:320px; }
.radial-canvas { display:block; width:320px; max-width:100%; height:320px; }
.radial-leyenda { display:grid; grid-template-columns:repeat(2,minmax(150px,1fr)); gap:9px; }
.radial-leyenda-item { display:grid; grid-template-columns:4px minmax(0,1fr); gap:10px; align-items:center; min-width:0; padding:10px 11px; border:1px solid #eef2f7; border-radius:7px; background:#fbfdff; }
.radial-leyenda-color { width:4px; height:100%; min-height:32px; border-radius:999px; }
.radial-leyenda-texto { min-width:0; }
.radial-leyenda-nombre { display:block; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; }
.radial-leyenda-valor { display:block; margin-top:2px; color:#0f1f3d; font-size:16px; font-weight:800; }
/* Contiene las tablas inferiores cuando el estilo global les agrega desplazamiento horizontal. */
.reporte-tablas-dobles { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:1.5rem; margin-top:1.5rem; }
.reporte-tablas-dobles > div { min-width:0; }
.reporte-tablas-dobles .ui-table-shell { max-width:100%; }
@media (max-width:900px) {
    .reporte-graficas { grid-template-columns:1fr; }
    .reporte-grafica-ordenes { grid-column:auto; }
    .reporte-general-radial { grid-template-columns:1fr; }
    .reporte-tablas-dobles { grid-template-columns:1fr; }
}
@media (max-width:700px) {
    .reporte-rango-cabecera { align-items:stretch; flex-direction:column; }
    .reporte-rango-panel { grid-template-columns:1fr; }
    .reporte-rango-submit { width:100%; }
    .reporte-grafica-cabecera { padding:1rem; }
    .reporte-grafica-cuerpo { padding:.85rem 1rem 1rem; }
    .reporte-grafica-resumen strong { font-size:14px; }
    .radial-canvas-wrap { min-height:280px; }
    .radial-canvas { width:280px; height:280px; }
    .radial-leyenda { grid-template-columns:1fr; }
}
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main { margin-left: 0 !important; }
    .content { padding: 0 !important; }
    body { background: white !important; }
    table, .card, .stat-card { box-shadow: none !important; }
    .reporte-graficas { grid-template-columns:1fr 1fr; gap:.65rem; }
    .reporte-grafica { box-shadow:none; }
    .reporte-grafica-ordenes { grid-column:1 / -1; }
    .grafica-barras-scroll { overflow:visible; }
}
</style>

<div class="page-header">
    <div>
        <h1>Reportes</h1>
        <div class="page-title-sub">
            {{-- El subtítulo muestra el rango exacto que se está consultando en la base de datos. --}}
            @if($periodo === 'acumulado')
                Actividad acumulada del {{ $inicio->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}
            @elseif($periodo === 'fecha')
                Actividad del {{ $inicio->format('d/m/Y') }}
            @else
                Actividad del {{ $inicio->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}
            @endif
        </div>
        {{-- Confirma visualmente que todas las tablas y tarjetas pertenecen a la sucursal activa. --}}
        <div class="reporte-sucursal">
            {{ $sucursalActiva ? 'Sucursal: '.$sucursalActiva->nombre : 'Selecciona una sucursal para consultar reportes' }}
        </div>
    </div>
    <button type="button" class="btn btn-primary no-print" onclick="window.print()">Imprimir / Guardar PDF</button>
</div>

{{-- Filtros: los accesos rápidos y el rango personalizado controlan todas las consultas del ReporteController. --}}
<div class="reporte-filtros no-print">
    <div class="reporte-accesos" aria-label="Periodos rápidos">
        <a href="{{ route('reportes.index', ['periodo' => 'dia']) }}" class="btn {{ $periodo === 'dia' ? 'btn-primary' : '' }}">Hoy</a>
        <a href="{{ route('reportes.index', ['periodo' => 'semana']) }}" class="btn {{ $periodo === 'semana' ? 'btn-primary' : '' }}">Últimos 7 días</a>
        <a href="{{ route('reportes.index', ['periodo' => 'mes']) }}" class="btn {{ $periodo === 'mes' ? 'btn-primary' : '' }}">Mes actual</a>
        <a href="{{ route('reportes.index', ['periodo' => 'acumulado']) }}" class="btn {{ $periodo === 'acumulado' ? 'btn-primary' : '' }}">Acumulado</a>
    </div>

    {{-- El formulario envía dos límites y su unidad; ReporteController los convierte en fechas completas. --}}
    <form method="GET" action="{{ route('reportes.index') }}" class="reporte-rango" id="form-rango-reportes">
        <input type="hidden" name="periodo" value="rango">
        <input type="hidden" name="tipo_rango" id="tipo-rango-reportes" value="{{ $tipoRango }}">

        <div class="reporte-rango-cabecera">
            <div class="reporte-rango-titulo">
                <span class="reporte-rango-icono" aria-hidden="true"><i data-lucide="calendar-range"></i></span>
                <span>
                    <strong>Periodo personalizado</strong>
                    <span>Consulta un intervalo completo de días, semanas o meses.</span>
                </span>
            </div>
            {{-- El control segmentado cambia el formato de los dos campos sin enviar todavía el formulario. --}}
            <div class="reporte-rango-modos" role="tablist" aria-label="Tipo de periodo">
                @foreach(['dia' => 'Días', 'semana' => 'Semanas', 'mes' => 'Meses'] as $tipo => $etiqueta)
                    <button
                        type="button"
                        class="reporte-rango-modo {{ $tipoRango === $tipo ? 'is-active' : '' }}"
                        data-tipo-rango="{{ $tipo }}"
                        role="tab"
                        aria-selected="{{ $tipoRango === $tipo ? 'true' : 'false' }}"
                    >{{ $etiqueta }}</button>
                @endforeach
            </div>
        </div>

        @foreach([
            'dia' => ['type' => 'date', 'inicio' => 'Día inicial', 'fin' => 'Día final'],
            'semana' => ['type' => 'week', 'inicio' => 'Semana inicial', 'fin' => 'Semana final'],
            'mes' => ['type' => 'month', 'inicio' => 'Mes inicial', 'fin' => 'Mes final'],
        ] as $tipo => $configuracion)
            {{-- Cada panel se conecta con el mismo par desde/hasta; solo el panel visible queda habilitado. --}}
            <div class="reporte-rango-panel" data-panel-rango="{{ $tipo }}" @if($tipoRango !== $tipo) hidden @endif>
                <div class="reporte-rango-campo">
                    <label for="rango-{{ $tipo }}-desde">{{ $configuracion['inicio'] }}</label>
                    <input
                        id="rango-{{ $tipo }}-desde"
                        type="{{ $configuracion['type'] }}"
                        value="{{ $valoresRango[$tipo]['desde'] }}"
                        data-limite-rango="desde"
                        @if($tipoRango === $tipo) name="desde" @endif
                        @disabled($tipoRango !== $tipo)
                        required
                    >
                </div>
                <div class="reporte-rango-campo">
                    <label for="rango-{{ $tipo }}-hasta">{{ $configuracion['fin'] }}</label>
                    <input
                        id="rango-{{ $tipo }}-hasta"
                        type="{{ $configuracion['type'] }}"
                        value="{{ $valoresRango[$tipo]['hasta'] }}"
                        data-limite-rango="hasta"
                        @if($tipoRango === $tipo) name="hasta" @endif
                        @disabled($tipoRango !== $tipo)
                        required
                    >
                </div>
                <button type="submit" class="btn btn-primary reporte-rango-submit">
                    <i data-lucide="search"></i>
                    Aplicar rango
                </button>
            </div>
        @endforeach

        @error('desde') <p class="reporte-rango-error">{{ $message }}</p> @enderror
        @error('hasta') <p class="reporte-rango-error">{{ $message }}</p> @enderror
    </form>
</div>

{{-- Sin sucursal activa se evita mezclar información de diferentes sedes. --}}
@if(!$sucursalActiva)
    <div class="alert alert-error no-print" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
        <span>Selecciona una sucursal para consultar sus reportes.</span>
        <a href="{{ route('sucursales.index') }}" class="btn">Ir a Sucursales</a>
    </div>
@endif

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Ventas</div>
        {{-- Cuenta operaciones de venta y usa el acumulado cuando las gráficas también son acumuladas. --}}
        <div class="stat-num blue">{{ $general['ventas_mostradas'] }}</div>
        @if($general['ventas_es_acumulado'] && $sucursalActiva)
            <span class="stat-alcance">Acumulado de {{ $sucursalActiva->nombre }}</span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-label">Total vendido</div>
        {{-- Esta cifra usa exactamente el mismo periodo o acumulado que la gráfica de ingresos. --}}
        <div class="stat-num green">${{ number_format($general['total_ventas_mostrado'], 2) }}</div>
        @if($general['total_ventas_es_acumulado'] && $sucursalActiva)
            <span class="stat-alcance">Acumulado de {{ $sucursalActiva->nombre }}</span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-label">Ordenes</div>
        {{-- Usa el mismo periodo o acumulado que la gráfica de Órdenes por estado. --}}
        <div class="stat-num amber">{{ $general['ordenes_mostradas'] }}</div>
        @if($general['ordenes_es_acumulado'] && $sucursalActiva)
            <span class="stat-alcance">Acumulado de {{ $sucursalActiva->nombre }}</span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-label">Clientes nuevos</div>
        {{-- Si el periodo está vacío, muestra los clientes registrados en la sucursal activa. --}}
        <div class="stat-num blue">{{ $general['clientes_mostrados'] }}</div>
        @if($general['clientes_es_acumulado'] && $sucursalActiva)
            <span class="stat-alcance">Acumulado de {{ $sucursalActiva->nombre }}</span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-label">Bajo stock</div>
        {{-- Bajo stock siempre representa la existencia actual, no un periodo histórico. --}}
        <div class="stat-num red">{{ $general['productos_bajo_stock'] }}</div>
        @if($sucursalActiva)
            <span class="stat-alcance stat-alcance-actual">Actual de {{ $sucursalActiva->nombre }}</span>
        @endif
    </div>
</div>

{{-- Las gráficas usan la fecha y sucursal activas; si no hay ventas, muestran su acumulado identificado. --}}
<section class="reporte-graficas" aria-label="Gráficas de resultados">
    {{-- Dona de unidades: consume productos.cantidades generado por ReporteController. --}}
    <article class="reporte-grafica reporte-grafica-ventas">
        <header class="reporte-grafica-cabecera">
            <div class="reporte-grafica-titulo">
                <span class="reporte-grafica-icono"><i data-lucide="shopping-bag"></i></span>
                <div>
                    <h2>Ventas por producto</h2>
                    <div class="reporte-grafica-subtitulo">
                        Distribución de piezas vendidas
                        @if($graficas['productos']['es_acumulado'])
                            <span class="grafica-alcance">Acumulado de {{ $sucursalActiva->nombre }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="reporte-grafica-resumen">
                <strong>{{ number_format($graficas['productos']['cantidades']->sum()) }}</strong>
                <span>Unidades</span>
            </div>
        </header>
        <div class="reporte-grafica-cuerpo">
            <div class="grafica-pastel-wrap">
                <canvas
                    id="grafica-ventas"
                    class="grafica-pastel"
                    width="250"
                    height="250"
                    role="img"
                    aria-label="Gráfica de dona de ventas por producto"
                ></canvas>
                <div id="tooltip-grafica-ventas" class="grafica-tooltip" role="status"></div>
            </div>
            <div id="leyenda-ventas" class="grafica-leyenda"></div>
        </div>
    </article>

    {{-- Dona de ingresos: reutiliza los mismos productos y conecta cada segmento con su subtotal. --}}
    <article class="reporte-grafica reporte-grafica-ingresos">
        <header class="reporte-grafica-cabecera">
            <div class="reporte-grafica-titulo">
                <span class="reporte-grafica-icono"><i data-lucide="circle-dollar-sign"></i></span>
                <div>
                    <h2>Total vendido por producto</h2>
                    <div class="reporte-grafica-subtitulo">
                        Participación en los ingresos
                        @if($graficas['productos']['es_acumulado'])
                            <span class="grafica-alcance">Acumulado de {{ $sucursalActiva->nombre }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="reporte-grafica-resumen">
                <strong>${{ number_format($graficas['productos']['ingresos']->sum(), 2) }}</strong>
                <span>Ingresos</span>
            </div>
        </header>
        <div class="reporte-grafica-cuerpo">
            <div class="grafica-pastel-wrap">
                <canvas
                    id="grafica-total-vendido"
                    class="grafica-pastel"
                    width="250"
                    height="250"
                    role="img"
                    aria-label="Gráfica de dona del total vendido por producto"
                ></canvas>
                <div id="tooltip-grafica-total-vendido" class="grafica-tooltip" role="status"></div>
            </div>
            <div id="leyenda-total-vendido" class="grafica-leyenda"></div>
        </div>
    </article>

    {{-- Barras de OS: cada barra representa un grupo de estados calculado en ReporteController. --}}
    <article class="reporte-grafica reporte-grafica-ordenes">
        <header class="reporte-grafica-cabecera">
            <div class="reporte-grafica-titulo">
                <span class="reporte-grafica-icono"><i data-lucide="chart-column-big"></i></span>
                <div>
                    <h2>Órdenes por estado</h2>
                    <div class="reporte-grafica-subtitulo">
                        Flujo operativo por etapa
                        @if($graficas['ordenes']['es_acumulado'])
                            <span class="grafica-alcance">Acumulado de {{ $sucursalActiva->nombre }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="reporte-grafica-resumen">
                <strong>{{ number_format($graficas['ordenes']['cantidades']->sum()) }}</strong>
                <span>Órdenes</span>
            </div>
        </header>
        <div class="reporte-grafica-cuerpo">
            <div class="grafica-barras-scroll">
                <canvas
                    id="grafica-ordenes"
                    class="grafica-barras"
                    width="900"
                    height="325"
                    role="img"
                    aria-label="Gráfica de barras verticales de órdenes por estado"
                ></canvas>
                <div id="tooltip-grafica-ordenes" class="grafica-tooltip" role="status"></div>
            </div>
        </div>
    </article>
</section>

{{-- Resumen radial: conecta Ventas, Órdenes y Caja del mismo periodo y sucursal. --}}
<article class="reporte-grafica reporte-grafica-general">
    <header class="reporte-grafica-cabecera">
        <div class="reporte-grafica-titulo">
            <span class="reporte-grafica-icono"><i data-lucide="gauge"></i></span>
            <div>
                <h2>Reporte general por periodo</h2>
                <div class="reporte-grafica-subtitulo">
                    Resumen de {{ strtolower($periodoEtiqueta) }} para {{ $sucursalActiva?->nombre ?? 'la sucursal seleccionada' }}
                </div>
            </div>
        </div>
        <div class="reporte-grafica-resumen">
            <strong>{{ $periodoEtiqueta }}</strong>
            <span>Alcance</span>
        </div>
    </header>
    <div class="reporte-grafica-cuerpo">
        <div class="reporte-general-radial">
            <div class="radial-canvas-wrap">
                <canvas
                    id="grafica-reporte-general"
                    class="radial-canvas"
                    width="320"
                    height="320"
                    role="img"
                    aria-label="Gráfica radial del reporte general por periodo"
                ></canvas>
            </div>
            {{-- La leyenda conserva valores exactos aunque los arcos usen escalas comparables. --}}
            <div id="leyenda-reporte-general" class="radial-leyenda" aria-label="Valores del reporte general"></div>
        </div>
    </div>
</article>

<div style="margin-top:1.5rem;">
    <h2 style="font-size:16px;color:#0f1f3d;margin-bottom:.75rem;">Reportes de ventas</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Sucursal</th>
                <th>Vendedor</th>
                <th>Total</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $venta)
            <tr>
                <td>#{{ $venta->id }}</td>
                <td>{{ $venta->cliente->nombre ?? 'Sin cliente' }}</td>
                <td>{{ $venta->sucursal->nombre ?? '-' }}</td>
                <td>{{ $venta->usuario->name ?? '-' }}</td>
                <td>${{ number_format($venta->total, 2) }}</td>
                <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#888;padding:2rem;">No hay ventas en este periodo</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- La tabla repetida de productos más vendidos se omite; sus datos continúan conectados
     con las gráficas superiores de cantidad vendida e ingresos por producto. --}}

<div style="margin-top:1.5rem;">
    <h2 style="font-size:16px;color:#0f1f3d;margin-bottom:.75rem;">Productos por existencia</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoria</th>
                <th>Sucursal</th>
                <th>Existencia</th>
                <th>Stock minimo</th>
                <th>Proveedor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productosExistencia as $item)
            <tr>
                <td>{{ $item->nombre }}</td>
                <td>{{ $item->categoria }}</td>
                <td>{{ $item->sucursal->nombre ?? '-' }}</td>
                <td>{{ $item->cantidad_disponible }}</td>
                <td>{{ $item->stock_minimo }}</td>
                <td>{{ $item->proveedor ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#888;padding:2rem;">No hay productos registrados</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Las tablas usan columnas flexibles para permanecer dentro del área del reporte. --}}
<div class="reporte-tablas-dobles">
    <div>
        <h2 style="font-size:16px;color:#0f1f3d;margin-bottom:.75rem;">Reporte por cliente</h2>
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Compras</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reporteClientes as $cliente)
                <tr>
                    <td>{{ $cliente['cliente'] }}</td>
                    <td>{{ $cliente['compras'] }}</td>
                    <td>${{ number_format($cliente['total'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align:center;color:#888;padding:2rem;">No hay clientes con compras</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        <h2 style="font-size:16px;color:#0f1f3d;margin-bottom:.75rem;">Reporte por proveedor</h2>
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Productos</th>
                    <th>Existencia</th>
                    <th>Valor costo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reporteProveedores as $proveedor)
                <tr>
                    <td>{{ $proveedor->proveedor ?? 'Sin proveedor' }}</td>
                    <td>{{ $proveedor->productos }}</td>
                    <td>{{ $proveedor->existencia }}</td>
                    <td>${{ number_format($proveedor->valor_costo ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#888;padding:2rem;">No hay proveedores registrados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
/*
 * Sincroniza el control Días/Semanas/Meses con los parámetros tipo_rango, desde y hasta.
 * Se conecta con rangoPersonalizado() de ReporteController y evita enviar campos ocultos.
 */
const formularioRangoReportes = document.getElementById('form-rango-reportes');
if (formularioRangoReportes) {
    const campoTipoRango = document.getElementById('tipo-rango-reportes');
    const botonesTipoRango = formularioRangoReportes.querySelectorAll('[data-tipo-rango]');
    const panelesRango = formularioRangoReportes.querySelectorAll('[data-panel-rango]');

    function activarTipoRango(tipo) {
        campoTipoRango.value = tipo;

        botonesTipoRango.forEach(function(boton) {
            const activo = boton.dataset.tipoRango === tipo;
            boton.classList.toggle('is-active', activo);
            boton.setAttribute('aria-selected', activo ? 'true' : 'false');
        });

        panelesRango.forEach(function(panel) {
            const activo = panel.dataset.panelRango === tipo;
            panel.hidden = !activo;

            panel.querySelectorAll('[data-limite-rango]').forEach(function(campo) {
                campo.disabled = !activo;
                campo.name = activo ? campo.dataset.limiteRango : '';
            });
        });
    }

    botonesTipoRango.forEach(function(boton) {
        boton.addEventListener('click', function() {
            activarTipoRango(boton.dataset.tipoRango);
        });
    });

    activarTipoRango(campoTipoRango.value);
}

/*
 * Datos preparados por ReporteController.
 * Se conectan con la sucursal activa y el periodo seleccionado.
 */
const datosGraficasReporte = @json($graficas);
/*
 * Reúne las cinco métricas de la gráfica radial.
 * Los valores provienen del ReporteController y respetan la sucursal y el periodo activos.
 */
const datosReporteGeneral = {
    periodo: @json($periodoEtiqueta),
    metricas: [
        { etiqueta: 'Ingresos', valor: Number(@json((float) $general['ingresos_caja'])), tipo: 'moneda' },
        { etiqueta: 'Egresos', valor: Number(@json((float) $general['egresos_caja'])), tipo: 'moneda' },
        {
            etiqueta: @json($general['ventas_es_acumulado'] ? 'Ventas acumuladas' : 'Ventas'),
            valor: Number(@json((int) $general['ventas_mostradas'])),
            tipo: 'conteo'
        },
        {
            etiqueta: @json($general['ordenes_es_acumulado'] ? 'Órdenes acumuladas' : 'Órdenes'),
            valor: Number(@json((int) $general['ordenes_mostradas'])),
            tipo: 'conteo'
        },
        { etiqueta: 'Movimientos de caja', valor: Number(@json((int) $general['movimientos_caja'])), tipo: 'conteo' },
    ],
};
const coloresGraficasReporte = [
    '#2563eb', '#0f766e', '#f59e0b', '#dc2626', '#7c3aed',
    '#0891b2', '#db2777', '#475569', '#16a34a', '#ea580c',
];

/*
 * Formateadores compartidos por gráficas, leyendas y tooltips.
 * Mantienen cifras mexicanas consistentes con las tarjetas del reporte.
 */
const formatoNumeroReporte = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 });
const formatoMonedaReporte = new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', minimumFractionDigits: 2,
});
let graficasReporteAnimadas = false;

/*
 * Ajusta el Canvas a pantallas de alta resolución sin cambiar su tamaño visual.
 * Se conecta con las funciones de pastel y barras para mantener texto y figuras nítidas.
 */
function prepararCanvas(canvas, width, height) {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    canvas.width = Math.round(width * ratio);
    canvas.height = Math.round(height * ratio);

    const context = canvas.getContext('2d');
    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    context.clearRect(0, 0, width, height);

    return { context, width, height };
}

/*
 * Dibuja el estado vacío cuando el periodo no contiene información.
 * Evita mostrar una gráfica engañosa con valores en cero.
 */
function dibujarSinDatos(context, width, height, mensaje) {
    context.fillStyle = '#f8fafc';
    context.beginPath();
    context.arc(width / 2, height / 2 - 8, Math.min(width, height) * .29, 0, Math.PI * 2);
    context.fill();
    context.strokeStyle = '#e2e8f0';
    context.lineWidth = 2;
    context.stroke();
    context.fillStyle = '#64748b';
    context.font = '700 12px system-ui, sans-serif';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillText(mensaje, width / 2, height / 2 - 8);
}

/*
 * Presenta información precisa al pasar el cursor sobre una dona o barra.
 * Se conecta con las zonas interactivas calculadas por cada función de dibujo.
 */
function mostrarTooltipGrafica(tooltip, event, etiqueta, valor) {
    const parentRect = tooltip.parentElement.getBoundingClientRect();
    tooltip.innerHTML = '';

    const label = document.createElement('span');
    label.className = 'grafica-tooltip-label';
    label.textContent = etiqueta;

    const value = document.createElement('strong');
    value.className = 'grafica-tooltip-value';
    value.textContent = valor;

    tooltip.append(label, value);
    tooltip.style.left = (event.clientX - parentRect.left) + 'px';
    tooltip.style.top = (event.clientY - parentRect.top) + 'px';
    tooltip.classList.add('visible');
}

function ocultarTooltipGrafica(tooltip) {
    tooltip.classList.remove('visible');
}

/* Ejecuta una entrada suave y respeta la preferencia del sistema para reducir movimiento. */
function animarGrafica(drawFrame, animate) {
    if (!animate || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        drawFrame(1);
        return;
    }

    const start = performance.now();
    const duration = 680;
    function frame(now) {
        const linear = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - linear, 3);
        drawFrame(eased);
        if (linear < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
}

/*
 * Construye la leyenda accesible de cada pastel.
 * Relaciona color, producto y valor exacto mostrado en la tabla de ventas.
 */
function construirLeyenda(elementId, labels, values, formatter) {
    const legend = document.getElementById(elementId);
    legend.innerHTML = '';
    const total = values.reduce((sum, value) => sum + Number(value || 0), 0);

    labels.forEach(function(label, index) {
        const value = Number(values[index] || 0);
        if (value <= 0) {
            return;
        }

        const item = document.createElement('div');
        item.className = 'grafica-leyenda-item';

        const color = document.createElement('span');
        color.className = 'grafica-leyenda-color';
        color.style.background = coloresGraficasReporte[index % coloresGraficasReporte.length];

        const name = document.createElement('span');
        name.className = 'grafica-leyenda-nombre';
        const percentage = total > 0 ? (value / total) * 100 : 0;
        name.title = label + ' · ' + percentage.toFixed(1) + '%';
        name.textContent = label;

        const formattedValue = document.createElement('span');
        formattedValue.className = 'grafica-leyenda-valor';
        formattedValue.textContent = formatter(value);

        item.append(color, name, formattedValue);
        legend.appendChild(item);
    });
}

/*
 * Dibuja una dona analítica con total central, animación y tooltip.
 * Ventas e ingresos llaman esta función con diferentes valores del ReporteController.
 */
function dibujarDona(canvasId, legendId, tooltipId, labels, values, formatter, centerLabel, animate) {
    const canvas = document.getElementById(canvasId);
    const availableWidth = Math.max(canvas.parentElement.clientWidth, 180);
    const size = Math.min(250, availableWidth);
    const numericValues = values.map(value => Math.max(Number(value || 0), 0));
    const total = numericValues.reduce((sum, value) => sum + value, 0);
    construirLeyenda(legendId, labels, numericValues, formatter);

    if (total <= 0) {
        const { context, width, height } = prepararCanvas(canvas, size, size);
        dibujarSinDatos(context, width, height, 'Sin ventas');
        return;
    }

    let sectores = [];
    animarGrafica(function(progress) {
        const { context, width, height } = prepararCanvas(canvas, size, size);
        const centerX = width / 2;
        const centerY = height / 2;
        const radius = Math.min(width, height) * .39;
        const lineWidth = Math.max(24, size * .12);
        let startAngle = -Math.PI / 2;
        sectores = [];

        // El aro de fondo conserva una lectura limpia incluso con pocos productos.
        context.beginPath();
        context.arc(centerX, centerY, radius, 0, Math.PI * 2);
        context.strokeStyle = '#edf2f7';
        context.lineWidth = lineWidth;
        context.stroke();

        numericValues.forEach(function(value, index) {
            if (value <= 0) return;
            const fullSweep = (value / total) * Math.PI * 2;
            const sweep = fullSweep * progress;
            const endAngle = startAngle + sweep;

            context.beginPath();
            context.arc(centerX, centerY, radius, startAngle + .018, Math.max(startAngle + .018, endAngle - .018));
            context.strokeStyle = coloresGraficasReporte[index % coloresGraficasReporte.length];
            context.lineWidth = lineWidth;
            context.lineCap = 'butt';
            context.stroke();

            if (progress === 1) {
                sectores.push({ index, start: startAngle, end: startAngle + fullSweep, radius, lineWidth, centerX, centerY });
            }
            startAngle += fullSweep * progress;
        });

        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillStyle = '#94a3b8';
        context.font = '800 9px system-ui, sans-serif';
        context.fillText(centerLabel.toUpperCase(), centerX, centerY - 9);
        context.fillStyle = '#0f1f3d';
        context.font = '800 18px system-ui, sans-serif';
        context.fillText(formatter(total * progress), centerX, centerY + 12);
    }, animate);

    // La detección radial enlaza el segmento visual con producto, valor y porcentaje.
    const tooltip = document.getElementById(tooltipId);
    canvas.onmousemove = function(event) {
        const rect = canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        const sector = sectores.find(function(item) {
            const distance = Math.hypot(x - item.centerX, y - item.centerY);
            let angle = Math.atan2(y - item.centerY, x - item.centerX);
            while (angle < -Math.PI / 2) angle += Math.PI * 2;
            return distance >= item.radius - item.lineWidth / 2
                && distance <= item.radius + item.lineWidth / 2
                && angle >= item.start && angle <= item.end;
        });

        if (!sector) return ocultarTooltipGrafica(tooltip);
        const value = numericValues[sector.index];
        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
        mostrarTooltipGrafica(tooltip, event, labels[sector.index], formatter(value) + ' · ' + percentage + '%');
    };
    canvas.onmouseleave = () => ocultarTooltipGrafica(tooltip);
}

/*
 * Divide etiquetas largas de estados para evitar que se encimen debajo de las barras.
 */
function dividirEtiquetaBarra(label, maxLength = 15) {
    const words = label.split(' ');
    const lines = [''];

    words.forEach(function(word) {
        const currentLine = lines[lines.length - 1];
        const candidate = currentLine ? currentLine + ' ' + word : word;

        if (candidate.length <= maxLength || lines.length >= 2) {
            lines[lines.length - 1] = candidate;
        } else {
            lines.push(word);
        }
    });

    return lines.slice(0, 2);
}

/*
 * Dibuja barras verticales redondeadas con animación y tooltip.
 * Sus valores provienen de ordenes_servicio filtrado por fecha y sucursal.
 */
function dibujarBarrasOrdenes(labels, values, animate) {
    const canvas = document.getElementById('grafica-ordenes');
    const width = Math.max(canvas.parentElement.clientWidth, labels.length * 105, 720);
    const height = 325;
    const numericValues = values.map(value => Math.max(Number(value || 0), 0));
    const total = numericValues.reduce((sum, value) => sum + value, 0);

    if (total <= 0) {
        const { context } = prepararCanvas(canvas, width, height);
        dibujarSinDatos(context, width, height, 'Sin órdenes');
        return;
    }

    const margins = { top: 28, right: 20, bottom: 78, left: 48 };
    const plotWidth = width - margins.left - margins.right;
    const plotHeight = height - margins.top - margins.bottom;
    const maxValue = Math.max(...numericValues, 1);
    const gridSteps = Math.min(Math.max(maxValue, 2), 5);
    const slotWidth = plotWidth / labels.length;
    const barWidth = Math.min(58, slotWidth * .55);
    let barras = [];

    animarGrafica(function(progress) {
        const { context } = prepararCanvas(canvas, width, height);
        barras = [];

        context.font = '10px system-ui, sans-serif';
        context.textAlign = 'right';
        context.textBaseline = 'middle';
        for (let step = 0; step <= gridSteps; step++) {
            const value = (maxValue / gridSteps) * step;
            const y = margins.top + plotHeight - (value / maxValue) * plotHeight;
            context.strokeStyle = '#e8edf4';
            context.setLineDash(step === 0 ? [] : [4, 5]);
            context.beginPath();
            context.moveTo(margins.left, y);
            context.lineTo(width - margins.right, y);
            context.stroke();
            context.setLineDash([]);
            context.fillStyle = '#94a3b8';
            context.fillText(String(Math.round(value)), margins.left - 9, y);
        }

        numericValues.forEach(function(value, index) {
            const finalBarHeight = (value / maxValue) * plotHeight;
            const barHeight = finalBarHeight * progress;
            const x = margins.left + slotWidth * index + (slotWidth - barWidth) / 2;
            const y = margins.top + plotHeight - barHeight;
            const color = coloresGraficasReporte[index % coloresGraficasReporte.length];
            const gradient = context.createLinearGradient(0, y, 0, margins.top + plotHeight);
            gradient.addColorStop(0, color);
            gradient.addColorStop(1, color + 'B8');
            context.fillStyle = gradient;
            context.beginPath();
            context.roundRect(x, y, barWidth, Math.max(barHeight, 1), [7, 7, 2, 2]);
            context.fill();

            context.fillStyle = '#0f1f3d';
            context.font = '800 11px system-ui, sans-serif';
            context.textAlign = 'center';
            context.textBaseline = 'bottom';
            context.fillText(String(Math.round(value * progress)), x + barWidth / 2, Math.max(y - 6, 16));

            context.fillStyle = '#475569';
            context.font = '600 10px system-ui, sans-serif';
            context.textBaseline = 'top';
            dividirEtiquetaBarra(labels[index]).forEach(function(line, lineIndex) {
                context.fillText(line, x + barWidth / 2, margins.top + plotHeight + 13 + lineIndex * 14);
            });

            if (progress === 1) barras.push({ index, x, y, width: barWidth, height: finalBarHeight });
        });
    }, animate);

    // El tooltip traduce la posición del cursor a la etapa exacta de la orden.
    const tooltip = document.getElementById('tooltip-grafica-ordenes');
    canvas.onmousemove = function(event) {
        const rect = canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        const bar = barras.find(item => x >= item.x && x <= item.x + item.width && y >= item.y && y <= item.y + item.height);
        if (!bar) return ocultarTooltipGrafica(tooltip);
        mostrarTooltipGrafica(tooltip, event, labels[bar.index], formatoNumeroReporte.format(numericValues[bar.index]) + ' órdenes');
    };
    canvas.onmouseleave = () => ocultarTooltipGrafica(tooltip);
}

/*
 * Construye la leyenda exacta de la gráfica radial.
 * Los importes usan moneda mexicana y las cantidades permanecen como números enteros.
 */
function construirLeyendaRadial(metricas, colores, numero, moneda) {
    const leyenda = document.getElementById('leyenda-reporte-general');
    leyenda.innerHTML = '';

    metricas.forEach(function(metrica, index) {
        const item = document.createElement('div');
        item.className = 'radial-leyenda-item';

        const color = document.createElement('span');
        color.className = 'radial-leyenda-color';
        color.style.background = colores[index];

        const texto = document.createElement('span');
        texto.className = 'radial-leyenda-texto';

        const nombre = document.createElement('span');
        nombre.className = 'radial-leyenda-nombre';
        nombre.textContent = metrica.etiqueta;

        const valor = document.createElement('strong');
        valor.className = 'radial-leyenda-valor';
        valor.textContent = metrica.tipo === 'moneda'
            ? moneda.format(metrica.valor)
            : numero.format(metrica.valor);

        texto.append(nombre, valor);
        item.append(color, texto);
        leyenda.appendChild(item);
    });
}

/*
 * Dibuja indicadores concéntricos animados con acabado de tablero ejecutivo.
 * Dinero se compara con dinero y conteos con conteos para evitar proporciones engañosas.
 */
function dibujarReporteGeneralRadial(numero, moneda, animate) {
    const canvas = document.getElementById('grafica-reporte-general');
    const availableWidth = Math.max(canvas.parentElement.clientWidth, 250);
    const size = Math.min(320, availableWidth);
    const metricas = datosReporteGeneral.metricas;
    const colores = ['#16a34a', '#dc2626', '#2563eb', '#f59e0b', '#0891b2'];
    const maxMoneda = Math.max(
        ...metricas.filter(item => item.tipo === 'moneda').map(item => item.valor),
        0
    );
    const maxConteo = Math.max(
        ...metricas.filter(item => item.tipo === 'conteo').map(item => item.valor),
        0
    );
    const lineWidth = Math.max(8, size * .032);
    const gap = Math.max(8, size * .025);
    const outerRadius = size * .43;
    const startAngle = Math.PI * .45;
    const completeAngle = Math.PI * 1.55;

    construirLeyendaRadial(metricas, colores, numero, moneda);

    animarGrafica(function(progress) {
        const { context, width, height } = prepararCanvas(canvas, size, size);
        const centerX = width / 2;
        const centerY = height / 2;

        metricas.forEach(function(metrica, index) {
            const radius = outerRadius - index * (lineWidth + gap);
            const maximum = metrica.tipo === 'moneda' ? maxMoneda : maxConteo;
            const proportion = maximum > 0 ? Math.min(metrica.valor / maximum, 1) : 0;

            // La guía gris conecta visualmente valores en cero con la misma escala radial.
            context.beginPath();
            context.arc(centerX, centerY, radius, startAngle, startAngle + completeAngle);
            context.strokeStyle = '#edf2f7';
            context.lineWidth = lineWidth;
            context.lineCap = 'round';
            context.stroke();

            if (proportion <= 0) return;
            const gradient = context.createLinearGradient(centerX - radius, centerY, centerX + radius, centerY);
            gradient.addColorStop(0, colores[index] + 'B8');
            gradient.addColorStop(1, colores[index]);
            context.beginPath();
            context.arc(centerX, centerY, radius, startAngle, startAngle + completeAngle * proportion * progress);
            context.strokeStyle = gradient;
            context.lineWidth = lineWidth;
            context.lineCap = 'round';
            context.stroke();
        });

        // El centro identifica el periodo consultado y permanece legible al imprimir.
        context.fillStyle = '#f8fafc';
        context.beginPath();
        context.arc(centerX, centerY, size * .15, 0, Math.PI * 2);
        context.fill();
        context.fillStyle = '#94a3b8';
        context.font = '800 9px system-ui, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText('PERIODO', centerX, centerY - 9);
        context.fillStyle = '#0f1f3d';
        context.font = '800 15px system-ui, sans-serif';
        context.fillText(String(datosReporteGeneral.periodo).toUpperCase(), centerX, centerY + 11);
    }, animate);
}

/*
 * Renderiza todas las gráficas y se reutiliza al cambiar el tamaño o imprimir.
 */
function renderizarGraficasReporte() {
    const productos = datosGraficasReporte.productos;
    const ordenes = datosGraficasReporte.ordenes;
    const animate = !graficasReporteAnimadas;

    dibujarReporteGeneralRadial(formatoNumeroReporte, formatoMonedaReporte, animate);
    dibujarDona(
        'grafica-ventas',
        'leyenda-ventas',
        'tooltip-grafica-ventas',
        productos.etiquetas,
        productos.cantidades,
        value => formatoNumeroReporte.format(value),
        'Unidades',
        animate
    );
    dibujarDona(
        'grafica-total-vendido',
        'leyenda-total-vendido',
        'tooltip-grafica-total-vendido',
        productos.etiquetas,
        productos.ingresos,
        value => formatoMonedaReporte.format(value),
        'Ingresos',
        animate
    );
    dibujarBarrasOrdenes(ordenes.etiquetas, ordenes.cantidades, animate);
    graficasReporteAnimadas = true;
}

let temporizadorGraficasReporte;
window.addEventListener('resize', function() {
    clearTimeout(temporizadorGraficasReporte);
    temporizadorGraficasReporte = setTimeout(renderizarGraficasReporte, 120);
});
window.addEventListener('beforeprint', renderizarGraficasReporte);
window.addEventListener('afterprint', renderizarGraficasReporte);
requestAnimationFrame(renderizarGraficasReporte);
</script>
@endsection
