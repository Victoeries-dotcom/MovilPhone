<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('numero_os')->unique();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', [
                'RECIBIDO',
                'EN DIAGNÓSTICO',
                'ESPERANDO AUTORIZACIÓN',
                'AUTORIZADO',
                'RECHAZADO',
                'EN REPARACIÓN',
                'ESPERANDO REFACCIÓN',
                'TERMINADO',
                'NOTIFICADO',
                'ENTREGADO',
                'GARANTÍA'
            ])->default('RECIBIDO');
            $table->string('marca');
            $table->string('modelo');
            $table->string('imei')->nullable();
            $table->text('problema_reportado');
            $table->text('problema_diagnosticado')->nullable();
            $table->text('accesorios_entregados');
            $table->text('estado_fisico');
            $table->decimal('cobro_diagnostico', 10, 2)->default(0);
            $table->decimal('presupuesto_total', 10, 2)->default(0);
            $table->decimal('mano_obra', 10, 2)->default(0);
            $table->date('fecha_entrega_estimada')->nullable();
            $table->timestamp('fecha_entrega_real')->nullable();
            $table->foreignId('os_origen_id')->nullable()->constrained('ordenes_servicio')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_servicio');
    }
};