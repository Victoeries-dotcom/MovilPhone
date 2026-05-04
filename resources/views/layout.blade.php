<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>MovilPhone — Sistema de Taller</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f5f5f4; color: #1a1a1a; }
        .sidebar { position: fixed; top: 0; left: 0; width: 220px; height: 100vh; background: #1a1a1a; padding: 1.5rem 1rem; display: flex; flex-direction: column; gap: 8px; }
        .brand { color: white; font-size: 16px; font-weight: 600; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #333; }
        .brand span { font-size: 11px; display: block; color: #888; font-weight: 400; margin-top: 2px; }
        .nav-link { display: block; padding: 8px 12px; color: #aaa; text-decoration: none; border-radius: 6px; font-size: 13px; }
        .nav-link:hover, .nav-link.active { background: #333; color: white; }
        .main { margin-left: 220px; padding: 2rem; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 20px; font-weight: 600; }
        .btn { padding: 7px 14px; border-radius: 6px; border: 1px solid #ddd; background: white; color: #1a1a1a; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #f5f5f4; }
        .btn-primary { background: #1a1a1a; color: white; border-color: #1a1a1a; }
        .btn-primary:hover { background: #333; }
        .btn-danger { color: #dc2626; border-color: #fca5a5; }
        .btn-danger:hover { background: #fef2f2; }
        .btn-success { color: #16a34a; border-color: #86efac; }
        .btn-success:hover { background: #f0fdf4; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 1.5rem; }
        .stat-card { background: white; border-radius: 8px; padding: 14px 16px; border: 1px solid #e5e5e5; }
        .stat-label { font-size: 11px; color: #888; margin-bottom: 4px; }
        .stat-num { font-size: 24px; font-weight: 600; }
        .stat-num.blue { color: #2563eb; }
        .stat-num.amber { color: #d97706; }
        .stat-num.green { color: #16a34a; }
        .stat-num.red { color: #dc2626; }
        .toolbar { display: flex; gap: 8px; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
        .toolbar input, .toolbar select { padding: 7px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; background: white; }
        .card { background: white; border-radius: 8px; border: 1px solid #e5e5e5; padding: 1rem 1.25rem; margin-bottom: 10px; }
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
        .form-group label { display: block; font-size: 12px; color: #666; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
        .full-width { grid-column: 1 / -1; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 1rem; font-size: 13px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e5e5e5; }
        th { background: #f9f9f9; padding: 10px 14px; text-align: left; font-size: 12px; color: #666; font-weight: 500; border-bottom: 1px solid #e5e5e5; }
        td { padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="brand">
        ⚙ MovilPhone
        <span>Sistema de Taller</span>
    </div>
    <a href="{{ route('ordenes.index') }}" class="nav-link {{ request()->is('ordenes*') ? 'active' : '' }}">📋 Órdenes de Servicio</a>
    <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->is('clientes*') ? 'active' : '' }}">👤 Clientes</a>
    <a href="{{ route('inventario.index') }}" class="nav-link {{ request()->is('inventario*') ? 'active' : '' }}">📦 Inventario</a>
    <a href="{{ route('caja.index') }}" class="nav-link {{ request()->is('caja*') ? 'active' : '' }}">💰 Caja</a>
</div>
<div class="main">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @yield('content')
</div>
</body>
</html>