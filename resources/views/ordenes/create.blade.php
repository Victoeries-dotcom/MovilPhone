@extends('layout')

@section('content')
<div class="page-header">
    <h1>Nueva Orden de Servicio</h1>
    <a href="{{ route('ordenes.index') }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('ordenes.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre del cliente *</label>
                <input type="text" name="cliente_nombre" required value="{{ old('cliente_nombre') }}" placeholder="Nombre completo" list="cli-list"/>
                <datalist id="cli-list">
                    @foreach($clientes as $c)
                        <option value="{{ $c->nombre }}">{{ $c->telefono_principal }}</option>
                    @endforeach
                </datalist>
            </div>
            <div class="form-group">
                <label>Teléfono *</label>
                <input type="text" name="cliente_telefono" required value="{{ old('cliente_telefono') }}" placeholder="999-000-0000"/>
            </div>
            <div class="form-group">
                <label>Marca *</label>
                <select name="marca" required>
                    <option value="">— Selecciona —</option>
                    @foreach(['Apple','Samsung','Xiaomi','LG','Sony','Huawei','Motorola','Otra'] as $m)
                        <option {{ old('marca') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Modelo *</label>
                <input type="text" name="modelo" required value="{{ old('modelo') }}" placeholder="Ej: iPhone 13"/>
            </div>
            <div class="form-group">
                <label>IMEI / Serie</label>
                <input type="text" name="imei" value="{{ old('imei') }}" placeholder="Opcional"/>
            </div>
            <div class="form-group">
                <label>Sucursal *</label>
                <select name="sucursal_id" required>
                    <option value="">— Selecciona —</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}" {{ old('sucursal_id') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Técnico asignado</label>
                <select name="tecnico_id">
                    <option value="">— Sin asignar —</option>
                    @foreach($tecnicos as $t)
                        <option value="{{ $t->id }}" {{ old('tecnico_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Cobro de diagnóstico ($)</label>
                <input type="number" name="cobro_diagnostico" value="{{ old('cobro_diagnostico', 0) }}" min="0" step="0.01"/>
            </div>
            <div class="form-group full-width">
                <label>Problema reportado por el cliente *</label>
                <textarea name="problema_reportado" required rows="3" placeholder="Describe el problema tal como lo reporta el cliente…">{{ old('problema_reportado') }}</textarea>
            </div>
            <div class="form-group full-width">
                <label>Estado físico externo *</label>
                <input type="text" name="estado_fisico" required value="{{ old('estado_fisico') }}" placeholder="Golpes, rayones, pantalla rota…"/>
            </div>
            <div class="form-group full-width">
                <label>Accesorios entregados *</label>
                <input type="text" name="accesorios_entregados" required value="{{ old('accesorios_entregados') }}" placeholder="Cargador, funda, audífonos…"/>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem">
            <a href="{{ route('ordenes.index') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar OS</button>
        </div>
    </form>
</div>
@endsection