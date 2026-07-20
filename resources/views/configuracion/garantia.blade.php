@extends('layout')

@section('content')
<div class="page-header">
    <h1>Política de Garantía</h1>
    <a href="{{ route('ordenes.index') }}" class="btn">← Volver</a>
</div>

{{-- Este formulario administra el texto conectado con todos los tickets de entrega. --}}
<div class="card" style="max-width:760px;">
    <form method="POST" action="{{ route('configuracion.garantia.guardar') }}">
        @csrf
        <div class="form-group">
            <label for="politica-garantia">Texto de la política de garantía</label>
            <textarea id="politica-garantia" name="politica_garantia" rows="7" maxlength="3000"
                style="width:100%;padding:12px;border:1px solid #dbe3ef;border-radius:8px;font-size:14px;font-family:inherit;box-sizing:border-box;">{{ old('politica_garantia', $politica) }}</textarea>
            <div style="font-size:12px;color:#64748b;margin-top:6px;">
                Este texto aparecerá al final de los tickets cuando se entregue un equipo.
            </div>
            @error('politica_garantia')
                <div style="color:#dc2626;font-size:12px;margin-top:6px;">{{ $message }}</div>
            @enderror
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:1rem;">
            <button type="submit" class="btn btn-primary">Guardar política</button>
        </div>
    </form>
</div>
@endsection
