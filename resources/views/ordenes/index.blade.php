@extends('layout')

@section('content')
{{-- Cabecera operativa: conecta accesos rápidos con Ventas, Caja y la creación de una nueva orden. --}}
<div class="page-header orders-page-header">
    <div>
        <h1>Órdenes de Servicio</h1>
        <p class="orders-page-subtitle">
            Sucursal: <strong>{{ $sucursalActiva?->nombre ?? 'Sin seleccionar' }}</strong>
        </p>
    </div>
    <div class="orders-page-actions">
        <a href="{{ route('ventas.create') }}" class="btn orders-action-primary">
            <i data-lucide="package-check" aria-hidden="true"></i>
            <span>Vender productos</span>
        </a>
        {{-- Garantía filtra las órdenes de la sucursal activa sin abrir Configuración, que es exclusiva del Super Usuario. --}}
        <a href="{{ route('ordenes.index', ['estado' => 'GARANTÍA']) }}" class="btn orders-action-neutral">
            <i data-lucide="shield-check" aria-hidden="true"></i>
            <span>Garantía</span>
        </a>
        <button type="button" class="btn btn-danger orders-action-outline" onclick="abrirModalEgreso()">
            <i data-lucide="circle-minus" aria-hidden="true"></i>
            <span>Egreso</span>
        </button>
        <button type="button" class="btn btn-success orders-action-outline" onclick="abrirModalIngreso()">
            <i data-lucide="circle-plus" aria-hidden="true"></i>
            <span>Ingreso</span>
        </button>
        <a href="{{ route('ordenes.create') }}" class="btn orders-action-primary">
            <i data-lucide="plus" aria-hidden="true"></i>
            <span>Nueva OS</span>
        </a>
    </div>
</div>

{{-- Indicadores: muestran los conteos calculados por OrdenServicioController::index para la sucursal seleccionada. --}}
<section class="stats-grid orders-stats" aria-label="Resumen de estados">
    <article class="stat-card orders-stat-card orders-stat-waiting">
        <div class="stat-label">En espera</div>
        <div class="stat-num blue">{{ $stats['recibidos'] }}</div>
    </article>
    <article class="stat-card orders-stat-card orders-stat-diagnostic">
        <div class="stat-label">Diagnóstico</div>
        <div class="stat-num amber">{{ $stats['diagnostico'] }}</div>
    </article>
    <article class="stat-card orders-stat-card orders-stat-repair">
        <div class="stat-label">Reparación</div>
        <div class="stat-num amber">{{ $stats['reparacion'] }}</div>
    </article>
    <article class="stat-card orders-stat-card orders-stat-ready">
        <div class="stat-label">Listo para recoger</div>
        <div class="stat-num green">{{ $stats['listos'] }}</div>
    </article>
    <article class="stat-card orders-stat-card orders-stat-delivered">
        <div class="stat-label">Entregado</div>
        <div class="stat-num green">{{ $stats['entregado'] }}</div>
    </article>
    <article class="stat-card orders-stat-card orders-stat-rejected">
        <div class="stat-label">No quedó / Rechazado</div>
        <div class="stat-num red">{{ $stats['rechazado'] }}</div>
    </article>
    <article class="stat-card orders-stat-card orders-stat-warranty">
        <div class="stat-label">Garantía</div>
        <div class="stat-num">{{ $stats['garantia'] }}</div>
    </article>
</section>

{{-- Filtros: envían search y estado por GET a OrdenServicioController::index y conservan el aislamiento por sucursal. --}}
<form method="GET" class="orders-filter-panel">
    <label class="orders-filter-field orders-filter-search">
        <span class="orders-filter-label"><i data-lucide="search" aria-hidden="true"></i> Buscar cliente</span>
        <span class="orders-input-wrap">
            <i data-lucide="search" aria-hidden="true"></i>
            <input type="search" name="search" placeholder="Nombre o teléfono..." value="{{ request('search') }}">
        </span>
    </label>
    <label class="orders-filter-field">
        <span class="orders-filter-label"><i data-lucide="list-filter" aria-hidden="true"></i> Estado de la orden</span>
        <span class="orders-select-wrap">
            <i data-lucide="list-filter" aria-hidden="true"></i>
            <select name="estado">
                <option value="">Todos los estados</option>
                @foreach([
                    'RECIBIDO' => 'En espera',
                    'EN DIAGNÓSTICO' => 'Diagnóstico',
                    'EN REPARACIÓN' => 'Reparación',
                    'TERMINADO' => 'Listo para recoger',
                    'NOTIFICADO' => 'Notificado',
                    'ENTREGADO' => 'Entregado',
                    'RECHAZADO' => 'No quedó / Rechazado',
                    'GARANTÍA' => 'Garantía',
                ] as $estado => $label)
                    <option value="{{ $estado }}" {{ request('estado') === $estado ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </span>
    </label>
    <div class="orders-filter-actions">
        <button type="submit" class="btn orders-action-primary">
            <i data-lucide="search" aria-hidden="true"></i>
            <span>Buscar</span>
        </button>
        @if(request('search') || request('estado'))
            <a href="{{ route('ordenes.index') }}" class="btn" title="Quitar filtros">
                <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                <span>Limpiar</span>
            </a>
        @endif
    </div>
</form>

{{-- Listado: cada tarjeta conserva las acciones conectadas con detalle, sticker, entrega, estados, edición y eliminación. --}}
<section class="orders-list" aria-label="Órdenes registradas">
@forelse($ordenes as $orden)
    @php
        // Estos indicadores controlan el color y las acciones visibles de las órdenes cerradas.
        $esEntregado = $orden->estado === 'ENTREGADO';
        $esRechazado = $orden->estado === 'RECHAZADO';
        $esGarantia = $orden->estado === 'GARANTÍA';
        $esCerrada = $esEntregado || $esRechazado || $esGarantia;
        $claseResultado = $esEntregado ? 'is-delivered' : ($esRechazado ? 'is-rejected' : ($esGarantia ? 'is-warranty' : ''));
        $badgeClass = [
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
        ][$orden->estado] ?? 'badge-recibido';
        $badgeLabel = [
            'RECIBIDO' => 'Recibido',
            'EN DIAGNÓSTICO' => 'Diagnóstico',
            'EN REPARACIÓN' => 'Reparación',
            'TERMINADO' => 'Listo para recoger',
            'NOTIFICADO' => 'Notificado',
            'ENTREGADO' => 'Entregado',
            'RECHAZADO' => 'Rechazado',
            'GARANTÍA' => 'En garantía',
        ][$orden->estado] ?? $orden->estado;
        // Las opciones usan los valores exactos almacenados en ordenes_servicio.estado.
        $estadosRapidos = [
            'EN DIAGNÓSTICO' => 'Diagnóstico',
            'EN REPARACIÓN' => 'Reparación',
            'TERMINADO' => 'Listo para recoger',
            'RECHAZADO' => 'No quedó / Rechazado',
            'GARANTÍA' => 'Garantía',
        ];
    @endphp
    <article class="card order-service-card {{ $claseResultado }}">
        <div class="order-service-content">
            <div class="order-service-summary">
                <div class="order-service-heading">
                    <strong>{{ $orden->numero_os }}</strong>
                    <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    @if($esEntregado)
                        <span class="order-result-badge is-success"><i data-lucide="badge-check"></i> Completado</span>
                    @elseif($esRechazado)
                        <span class="order-result-badge is-danger"><i data-lucide="circle-x"></i> No quedó</span>
                    @elseif($esGarantia)
                        <span class="order-result-badge is-warranty"><i data-lucide="shield-check"></i> En garantía</span>
                    @endif
                </div>
                <p class="order-service-client">
                    {{ $orden->cliente->nombre ?? 'Sin cliente' }} · {{ $orden->cliente->telefono_principal ?? 'Sin teléfono' }}
                </p>
                <p class="order-service-device">
                    {{ $orden->tipo_dispositivo ? $orden->tipo_dispositivo.' · ' : '' }}
                    {{ trim(($orden->marca ?? '').' '.($orden->modelo ?? '')) }}
                    {{ $orden->tecnico ? ' · Técnico: '.$orden->tecnico->name : '' }}
                </p>
                <p class="order-service-problem">{{ $orden->problema_reportado }}</p>
                @if((float) $orden->anticipo > 0)
                    {{-- El anticipo proviene de ordenes_servicio y se refleja también en movimientos_caja. --}}
                    <p class="order-service-advance"><i data-lucide="circle-dollar-sign"></i> Anticipo: ${{ number_format($orden->anticipo, 2) }}</p>
                @endif
            </div>

            <div class="order-service-actions">
                <a href="{{ route('ordenes.show', $orden) }}" class="btn">
                    <i data-lucide="eye" aria-hidden="true"></i><span>Ver detalle</span>
                </a>
                @if($orden->estado === 'RECIBIDO')
                    {{-- El sticker se imprime al recibir el equipo y se conecta con ordenes.sticker. --}}
                    <a href="{{ route('ordenes.sticker', $orden) }}" class="btn">
                        <i data-lucide="tag" aria-hidden="true"></i><span>Sticker</span>
                    </a>
                @endif
                @if($orden->estado === 'TERMINADO')
                    {{-- Entregar abre el flujo que registra técnico, cobro final y movimiento de Caja. --}}
                    <button type="button" class="btn btn-success"
                        onclick="abrirModalEntregar({{ $orden->id }}, '{{ addslashes($orden->numero_os) }}', {{ $orden->cobro_diagnostico ?? 0 }}, {{ $orden->tecnico_id ?? 'null' }})">
                        <i data-lucide="package-check" aria-hidden="true"></i><span>Entregar</span>
                    </button>
                @endif
                @if(!$esCerrada)
                    {{-- El selector se conecta con ordenes.avanzarEstado y abre el modal especial cuando se elige RECHAZADO. --}}
                    <form method="POST" action="{{ route('ordenes.avanzarEstado', $orden) }}" class="order-status-form">
                        @csrf
                        <span class="orders-select-wrap order-status-select">
                            <i data-lucide="clock-3" aria-hidden="true"></i>
                            <select name="estado"
                                aria-label="Cambiar estado de {{ $orden->numero_os }}"
                                onchange="manejarCambioEstadoOrden(this, {{ $orden->id }}, '{{ addslashes($orden->numero_os) }}', {{ (float) ($orden->anticipo ?? 0) }})">
                                <option value="">Cambiar estado</option>
                                @foreach($estadosRapidos as $estadoRapido => $labelEstado)
                                    <option value="{{ $estadoRapido }}" {{ $orden->estado === $estadoRapido ? 'disabled' : '' }}>{{ $labelEstado }}</option>
                                @endforeach
                            </select>
                        </span>
                    </form>
                @endif
                <a href="{{ route('ordenes.edit', $orden) }}" class="btn">
                    <i data-lucide="pencil" aria-hidden="true"></i><span>Editar</span>
                </a>
                <form method="POST" action="{{ route('ordenes.destroy', $orden) }}"
                    onsubmit="return confirmarEliminacionSistema(event, 'la orden de servicio', '{{ addslashes($orden->numero_os) }}');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i data-lucide="trash-2" aria-hidden="true"></i><span>Eliminar</span>
                    </button>
                </form>
            </div>
        </div>
    </article>
@empty
    <div class="orders-empty-state">
        <i data-lucide="clipboard-x" aria-hidden="true"></i>
        <strong>No hay órdenes que coincidan</strong>
        <span>No se encontraron registros para {{ $sucursalActiva?->nombre ?? 'la sucursal seleccionada' }}.</span>
    </div>
@endforelse
</section>

{{-- Modal de rechazo: solicita motivo y devolución antes de conectar con ordenes.rechazar. --}}
<div id="modal-rechazar-orden" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1100;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:white;border-radius:14px;padding:2rem;width:100%;max-width:520px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:22px;font-weight:800;color:#991b1b;margin:0 0 .5rem;">Rechazar orden</h2>
        <p style="font-size:14px;color:#64748b;margin:0 0 1.25rem;">Orden: <strong id="rechazo-numero-os"></strong></p>
        <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:.45rem;">Motivo *</label>
        <textarea id="rechazo-motivo" rows="3" maxlength="500" style="width:100%;padding:12px 14px;border:1px solid #dbe3ef;border-radius:8px;font:inherit;box-sizing:border-box;"></textarea>
        {{-- La devolución solo se habilita cuando la OS tiene un anticipo registrado. --}}
        <div id="rechazo-bloque-devolucion">
            <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin:.9rem 0 .45rem;">Devolución del anticipo ($)</label>
            <input type="number" id="rechazo-devolucion" min="0" step="0.01" value="0" disabled style="width:100%;padding:12px 14px;border:1px solid #dbe3ef;border-radius:8px;font-size:15px;box-sizing:border-box;">
            <div style="margin-top:.45rem;color:#64748b;font-size:12px;">Anticipo disponible: <strong id="rechazo-anticipo-disponible">$0.00</strong></div>
        </div>
        {{-- Este aviso evita intentar devolver dinero cuando la orden no recibió anticipo. --}}
        <div id="rechazo-sin-anticipo" style="display:none;margin-top:.9rem;padding:.8rem 1rem;background:#f1f5f9;border:1px solid #dbe3ef;border-radius:8px;color:#475569;font-size:13px;">
            Esta orden no tiene anticipo registrado. Se rechazará sin devolución.
        </div>
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;">
            <button type="button" class="btn" onclick="cerrarModalRechazoOrden()">Cancelar</button>
            <button type="button" class="btn btn-danger" onclick="confirmarRechazoOrden()">Confirmar rechazo</button>
        </div>
    </div>
</div>

{{-- Formulario oculto: Laravel valida nuevamente el motivo y que la devolución no supere el anticipo. --}}
<form id="form-rechazo-orden" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="motivo" id="rechazo-motivo-hidden">
    <input type="hidden" name="devolucion" id="rechazo-devolucion-hidden">
</form>

{{-- Modal de entrega: recopila técnico y cobro final antes de cerrar la OS como ENTREGADO. --}}
<div id="modal-entregar-tecnico" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:white;border-radius:14px;padding:2rem;width:100%;max-width:520px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:22px;font-weight:800;color:#0f1f3d;margin:0 0 .5rem;">📦 Entregar equipo</h2>
        <p style="font-size:14px;color:#64748b;margin:0 0 1.5rem;">Selecciona al técnico registrado que realizó la reparación.</p>
        <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:.45rem;">Técnico que realizó la reparación *</label>
        {{-- Lista conectada con users.rol y users.sucursal_id; solo incluye técnicos de la sucursal activa. --}}
        <select id="entrega-tecnico" style="width:100%;padding:12px 14px;border:1px solid #dbe3ef;border-radius:8px;font-size:15px;background:white;">
            <option value="">Selecciona un técnico</option>
            @forelse($tecnicos as $tecnico)
                <option value="{{ $tecnico->id }}" data-name="{{ $tecnico->name }}">
                    {{ $tecnico->name }}
                </option>
            @empty
                <option value="" disabled>No hay técnicos asignados a {{ $sucursalActiva?->nombre ?? 'esta sucursal' }}</option>
            @endforelse
        </select>
        @if($tecnicos->isEmpty())
            <div style="margin-top:.55rem;color:#b45309;font-size:12px;font-weight:700;">
                Asigna la sucursal a un usuario con rol Técnico desde el módulo Usuarios.
            </div>
        @endif
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;">
            <button type="button" class="btn" onclick="cerrarModalEntrega()">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="pasarACobroEntrega()" {{ $tecnicos->isEmpty() ? 'disabled' : '' }}>Siguiente →</button>
        </div>
    </div>
</div>

{{-- Modal de cobro: registra el saldo o cobro final y se conecta con movimientos_caja. --}}
<div id="modal-entregar-cobro" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:white;border-radius:14px;padding:2rem;width:100%;max-width:520px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:22px;font-weight:800;color:#0f1f3d;margin:0 0 .5rem;">💵 Cobro final</h2>
        <p style="font-size:14px;color:#64748b;margin:0 0 1.5rem;">Ingresa el monto que se cobrará al entregar el equipo.</p>
        <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:.45rem;">Cobro final ($)</label>
        <input type="number" id="entrega-cobro" min="0" step="0.01" placeholder="0.00" style="width:100%;padding:12px 14px;border:1px solid #dbe3ef;border-radius:8px;font-size:15px;">
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;">
            <button type="button" class="btn" onclick="volverATecnicoEntrega()">← Atrás</button>
            <button type="button" class="btn btn-primary" onclick="pasarAConfirmarEntrega()">Siguiente →</button>
        </div>
    </div>
</div>

{{-- Modal de confirmación: envía el formulario oculto a OrdenServicioController::entregar. --}}
<div id="modal-entregar-confirmar" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:white;border-radius:14px;padding:2rem;width:100%;max-width:520px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:22px;font-weight:800;color:#0f1f3d;margin:0 0 .5rem;">🧾 Confirmar entrega</h2>
        <p style="font-size:14px;color:#64748b;margin:.35rem 0;">Orden: <strong id="entrega-label-os"></strong></p>
        <p style="font-size:14px;color:#64748b;margin:.35rem 0;">Técnico: <strong id="entrega-label-tecnico"></strong></p>
        <p style="font-size:14px;color:#64748b;margin:.35rem 0 1.25rem;">Cobro final: <strong id="entrega-label-cobro"></strong></p>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:.8rem;color:#1e3a8a;font-size:13px;">Al confirmar, la orden cambiará a ENTREGADO, se guardará en el historial y se generará el ticket.</div>
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;">
            <button type="button" class="btn" onclick="volverACobroEntrega()">← Atrás</button>
            <button type="button" class="btn btn-primary" onclick="confirmarEntregaEquipo()">Confirmar y generar ticket</button>
        </div>
    </div>
</div>

{{-- Egreso rápido, paso 1: captura el motivo conectado con movimientos_caja.descripcion. --}}
<div id="modal-egreso-concepto" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1100;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:white;border-radius:12px;padding:2rem;width:100%;max-width:460px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:20px;font-weight:800;color:#b91c1c;margin:0 0 .5rem;">Registrar egreso</h2>
        <p style="font-size:13px;color:#64748b;margin:0 0 1.25rem;">¿Por qué sale este dinero de Caja?</p>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.45rem;">Concepto *</label>
        <textarea id="input-egreso-concepto" rows="3" maxlength="500" placeholder="Ej. Compra de refacciones"
            style="width:100%;padding:11px;border:1px solid #dbe3ef;border-radius:8px;font:inherit;box-sizing:border-box;"></textarea>
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.25rem;">
            <button type="button" class="btn" onclick="cerrarModalEgreso()">Cancelar</button>
            <button type="button" class="btn btn-danger" onclick="siguienteModalEgreso()">Siguiente →</button>
        </div>
    </div>
</div>

{{-- Egreso rápido, paso 2: captura monto y método para la sucursal activa. --}}
<div id="modal-egreso-monto" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1100;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:white;border-radius:12px;padding:2rem;width:100%;max-width:460px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:20px;font-weight:800;color:#b91c1c;margin:0 0 .5rem;">Monto del egreso</h2>
        <p style="font-size:13px;color:#64748b;margin:0 0 1.25rem;">Concepto: <strong id="label-egreso-concepto"></strong></p>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.45rem;">Monto ($) *</label>
        <input type="number" id="input-egreso-monto" min="0.01" step="0.01" placeholder="0.00"
            style="width:100%;padding:11px;border:1px solid #dbe3ef;border-radius:8px;font-size:15px;box-sizing:border-box;">
        <div style="margin-top:1rem;font-size:13px;font-weight:700;">Método de pago</div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:.55rem;">
            <label><input type="radio" name="metodo_egreso" value="efectivo" checked> Efectivo</label>
            <label><input type="radio" name="metodo_egreso" value="transferencia"> Transferencia</label>
            <label><input type="radio" name="metodo_egreso" value="tarjeta"> Tarjeta</label>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;">
            <button type="button" class="btn" onclick="volverModalEgreso()">← Atrás</button>
            <button type="button" class="btn btn-danger" onclick="confirmarEgreso()">Registrar egreso</button>
        </div>
    </div>
</div>

{{-- Ingreso rápido, paso 1: captura el concepto conectado con movimientos_caja.descripcion. --}}
<div id="modal-ingreso-concepto" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1100;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:white;border-radius:12px;padding:2rem;width:100%;max-width:460px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:20px;font-weight:800;color:#15803d;margin:0 0 .5rem;">Registrar ingreso</h2>
        <p style="font-size:13px;color:#64748b;margin:0 0 1.25rem;">¿Por qué entra este dinero a Caja?</p>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.45rem;">Concepto *</label>
        <textarea id="input-ingreso-concepto" rows="3" maxlength="500" placeholder="Ej. Pago de servicio"
            style="width:100%;padding:11px;border:1px solid #dbe3ef;border-radius:8px;font:inherit;box-sizing:border-box;"></textarea>
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.25rem;">
            <button type="button" class="btn" onclick="cerrarModalIngreso()">Cancelar</button>
            <button type="button" class="btn btn-success" onclick="siguienteModalIngreso()">Siguiente →</button>
        </div>
    </div>
</div>

{{-- Ingreso rápido, paso 2: captura monto y método para la sucursal activa. --}}
<div id="modal-ingreso-monto" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1100;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:white;border-radius:12px;padding:2rem;width:100%;max-width:460px;box-shadow:0 24px 70px rgba(15,23,42,.24);">
        <h2 style="font-size:20px;font-weight:800;color:#15803d;margin:0 0 .5rem;">Monto del ingreso</h2>
        <p style="font-size:13px;color:#64748b;margin:0 0 1.25rem;">Concepto: <strong id="label-ingreso-concepto"></strong></p>
        <label style="display:block;font-size:13px;font-weight:700;margin-bottom:.45rem;">Monto ($) *</label>
        <input type="number" id="input-ingreso-monto" min="0.01" step="0.01" placeholder="0.00"
            style="width:100%;padding:11px;border:1px solid #dbe3ef;border-radius:8px;font-size:15px;box-sizing:border-box;">
        <div style="margin-top:1rem;font-size:13px;font-weight:700;">Método de pago</div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:.55rem;">
            <label><input type="radio" name="metodo_ingreso" value="efectivo" checked> Efectivo</label>
            <label><input type="radio" name="metodo_ingreso" value="transferencia"> Transferencia</label>
            <label><input type="radio" name="metodo_ingreso" value="tarjeta"> Tarjeta</label>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;">
            <button type="button" class="btn" onclick="volverModalIngreso()">← Atrás</button>
            <button type="button" class="btn btn-success" onclick="confirmarIngreso()">Registrar ingreso</button>
        </div>
    </div>
</div>

{{-- Formulario oculto de entrega: Laravel recibe estos datos por POST en ordenes.entregar. --}}
<form id="form-entrega-equipo" method="POST" style="display:none;">
    @csrf
    {{-- Laravel recibe el ID y vuelve a validar que corresponda a la sucursal de la orden. --}}
    <input type="hidden" name="tecnico_entrega_id" id="entrega-tecnico-id-hidden">
    <input type="hidden" id="entrega-tecnico-nombre-hidden">
    <input type="hidden" name="cobro_final" id="entrega-cobro-hidden">
</form>

{{-- Formularios ocultos: envían los accesos rápidos a MovimientoCajaController. --}}
<form id="form-egreso-rapido" method="POST" action="{{ route('caja.egreso') }}" style="display:none;">
    @csrf
    <input type="hidden" name="concepto" id="egreso-concepto-hidden">
    <input type="hidden" name="monto" id="egreso-monto-hidden">
    <input type="hidden" name="metodo_pago" id="egreso-metodo-hidden">
</form>
<form id="form-ingreso-rapido" method="POST" action="{{ route('caja.ingreso') }}" style="display:none;">
    @csrf
    <input type="hidden" name="concepto" id="ingreso-concepto-hidden">
    <input type="hidden" name="monto" id="ingreso-monto-hidden">
    <input type="hidden" name="metodo_pago" id="ingreso-metodo-hidden">
</form>

<script>
// Variables del rechazo: conectan el selector de estado con el modal y el formulario POST.
let rechazoOrdenId = null;
let rechazoAnticipoMaximo = 0;

function manejarCambioEstadoOrden(select, ordenId, numeroOs, anticipo) {
    if (select.value !== 'RECHAZADO') {
        if (select.value) select.form.submit();
        return;
    }

    select.value = '';
    rechazoOrdenId = ordenId;
    rechazoAnticipoMaximo = Number(anticipo || 0);
    document.getElementById('rechazo-numero-os').textContent = numeroOs;
    document.getElementById('rechazo-motivo').value = '';
    const campoDevolucion = document.getElementById('rechazo-devolucion');
    const tieneAnticipo = rechazoAnticipoMaximo > 0;

    // Bloquea la devolución cuando no existe anticipo y conserva cero para el controlador.
    campoDevolucion.value = '0';
    campoDevolucion.max = rechazoAnticipoMaximo.toFixed(2);
    campoDevolucion.disabled = !tieneAnticipo;
    document.getElementById('rechazo-bloque-devolucion').style.display = tieneAnticipo ? 'block' : 'none';
    document.getElementById('rechazo-sin-anticipo').style.display = tieneAnticipo ? 'none' : 'block';
    document.getElementById('rechazo-anticipo-disponible').textContent = '$' + rechazoAnticipoMaximo.toFixed(2);
    document.getElementById('modal-rechazar-orden').style.display = 'flex';
    setTimeout(() => document.getElementById('rechazo-motivo').focus(), 100);
}

function cerrarModalRechazoOrden() {
    document.getElementById('modal-rechazar-orden').style.display = 'none';
}

function confirmarRechazoOrden() {
    const motivo = document.getElementById('rechazo-motivo').value.trim();
    const campoDevolucion = document.getElementById('rechazo-devolucion');
    const devolucion = campoDevolucion.disabled ? 0 : (parseFloat(campoDevolucion.value) || 0);

    if (!motivo) {
        alert('Escribe el motivo del rechazo.');
        return;
    }
    if (devolucion < 0 || devolucion > rechazoAnticipoMaximo) {
        alert('La devolución no puede superar el anticipo disponible.');
        return;
    }

    document.getElementById('rechazo-motivo-hidden').value = motivo;
    document.getElementById('rechazo-devolucion-hidden').value = devolucion.toFixed(2);
    const form = document.getElementById('form-rechazo-orden');
    form.action = '/ordenes/' + rechazoOrdenId + '/rechazar';
    form.submit();
}

// Variables temporales del modal de entrega; conectan los pasos visuales con el formulario oculto.
let entregaOrdenId = null;
let entregaNumeroOs = '';

// Abre el primer paso del modal cuando la OS está lista para recoger.
function abrirModalEntregar(id, numeroOs, cobroActual, tecnicoId = null) {
    entregaOrdenId = id;
    entregaNumeroOs = numeroOs;
    const tecnicoSelect = document.getElementById('entrega-tecnico');
    tecnicoSelect.value = tecnicoId ? String(tecnicoId) : '';
    document.getElementById('entrega-cobro').value = Number(cobroActual || 0) > 0 ? Number(cobroActual).toFixed(2) : '';
    document.getElementById('modal-entregar-tecnico').style.display = 'flex';
    setTimeout(() => tecnicoSelect.focus(), 100);
}

// Cierra todos los pasos del modal sin guardar cambios.
function cerrarModalEntrega() {
    document.getElementById('modal-entregar-tecnico').style.display = 'none';
    document.getElementById('modal-entregar-cobro').style.display = 'none';
    document.getElementById('modal-entregar-confirmar').style.display = 'none';
}

// Valida el técnico y pasa al paso de cobro final.
function pasarACobroEntrega() {
    const tecnicoSelect = document.getElementById('entrega-tecnico');
    const tecnicoId = tecnicoSelect.value;
    const tecnicoNombre = tecnicoSelect.selectedOptions[0]?.dataset.name || '';

    if (!tecnicoId || !tecnicoNombre) {
        alert('Selecciona al técnico que realizó la reparación.');
        return;
    }

    // Conserva ID y nombre: el servidor valida el ID y el resumen muestra el nombre.
    document.getElementById('entrega-tecnico-id-hidden').value = tecnicoId;
    document.getElementById('entrega-tecnico-nombre-hidden').value = tecnicoNombre;
    document.getElementById('modal-entregar-tecnico').style.display = 'none';
    document.getElementById('modal-entregar-cobro').style.display = 'flex';
    setTimeout(() => document.getElementById('entrega-cobro').focus(), 100);
}

// Regresa del paso de cobro al paso de técnico.
function volverATecnicoEntrega() {
    document.getElementById('modal-entregar-cobro').style.display = 'none';
    document.getElementById('modal-entregar-tecnico').style.display = 'flex';
}

// Prepara el resumen final antes de enviar la entrega al servidor.
function pasarAConfirmarEntrega() {
    const cobro = parseFloat(document.getElementById('entrega-cobro').value) || 0;
    document.getElementById('entrega-cobro-hidden').value = cobro.toFixed(2);
    document.getElementById('entrega-label-os').textContent = entregaNumeroOs;
    document.getElementById('entrega-label-tecnico').textContent = document.getElementById('entrega-tecnico-nombre-hidden').value;
    document.getElementById('entrega-label-cobro').textContent = '$' + cobro.toFixed(2);
    document.getElementById('modal-entregar-cobro').style.display = 'none';
    document.getElementById('modal-entregar-confirmar').style.display = 'flex';
}

// Regresa del resumen al paso de cobro.
function volverACobroEntrega() {
    document.getElementById('modal-entregar-confirmar').style.display = 'none';
    document.getElementById('modal-entregar-cobro').style.display = 'flex';
}

// Envía la entrega a Laravel; el controlador actualiza estado, historial, caja y ticket.
function confirmarEntregaEquipo() {
    const form = document.getElementById('form-entrega-equipo');
    form.action = '/ordenes/' + entregaOrdenId + '/entregar';
    form.submit();
}

// Abre y controla el registro rápido de un egreso en la sucursal seleccionada.
function abrirModalEgreso() {
    document.getElementById('input-egreso-concepto').value = '';
    document.getElementById('input-egreso-monto').value = '';
    document.getElementById('modal-egreso-concepto').style.display = 'flex';
    setTimeout(() => document.getElementById('input-egreso-concepto').focus(), 100);
}

function siguienteModalEgreso() {
    const concepto = document.getElementById('input-egreso-concepto').value.trim();
    if (!concepto) {
        alert('Escribe el concepto del egreso.');
        return;
    }
    document.getElementById('label-egreso-concepto').textContent = concepto;
    document.getElementById('egreso-concepto-hidden').value = concepto;
    document.getElementById('modal-egreso-concepto').style.display = 'none';
    document.getElementById('modal-egreso-monto').style.display = 'flex';
    setTimeout(() => document.getElementById('input-egreso-monto').focus(), 100);
}

function volverModalEgreso() {
    document.getElementById('modal-egreso-monto').style.display = 'none';
    document.getElementById('modal-egreso-concepto').style.display = 'flex';
}

function cerrarModalEgreso() {
    document.getElementById('modal-egreso-concepto').style.display = 'none';
    document.getElementById('modal-egreso-monto').style.display = 'none';
}

function confirmarEgreso() {
    const monto = parseFloat(document.getElementById('input-egreso-monto').value) || 0;
    if (monto <= 0) {
        alert('Escribe un monto válido.');
        return;
    }
    document.getElementById('egreso-monto-hidden').value = monto.toFixed(2);
    document.getElementById('egreso-metodo-hidden').value =
        document.querySelector('input[name="metodo_egreso"]:checked').value;
    document.getElementById('form-egreso-rapido').submit();
}

// Abre y controla el registro rápido de un ingreso en la sucursal seleccionada.
function abrirModalIngreso() {
    document.getElementById('input-ingreso-concepto').value = '';
    document.getElementById('input-ingreso-monto').value = '';
    document.getElementById('modal-ingreso-concepto').style.display = 'flex';
    setTimeout(() => document.getElementById('input-ingreso-concepto').focus(), 100);
}

function siguienteModalIngreso() {
    const concepto = document.getElementById('input-ingreso-concepto').value.trim();
    if (!concepto) {
        alert('Escribe el concepto del ingreso.');
        return;
    }
    document.getElementById('label-ingreso-concepto').textContent = concepto;
    document.getElementById('ingreso-concepto-hidden').value = concepto;
    document.getElementById('modal-ingreso-concepto').style.display = 'none';
    document.getElementById('modal-ingreso-monto').style.display = 'flex';
    setTimeout(() => document.getElementById('input-ingreso-monto').focus(), 100);
}

function volverModalIngreso() {
    document.getElementById('modal-ingreso-monto').style.display = 'none';
    document.getElementById('modal-ingreso-concepto').style.display = 'flex';
}

function cerrarModalIngreso() {
    document.getElementById('modal-ingreso-concepto').style.display = 'none';
    document.getElementById('modal-ingreso-monto').style.display = 'none';
}

function confirmarIngreso() {
    const monto = parseFloat(document.getElementById('input-ingreso-monto').value) || 0;
    if (monto <= 0) {
        alert('Escribe un monto válido.');
        return;
    }
    document.getElementById('ingreso-monto-hidden').value = monto.toFixed(2);
    document.getElementById('ingreso-metodo-hidden').value =
        document.querySelector('input[name="metodo_ingreso"]:checked').value;
    document.getElementById('form-ingreso-rapido').submit();
}

// Enter confirma el importe en los segundos pasos, igual que en el módulo original.
document.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' || event.shiftKey) return;

    if (document.getElementById('modal-egreso-monto').style.display === 'flex'
        && document.activeElement.id === 'input-egreso-monto') {
        event.preventDefault();
        confirmarEgreso();
    } else if (document.getElementById('modal-ingreso-monto').style.display === 'flex'
        && document.activeElement.id === 'input-ingreso-monto') {
        event.preventDefault();
        confirmarIngreso();
    }
});
</script>
@endsection



