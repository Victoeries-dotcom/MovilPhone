<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Restablece el acceso de la cuenta Admin existente.
     * Se conecta con users.email y users.password para que el mismo acceso
     * funcione en la base local y en Laravel Cloud después del despliegue.
     */
    public function up(): void
    {
        $adminId = DB::table('users')
            ->whereRaw('LOWER(email) = ?', ['admin@movilphone.com'])
            ->value('id');

        // Las bases nuevas de pruebas no tienen este usuario y no deben crearlo automáticamente.
        if (! $adminId) {
            return;
        }

        DB::table('users')->where('id', $adminId)->update([
            // El repositorio guarda únicamente el hash; la contraseña legible nunca se almacena aquí.
            'password' => '$2y$10$BpO3ay1xSPEZo.T1SgKKU.nIIX0lA1vAtlQNbMNkz78GaVNa581PC',
            'rol' => 'superusuario',
            'sucursal_id' => null,
            'updated_at' => now(),
        ]);
    }

    /**
     * No restaura una contraseña anterior porque Laravel no conserva texto legible.
     * Las actualizaciones futuras se conectan con Usuarios > Editar > Actualizar contraseña.
     */
    public function down(): void
    {
        // Intencionalmente vacío para no bloquear nuevamente una cuenta administrativa real.
    }
};
