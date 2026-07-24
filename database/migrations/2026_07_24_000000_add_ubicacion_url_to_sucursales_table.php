<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el enlace de Google Maps usado por los formularios de sucursales.
     * La verificación conecta instalaciones nuevas sin duplicar la columna existente en producción.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sucursales', 'ubicacion_url')) {
            Schema::table('sucursales', function (Blueprint $table) {
                $table->string('ubicacion_url', 2048)->nullable()->after('ubicacion');
            });
        }
    }

    /**
     * Revierte únicamente la columna creada por esta migración.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sucursales', 'ubicacion_url')) {
            Schema::table('sucursales', function (Blueprint $table) {
                $table->dropColumn('ubicacion_url');
            });
        }
    }
};
