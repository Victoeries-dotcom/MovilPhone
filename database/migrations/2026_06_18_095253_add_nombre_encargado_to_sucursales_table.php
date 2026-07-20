<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->string('nombre_encargado')->nullable()->after('ubicacion');
        });
    }

    public function down()
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropColumn('nombre_encargado');
        });
    }
};