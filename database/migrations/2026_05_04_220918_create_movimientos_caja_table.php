<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->enum('tipo', ['INGRESO', 'EGRESO']);
            $table->string('categoria');
            $table->decimal('monto', 10, 2);
            $table->text('descripcion')->nullable();
            $table->foreignId('os_id')
                  ->nullable()
                  ->constrained('ordenes_servicio')
                  ->nullOnDelete();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};