<?php

namespace App\Http\Controllers;

use App\Support\AdminActivityLogger;
use App\Support\SystemBackupExcelService;
use App\Support\SystemBackupService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function __construct(
        private readonly SystemBackupService $backups,
        private readonly SystemBackupExcelService $excel
    ) {}

    /** Muestra respaldos privados y se conecta con configuracion.respaldos. */
    public function index()
    {
        return view('configuracion.respaldos', ['respaldos' => $this->backups->all()]);
    }

    /** Crea un respaldo manual y registra la accion en la auditoria administrativa. */
    public function store()
    {
        $ruta = $this->backups->create('manual');

        AdminActivityLogger::registrar(
            'SISTEMA',
            'RESPALDO',
            'Respaldo manual creado: '.basename($ruta),
            session('sucursal_id')
        );

        return back()->with('success', 'Respaldo creado correctamente.');
    }

    /** Respalda y luego elimina únicamente los registros operativos, previa confirmación escrita. */
    public function clearOperationalData(Request $request)
    {
        abort_unless($request->user()->rol === 'superusuario', 403);
        $request->validate(['confirmacion' => ['required', 'in:ELIMINAR DATOS']]);

        // La limpieza no comienza si no pudo crearse el respaldo completo de la base activa.
        $backupPath = $this->backups->create('previo-limpieza');

        DB::transaction(function (): void {
            // El orden respeta las llaves foráneas de movimientos, órdenes, ventas y detalles.
            foreach (['movimientos_caja', 'historial_estados', 'ordenes_servicio', 'venta_detalles', 'ventas', 'inventario', 'clientes'] as $table) {
                DB::table($table)->delete();
            }
            // Conserva el registro de esta acción como primera actividad posterior a la limpieza.
            if (\Illuminate\Support\Facades\Schema::hasTable('admin_activities')) {
                DB::table('admin_activities')->delete();
            }
        });

        AdminActivityLogger::registrar('SISTEMA', 'LIMPIEZA', 'Se eliminaron datos operativos. Respaldo: '.basename($backupPath));

        return back()->with('success', 'Datos operativos eliminados. Se conservaron usuarios, sucursales, catálogos, configuración, logo y el respaldo previo.');
    }

    /** Descarga un respaldo validado sin permitir rutas fuera de storage/app/private/backups. */
    public function download(string $archivo)
    {
        $ruta = $this->safePath($archivo);
        abort_unless(Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->download($ruta);
    }

    /** Convierte el JSON seleccionado en un Excel temporal separado por secciones operativas. */
    public function downloadExcel(string $archivo)
    {
        $ruta = $this->safePath($archivo);
        abort_unless(Storage::disk('local')->exists($ruta), 404);

        $rutaExcel = $this->excel->create($ruta);
        $nombreExcel = pathinfo($archivo, PATHINFO_FILENAME).'.xlsx';

        // El temporal se elimina después de enviarse; el respaldo JSON privado permanece intacto.
        return response()->download(
            $rutaExcel,
            $nombreExcel,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /** Elimina un respaldo seleccionado y mantiene intacta la base de datos activa. */
    public function destroy(string $archivo)
    {
        $ruta = $this->safePath($archivo);
        abort_unless(Storage::disk('local')->exists($ruta), 404);
        Storage::disk('local')->delete($ruta);

        return back()->with('success', 'Respaldo eliminado.');
    }

    /** Acepta solo nombres JSON simples para evitar acceso arbitrario al sistema de archivos. */
    private function safePath(string $archivo): string
    {
        abort_unless($archivo === basename($archivo) && str_ends_with($archivo, '.json'), 404);

        return 'backups/'.$archivo;
    }
}
