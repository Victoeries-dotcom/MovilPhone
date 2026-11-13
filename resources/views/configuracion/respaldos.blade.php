@extends('layout')

@section('content')
{{-- Panel de respaldos: se conecta con BackupController y los archivos privados del disco local. --}}
<div class="page-header">
    <div>
        <h1>Respaldos del sistema</h1>
        <p class="page-title-sub">Copias portables de la base de datos, protegidas fuera de la carpeta publica.</p>
    </div>
    <form method="POST" action="{{ route('respaldos.store') }}">
        @csrf
        <button class="btn btn-primary" type="submit"><i data-lucide="database-backup"></i><span>Crear respaldo</span></button>
    </form>
</div>

<div class="backup-notice">
    <i data-lucide="shield-check"></i>
    <div>
        <strong>Respaldo automatico programado</strong>
        <p>Laravel genera una copia diaria a las 02:00 y conserva las catorce mas recientes.</p>
        {{-- Ambos formatos conservan los mismos datos; el Excel los distribuye en hojas para facilitar su consulta. --}}
        <p>JSON y Excel contienen los mismos datos completos. El Excel los organiza por secciones y debe protegerse porque incluye información sensible.</p>
    </div>
</div>

{{-- Limpia exclusivamente datos operativos; requiere frase exacta y respaldo previo automático. --}}
<section class="card" style="margin:20px 0;border:1px solid #fecaca">
    <h2 style="color:#991b1b">Borrar datos operativos</h2>
    <p>Elimina clientes, órdenes e historial, ventas y detalles, inventario, movimientos de caja y devoluciones. Conserva usuarios, sucursales, categorías y catálogos, configuración, logo y archivos de respaldo.</p>
    <form method="POST" action="{{ route('respaldos.datos.clear') }}" onsubmit="return confirm('Se creará un respaldo completo y luego se borrarán los datos operativos. ¿Deseas continuar?')">
        @csrf @method('DELETE')
        <label for="confirmacion-limpieza">Escribe <strong>ELIMINAR DATOS</strong> para confirmar</label>
        <input id="confirmacion-limpieza" name="confirmacion" required autocomplete="off" pattern="ELIMINAR DATOS" title="Escribe exactamente ELIMINAR DATOS">
        @error('confirmacion')<small class="brand-logo-error">{{ $message }}</small>@enderror
        <button class="btn btn-danger" type="submit">Borrar todos los datos operativos</button>
    </form>
</section>

<table>
    <thead><tr><th>Archivo</th><th>Origen</th><th>Fecha</th><th>Tamano</th><th>Acciones</th></tr></thead>
    <tbody>
        @forelse($respaldos as $respaldo)
            <tr>
                <td><strong>{{ $respaldo['nombre'] }}</strong></td>
                <td>{{ str_contains($respaldo['nombre'], 'automatico') ? 'Automatico' : 'Manual' }}</td>
                <td>{{ \Carbon\Carbon::createFromTimestamp($respaldo['fecha'])->format('d/m/Y H:i') }}</td>
                <td>{{ number_format($respaldo['tamano'] / 1024, 1) }} KB</td>
                <td>
                    <a class="btn btn-sm" href="{{ route('respaldos.download', $respaldo['nombre']) }}"><i data-lucide="download"></i><span>Descargar</span></a>
                    <a class="btn btn-sm btn-success" href="{{ route('respaldos.excel', $respaldo['nombre']) }}"><i data-lucide="file-spreadsheet"></i><span>Excel</span></a>
                    <form method="POST" action="{{ route('respaldos.destroy', $respaldo['nombre']) }}" style="display:inline" onsubmit="return confirmarEliminacionSistema(event, 'el respaldo', @js($respaldo['nombre']), 'se eliminara solamente este archivo de respaldo')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit"><i data-lucide="trash-2"></i><span>Eliminar</span></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Todavia no existen respaldos. Usa “Crear respaldo” para generar el primero.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
