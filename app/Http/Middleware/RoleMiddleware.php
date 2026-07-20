<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Autoriza una ruta solo para los roles indicados.
     * Se conecta con users.rol y protege el backend aunque el enlace no aparezca en el menu.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user() && in_array($request->user()->rol, $roles, true), 403);

        return $next($request);
    }
}
