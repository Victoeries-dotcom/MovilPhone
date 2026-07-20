<?php

namespace App\Support;

use App\Models\AdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdminActivityLogger
{
    /**
     * Registra una acción importante para que el admin la vea casi en tiempo real.
     * Se conecta con el controlador que hizo la acción y con la tabla admin_activities.
     */
    public static function registrar(
        string $modulo,
        string $accion,
        string $descripcion,
        ?int $sucursalId = null,
        ?Model $registro = null
    ): void {
        AdminActivity::create([
            'modulo' => $modulo,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'sucursal_id' => $sucursalId ?: session('sucursal_id'),
            'user_id' => Auth::id(),
            'registro_tipo' => $registro ? get_class($registro) : null,
            'registro_id' => $registro?->getKey(),
        ]);
    }
}
