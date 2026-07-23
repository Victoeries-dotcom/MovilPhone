<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }} · Sistema de Taller</title>
    {{-- Metadatos globales: conectan peticiones AJAX y modo instalable con la misma sesion Laravel. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $configuracionGlobal['color_primario'] ?? '#1650c5' }}">
    {{-- Tema inicial: recupera la preferencia antes de pintar la pagina para evitar destellos de color. --}}
    <script>
        (function () {
            const temaGuardado = window.localStorage.getItem('movilphone.ui.theme');
            document.documentElement.dataset.uiTheme = temaGuardado === 'light' ? 'light' : 'dark';
        })();
    </script>
    <link rel="manifest" href="/manifest.webmanifest?v=20260720">
    {{-- Identidad de pestaña: usa una ruta relativa para conectarse al mismo host y puerto del sistema. --}}
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=20260717">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f0f2f5; color: #1a1a1a; }

        /* Colores del menú lateral: cambia estos valores para personalizar todo el menú. */
        :root {
            --menu-fondo: {{ $configuracionGlobal['color_primario'] ?? '#1650c5' }}; /* Fondo conectado con Configuracion comercial. */
            --menu-texto: rgba(255,255,255,0.65); /* Color normal de los enlaces del menú. */
            --menu-texto-activo: #ffffff; /* Color del texto cuando una opción está activa o seleccionada. */
            --menu-hover: rgba(255,255,255,0.07); /* Fondo al pasar el mouse sobre una opción del menú. */
            --menu-activo: rgba(59,130,246,0.18); /* Fondo de la opción seleccionada; se conecta con .nav-link.active. */
            --menu-linea-activa: #3b82f6; /* Línea izquierda de la opción seleccionada. */
            --menu-bordes: rgba(255,255,255,0.08); /* Líneas divisorias del menú y pie de usuario. */
        }

        .sidebar { position: fixed; top: 0; left: 0; width: 230px; height: 100vh; background: var(--menu-fondo); padding: 0; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.2); overflow-y: auto; }
        .brand { padding: 1.5rem 1.25rem; border-bottom: 1px solid var(--menu-bordes); }
        .brand-name { color: white; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        .brand-sub { color: rgba(255,255,255,0.4); font-size: 11px; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.05em; }
        /* Presenta la sucursal activa y conecta el botón con el selector dinámico del menú. */
        .branch-switcher-row { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-top: 7px; }
        .branch-current { color: #dbeafe; font-size: 12px; font-weight: 700; }
        .branch-switcher { position: relative; }
        .branch-switcher summary { list-style: none; }
        .branch-switcher summary::-webkit-details-marker { display: none; }
        .branch-switch-button { display: inline-flex; align-items: center; gap: 4px; padding: 4px 7px; border: 1px solid rgba(255,255,255,0.25); border-radius: 5px; background: rgba(255,255,255,0.1); color: white; font: inherit; font-size: 10px; font-weight: 700; cursor: pointer; }
        .branch-switch-button:hover { background: rgba(255,255,255,0.18); }
        /* El menú consulta todas las sucursales compartidas por AppServiceProvider. */
        .branch-menu { position: absolute; top: calc(100% + 6px); left: 0; z-index: 100; width: 180px; padding: 6px; border: 1px solid #dbe3ef; border-radius: 6px; background: white; box-shadow: 0 12px 28px rgba(15,31,61,0.24); }
        .branch-option { width: 100%; padding: 8px 9px; border: 0; border-radius: 4px; background: transparent; color: #334155; font: inherit; font-size: 12px; font-weight: 600; text-align: left; cursor: pointer; }
        .branch-option:hover { background: #eff6ff; color: #1d4ed8; }
        .branch-option.active { background: #dbeafe; color: #1d4ed8; cursor: default; }
        .branch-empty { padding: 8px 9px; color: #64748b; font-size: 11px; }
        .nav-section { padding: 1rem 1.25rem 0.4rem; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.25); }
        .nav-link { display: flex; align-items: center; gap: 0.65rem; padding: 0.65rem 1.25rem; color: var(--menu-texto); text-decoration: none; font-size: 13.5px; font-weight: 500; border-left: 3px solid transparent; transition: all 0.15s; }
        .nav-link:hover { background: var(--menu-hover); color: var(--menu-texto-activo); }
        .nav-link.active { background: var(--menu-activo); color: var(--menu-texto-activo); border-left-color: var(--menu-linea-activa); }
        .nav-link .icon { font-size: 15px; width: 20px; text-align: center; }
        .nav-divider { border-top: 1px solid var(--menu-bordes); margin: 0.5rem 0; }
        .sidebar-footer { margin-top: auto; padding: 1rem 1.25rem; border-top: 1px solid var(--menu-bordes); }
        .user-info { display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.75rem; }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #3b82f6; display: flex; align-items: center; justify-content: center; color: white; font-size: 13px; font-weight: 700; flex-shrink: 0; }
        .user-name { color: white; font-size: 13px; font-weight: 500; }
        .user-rol { color: rgba(255,255,255,0.4); font-size: 11px; }
        .logout-btn { display: block; padding: 0.45rem 0.75rem; background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.65); border-radius: 6px; font-size: 12px; text-align: center; cursor: pointer; border: none; width: 100%; font-family: inherit; transition: all 0.15s; }
        .logout-btn:hover { background: rgba(255,255,255,0.12); color: white; }
        .main { margin-left: 230px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: white; border-bottom: 1px solid #e2e8f0; padding: 0.85rem 2rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
        .topbar-title { font-size: 15px; font-weight: 600; color: #1e293b; }
        .topbar-badge { font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .topbar-badge.super { background: #dbeafe; color: #1e40af; }
        .topbar-badge.capturista { background: #fef9c3; color: #854d0e; }
        .topbar-badge.vendedor { background: #ede9fe; color: #5b21b6; }
        .topbar-badge.user { background: #dcfce7; color: #166534; }
        .content { padding: 2rem; flex: 1; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 1rem; font-size: 13px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .btn { padding: 7px 14px; border-radius: 6px; border: 1px solid #ddd; background: white; color: #1a1a1a; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; transition: all 0.15s; }
        .btn:hover { background: #f5f5f4; }
        .btn-primary { background: #0f1f3d; color: white; border-color: #265f86; }
        .btn-primary:hover { background: #1e3a8a; border-color: #1e3a8a; }
        .btn-danger { color: #dc2626; border-color: #fca5a5; }
        .btn-danger:hover { background: #fef2f2; }
        .btn-success { color: #16a34a; border-color: #86efac; }
        .btn-success:hover { background: #f0fdf4; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 1.5rem; }
        .stat-card { background: white; border-radius: 10px; padding: 1.25rem 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .stat-label { font-size: 11px; color: #888; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        .stat-num { font-size: 26px; font-weight: 700; color: #0f1f3d; }
        .stat-num.blue { color: #2563eb; }
        .stat-num.amber { color: #d97706; }
        .stat-num.green { color: #16a34a; }
        .stat-num.red { color: #dc2626; }
        .toolbar { display: flex; gap: 8px; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
        .toolbar input, .toolbar select { padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; background: white; }
        .card { background: white; border-radius: 10px; border: 1px solid #e2e8f0; padding: 1rem 1.25rem; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 500; }
        .badge-recibido { background: #e5e5e5; color: #555; }
        .badge-diagnostico { background: #dbeafe; color: #1d4ed8; }
        .badge-espera { background: #fef3c7; color: #92400e; }
        .badge-autorizado { background: #dcfce7; color: #166534; }
        .badge-rechazado { background: #fee2e2; color: #991b1b; }
        .badge-reparacion { background: #ede9fe; color: #5b21b6; }
        .badge-terminado { background: #ccfbf1; color: #065f46; }
        .badge-entregado { background: #f0fdf4; color: #166534; }
        .badge-garantia { background: #fce7f3; color: #9d174d; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; transition: border-color 0.15s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
        .full-width { grid-column: 1 / -1; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        th { background: #f8fafc; padding: 10px 14px; text-align: left; font-size: 11.5px; color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem; }
        .page-header h1 { font-size: 20px; font-weight: 700; color: #0f1f3d; letter-spacing: -0.02em; }
        .page-title-sub { font-size: 13px; color: #64748b; margin-top: 2px; }
    </style>
    {{-- Capa visual global: conecta todas las vistas con colores, sombras y animaciones profesionales. --}}
{{-- Estilos globales: la ruta relativa evita depender de APP_URL al trabajar con Laragon o artisan serve. --}}
<link rel="stylesheet" href="/css/movilphone-ui.css?v=20260720-theme-toggle-2">
</head>
<body data-demo-mode="{{ !empty($configuracionGlobal['modo_demo']) ? 'true' : 'false' }}">
@auth
    @php
        /*
         * Resuelve la sucursal visible desde la sesion y usa la asignacion del usuario como respaldo.
         * Se conecta con el selector lateral, la cabecera y todos los controladores filtrados por sucursal.
         */
        $sucursalUiId = (int) (session('sucursal_id') ?: auth()->user()->sucursal_id);
        $sucursalUiNombre = session('sucursal_nombre')
            ?? auth()->user()->sucursal?->nombre
            ?? 'Sin sucursal';
    @endphp
@endauth
<div class="sidebar">
    <div class="brand">
        {{-- Marca principal: usa el icono Smartphone de Lucide y se conecta con el menú lateral. --}}
        <div class="brand-name">
            <span class="brand-mark" aria-hidden="true"><i data-lucide="smartphone"></i></span>
            <span>{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }}</span>
        </div>
        <div class="brand-sub">{{ $configuracionGlobal['negocio_subtitulo'] ?? 'Sistema de Taller' }}</div>
        @auth
            {{-- Muestra la sucursal activa y permite al Super Usuario cambiarla sin salir de la pantalla actual. --}}
            <div class="branch-switcher-row">
                <span class="branch-current">{{ $sucursalUiNombre }}</span>

                @if(auth()->user()->rol === 'superusuario')
                    <details class="branch-switcher">
                        <summary class="branch-switch-button">
                            <i data-lucide="store" aria-hidden="true"></i>
                            Cambiar sucursal
                            <i data-lucide="chevron-down" aria-hidden="true"></i>
                        </summary>
                        <div class="branch-menu">
                            @forelse($sucursalesMenu as $sucursalMenu)
                                <form method="POST" action="{{ route('sucursales.cambiar') }}">
                                    @csrf
                                    <input type="hidden" name="sucursal_id" value="{{ $sucursalMenu->id }}">
                                    <button
                                        type="submit"
                                        class="branch-option {{ $sucursalUiId === $sucursalMenu->id ? 'active' : '' }}"
                                        {{ $sucursalUiId === $sucursalMenu->id ? 'disabled' : '' }}
                                    >
                                        {{ $sucursalMenu->nombre }}
                                    </button>
                                </form>
                            @empty
                                <div class="branch-empty">No hay sucursales registradas.</div>
                            @endforelse
                        </div>
                    </details>
                @endif
            </div>
        @endauth
    </div>

    <div class="nav-section">Menú</div>

    @auth
        {{-- Panel principal: todos los roles ven indicadores correspondientes a su sucursal activa. --}}
        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
            <span class="icon"><i data-lucide="layout-dashboard"></i></span> Panel principal
        </a>
        @php
            /*
             * Permisos del menú: usa siempre users.rol de la cuenta autenticada.
             * La sucursal activa filtra los registros, pero nunca eleva permisos a superusuario.
             */
            $rolMenu = auth()->user()->rol;
        @endphp
        @if($rolMenu === 'superusuario')
            <a href="{{ route('ordenes.index') }}" class="nav-link {{ request()->is('ordenes*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="wrench"></i></span> Órdenes de Servicio
            </a>
            <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->is('clientes*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="contact"></i></span> Clientes
            </a>
            <a href="{{ route('inventario.index') }}" class="nav-link {{ request()->is('inventario*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="package-open"></i></span> Inventario
            </a>
            <a href="{{ route('caja.index') }}" class="nav-link {{ request()->is('caja*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="wallet-cards"></i></span> Caja
            </a>
            <a href="{{ route('usuarios.index') }}" class="nav-link {{ request()->is('usuarios*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="users"></i></span> Usuarios
            </a>
            <a href="{{ route('sucursales.index') }}" class="nav-link {{ request()->is('sucursales*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="store"></i></span> Sucursales
            </a>
            <div class="nav-divider"></div>
            <div class="nav-section">Roles</div>
            <a href="{{ route('categorias.index') }}" class="nav-link {{ request()->is('categorias*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="tags"></i></span> Categorías
            </a>
            <a href="{{ route('ventas.index') }}" class="nav-link {{ request()->is('ventas*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="shopping-cart"></i></span> Ventas
            </a>
            <div class="nav-divider"></div>
            <div class="nav-section">Reportes</div>
            <a href="{{ route('actividad.index') }}" class="nav-link {{ request()->is('actividad*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="bell-ring"></i></span> Actividad
            </a>
            <a href="{{ route('reportes.index') }}" class="nav-link {{ request()->is('reportes*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="chart-no-axes-combined"></i></span> Reportes
            </a>
            <a href="{{ route('configuracion.edit') }}" class="nav-link {{ request()->is('configuracion*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="settings-2"></i></span> Configuracion
            </a>
        @elseif($rolMenu === 'capturista')
            <a href="{{ route('inventario.index') }}" class="nav-link {{ request()->is('inventario*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="package-open"></i></span> Inventario
            </a>
            <a href="{{ route('categorias.index') }}" class="nav-link {{ request()->is('categorias*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="tags"></i></span> Categorías
            </a>
        @elseif($rolMenu === 'vendedor')
            <a href="{{ route('ventas.index') }}" class="nav-link {{ request()->is('ventas*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="shopping-cart"></i></span> Ventas
            </a>
            <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->is('clientes*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="contact"></i></span> Clientes
            </a>
        @elseif($rolMenu === 'tecnico')
            {{-- Técnico: se conecta con las rutas de taller y caja autorizadas por RoleMiddleware. --}}
            <a href="{{ route('ordenes.index') }}" class="nav-link {{ request()->is('ordenes*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="wrench"></i></span> Órdenes de Servicio
            </a>
            <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->is('clientes*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="contact"></i></span> Clientes
            </a>
            <a href="{{ route('caja.index') }}" class="nav-link {{ request()->is('caja*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="wallet-cards"></i></span> Caja
            </a>
        @elseif($rolMenu === 'usuario')
            {{-- Usuario de sucursal: conserva los siete módulos operativos definidos para su trabajo diario. --}}
            <a href="{{ route('ordenes.index') }}" class="nav-link {{ request()->is('ordenes*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="wrench"></i></span> Órdenes de Servicio
            </a>
            <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->is('clientes*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="contact"></i></span> Clientes
            </a>
            <a href="{{ route('inventario.index') }}" class="nav-link {{ request()->is('inventario*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="package-open"></i></span> Inventario
            </a>
            <a href="{{ route('caja.index') }}" class="nav-link {{ request()->is('caja*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="wallet-cards"></i></span> Caja
            </a>
            <a href="{{ route('categorias.index') }}" class="nav-link {{ request()->is('categorias*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="tags"></i></span> Categorías
            </a>
            <a href="{{ route('ventas.index') }}" class="nav-link {{ request()->is('ventas*') ? 'active' : '' }}">
                <span class="icon"><i data-lucide="shopping-cart"></i></span> Ventas
            </a>
        @endif
    @endauth

    <div class="sidebar-footer">
        @auth
        <div class="user-info">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div>
                <div class="user-name">{{ auth()->user()->name }}</div>
                {{-- Identidad lateral: muestra el rol real guardado en users.rol para evitar permisos aparentes. --}}
                <div class="user-rol">{{ auth()->user()->rol }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            {{-- Cierre de sesión: conserva la ruta logout y presenta su acción con iconografía consistente. --}}
            <button type="submit" class="logout-btn"><i data-lucide="log-out"></i><span>Cerrar sesión</span></button>
        </form>
        @endauth
    </div>
</div>

<div class="main">
    <div class="topbar">
        @php
            // Define el contexto visible de la cabecera y se conecta con la ruta activa de cada módulo.
            [$seccionActual, $iconoSeccion] = match (true) {
                request()->is('ordenes*') => ['Órdenes de Servicio', 'wrench'],
                request()->is('clientes*') => ['Clientes', 'contact'],
                request()->is('inventario*') => ['Inventario', 'package-open'],
                request()->is('caja*') => ['Caja y Finanzas', 'wallet-cards'],
                request()->is('usuarios*') => ['Usuarios', 'users'],
                request()->is('sucursales*') => ['Sucursales', 'store'],
                request()->is('categorias*') => ['Categorías', 'tags'],
                request()->is('ventas*') => ['Ventas', 'shopping-cart'],
                request()->is('actividad*') => ['Actividad', 'bell-ring'],
                request()->is('reportes*') => ['Reportes', 'chart-no-axes-combined'],
                request()->is('configuracion*') => ['Configuracion', 'settings-2'],
                default => ['Panel principal', 'layout-dashboard'],
            };
        @endphp
        {{-- Contexto superior: orienta al usuario y se actualiza automáticamente según la ruta visitada. --}}
        <div class="topbar-context">
            <span class="topbar-section-icon" aria-hidden="true"><i data-lucide="{{ $iconoSeccion }}"></i></span>
            <div>
                <span class="topbar-title">{{ $seccionActual }}</span>
                <span class="topbar-path">{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }} / {{ $seccionActual }}</span>
            </div>
        </div>
        @auth
        @php
            /*
             * Rol de cabecera: se conecta con users.rol y controla notificaciones, distintivo y accesos visuales.
             * Cambiar la sucursal solo actualiza el contexto de datos y conserva estas autorizaciones.
             */
            $rol = auth()->user()->rol;
        @endphp
        {{-- Busqueda global: consulta registros autorizados y abre resultados sin abandonar la pantalla. --}}
        <div class="global-search" data-search-url="{{ route('buscar.global') }}" data-detail-template="{{ route('buscar.detalle', ['tipo' => '__TYPE__', 'id' => '__ID__']) }}">
            <i data-lucide="search" aria-hidden="true"></i>
            <input type="search" id="globalSearchInput" placeholder="Buscar cliente, OS, pieza o venta" autocomplete="off" aria-label="Buscar en MovilPhone" aria-expanded="false">
            <kbd>Ctrl K</kbd>
            <div class="global-search-results" id="globalSearchResults" aria-live="polite"></div>
        </div>
        {{-- Acciones de contexto: muestran notificaciones, sucursal y rol de la sesion autenticada. --}}
        <div class="topbar-actions">
            @if($rol === 'superusuario')
                <div class="notification-center" data-notifications-url="{{ route('actividad.notificaciones') }}" data-read-url="{{ route('actividad.notificaciones.leidas') }}">
                    <button type="button" class="topbar-icon-button" id="notificationToggle" aria-label="Abrir notificaciones" aria-expanded="false">
                        <i data-lucide="bell"></i><span class="notification-count" hidden>0</span>
                    </button>
                    <div class="notification-panel" id="notificationPanel">
                        <div class="notification-header"><div><strong>Notificaciones</strong><small>Actividad de {{ $sucursalUiNombre }}</small></div><a href="{{ route('actividad.index') }}">Ver todo</a></div>
                        <div class="notification-list" id="notificationList"><div class="notification-loading">Consultando actividad...</div></div>
                    </div>
                </div>
            @endif
            {{-- Selector visual: cambia entre temas y se conecta con la preferencia local del navegador. --}}
            <button type="button" class="topbar-icon-button theme-toggle" id="themeToggle" aria-label="Cambiar a modo claro" aria-pressed="false" title="Cambiar a modo claro">
                <i data-lucide="sun" aria-hidden="true"></i>
            </button>
            <span class="topbar-branch"><i data-lucide="map-pin"></i>{{ $sucursalUiNombre }}</span>
            <span class="topbar-badge {{ $rol === 'superusuario' ? 'super' : ($rol === 'capturista' ? 'capturista' : ($rol === 'vendedor' ? 'vendedor' : 'user')) }}">
                @if($rol === 'superusuario')
                    <i data-lucide="shield-check"></i> Super Usuario
                @elseif($rol === 'capturista')
                    <i data-lucide="clipboard-list"></i> Capturista
                @elseif($rol === 'vendedor')
                    <i data-lucide="shopping-cart"></i> Vendedor
                @elseif($rol === 'tecnico')
                    <i data-lucide="wrench"></i> Técnico
                @else
                    <i data-lucide="user"></i> Usuario
                @endif
            </span>
        </div>
        @endauth
    </div>
    @if(!empty($configuracionGlobal['modo_demo']))
        {{-- Aviso comercial: indica que ProtectDemoMode bloquea eliminaciones durante la demostracion. --}}
        <div class="demo-mode-banner"><i data-lucide="presentation"></i><strong>Modo demostracion activo</strong><span>Las eliminaciones estan protegidas.</span></div>
    @endif
    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</div>

{{-- Panel rapido: recibe JSON de GlobalSearchController y muestra detalles sin perder el contexto actual. --}}
<button type="button" class="quick-view-backdrop" id="quickViewBackdrop" aria-label="Cerrar detalle rapido"></button>
<aside class="quick-view-drawer" id="quickViewDrawer" aria-hidden="true" aria-labelledby="quickViewTitle">
    <div class="quick-view-header">
        <div><span id="quickViewSubtitle">DETALLE</span><h2 id="quickViewTitle">Vista rapida</h2></div>
        <button type="button" class="topbar-icon-button" id="quickViewClose" aria-label="Cerrar detalle"><i data-lucide="x"></i></button>
    </div>
    <div class="quick-view-body" id="quickViewBody"></div>
    <div class="quick-view-footer"><a href="#" class="btn btn-primary" id="quickViewOpen"><i data-lucide="external-link"></i><span>Abrir registro completo</span></a></div>
</aside>

{{-- Modal de confirmación: reemplaza confirm() y se conecta con todos los formularios de eliminación. --}}
<div id="ui-confirm-dialog" class="ui-dialog" aria-hidden="true">
    <div class="ui-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="ui-confirm-title" aria-describedby="ui-confirm-message">
        <button type="button" class="ui-dialog-close" data-ui-dialog-close aria-label="Cerrar confirmación"><i data-lucide="x"></i></button>
        <span class="ui-dialog-icon ui-dialog-icon-warning" aria-hidden="true"><i data-lucide="triangle-alert"></i></span>
        <div class="ui-dialog-step" id="ui-confirm-step">Confirmación</div>
        <h2 id="ui-confirm-title">¿Confirmar acción?</h2>
        <p id="ui-confirm-message"></p>
        <div class="ui-dialog-actions">
            <button type="button" class="btn" data-ui-dialog-close>Cancelar</button>
            <button type="button" class="btn btn-danger" id="ui-confirm-continue"><i data-lucide="arrow-right"></i><span>Continuar</span></button>
        </div>
    </div>
</div>

{{-- Modal informativo: sustituye alert() y se conecta con validaciones de órdenes, caja y formularios. --}}
<div id="ui-notice-dialog" class="ui-dialog" aria-hidden="true">
    <div class="ui-dialog-panel" role="alertdialog" aria-modal="true" aria-labelledby="ui-notice-title" aria-describedby="ui-notice-message">
        <button type="button" class="ui-dialog-close" data-ui-notice-close aria-label="Cerrar aviso"><i data-lucide="x"></i></button>
        <span class="ui-dialog-icon" aria-hidden="true"><i data-lucide="info"></i></span>
        <div class="ui-dialog-step">{{ $configuracionGlobal['negocio_nombre'] ?? 'MovilPhone' }}</div>
        <h2 id="ui-notice-title">Revisa la información</h2>
        <p id="ui-notice-message"></p>
        <div class="ui-dialog-actions">
            <button type="button" class="btn btn-primary" data-ui-notice-close><i data-lucide="check"></i><span>Entendido</span></button>
        </div>
    </div>
</div>
<script>
    /*
     * Convierte a MAYÚSCULAS los textos capturados en formularios del sistema.
     * Se conecta con todos los input/textarea visibles para guardar registros uniformes.
     */
    function debeConvertirAMayusculas(campo) {
        const tipo = (campo.type || '').toLowerCase();
        const tiposExcluidos = ['email', 'password', 'hidden', 'number', 'url', 'date', 'time', 'datetime-local', 'month', 'week', 'color', 'file', 'checkbox', 'radio'];
        const form = campo.closest('form');
        const metodo = form ? (form.method || 'get').toLowerCase() : 'get';

        // Solo aplica a formularios que guardan datos; no toca URLs, buscadores o filtros GET.
        return metodo !== 'get' && !tiposExcluidos.includes(tipo) && !campo.dataset.noMayusculas;
    }

    /*
     * Aplica MAYÚSCULAS mientras se escribe y antes de enviar cualquier formulario.
     * Esto ayuda a que Inventario, Usuarios, Categorías, Ventas y demás registros queden consistentes.
     */
    document.addEventListener('input', function(event) {
        const campo = event.target;

        if (!campo.matches('input, textarea') || !debeConvertirAMayusculas(campo)) {
            return;
        }

        const inicio = campo.selectionStart;
        const fin = campo.selectionEnd;
        campo.value = campo.value.toUpperCase();

        if (inicio !== null && fin !== null) {
            campo.setSelectionRange(inicio, fin);
        }
    });

    document.addEventListener('submit', function(event) {
        event.target.querySelectorAll('input, textarea').forEach(function(campo) {
            if (debeConvertirAMayusculas(campo)) {
                campo.value = campo.value.toUpperCase();
            }
        });
    });

    /*
     * Protege todas las acciones de Eliminar con doble confirmación.
     * Se conecta con los formularios DELETE del sistema antes de llamar a sus controladores destroy.
     */
    function confirmarEliminacionSistema(event, tipoRegistro, nombreRegistro, detalleExtra = '') {
        event.preventDefault();
        event.stopPropagation();

        // Envía el formulario únicamente después de las dos etapas del modal profesional.
        window.MovilPhoneUI.confirmDelete({
            form: event.currentTarget,
            recordType: tipoRegistro,
            recordName: nombreRegistro || 'ESTE REGISTRO',
            detail: detalleExtra || 'se eliminarán todos sus datos relacionados en el sistema',
        });

        return false;
    }
</script>
<script>
    /*
     * Expone datos no sensibles para las interacciones globales y registra el modo instalable.
     * Se conecta con CSRF, configuracion comercial y public/service-worker.js.
     */
    window.MovilPhoneConfig = {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
        demoMode: document.body.dataset.demoMode === 'true',
        currency: @js($configuracionGlobal['moneda'] ?? 'MXN'),
    };

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/service-worker.js?v=20260720').catch(function () {
                // El sistema sigue funcionando normalmente si el navegador no permite instalarlo.
            });
        });
    }
</script>
{{-- Interacciones globales: conectan Lucide y la UI al mismo servidor que entrega la vista actual. --}}
<script src="/js/lucide.min.js?v=1.25.0" defer></script>
<script src="/js/movilphone-ui.js?v=20260720-theme-toggle" defer></script>
</body>
</html>
