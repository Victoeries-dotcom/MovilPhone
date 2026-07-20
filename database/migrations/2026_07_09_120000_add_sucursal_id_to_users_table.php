<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la sucursal del usuario.
     * Se conecta con la tabla `sucursales` para saber a qué sucursal pertenece
     * cada técnico, usuario o rol del sistema.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Guarda la sucursal asignada al usuario; queda nullable para no romper usuarios antiguos.
            $table->foreignId('sucursal_id')
                ->nullable()
                ->after('rol')
                ->constrained('sucursales')
                ->nullOnDelete();
        });
    }

    /**
     * Revierte la conexión entre usuarios y sucursales si se deshace la migración.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Elimina primero la llave foránea y luego la columna de sucursal.
            $table->dropConstrainedForeignId('sucursal_id');
        });
    }
};
