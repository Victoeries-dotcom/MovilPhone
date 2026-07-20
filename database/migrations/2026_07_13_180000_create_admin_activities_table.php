<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el historial de actividad visible para el admin.
     * Se conecta con usuarios, sucursales y los registros creados en clientes, órdenes y caja.
     */
    public function up(): void
    {
        Schema::create('admin_activities', function (Blueprint $table) {
            $table->id();
            $table->string('modulo');
            $table->string('accion');
            $table->text('descripcion');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('registro_tipo')->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de actividad si se revierte la migración.
     * Sirve para limpiar solo este registro del admin sin tocar los módulos principales.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_activities');
    }
};
