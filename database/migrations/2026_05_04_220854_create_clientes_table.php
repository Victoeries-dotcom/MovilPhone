<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('telefono_principal')->index();
            $table->string('telefono_alternativo')->nullable();
            $table->text('direccion')->nullable();
            $table->foreignId('sucursal_habitual_id')
                  ->nullable()
                  ->constrained('sucursales')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};