<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique(); // Ej: 'hell-riders'
            $table->text('desc');
            $table->string('version_actual'); // Ej: '1.2.0'
            $table->string('url_descarga'); // Ruta al .exe o .zip
            $table->string('imagen'); // Ruta al banner en assets
            
            // Control de Estado del Juego
            $table->enum('status', ['activo', 'mantenimiento', 'bug_critico'])->default('activo');
            $table->text('mensaje_mantenimiento')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
