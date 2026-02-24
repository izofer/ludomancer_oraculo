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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Detalles Financieros
            $table->decimal('amount', 10, 2); // Ejemplo: 15.00
            $table->string('currency', 3)->default('USD'); // USD, COP, EUR
            $table->decimal('amount_usd', 10, 2); // Monto convertido a USD para reportes globales
            $table->string('payment_method'); // stripe, paypal, wompi, crypto
            $table->string('gateway_transaction_id')->unique(); // El ID de recibo de la pasarela
            
            // Estado y Producto
            $table->string('status'); // pending, completed, failed, refunded
            $table->string('plan_name'); // Ejemplo: "Licencia 30 Días"
            $table->integer('days_added'); // Cuántos días se le sumaron al usuario por este pago
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
