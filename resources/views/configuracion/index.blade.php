@extends('layout')

@section('content')
{{-- Configuracion comercial: cada campo se conecta con ConfiguracionController y la tabla configuraciones. --}}
<div class="page-header">
    <div>
        <h1>Configuracion del sistema</h1>
        <p class="page-title-sub">Identidad comercial, moneda, impuestos y proteccion para demostraciones.</p>
    </div>
    <a href="{{ route('respaldos.index') }}" class="btn"><i data-lucide="database-backup"></i><span>Respaldos</span></a>
</div>

<form method="POST" action="{{ route('configuracion.update') }}" class="settings-layout">
    @csrf
    @method('PUT')

    <section class="settings-section">
        <div class="settings-section-copy">
            <span class="settings-icon"><i data-lucide="store"></i></span>
            <div><h2>Identidad comercial</h2><p>Aparece en el menu, tickets y documentos del negocio.</p></div>
        </div>
        <div class="settings-fields form-grid">
            <div class="form-group">
                <label for="negocio_nombre">Nombre comercial *</label>
                <input id="negocio_nombre" name="negocio_nombre" required value="{{ old('negocio_nombre', $configuracion['negocio_nombre'] ?? 'MovilPhone') }}">
            </div>
            <div class="form-group">
                <label for="negocio_subtitulo">Descripcion corta</label>
                <input id="negocio_subtitulo" name="negocio_subtitulo" value="{{ old('negocio_subtitulo', $configuracion['negocio_subtitulo'] ?? 'Sistema de Taller') }}">
            </div>
            <div class="form-group">
                <label for="negocio_telefono">Telefono</label>
                <input id="negocio_telefono" name="negocio_telefono" value="{{ old('negocio_telefono', $configuracion['negocio_telefono'] ?? '') }}">
            </div>
            <div class="form-group">
                <label for="negocio_email">Correo</label>
                <input type="email" id="negocio_email" name="negocio_email" data-no-mayusculas value="{{ old('negocio_email', $configuracion['negocio_email'] ?? '') }}">
            </div>
            <div class="form-group full-width">
                <label for="negocio_direccion">Direccion</label>
                <input id="negocio_direccion" name="negocio_direccion" value="{{ old('negocio_direccion', $configuracion['negocio_direccion'] ?? '') }}">
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-copy">
            <span class="settings-icon"><i data-lucide="palette"></i></span>
            <div><h2>Apariencia y finanzas</h2><p>Personaliza el color principal y los valores usados en reportes.</p></div>
        </div>
        <div class="settings-fields form-grid">
            <div class="form-group">
                <label for="color_primario">Color principal</label>
                <div class="settings-color-field">
                    <input type="color" id="color_primario_picker" value="{{ old('color_primario', $configuracion['color_primario'] ?? '#1650c5') }}" aria-label="Elegir color principal">
                    <input id="color_primario" name="color_primario" required pattern="#[0-9A-Fa-f]{6}" value="{{ old('color_primario', $configuracion['color_primario'] ?? '#1650c5') }}">
                </div>
            </div>
            <div class="form-group">
                <label for="moneda">Moneda</label>
                <select id="moneda" name="moneda" required>
                    <option value="MXN" @selected(old('moneda', $configuracion['moneda'] ?? 'MXN') === 'MXN')>MXN · Peso mexicano</option>
                    <option value="USD" @selected(old('moneda', $configuracion['moneda'] ?? 'MXN') === 'USD')>USD · Dolar estadounidense</option>
                </select>
            </div>
            <div class="form-group">
                <label for="impuesto_porcentaje">Impuesto (%)</label>
                <input type="number" min="0" max="100" step="0.01" id="impuesto_porcentaje" name="impuesto_porcentaje" required value="{{ old('impuesto_porcentaje', $configuracion['impuesto_porcentaje'] ?? '0') }}">
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-copy">
            <span class="settings-icon"><i data-lucide="presentation"></i></span>
            <div><h2>Modo demostracion</h2><p>Permite mostrar el sistema sin riesgo de eliminar registros.</p></div>
        </div>
        <div class="settings-fields">
            <label class="settings-switch">
                <input type="checkbox" name="modo_demo" value="1" @checked(old('modo_demo', $configuracion['modo_demo'] ?? '0') === '1')>
                <span class="settings-switch-track" aria-hidden="true"></span>
                <span><strong>Proteger eliminaciones</strong><small>Los formularios pueden consultarse, pero DELETE queda bloqueado.</small></span>
            </label>
        </div>
    </section>

    <div class="settings-footer">
        <span>Los cambios se aplican al volver a cargar cualquier modulo.</span>
        <button class="btn btn-primary" type="submit"><i data-lucide="save"></i><span>Guardar configuracion</span></button>
    </div>
</form>

<script>
    // Sincroniza el selector visual con el codigo hexadecimal enviado a ConfiguracionController.
    document.getElementById('color_primario_picker').addEventListener('input', function () {
        document.getElementById('color_primario').value = this.value.toUpperCase();
    });
</script>
@endsection
