@extends('layout')

@section('content')
{{-- Encabezado: identifica la configuración y conecta el regreso con el módulo de Órdenes. --}}
<section class="garantia-hero">
    <div class="garantia-titulo">
        <span class="garantia-hero-icon"><i data-lucide="shield-check" aria-hidden="true"></i></span>
        <div>
            <small>Configuración del taller</small>
            <h1>Política de Garantía</h1>
            <p>Define las condiciones que recibirán tus clientes al entregar un equipo.</p>
        </div>
    </div>
    <a href="{{ route('ordenes.index') }}" class="btn garantia-volver">← Volver a órdenes</a>
</section>

{{-- Confirmación: informa cuando ConfiguracionController guardó correctamente la política. --}}
@if(session('success'))
    <div class="garantia-alerta"><i data-lucide="circle-check" aria-hidden="true"></i> {{ session('success') }}</div>
@endif

<div class="garantia-grid">
    {{-- Formulario principal: guarda la clave politica_garantia usada por los tickets de entrega. --}}
    <form method="POST" action="{{ route('configuracion.garantia.guardar') }}" class="garantia-card">
        @csrf
        <div class="garantia-card-head">
            <span><i data-lucide="clipboard-list" aria-hidden="true"></i></span>
            <div>
                <h2>Texto de la política</h2>
                <p>Este contenido se agregará automáticamente al final de cada ticket de entrega.</p>
            </div>
        </div>

        <label for="politica_garantia">Condiciones de garantía</label>
        <textarea id="politica_garantia" name="politica_garantia" rows="8" maxlength="1500" required>{{ old('politica_garantia', $politica) }}</textarea>
        <div class="garantia-meta">
            <span><i data-lucide="receipt-text" aria-hidden="true"></i> Visible antes del folio en los tickets de entrega</span>
            <strong><span id="garantia-contador">0</span>/1500</strong>
        </div>
        @error('politica_garantia')
            <p class="garantia-error">{{ $message }}</p>
        @enderror

        <div class="garantia-actions">
            <a href="{{ route('ordenes.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" aria-hidden="true"></i> Guardar política
            </button>
        </div>
    </form>

    {{-- Recomendaciones: orientan la redacción sin modificar ni guardar información adicional. --}}
    <aside class="garantia-info">
        <span class="garantia-info-icon"><i data-lucide="shield-check" aria-hidden="true"></i></span>
        <h3>Recomendaciones</h3>
        <p>Una política clara evita malentendidos y ayuda a proteger tanto al taller como al cliente.</p>
        <ul>
            <li><i data-lucide="check" aria-hidden="true"></i> Indica el periodo de cobertura.</li>
            <li><i data-lucide="check" aria-hidden="true"></i> Especifica qué reparación está cubierta.</li>
            <li><i data-lucide="check" aria-hidden="true"></i> Menciona daños y situaciones excluidas.</li>
        </ul>
        <div class="garantia-nota">
            <strong>Importante</strong>
            <span>Los cambios se aplicarán a los próximos tickets que se visualicen o impriman.</span>
        </div>
    </aside>
</div>

<script>
// Contador: se conecta con el textarea y muestra en tiempo real el límite admitido por el controlador.
(() => {
    const campo = document.getElementById('politica_garantia');
    const contador = document.getElementById('garantia-contador');
    if (!campo || !contador) return;

    const actualizar = () => contador.textContent = campo.value.length;
    campo.addEventListener('input', actualizar);
    actualizar();
})();
</script>

<style>
/* Interfaz de garantía: organiza el formulario y mantiene contraste profesional en ambos temas. */
.garantia-hero{background:#111;color:#fff;border-radius:16px;padding:1.35rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;box-shadow:0 12px 28px rgba(15,23,42,.12)}.garantia-titulo{display:flex;align-items:center;gap:.9rem}.garantia-hero-icon{width:48px;height:48px;border-radius:13px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;color:#fff}.garantia-titulo small{display:block;color:#a1a1aa;text-transform:uppercase;letter-spacing:.15em;font-size:9px;font-weight:800}.garantia-titulo h1{font-size:25px;margin:.15rem 0}.garantia-titulo p{font-size:11px;color:#d4d4d8}.garantia-volver{background:#fff!important;color:#111!important;border-color:#fff!important}.garantia-alerta{display:flex;align-items:center;gap:.45rem;background:#ecfdf3;color:#15803d;border:1px solid #bbf7d0;border-radius:10px;padding:.7rem .9rem;margin-bottom:1rem;font-size:12px;font-weight:700}.garantia-grid{display:grid;grid-template-columns:minmax(0,2.1fr) minmax(260px,.9fr);gap:1rem;align-items:start}.garantia-card,.garantia-info{background:#fff;color:#111827;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 8px 22px rgba(15,23,42,.05)}.garantia-card{padding:1.35rem}.garantia-card-head{display:flex;gap:.75rem;align-items:flex-start;padding-bottom:1rem;margin-bottom:1rem;border-bottom:1px solid #edf0f3}.garantia-card-head>span{width:39px;height:39px;border-radius:10px;background:#f4f4f5;color:#18181b;display:flex;align-items:center;justify-content:center}.garantia-card-head h2{font-size:16px;margin:0 0 .2rem}.garantia-card-head p{font-size:11px;color:#64748b}.garantia-card>label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:800;color:#475569;margin-bottom:.45rem}.garantia-card textarea{display:block;width:100%;min-height:190px;resize:vertical;border:1px solid #dbe2e9;border-radius:10px;background:#f8fafc;padding:.9rem;font:inherit;font-size:13px;line-height:1.6;color:#111827;outline:none;transition:.2s}.garantia-card textarea:focus{background:#fff;border-color:#111;box-shadow:0 0 0 4px rgba(0,0,0,.06)}.garantia-meta{display:flex;align-items:center;justify-content:space-between;color:#94a3b8;font-size:10px;margin-top:.5rem}.garantia-meta>span{display:flex;align-items:center;gap:.35rem}.garantia-meta strong{color:#64748b}.garantia-error{font-size:11px;color:#dc2626;margin-top:.45rem}.garantia-actions{display:flex;align-items:center;justify-content:flex-end;gap:.55rem;margin-top:1.25rem;padding-top:1rem;border-top:1px solid #edf0f3}.garantia-actions .btn{display:inline-flex;align-items:center;gap:.4rem}.garantia-info{padding:1.25rem}.garantia-info-icon{width:44px;height:44px;border-radius:12px;background:#f0fdf4;color:#16a34a;display:flex;align-items:center;justify-content:center;margin-bottom:.85rem}.garantia-info h3{font-size:15px;margin-bottom:.35rem}.garantia-info>p{color:#64748b;font-size:11px;line-height:1.55}.garantia-info ul{list-style:none;padding:0;margin:1rem 0;display:flex;flex-direction:column;gap:.65rem}.garantia-info li{display:flex;align-items:flex-start;gap:.45rem;color:#475569;font-size:11px}.garantia-info li svg{color:#16a34a;flex:none}.garantia-nota{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.8rem}.garantia-nota strong,.garantia-nota span{display:block}.garantia-nota strong{font-size:10px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.25rem}.garantia-nota span{font-size:10px;color:#64748b;line-height:1.45}html[data-ui-theme="dark"] .garantia-card,html[data-ui-theme="dark"] .garantia-info{background:#111827;color:#f8fafc;border-color:#334155}html[data-ui-theme="dark"] .garantia-card-head,html[data-ui-theme="dark"] .garantia-actions{border-color:#334155}html[data-ui-theme="dark"] .garantia-card-head>span,html[data-ui-theme="dark"] .garantia-nota{background:#1e293b;color:#f8fafc;border-color:#334155}html[data-ui-theme="dark"] .garantia-card-head p,html[data-ui-theme="dark"] .garantia-card>label,html[data-ui-theme="dark"] .garantia-info>p,html[data-ui-theme="dark"] .garantia-info li,html[data-ui-theme="dark"] .garantia-nota span{color:#cbd5e1}html[data-ui-theme="dark"] .garantia-card textarea{background:#0f172a;color:#f8fafc;border-color:#475569}html[data-ui-theme="dark"] .garantia-card textarea:focus{background:#0f172a;border-color:#a78bfa;box-shadow:0 0 0 4px rgba(167,139,250,.14)}@media(max-width:900px){.garantia-grid{grid-template-columns:1fr}}@media(max-width:650px){.garantia-hero{align-items:flex-start;flex-direction:column}.garantia-titulo p{display:none}.garantia-actions{align-items:stretch;flex-direction:column-reverse}.garantia-actions .btn{justify-content:center}}
</style>
@endsection
