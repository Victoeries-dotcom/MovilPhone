@extends('layout')

@section('content')
<div class="page-header">
    <div>
        <h1>Actividad del sistema</h1>
        <div class="page-title-sub">Movimientos recientes capturados por usuarios en clientes, órdenes y caja.</div>
    </div>
</div>

{{-- Resumen por usuario: conecta los conteos acumulados del controlador con la sucursal activa. --}}
<section class="activity-user-summary" aria-labelledby="activityUserSummaryTitle">
    <div class="activity-section-heading">
        <div>
            <span class="activity-section-kicker">AUDITORÍA POR USUARIO</span>
            <h2 id="activityUserSummaryTitle">Usuarios con actividad registrada</h2>
            <p>Acciones acumuladas dentro de la sucursal seleccionada.</p>
        </div>
    </div>

    {{-- Indicadores generales: muestran el total y las acciones principales guardadas en admin_activities. --}}
    <div class="stats-grid activity-stats-grid">
        <article class="stat-card">
            <span class="stat-label">TOTAL DE REGISTROS</span>
            <strong class="stat-num" id="actividad-total">{{ number_format($totalesActividad['total']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">AGREGADOS</span>
            <strong class="stat-num green" id="actividad-agregados">{{ number_format($totalesActividad['agregados']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">EDITADOS</span>
            <strong class="stat-num amber" id="actividad-editados">{{ number_format($totalesActividad['editados']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">ELIMINADOS</span>
            <strong class="stat-num red" id="actividad-eliminados">{{ number_format($totalesActividad['eliminados']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">OTRAS ACCIONES</span>
            <strong class="stat-num" id="actividad-otras">{{ number_format($totalesActividad['otras']) }}</strong>
        </article>
    </div>

    {{-- Tabla agrupada: presenta una fila por usuario y sucursal sin alterar el historial detallado. --}}
    <div class="activity-summary-table-card">
        <table id="actividad-resumen-usuarios" data-ui-table-static>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Sucursal</th>
                    <th>Total registros</th>
                    <th>Agregados</th>
                    <th>Editados</th>
                    <th>Eliminados</th>
                    <th>Otras acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resumenUsuarios as $resumen)
                    <tr data-resumen-clave="{{ ($resumen->user_id ?? 0).'-'.($resumen->sucursal_id ?? 0) }}">
                        <td data-resumen-usuario>{{ $resumen->usuario->name ?? 'SISTEMA' }}</td>
                        <td data-resumen-sucursal>{{ $resumen->sucursal->nombre ?? 'SIN SUCURSAL' }}</td>
                        <td data-resumen-total>{{ number_format($resumen->total_registros) }}</td>
                        <td data-resumen-agregados>{{ number_format($resumen->agregados) }}</td>
                        <td data-resumen-editados>{{ number_format($resumen->editados) }}</td>
                        <td data-resumen-eliminados>{{ number_format($resumen->eliminados) }}</td>
                        <td data-resumen-otras>{{ number_format($resumen->otras_acciones) }}</td>
                    </tr>
                @empty
                    <tr id="actividad-resumen-vacio">
                        <td colspan="7">Aún no hay usuarios con actividad registrada en esta sucursal.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Historial detallado: conserva las columnas operativas solicitadas para revisar cada movimiento. --}}
<section class="activity-detail-section" aria-labelledby="activityDetailTitle">
    <div class="activity-section-heading">
        <div>
            <span class="activity-section-kicker">HISTORIAL</span>
            <h2 id="activityDetailTitle">Registro detallado de actividad</h2>
            <p>Últimos 40 movimientos con fecha, módulo, acción, usuario y sucursal.</p>
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
</section>

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
        actualizarResumenUsuario(actividad);
        ultimaActividadId = Math.max(ultimaActividadId, Number(actividad.id));
    }

    /*
     * Clasifica la acción recibida para actualizar su columna correspondiente.
     * Se conecta con los nombres CREAR, EDITAR y ELIMINAR guardados en admin_activities.
     */
    function categoriaDeActividad(accion) {
        const valor = String(accion || '').toUpperCase();
        if (['CREAR', 'AGREGAR', 'REGISTRAR'].includes(valor)) return 'agregados';
        if (['EDITAR', 'ACTUALIZAR'].includes(valor)) return 'editados';
        if (['ELIMINAR', 'BORRAR'].includes(valor)) return 'eliminados';
        return 'otras';
    }

    /*
     * Incrementa un contador visible sin modificar la base de datos.
     * Se conecta con las tarjetas generales y con cada fila agrupada por usuario y sucursal.
     */
    function incrementarContador(elemento) {
        if (!elemento) return;
        const actual = Number(elemento.textContent.replace(/[^\d]/g, '')) || 0;
        elemento.textContent = new Intl.NumberFormat('es-MX').format(actual + 1);
    }

    /*
     * Refleja en tiempo real quién realizó la nueva acción y en qué sucursal.
     * Se conecta con actividad.latest y crea una fila si ese usuario todavía no estaba en el resumen.
     */
    function actualizarResumenUsuario(actividad) {
        const categoria = categoriaDeActividad(actividad.accion);
        const clave = `${actividad.usuario_id || 0}-${actividad.sucursal_id || 0}`;
        const cuerpo = document.querySelector('#actividad-resumen-usuarios tbody');
        let fila = cuerpo?.querySelector(`[data-resumen-clave="${clave}"]`);

        document.getElementById('actividad-resumen-vacio')?.remove();
        incrementarContador(document.getElementById('actividad-total'));
        incrementarContador(document.getElementById(`actividad-${categoria}`));

        if (!cuerpo) return;
        if (!fila) {
            fila = document.createElement('tr');
            fila.dataset.resumenClave = clave;
            fila.innerHTML = `
                <td data-resumen-usuario>${textoSeguro(actividad.usuario)}</td>
                <td data-resumen-sucursal>${textoSeguro(actividad.sucursal)}</td>
                <td data-resumen-total>0</td>
                <td data-resumen-agregados>0</td>
                <td data-resumen-editados>0</td>
                <td data-resumen-eliminados>0</td>
                <td data-resumen-otras>0</td>
            `;
            cuerpo.prepend(fila);
        }

        incrementarContador(fila.querySelector('[data-resumen-total]'));
        incrementarContador(fila.querySelector(`[data-resumen-${categoria}]`));
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
