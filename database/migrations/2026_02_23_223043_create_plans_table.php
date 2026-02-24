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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ejemplo: "Ludomancer Pro - 30 Días"
            $table->decimal('price', 10, 2); // Ejemplo: 55000.00
            $table->string('currency', 3)->default('COP'); 
            $table->integer('days_of_power'); // Cuántos días otorga este plan (ej: 30)
            $table->boolean('is_active')->default(true); // Para poder apagar planes viejos sin borrarlos
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
