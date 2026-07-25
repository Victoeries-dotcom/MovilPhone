<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recupera el acceso del usuario de Buctzotz en cada entorno.
     * Se conecta con users.email, users.password y sucursales para que LoginRequest
     * autentique la misma cuenta tanto en la base local como en Laravel Cloud.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            // Localiza la sucursal por nombre para no depender de IDs diferentes entre local y producción.
            $sucursalId = DB::table('sucursales')
                ->whereRaw('LOWER(nombre) = ?', ['buctzotz'])
                ->value('id');

            // En bases nuevas de pruebas todavía no hay sucursales; se omite sin bloquear las demás migraciones.
            if (! $sucursalId) {
                return;
            }

            $emailAcceso = 'sucurbuc@movilphone.com';

            // Busca primero el correo de acceso y después el correo de contacto de registros anteriores.
            $usuario = DB::table('users')
                ->whereRaw('LOWER(email) = ?', [$emailAcceso])
                ->first();

            if (! $usuario && Schema::hasColumn('users', 'correo_contacto')) {
                $usuario = DB::table('users')
                    ->whereRaw('LOWER(correo_contacto) = ?', [$emailAcceso])
                    ->first();
            }

            // El hash corresponde a una contraseña temporal; nunca se guarda texto legible en el repositorio.
            $credenciales = [
                'email' => $emailAcceso,
                'password' => '$2y$10$lac02nHpQ/suJ6FKJSrgNOKO9E09UVSRmLDZDvP0TUDngtf15h6e6',
                'rol' => 'usuario',
                'sucursal_id' => $sucursalId,
                'updated_at' => now(),
            ];

            if ($usuario) {
                // Actualiza únicamente los campos vinculados con autenticación y conserva sus datos operativos.
                DB::table('users')->where('id', $usuario->id)->update($credenciales);

                return;
            }

            // Crea la cuenta solo cuando no existe ningún registro compatible en el entorno.
            DB::table('users')->insert(array_merge($credenciales, [
                'name' => 'USUARIO BUCTZOTZ',
                'telefono' => null,
                'correo_contacto' => Schema::hasColumn('users', 'correo_contacto')
                    ? $emailAcceso
                    : null,
                'email_verified_at' => null,
                'remember_token' => null,
                'created_at' => now(),
            ]));
        });
    }

    /**
     * No elimina ni debilita una cuenta real durante un rollback.
     * La credencial puede cambiarse después desde Usuarios y queda conectada con UsuarioController::update.
     */
    public function down(): void
    {
        // Intencionalmente vacío: revertir una migración no debe borrar usuarios ni restaurar contraseñas antiguas.
    }
};
