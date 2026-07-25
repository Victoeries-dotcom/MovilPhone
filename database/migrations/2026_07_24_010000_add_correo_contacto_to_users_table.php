<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa el correo informativo del correo único conectado con el login.
     * También conserva el correo anterior como contacto para evitar pérdida de datos.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'correo_contacto')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('correo_contacto')->nullable()->after('telefono');
            });
        }

        DB::table('users')
            ->whereNull('correo_contacto')
            ->update(['correo_contacto' => DB::raw('email')]);
    }

    /**
     * Revierte solamente el dato de contacto; users.email sigue siendo la credencial.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'correo_contacto')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('correo_contacto');
            });
        }
    }
};
