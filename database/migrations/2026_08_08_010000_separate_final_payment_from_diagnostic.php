<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa el precio diagnosticado del dinero recibido al entregar y normaliza registros anteriores.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('ordenes_servicio', 'pago_final')) {
            Schema::table('ordenes_servicio', function (Blueprint $table) {
                $table->decimal('pago_final', 10, 2)->default(0)->after('metodo_pago_anticipo');
            });
        }

        DB::table('ordenes_servicio')->orderBy('id')->chunkById(100, function ($ordenes): void {
            foreach ($ordenes as $orden) {
                $anticipo = (float) ($orden->anticipo ?? 0);
                $diagnostico = (float) ($orden->cobro_diagnostico ?? 0);
                $presupuesto = (float) ($orden->presupuesto_total ?? 0);

                if ($orden->estado === 'ENTREGADO') {
                    // Antes de esta migración, cobro_diagnostico ya había sido sobrescrito con el pago final.
                    DB::table('ordenes_servicio')->where('id', $orden->id)->update([
                        'pago_final' => $diagnostico,
                        'presupuesto_total' => $presupuesto > 0 ? $presupuesto : $anticipo + $diagnostico,
                        'cobro_diagnostico' => 0,
                    ]);
                    $pagoFinal = $diagnostico;
                    $presupuesto = $presupuesto > 0 ? $presupuesto : $anticipo + $pagoFinal;
                } elseif ($presupuesto <= 0 && $diagnostico > 0) {
                    // En órdenes abiertas, el diagnóstico monetario funciona como precio inicial del servicio.
                    DB::table('ordenes_servicio')->where('id', $orden->id)->update([
                        'presupuesto_total' => $diagnostico,
                    ]);
                    $pagoFinal = 0;
                    $presupuesto = $diagnostico;
                } else {
                    $pagoFinal = 0;
                }

                // Corrige la fila financiera anterior para que Caja solo refleje dinero realmente recibido.
                $movimiento = DB::table('movimientos_caja')
                    ->where('os_id', $orden->id)
                    ->where('categoria', 'Orden de Servicio');
                $totalPagado = $anticipo + $pagoFinal;

                if ($totalPagado <= 0) {
                    $movimiento->delete();
                } else {
                    $descripcion = $pagoFinal > 0
                        ? 'Anticipo $'.number_format($anticipo, 2).' + Pago final $'.number_format($pagoFinal, 2)
                        : 'Anticipo $'.number_format($anticipo, 2);
                    $movimiento->update([
                        'monto' => $totalPagado,
                        'anticipo' => $anticipo,
                        'saldo_pendiente' => max(0, $presupuesto - $totalPagado),
                        'es_anticipo' => $anticipo > 0,
                        'es_pago_final' => $pagoFinal > 0 && $orden->estado === 'ENTREGADO',
                        'descripcion' => $descripcion,
                    ]);
                }
            }
        });
    }

    /**
     * Restaura el formato anterior si se revierte la migración.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('ordenes_servicio', 'pago_final')) {
            return;
        }

        DB::table('ordenes_servicio')
            ->where('estado', 'ENTREGADO')
            ->where('cobro_diagnostico', 0)
            ->update(['cobro_diagnostico' => DB::raw('pago_final')]);

        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn('pago_final');
        });
    }
};
