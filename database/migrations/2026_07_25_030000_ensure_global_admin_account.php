<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garantiza que exista una cuenta administrativa global en cada entorno.
     * Se conecta con la tabla users y con el formulario de login de Laravel,
     * conservando una sola identidad aunque local y Cloud tengan datos distintos.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $email = 'admin@movilphone.com';

            // Busca primero el correo definitivo y luego una cuenta Admin global anterior.
            $admin = DB::table('users')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if (! $admin) {
                $admin = DB::table('users')
                    ->whereRaw('LOWER(name) = ?', ['admin'])
                    ->whereRaw('LOWER(rol) = ?', ['superusuario'])
                    ->first();
            }

            // Estos campos conectan la cuenta con Auth y evitan limitarla a una sucursal.
            $credenciales = [
                'name' => 'Admin',
                'email' => $email,
                'password' => '$2y$10$BpO3ay1xSPEZo.T1SgKKU.nIIX0lA1vAtlQNbMNkz78GaVNa581PC',
                'rol' => 'superusuario',
                'sucursal_id' => null,
                'email_verified_at' => now(),
                'updated_at' => now(),
            ];

            if ($admin) {
                // Actualiza la cuenta encontrada sin modificar otras cuentas administrativas.
                DB::table('users')->where('id', $admin->id)->update($credenciales);

                return;
            }

            // Crea la cuenta únicamente cuando producción todavía no tiene un Admin compatible.
            $nuevoAdmin = array_merge($credenciales, [
                'remember_token' => null,
                'created_at' => now(),
            ]);

            if (Schema::hasColumn('users', 'telefono')) {
                $nuevoAdmin['telefono'] = null;
            }

            if (Schema::hasColumn('users', 'correo_contacto')) {
                $nuevoAdmin['correo_contacto'] = $email;
            }

            DB::table('users')->insert($nuevoAdmin);
        });
    }

    /**
     * No elimina el administrador durante un rollback para evitar perder acceso.
     * La contraseña queda conectada con Usuarios > Editar para cambios posteriores.
     */
    public function down(): void
    {
        // Intencionalmente vacío: un rollback no debe bloquear el acceso administrativo.
    }
};
