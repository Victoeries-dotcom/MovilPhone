<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NULL permite varios clientes sin contacto y conserva la unicidad de los teléfonos capturados.
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('telefono_principal')->nullable()->change();
            $table->string('telefono_normalizado', 80)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Restaura el contrato anterior cuando no existen clientes con teléfono vacío.
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('telefono_principal')->nullable(false)->change();
            $table->string('telefono_normalizado', 80)->nullable(false)->change();
        });
    }
};
