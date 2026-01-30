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
        Schema::table('orders', function (Blueprint $table) {
            // Ajout du total pour les calculs financiers (somme des ventes)
            if (!Schema::hasColumn('orders', 'total')) {
                $table->decimal('total', 10, 2)->default(0)->after('user_id');
            }

            // Ajout du statut de paiement indispensable pour le filtre 'paid' du dashboard
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('total');
            }

            // Ajout du Soft Delete (le fameux deleted_at qui causait l'erreur 500)
            if (!Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['total', 'payment_status', 'deleted_at']);
        });
    }
};