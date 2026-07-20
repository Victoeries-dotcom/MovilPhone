<?php

namespace App\Http\Controllers;

use App\Support\AdminActivityLogger;
use App\Support\SystemBackupService;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function __construct(private readonly SystemBackupService $backups)
    {
    }

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

    /** Descarga un respaldo validado sin permitir rutas fuera de storage/app/private/backups. */
    public function download(string $archivo)
    {
        $ruta = $this->safePath($archivo);
        abort_unless(Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->download($ruta);
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
