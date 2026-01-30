<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // On remplace 'addresses' par 'orders'
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Détails du paiement et montant
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('en_attente'); // en_attente, preparation, livraison, livree
            $table->string('payment_status')->default('non_paye');
            $table->string('payment_method')->nullable(); // airtel_money, mobile_cash
            
            // Snapshot de l'adresse de livraison (US-T2)
            $table->string('shipping_address_line1');
            $table->string('shipping_city');
            $table->string('shipping_postal_code');
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};