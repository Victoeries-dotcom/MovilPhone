<?php

namespace App\Providers;

use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Comparte identidad comercial con layout, tickets y documentos imprimibles.
         * Se conecta con la tabla configuraciones y usa valores seguros antes de la primera configuracion.
         */
        View::composer('*', function ($view) {
            static $configuracion = null;

            if ($configuracion === null) {
                try {
                    $configuracion = DB::table('configuraciones')->pluck('valor', 'clave');
                } catch (\Throwable) {
                    $configuracion = collect();
                }
            }

            $view->with('configuracionGlobal', collect([
                'negocio_nombre' => $configuracion->get('negocio_nombre', 'MovilPhone'),
                'negocio_subtitulo' => $configuracion->get('negocio_subtitulo', 'Sistema de Taller'),
                'negocio_telefono' => $configuracion->get('negocio_telefono'),
                'negocio_email' => $configuracion->get('negocio_email'),
                'negocio_direccion' => $configuracion->get('negocio_direccion'),
                'color_primario' => $configuracion->get('color_primario', '#1650c5'),
                'moneda' => $configuracion->get('moneda', 'MXN'),
                'impuesto_porcentaje' => $configuracion->get('impuesto_porcentaje', '0'),
                'modo_demo' => $configuracion->get('modo_demo', '0') === '1',
            ]));
        });

        /*
         * Comparte con el menú lateral todas las sucursales registradas.
         * La consulta se conecta con la tabla sucursales y también incluye
         * automáticamente cualquier sucursal que se agregue en el futuro.
         */
        View::composer('layout', function ($view) {
            $view->with(
                'sucursalesMenu',
                Sucursal::query()->orderBy('nombre')->get()
            );
        });
    }
}
