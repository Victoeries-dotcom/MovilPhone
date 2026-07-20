@extends('layout')

@section('content')
<div class="page-header">
    <h1>{{ $cliente->nombre }}</h1>
    <a href="{{ route('clientes.index') }}" class="btn">← Volver</a>
</div>

{{-- Resumen del cliente: usa los datos de clientes y el total real de órdenes cargado por ClienteController::show. --}}
<div class="card" style="margin-bottom:10px">
    <div class="form-grid">
        <div>
            <div style="font-size:11px;color:#888">Teléfono principal</div>
            <div style="font-size:14px">{{ $cliente->telefono_principal }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Teléfono alternativo</div>
            <div style="font-size:14px">{{ $cliente->telefono_alternativo ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Cliente desde</div>
            <div style="font-size:14px">{{ $cliente->created_at->format('d/m/Y') }}</div>
        </div>
        <div>
            <div style="font-size:11px;color:#888">Total de visitas</div>
            <div style="font-size:14px">{{ $cliente->ordenes->count() }}</div>
        </div>
    </div>
</div>

@php
    // Separa el trabajo vigente del historial cerrado sin guardar contadores duplicados en clientes.
    $ordenesActivas = $cliente->ordenes->whereNotIn('estado', ['ENTREGADO', 'RECHAZADO']);
    $serviciosAnteriores = $cliente->ordenes->whereIn('estado', ['ENTREGADO', 'RECHAZADO']);

    // Relaciona cada estado de OrdenServicio con los colores definidos en layout.blade.php.
    $clasesEstado = [
        'RECIBIDO' => 'badge-recibido',
        'EN DIAGNÓSTICO' => 'badge-diagnostico',
        'ESPERANDO AUTORIZACIÓN' => 'badge-espera',
        'AUTORIZADO' => 'badge-autorizado',
        'RECHAZADO' => 'badge-rechazado',
        'EN REPARACIÓN' => 'badge-reparacion',
        'ESPERANDO REFACCIÓN' => 'badge-espera',
        'TERMINADO' => 'badge-terminado',
        'NOTIFICADO' => 'badge-terminado',
        'ENTREGADO' => 'badge-entregado',
        'GARANTÍA' => 'badge-garantia',
    ];
@endphp

{{-- Órdenes activas: conecta al cliente con los equipos que todavía siguen en el taller. --}}
<div style="font-size:14px;font-weight:700;margin:1.5rem 0 10px;color:#0f1f3d;">
    Órdenes activas ({{ $ordenesActivas->count() }})
</div>
<div style="display:grid;gap:10px;margin-bottom:1.5rem;">
    @forelse($ordenesActivas as $orden)
        {{-- Tarjeta de orden activa: resume el equipo y enlaza directamente con OrdenServicioController::show. --}}
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-left:4px solid #2563eb;border-radius:8px;padding:16px 18px;">
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                <div style="min-width:240px;flex:1;">
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px;">
                        <strong style="font-size:16px;color:#0f1f3d;">{{ $orden->numero_os }}</strong>
                        <span class="badge {{ $clasesEstado[$orden->estado] ?? 'badge-recibido' }}">{{ $orden->estado }}</span>
                    </div>
                    <div style="font-size:13px;color:#334155;margin-bottom:5px;">
                        <strong>Equipo:</strong> {{ trim(($orden->tipo_dispositivo ?? '').' '.$orden->marca.' '.$orden->modelo) }}
                    </div>
                    <div style="font-size:13px;color:#334155;margin-bottom:5px;">
                        <strong>Problema:</strong> {{ Str::limit($orden->problema_reportado, 100) }}
                    </div>
                    <div style="font-size:12px;color:#64748b;">
                        Ingresó el {{ $orden->created_at->format('d/m/Y') }}
                    </div>
                    @if(($orden->anticipo ?? 0) > 0)
                        <div style="font-size:12px;color:#15803d;font-weight:700;margin-top:6px;">
                            Anticipo: ${{ number_format($orden->anticipo, 2) }}
                        </div>
                    @endif
                </div>
                <a href="{{ route('ordenes.show', $orden) }}" class="btn btn-primary">Ver orden</a>
            </div>
        </div>
    @empty
        <div style="text-align:center;color:#888;padding:2rem;background:#fff;border:1px solid #e2e8f0;border-radius:8px;">
            Este cliente no tiene órdenes activas en este momento.
        </div>
    @endforelse
</div>

{{-- Servicios anteriores: muestra únicamente órdenes entregadas o rechazadas. --}}
<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:10px;flex-wrap:wrap;">
    <div style="font-size:14px;font-weight:700;color:#0f1f3d;">
        Servicios anteriores ({{ $serviciosAnteriores->count() }})
    </div>
    @if($serviciosAnteriores->isNotEmpty())
        {{-- Aclara que este historial se conecta solamente con estados ENTREGADO y RECHAZADO. --}}
        <span style="font-size:12px;color:#64748b;">Entregados o rechazados</span>
    @endif
</div>
<table>
    <thead>
        <tr>
            <th>Número OS</th>
            <th>Equipo</th>
            <th>Problema</th>
            <th>Estado</th>
            <th>Monto</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @forelse($serviciosAnteriores as $orden)
            <tr>
                <td><a href="{{ route('ordenes.show', $orden) }}" style="color:#2563eb">{{ $orden->numero_os }}</a></td>
                <td>{{ $orden->marca }} {{ $orden->modelo }}</td>
                <td>{{ Str::limit($orden->problema_reportado, 40) }}</td>
                <td><span class="badge {{ $orden->estado === 'RECHAZADO' ? 'badge-rechazado' : 'badge-entregado' }}">{{ $orden->estado }}</span></td>
                <td>${{ number_format($orden->presupuesto_total ?? 0, 2) }}</td>
                <td>
                    {{ $orden->created_at->format('d/m/Y') }}
                    @if($orden->fecha_entrega_real)
                        <div style="font-size:11px;color:#16a34a;">Entregado: {{ \Carbon\Carbon::parse($orden->fecha_entrega_real)->format('d/m/Y') }}</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#888;padding:2rem">Este cliente todavía no tiene servicios anteriores.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="display:flex;gap:8px;margin-top:1rem">
    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-primary">Editar cliente</a>
    <a href="{{ route('ordenes.create') }}" class="btn btn-success">+ Nueva OS para este cliente</a>
</div>
@endsection
