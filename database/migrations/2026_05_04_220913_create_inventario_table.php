<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->integer('cantidad_disponible')->default(0);
            $table->integer('stock_minimo')->default(2);
            $table->decimal('precio_costo', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->string('proveedor')->nullable();
            $table->string('dispositivo_compatible')->nullable();
            $table->string('calidad')->default('Original');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};