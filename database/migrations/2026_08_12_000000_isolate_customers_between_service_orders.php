<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite repetir teléfonos y separa las órdenes antiguas que todavía comparten clientes.id.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // El teléfono deja de ser una llave de identidad: cada cliente se modifica exclusivamente por su ID.
            $table->dropUnique('clientes_telefono_normalizado_unique');
        });

        $clientesCompartidos = DB::table('ordenes_servicio')
            ->select('cliente_id')
            ->groupBy('cliente_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('cliente_id');

        foreach ($clientesCompartidos as $clienteId) {
            $cliente = DB::table('clientes')->where('id', $clienteId)->first();

            if (! $cliente) {
                continue;
            }

            $ordenes = DB::table('ordenes_servicio')
                ->where('cliente_id', $clienteId)
                ->orderBy('id')
                ->pluck('id');

            // La primera OS conserva el registro original; cada OS adicional recibe una copia con un ID propio.
            foreach ($ordenes->skip(1) as $ordenId) {
                $nuevoClienteId = DB::table('clientes')->insertGetId([
                    'nombre' => $cliente->nombre,
                    'telefono_principal' => $cliente->telefono_principal,
                    'telefono_normalizado' => $cliente->telefono_normalizado,
                    'telefono_alternativo' => $cliente->telefono_alternativo,
                    'direccion' => $cliente->direccion,
                    'sucursal_habitual_id' => $cliente->sucursal_habitual_id,
                    'created_at' => $cliente->created_at,
                    'updated_at' => $cliente->updated_at,
                ]);

                DB::table('ordenes_servicio')
                    ->where('id', $ordenId)
                    ->update(['cliente_id' => $nuevoClienteId]);
            }
        }
    }

    /**
     * Restaura el contrato anterior agrupando nuevamente los teléfonos antes de recrear la llave única.
     */
    public function down(): void
    {
        $telefonosDuplicados = DB::table('clientes')
            ->select('telefono_normalizado')
            ->whereNotNull('telefono_normalizado')
            ->groupBy('telefono_normalizado')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('telefono_normalizado');

        foreach ($telefonosDuplicados as $telefono) {
            $clientes = DB::table('clientes')
                ->where('telefono_normalizado', $telefono)
                ->orderBy('id')
                ->pluck('id');
            $clientePrincipal = $clientes->first();

            foreach ($clientes->skip(1) as $clienteDuplicado) {
                // El rollback reconecta órdenes y ventas antes de retirar el registro duplicado.
                DB::table('ordenes_servicio')
                    ->where('cliente_id', $clienteDuplicado)
                    ->update(['cliente_id' => $clientePrincipal]);
                DB::table('ventas')
                    ->where('cliente_id', $clienteDuplicado)
                    ->update(['cliente_id' => $clientePrincipal]);
                DB::table('clientes')->where('id', $clienteDuplicado)->delete();
            }
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->unique('telefono_normalizado', 'clientes_telefono_normalizado_unique');
        });
    }
};
