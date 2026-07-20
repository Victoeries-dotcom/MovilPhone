<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los campos solicitados para la Nueva OS.
     * Se conecta con el formulario de órdenes, el controlador y la tabla ordenes_servicio.
     */
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_servicio', 'cliente_telefono_extra')) {
                $table->string('cliente_telefono_extra')->nullable()->after('cliente_id');
            }

            if (!Schema::hasColumn('ordenes_servicio', 'tipo_dispositivo')) {
                $table->string('tipo_dispositivo')->nullable()->after('modelo');
            }

            if (!Schema::hasColumn('ordenes_servicio', 'anticipo')) {
                $table->decimal('anticipo', 10, 2)->default(0)->after('cobro_diagnostico');
            }

            if (!Schema::hasColumn('ordenes_servicio', 'metodo_pago_anticipo')) {
                $table->string('metodo_pago_anticipo')->default('efectivo')->after('anticipo');
            }
        });
    }

    /**
     * Quita los campos de Nueva OS si se revierte esta migración.
     * Sirve para regresar ordenes_servicio a su estructura previa sin tocar otros módulos.
     */
    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            foreach (['cliente_telefono_extra', 'tipo_dispositivo', 'anticipo', 'metodo_pago_anticipo'] as $campo) {
                if (Schema::hasColumn('ordenes_servicio', $campo)) {
                    $table->dropColumn($campo);
                }
            }
        });
    }
};
