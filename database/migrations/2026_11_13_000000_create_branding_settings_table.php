<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crea el almacenamiento de la única imagen global de marca. */
    public function up(): void
    {
        Schema::create('branding_settings', function (Blueprint $table): void {
            // El identificador fijo 1 representa la marca compartida de toda la aplicación.
            $table->unsignedTinyInteger('id')->primary();
            $table->string('logo_mime_type', 40)->nullable();
            $table->longText('logo_data')->nullable();
            $table->timestamps();
        });
    }

    /** Elimina la tabla si se revierte esta migración. */
    public function down(): void
    {
        Schema::dropIfExists('branding_settings');
    }
};
