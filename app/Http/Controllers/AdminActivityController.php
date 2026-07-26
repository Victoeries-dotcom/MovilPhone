<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use Illuminate\Http\Request;

class AdminActivityController extends Controller
{
    /**
     * Muestra el panel de actividad del admin.
     * Se conecta con la vista actividad.index y carga los últimos movimientos del sistema.
     */
    public function index()
    {
        $this->soloAdmin();
        $sucursalId = session('sucursal_id');

        /*
         * Define una sola consulta por sucursal para que el resumen y el historial
         * se conecten con los mismos registros de admin_activities.
         */
        $consultaSucursal = AdminActivity::query()
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->when(! $sucursalId, fn ($query) => $query->whereRaw('1 = 0'));

        /*
         * Agrupa la actividad por usuario y sucursal.
         * Se conecta con users y sucursales para mostrar quién realizó cada tipo de acción.
         */
        $resumenUsuarios = (clone $consultaSucursal)
            ->with(['usuario', 'sucursal'])
            ->select(['user_id', 'sucursal_id'])
            ->selectRaw('COUNT(*) AS total_registros')
            ->selectRaw("SUM(CASE WHEN UPPER(accion) IN ('CREAR', 'AGREGAR', 'REGISTRAR') THEN 1 ELSE 0 END) AS agregados")
            ->selectRaw("SUM(CASE WHEN UPPER(accion) IN ('EDITAR', 'ACTUALIZAR') THEN 1 ELSE 0 END) AS editados")
            ->selectRaw("SUM(CASE WHEN UPPER(accion) IN ('ELIMINAR', 'BORRAR') THEN 1 ELSE 0 END) AS eliminados")
            ->selectRaw("SUM(CASE WHEN UPPER(accion) NOT IN ('CREAR', 'AGREGAR', 'REGISTRAR', 'EDITAR', 'ACTUALIZAR', 'ELIMINAR', 'BORRAR') THEN 1 ELSE 0 END) AS otras_acciones")
            ->groupBy('user_id', 'sucursal_id')
            ->orderByDesc('total_registros')
            ->get();

        /*
         * Calcula los indicadores generales desde el resumen agrupado.
         * Se conecta con las tarjetas de la vista y representa toda la actividad de la sucursal.
         */
        $totalesActividad = [
            'total' => (int) $resumenUsuarios->sum('total_registros'),
            'agregados' => (int) $resumenUsuarios->sum('agregados'),
            'editados' => (int) $resumenUsuarios->sum('editados'),
            'eliminados' => (int) $resumenUsuarios->sum('eliminados'),
            'otras' => (int) $resumenUsuarios->sum('otras_acciones'),
        ];

        /*
         * Mantiene el historial detallado limitado a los 40 movimientos más recientes.
         * Se conecta con la tabla inferior y con la actualización automática de la vista.
         */
        $actividades = (clone $consultaSucursal)
            ->with(['usuario', 'sucursal'])
            ->latest()
            ->limit(40)
            ->get();

        return view('actividad.index', compact('actividades', 'resumenUsuarios', 'totalesActividad'));
    }

    /**
     * Entrega actividades nuevas en formato JSON.
     * Se conecta con el JavaScript de la vista para actualizar la tabla sin recargar.
     */
    public function latest(Request $request)
    {
        $this->soloAdmin();

        $desdeId = (int) $request->get('desde_id', 0);
        $sucursalId = session('sucursal_id');

        $actividades = AdminActivity::with(['usuario', 'sucursal'])
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->when(! $sucursalId, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($desdeId > 0, fn ($query) => $query->where('id', '>', $desdeId))
            ->oldest()
            ->limit(30)
            ->get()
            ->map(fn ($actividad) => [
                'id' => $actividad->id,
                'modulo' => $actividad->modulo,
                'accion' => $actividad->accion,
                'descripcion' => $actividad->descripcion,
                'usuario_id' => $actividad->user_id ?? 0,
                'sucursal_id' => $actividad->sucursal_id ?? 0,
                'usuario' => $actividad->usuario->name ?? 'SISTEMA',
                'sucursal' => $actividad->sucursal->nombre ?? 'SIN SUCURSAL',
                'fecha' => $actividad->created_at->format('d/m/Y H:i:s'),
            ]);

        return response()->json($actividades);
    }

    /**
     * Alimenta la campana del administrador con actividad reciente y conteo no leido.
     * Se conecta con admin_activities y con la marca de lectura guardada en la sesion.
     */
    public function notifications(Request $request)
    {
        $this->soloAdmin();

        $ultimaLectura = session('notifications_seen_at');
        $sucursalId = session('sucursal_id');
        $consulta = AdminActivity::with(['usuario', 'sucursal'])
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId));

        $noLeidas = (clone $consulta)
            ->when($ultimaLectura, fn ($query) => $query->where('created_at', '>', $ultimaLectura))
            ->count();

        $actividades = $consulta->latest()->limit(8)->get()->map(fn ($actividad) => [
            'id' => $actividad->id,
            'modulo' => $actividad->modulo,
            'accion' => $actividad->accion,
            'descripcion' => $actividad->descripcion,
            'usuario' => $actividad->usuario->name ?? 'SISTEMA',
            'sucursal' => $actividad->sucursal->nombre ?? 'SIN SUCURSAL',
            'fecha' => $actividad->created_at->diffForHumans(),
        ]);

        return response()->json(['no_leidas' => $noLeidas, 'actividades' => $actividades]);
    }

    /** Marca la campana como leida y se conecta con la sesion del superusuario actual. */
    public function markNotificationsRead(Request $request)
    {
        $this->soloAdmin();
        $request->session()->put('notifications_seen_at', now());

        return response()->json(['ok' => true]);
    }

    /**
     * Restringe el panel de actividad únicamente al superusuario.
     * Se conecta con las rutas de actividad para proteger información administrativa.
     */
    private function soloAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->rol === 'superusuario', 403);
    }
}
