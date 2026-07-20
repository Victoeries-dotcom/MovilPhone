<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Agrega el identificador telefónico único de Clientes.
     * Se conecta con la búsqueda de Cliente anterior y evita duplicados
     * causados por espacios, guiones, paréntesis o diferencias de formato.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('telefono_normalizado', 80)
                ->nullable()
                ->after('telefono_principal');
        });

        $telefonosRegistrados = [];
        $clientes = DB::table('clientes')
            ->select('id', 'telefono_principal')
            ->orderBy('id')
            ->get();

        foreach ($clientes as $cliente) {
            // Aplica la misma normalización usada por App\Models\Cliente.
            $normalizado = Str::upper(
                preg_replace('/[^A-Za-z0-9]/', '', $cliente->telefono_principal ?? '') ?? ''
            );

            if ($normalizado === '') {
                throw new RuntimeException(
                    'El cliente '.$cliente->id.' no tiene un teléfono válido para generar su identificador.'
                );
            }

            if (isset($telefonosRegistrados[$normalizado])) {
                throw new RuntimeException(
                    'Los clientes '.$telefonosRegistrados[$normalizado].' y '.$cliente->id
                    .' comparten el mismo teléfono normalizado: '.$normalizado.'.'
                );
            }

            $telefonosRegistrados[$normalizado] = $cliente->id;

            DB::table('clientes')
                ->where('id', $cliente->id)
                ->update(['telefono_normalizado' => $normalizado]);
        }

        Schema::table('clientes', function (Blueprint $table) {
            // La columna no admite valores vacíos y MySQL garantiza su unicidad.
            $table->string('telefono_normalizado', 80)->nullable(false)->change();
            $table->unique('telefono_normalizado', 'clientes_telefono_normalizado_unique');
        });
    }

    /**
     * Revierte únicamente la llave telefónica agregada por esta migración.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_telefono_normalizado_unique');
            $table->dropColumn('telefono_normalizado');
        });
    }
};
