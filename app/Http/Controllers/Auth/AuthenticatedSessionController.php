<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /*
         * Sincroniza la sucursal asignada con la sesion que consumen todos los modulos.
         * Se conecta con users.sucursal_id, la relacion User::sucursal y los filtros globales.
         */
        $usuario = $request->user();
        if ($usuario?->sucursal_id) {
            $usuario->loadMissing('sucursal');
            $request->session()->put([
                'sucursal_id' => $usuario->sucursal_id,
                'sucursal_nombre' => $usuario->sucursal?->nombre ?? 'Sin sucursal',
            ]);
        } else {
            $request->session()->forget(['sucursal_id', 'sucursal_nombre']);
        }

        /*
         * Mantiene compatibilidad con Laravel Breeze y lleva al mismo panel profesional.
         * La ruta dashboard se conecta mediante alias con DashboardController@index.
         */
        return redirect()->intended(route('dashboard'));
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
