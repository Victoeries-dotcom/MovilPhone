<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProtectDemoMode
{
    /**
     * Bloquea eliminaciones cuando el modo demostracion esta activo.
     * Se conecta con configuraciones.modo_demo y protege los datos durante presentaciones comerciales.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('DELETE') && $this->demoActivo()) {
            return back()->with('error', 'El modo demostracion protege los registros contra eliminaciones.');
        }

        return $next($request);
    }

    /** Consulta el interruptor global sin depender de una sesion especifica. */
    private function demoActivo(): bool
    {
        try {
            return DB::table('configuraciones')->where('clave', 'modo_demo')->value('valor') === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}
