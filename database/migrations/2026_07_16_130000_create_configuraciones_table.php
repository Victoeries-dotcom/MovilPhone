<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea configuraciones para textos generales usados por distintos módulos.
     * politica_garantia se conecta con Órdenes y el ticket de entrega.
     */
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->timestamps();
        });
    }

    /** Elimina únicamente la tabla creada por esta migración. */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
