<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->string('ubicacion')->nullable()->after('nombre');
            $table->string('Nombre_del_encargado')->nullable()->after('nombre');
            $table->string('telefono_encargado')->nullable()->after('ubicacion');
            $table->string('horario')->nullable()->after('telefono_encargado');
        });
    }

    public function down(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropColumn(['ubicacion', 'telefono_encargado', 'horario']);
        });
    }
};
