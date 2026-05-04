@extends('layout')

@section('content')
<div class="page-header">
    <h1>Editar {{ $ordenServicio->numero_os }}</h1>
    <a href="{{ route('ordenes.show', $ordenServicio) }}" class="btn">← Volver</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('ordenes.update', $ordenServicio) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>Marca *</label>
                <select name="marca" required>
                    @foreach(['Apple','Samsung','Xiaomi','LG','Sony','Huawei','Motorola','Otra'] as $m)
                        <option {{ $ordenServicio->marca == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Modelo *</label>
                <input type="text" name="modelo" required value="{{ $ordenServicio->modelo }}"/>
            </div>
            <div class="form-group">
                <label>IMEI / Serie</label>
                <input type="text" name="imei" value="{{ $ordenServicio->imei }}"/>
            </div>
            <div class="form-group">
                <label>Técnico asignado</label>
                <select name="tecnico_id">
                    <option value="">— Sin asignar —</option>
                    @foreach($tecnicos as $t)
                        <option value="{{ $t->id }}" {{ $ordenServicio->tecnico_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group full-width">
                <label>Problema reportado *</label>
                <textarea name="problema_reportado" required rows="3">{{ $ordenServicio->problema_reportado }}</textarea>
            </div>
            <div class="form-group full-width">
                <label>Diagnóstico técnico</label>
                <textarea name="problema_diagnosticado" rows="3">{{ $ordenServicio->problema_diagnosticado }}</textarea>
            </div>
            <div class="form-group full-width">
                <label>Estado físico *</label>
                <input type="text" name="estado_fisico" required value="{{ $ordenServicio->estado_fisico }}"/>
            </div>
            <div class="form-group full-width">
                <label>Accesorios entregados *</label>
                <input type="text" name="accesorios_entregados" required value="{{ $ordenServicio->accesorios_entregados }}"/>
            </div>
            <div class="form-group">
                <label>Cobro de diagnóstico ($)</label>
                <input type="number" name="cobro_diagnostico" value="{{ $ordenServicio->cobro_diagnostico }}" min="0" step="0.01"/>
            </div>
            <div class="form-group">
                <label>Mano de obra ($)</label>
                <input type="number" name="mano_obra" value="{{ $ordenServicio->mano_obra }}" min="0" step="0.01"/>
            </div>
            <div class="form-group">
                <label>Presupuesto total ($)</label>
                <input type="number" name="presupuesto_total" value="{{ $ordenServicio->presupuesto_total }}" min="0" step="0.01"/>
            </div>
            <div class="form-group">
                <label>Fecha estimada de entrega</label>
                <input type="date" name="fecha_entrega_estimada" value="{{ $ordenServicio->fecha_entrega_estimada }}"/>
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
            <a href="{{ route('ordenes.show', $ordenServicio) }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection