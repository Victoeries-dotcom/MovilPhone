@extends('layout')

@section('content')
<div class="page-header">
    <div>
        <h1>Corte de Caja</h1>
        {{-- El corte pertenece únicamente a la sucursal activa y fecha seleccionada. --}}
        <p style="font-size:13px;color:#6b7280;margin-top:3px;">
            {{ $sucursalActiva->nombre }} · {{ \Carbon\Carbon::parse($corte['fecha'])->format('d/m/Y') }}
        </p>
    </div>
    <div style="display:flex;gap:.75rem;">
        <a href="{{ route('caja.index') }}" class="btn">← Volver</a>
    </div>
</div>

<form method="GET" style="margin-bottom:1.5rem;display:flex;gap:.75rem;align-items:center;">
    <input type="date" name="fecha" value="{{ $corte['fecha'] }}"
           style="padding:.45rem .75rem;border:1px solid #e2e8f0;border-radius:6px;font-size:13.5px;">
    <button type="submit" class="btn btn-primary">Ver corte</button>
</form>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-label">Total Ingresos</div>
        <div style="font-size:26px;font-weight:700;color:#16a34a;">${{ number_format($corte['total_ingresos'],2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Egresos</div>
        <div style="font-size:26px;font-weight:700;color:#dc2626;">${{ number_format($corte['total_egresos'],2) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Balance del Día</div>
        <div style="font-size:26px;font-weight:700;color:{{ $corte['balance']>=0?'#0f1f3d':'#dc2626' }};">
            ${{ number_format($corte['balance'],2) }}
        </div>
    </div>
</div>

<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem;">
    <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;margin-bottom:1rem;">Desglose por método de pago</div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
        <div style="text-align:center;padding:1rem;background:#f8fafc;border-radius:8px;">
            <div style="font-size:24px;">💵</div>
            <div style="font-size:11px;color:#6b7280;font-weight:700;text-transform:uppercase;">Efectivo</div>
            <div style="font-size:20px;font-weight:700;color:#0f1f3d;margin-top:.25rem;">${{ number_format($corte['efectivo'],2) }}</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#f8fafc;border-radius:8px;">
            <div style="font-size:24px;">🏦</div>
            <div style="font-size:11px;color:#6b7280;font-weight:700;text-transform:uppercase;">Transferencia</div>
            <div style="font-size:20px;font-weight:700;color:#0f1f3d;margin-top:.25rem;">${{ number_format($corte['transferencia'],2) }}</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#f8fafc;border-radius:8px;">
            <div style="font-size:24px;">💳</div>
            <div style="font-size:11px;color:#6b7280;font-weight:700;text-transform:uppercase;">Tarjeta</div>
            <div style="font-size:20px;font-weight:700;color:#0f1f3d;margin-top:.25rem;">${{ number_format($corte['tarjeta'],2) }}</div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;">
        <div style="padding:.75rem 1rem;background:#fef9c3;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:13px;color:#854d0e;font-weight:600;">⏳ Anticipos del día</span>
            <span style="font-size:16px;font-weight:700;color:#854d0e;">${{ number_format($corte['anticipos'],2) }}</span>
        </div>
        <div style="padding:.75rem 1rem;background:#dcfce7;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:13px;color:#166534;font-weight:600;">✅ Pagos finales</span>
            <span style="font-size:16px;font-weight:700;color:#166534;">${{ number_format($corte['pagos_finales'],2) }}</span>
        </div>
    </div>
</div>

@if(auth()->user()->rol === 'superusuario')
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem;">
    <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;margin-bottom:1rem;">⚙️ Configuración de corte automático</div>
    {{-- Esta configuración solo informa la hora habitual; no altera movimientos existentes. --}}
    <form method="POST" action="{{ route('caja.horaCorte') }}" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
        @csrf
        <div style="font-size:13.5px;color:#374151;">Hora de corte diario:</div>
        <input type="time" name="hora_corte" value="{{ $horaCorte }}"
               style="padding:.45rem .75rem;border:1px solid #e2e8f0;border-radius:6px;font-size:13.5px;">
        <button type="submit" class="btn btn-primary">💾 Guardar hora</button>
        <span style="font-size:12px;color:#6b7280;">Configurada: <strong>{{ $horaCorte }}</strong></span>
    </form>
</div>
@endif

<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0;background:#fafbfc;">
        <span style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Movimientos del día</span>
    </div>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#fafbfc;">
                <th style="text-align:left;padding:.75rem 1.25rem;font-size:11.5px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e2e8f0;">Hora</th>
                <th style="text-align:left;padding:.75rem 1.25rem;font-size:11.5px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e2e8f0;">Tipo</th>
                <th style="text-align:left;padding:.75rem 1.25rem;font-size:11.5px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e2e8f0;">Categoría</th>
                <th style="text-align:left;padding:.75rem 1.25rem;font-size:11.5px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e2e8f0;">Método</th>
                <th style="text-align:left;padding:.75rem 1.25rem;font-size:11.5px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e2e8f0;">Sucursal</th>
                <th style="text-align:right;padding:.75rem 1.25rem;font-size:11.5px;font-weight:700;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e2e8f0;">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($corte['movimientos'] as $mov)
            <tr>
                <td style="padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $mov->created_at->format('H:i') }}</td>
                <td style="padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;">
                    <span style="padding:.2rem .55rem;border-radius:4px;font-size:11.5px;font-weight:600;background:{{ $mov->tipo=='INGRESO'?'#dcfce7':'#fee2e2' }};color:{{ $mov->tipo=='INGRESO'?'#166534':'#991b1b' }};">
                        {{ $mov->tipo }}
                    </span>
                </td>
                <td style="padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $mov->categoria }}</td>
                <td style="padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;font-size:13px;text-transform:capitalize;">
                    @if($mov->metodo_pago=='efectivo') 💵
                    @elseif($mov->metodo_pago=='transferencia') 🏦
                    @else 💳
                    @endif
                    {{ $mov->metodo_pago }}
                </td>
                <td style="padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $mov->sucursal->nombre ?? '—' }}</td>
                <td style="padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;font-size:13px;text-align:right;font-weight:600;color:{{ $mov->tipo=='INGRESO'?'#16a34a':'#dc2626' }};">
                    {{ $mov->tipo=='INGRESO'?'+':'-' }}${{ number_format($mov->monto,2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:2rem;color:#9ca3af;font-size:13.5px;">No hay movimientos en este día</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background:#f8fafc;">
                <td colspan="5" style="padding:1rem 1.25rem;font-weight:700;font-size:14px;">BALANCE FINAL</td>
                <td style="padding:1rem 1.25rem;font-weight:800;font-size:16px;text-align:right;color:{{ $corte['balance']>=0?'#0f1f3d':'#dc2626' }};">
                    ${{ number_format($corte['balance'],2) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection
