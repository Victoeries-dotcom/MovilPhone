<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa en Caja el anticipo y la liquidación, y conserva el método usado al entregar.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('ordenes_servicio', 'metodo_pago_final')) {
            Schema::table('ordenes_servicio', function (Blueprint $table) {
                $table->string('metodo_pago_final', 30)->nullable()->after('pago_final');
            });
        }

        DB::table('ordenes_servicio')->orderBy('id')->chunkById(100, function ($ordenes): void {
            foreach ($ordenes as $orden) {
                $anticipo = (float) ($orden->anticipo ?? 0);
                $pagoFinal = (float) ($orden->pago_final ?? 0);
                $presupuesto = (float) ($orden->presupuesto_total ?? 0);
                $movimiento = DB::table('movimientos_caja')
                    ->where('os_id', $orden->id)
                    ->where('categoria', 'Orden de Servicio')
                    ->first();

                if (! $movimiento) {
                    continue;
                }

                if ($anticipo > 0) {
                    // Reutiliza la fila existente para conservar la fecha real en que entró el anticipo.
                    DB::table('movimientos_caja')->where('id', $movimiento->id)->update([
                        'categoria' => 'Anticipo de Orden',
                        'monto' => $anticipo,
                        'anticipo' => $anticipo,
                        'saldo_pendiente' => max(0, $presupuesto - $anticipo),
                        'es_anticipo' => true,
                        'es_pago_final' => false,
                        'descripcion' => 'Anticipo $'.number_format($anticipo, 2),
                    ]);
                } else {
                    DB::table('movimientos_caja')->where('id', $movimiento->id)->delete();
                }

                if ($pagoFinal > 0 && $orden->estado === 'ENTREGADO') {
                    // En históricos conserva el método que la fila combinada ya declaraba; no existe otro dato previo.
                    $metodoFinal = strtolower($movimiento->metodo_pago ?: 'efectivo');
                    DB::table('ordenes_servicio')->where('id', $orden->id)->update([
                        'metodo_pago_final' => $metodoFinal,
                    ]);

                    // Crea la liquidación con la fecha de entrega para que aparezca como ingreso independiente.
                    DB::table('movimientos_caja')->insert([
                        'sucursal_id' => $orden->sucursal_id,
                        'tipo' => 'INGRESO',
                        'categoria' => 'Pago final de Orden',
                        'monto' => $pagoFinal,
                        'metodo_pago' => $metodoFinal,
                        'anticipo' => 0,
                        'saldo_pendiente' => 0,
                        'es_anticipo' => false,
                        'es_pago_final' => true,
                        'descripcion' => 'Pago final $'.number_format($pagoFinal, 2),
                        'os_id' => $orden->id,
                        'user_id' => $movimiento->user_id,
                        'created_at' => $orden->fecha_entrega_real ?: $movimiento->updated_at,
                        'updated_at' => $orden->fecha_entrega_real ?: $movimiento->updated_at,
                    ]);
                }
            }
        });
    }

    /**
     * Une nuevamente ambos importes para permitir una reversión segura de la estructura anterior.
     */
    public function down(): void
    {
        // Vuelve a sumar ambas filas para que una reversión no pierda dinero registrado.
        $pagosFinales = DB::table('movimientos_caja')
            ->where('categoria', 'Pago final de Orden')
            ->get();

        foreach ($pagosFinales as $pagoFinal) {
            $anticipo = DB::table('movimientos_caja')
                ->where('os_id', $pagoFinal->os_id)
                ->where('categoria', 'Anticipo de Orden')
                ->first();

            if ($anticipo) {
                DB::table('movimientos_caja')->where('id', $anticipo->id)->update([
                    'categoria' => 'Orden de Servicio',
                    'monto' => (float) $anticipo->monto + (float) $pagoFinal->monto,
                    'saldo_pendiente' => 0,
                    'es_pago_final' => true,
                    'descripcion' => 'Anticipo $'.number_format((float) $anticipo->monto, 2)
                        .' + Pago final $'.number_format((float) $pagoFinal->monto, 2),
                ]);
            } else {
                DB::table('movimientos_caja')->where('id', $pagoFinal->id)->update([
                    'categoria' => 'Orden de Servicio',
                ]);
            }
        }

        DB::table('movimientos_caja')->where('categoria', 'Pago final de Orden')->delete();
        DB::table('movimientos_caja')->where('categoria', 'Anticipo de Orden')
            ->update(['categoria' => 'Orden de Servicio']);

        if (Schema::hasColumn('ordenes_servicio', 'metodo_pago_final')) {
            Schema::table('ordenes_servicio', function (Blueprint $table) {
                $table->dropColumn('metodo_pago_final');
            });
        }
    }
};
