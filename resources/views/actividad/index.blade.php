@extends('layout')

@section('content')
<div class="page-header">
    <div>
        <h1>Actividad del sistema</h1>
        <div class="page-title-sub">Movimientos recientes capturados por usuarios en clientes, órdenes y caja.</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Módulo</th>
            <th>Acción</th>
            <th>Descripción</th>
            <th>Usuario</th>
            <th>Sucursal</th>
        </tr>
    </thead>
    <tbody id="actividad-cuerpo">
        @forelse($actividades as $actividad)
            <tr data-actividad-id="{{ $actividad->id }}">
                <td>{{ $actividad->created_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $actividad->modulo }}</td>
                <td><span class="badge badge-diagnostico">{{ $actividad->accion }}</span></td>
                <td>{{ $actividad->descripcion }}</td>
                <td>{{ $actividad->usuario->name ?? 'SISTEMA' }}</td>
                <td>{{ $actividad->sucursal->nombre ?? 'SIN SUCURSAL' }}</td>
            </tr>
        @empty
            <tr id="actividad-vacia">
                <td colspan="6" style="text-align:center;color:#888;padding:2rem">Aún no hay actividad registrada</td>
            </tr>
        @endforelse
    </tbody>
</table>

<script>
    /*
     * Guarda el último ID visto en la tabla de actividad.
     * Se conecta con la ruta actividad.latest para pedir solo registros nuevos.
     */
    let ultimaActividadId = Number(document.querySelector('[data-actividad-id]')?.dataset.actividadId || 0);

    /*
     * Limpia texto antes de insertarlo en HTML.
     * Se conecta con agregarActividadEnPantalla para evitar que datos capturados rompan la tabla.
     */
    function textoSeguro(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, function(caracter) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[caracter];
        });
    }

    /*
     * Inserta una actividad nueva en la primera fila de la tabla.
     * Se conecta con el JSON que devuelve AdminActivityController::latest.
     */
    function agregarActividadEnPantalla(actividad) {
        const cuerpo = document.getElementById('actividad-cuerpo');
        const vacia = document.getElementById('actividad-vacia');

        if (vacia) {
            vacia.remove();
        }

        const fila = document.createElement('tr');
        fila.dataset.actividadId = actividad.id;
        fila.innerHTML = `
            <td>${textoSeguro(actividad.fecha)}</td>
            <td>${textoSeguro(actividad.modulo)}</td>
            <td><span class="badge badge-diagnostico">${textoSeguro(actividad.accion)}</span></td>
            <td>${textoSeguro(actividad.descripcion)}</td>
            <td>${textoSeguro(actividad.usuario)}</td>
            <td>${textoSeguro(actividad.sucursal)}</td>
        `;

        cuerpo.prepend(fila);
        ultimaActividadId = Math.max(ultimaActividadId, Number(actividad.id));
    }

    /*
     * Consulta actividades nuevas cada 3 segundos para simular tiempo real sin recargar la página.
     * Se conecta con /actividad/ultimas y mantiene informado al admin mientras el sistema está abierto.
     */
    async function actualizarActividadAdmin() {
        const respuesta = await fetch(`{{ route('actividad.latest') }}?desde_id=${ultimaActividadId}`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!respuesta.ok) {
            return;
        }

        const actividades = await respuesta.json();
        actividades.forEach(agregarActividadEnPantalla);
    }

    setInterval(actualizarActividadAdmin, 3000);
</script>
@endsection
