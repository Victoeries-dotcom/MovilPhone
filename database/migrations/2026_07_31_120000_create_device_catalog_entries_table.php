<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda los dispositivos, marcas y modelos escritos manualmente para reutilizarlos en el asistente.
     */
    public function up(): void
    {
        Schema::create('device_catalog_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('device_type');
            $table->string('brand');
            $table->string('model');
            $table->timestamps();

            // Acelera la carga ordenada y evita registrar dos veces la misma combinación.
            $table->unique(['device_type', 'brand', 'model'], 'device_catalog_combination_unique');
        });
    }

    /**
     * Elimina únicamente el catálogo aprendido; las órdenes conservan sus textos originales.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_catalog_entries');
    }
};
