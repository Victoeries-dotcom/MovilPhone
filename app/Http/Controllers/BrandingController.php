<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandingController extends Controller
{
    /** Entrega el logo global a los usuarios autenticados del sistema. */
    public function logo()
    {
        // La fila fija conserva una sola imagen compartida por todas las sucursales.
        $branding = DB::table('branding_settings')->where('id', 1)->first();

        if (! $branding?->logo_data || ! $branding?->logo_mime_type) {
            // Conserva el logo oficial incluido en el proyecto hasta que se cargue uno personalizado.
            return response()->file(public_path('images/movilphone-logo-final.png'), [
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        }

        return response(base64_decode($branding->logo_data, true))
            ->header('Content-Type', $branding->logo_mime_type)
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    /** Guarda un nuevo logo global; solo el Super Usuario puede reemplazarlo. */
    public function updateLogo(Request $request)
    {
        abort_unless($request->user()->rol === 'superusuario', 403);

        // Limita los formatos a imágenes web y controla el tamaño guardado en la base de datos.
        $validated = $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $image = $validated['logo'];

        // La base de datos compartida mantiene el logo entre despliegues y réplicas de Laravel Cloud.
        DB::table('branding_settings')->updateOrInsert(
            ['id' => 1],
            [
                'logo_mime_type' => $image->getMimeType(),
                'logo_data' => base64_encode(file_get_contents($image->getRealPath())),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return back()->with('success', 'El logo se actualizó para todos los usuarios y sucursales.');
    }
}
