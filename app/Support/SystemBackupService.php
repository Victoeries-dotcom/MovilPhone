<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SystemBackupService
{
    /**
     * Crea un respaldo JSON portable de todas las tablas de la conexion activa.
     * Se conecta con la base configurada en .env y guarda el archivo en storage/app/private/backups.
     */
    public function create(string $origen = 'manual'): string
    {
        $baseDatos = DB::connection()->getDatabaseName();
        /*
         * Limita la exportacion a la base configurada en .env.
         * El segundo argumento evita prefijos y se conecta solo con MovilPhone2026.
         */
        $tablas = Schema::getTableListing($baseDatos, false);
        $contenido = [
            'metadata' => [
                'aplicacion' => config('app.name', 'MovilPhone'),
                'base_datos' => $baseDatos,
                'creado_en' => now()->toIso8601String(),
                'origen' => $origen,
                'version' => 1,
            ],
            'tablas' => [],
        ];

        foreach ($tablas as $tabla) {
            // Cada tabla conserva sus filas completas para permitir restauracion administrativa futura.
            $contenido['tablas'][$tabla] = DB::table($tabla)->orderBy($this->orderColumn($tabla))->get()->toArray();
        }

        $nombre = 'movilphone-'.Str::slug($origen).'-'.now()->format('Y-m-d-His').'.json';
        $ruta = 'backups/'.$nombre;
        Storage::disk('local')->put(
            $ruta,
            json_encode($contenido, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $ruta;
    }

    /**
     * Lista los respaldos disponibles con metadatos para la pantalla administrativa.
     * Se conecta con el disco local privado y no expone archivos mediante public/.
     */
    public function all(): array
    {
        return collect(Storage::disk('local')->files('backups'))
            ->filter(fn (string $ruta) => str_ends_with($ruta, '.json'))
            ->map(fn (string $ruta) => [
                'ruta' => $ruta,
                'nombre' => basename($ruta),
                'tamano' => Storage::disk('local')->size($ruta),
                'fecha' => Storage::disk('local')->lastModified($ruta),
            ])
            ->sortByDesc('fecha')
            ->values()
            ->all();
    }

    /** Conserva los respaldos mas recientes y se conecta con la tarea programada diaria. */
    public function prune(int $conservar = 14): void
    {
        collect($this->all())
            ->slice($conservar)
            ->each(fn (array $respaldo) => Storage::disk('local')->delete($respaldo['ruta']));
    }

    /** Elige una columna estable para ordenar la exportacion sin asumir que todas usan id. */
    private function orderColumn(string $tabla): string
    {
        return Schema::hasColumn($tabla, 'id') ? 'id' : Schema::getColumnListing($tabla)[0];
    }
}
