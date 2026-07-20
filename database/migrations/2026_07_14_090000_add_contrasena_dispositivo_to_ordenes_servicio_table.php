<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el patrón, PIN o contraseña del dispositivo recibido.
     * Se conecta con el formulario de órdenes y permite consultar ese dato desde el detalle del servicio.
     */
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->string('contrasena_dispositivo')->nullable()->after('estado_fisico');
        });
    }

    /**
     * Quita el campo si se revierte esta migración.
     * Sirve para regresar la tabla ordenes_servicio a su estructura anterior.
     */
    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn('contrasena_dispositivo');
        });
    }
};
