<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los datos necesarios para métodos de pago, anticipos y saldos.
     * Estos campos conectan Caja con los cobros registrados en órdenes de servicio.
     */
    public function up(): void
    {
        Schema::table('movimientos_caja', function (Blueprint $table) {
            $table->string('metodo_pago', 30)->default('efectivo')->after('categoria');
            $table->decimal('anticipo', 10, 2)->default(0)->after('monto');
            $table->decimal('saldo_pendiente', 10, 2)->default(0)->after('anticipo');
            $table->boolean('es_anticipo')->default(false)->after('saldo_pendiente');
            $table->boolean('es_pago_final')->default(false)->after('es_anticipo');
            $table->string('referencia_pago')->nullable()->after('es_pago_final');
        });

        // Recupera método y anticipo de las órdenes existentes para que los totales históricos sean coherentes.
        $movimientos = DB::table('movimientos_caja as movimiento')
            ->join('ordenes_servicio as orden', 'orden.id', '=', 'movimiento.os_id')
            ->select([
                'movimiento.id',
                'movimiento.monto',
                'orden.anticipo',
                'orden.metodo_pago_anticipo',
                'orden.estado',
            ])
            ->get();

        foreach ($movimientos as $movimiento) {
            $anticipo = min((float) ($movimiento->anticipo ?? 0), (float) $movimiento->monto);
            DB::table('movimientos_caja')
                ->where('id', $movimiento->id)
                ->update([
                    'metodo_pago' => strtolower($movimiento->metodo_pago_anticipo ?: 'efectivo'),
                    'anticipo' => $anticipo,
                    'es_anticipo' => $anticipo > 0,
                    'es_pago_final' => strtoupper((string) $movimiento->estado) === 'ENTREGADO',
                ]);
        }
    }

    /** Elimina únicamente los campos financieros agregados por esta migración. */
    public function down(): void
    {
        Schema::table('movimientos_caja', function (Blueprint $table) {
            $table->dropColumn([
                'metodo_pago',
                'anticipo',
                'saldo_pendiente',
                'es_anticipo',
                'es_pago_final',
                'referencia_pago',
            ]);
        });
    }
};
