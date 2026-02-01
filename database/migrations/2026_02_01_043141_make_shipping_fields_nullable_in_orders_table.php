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
            $table->string('shipping_address_line1')->nullable()->change();
            $table->string('shipping_city')->nullable()->change();
            $table->string('shipping_postal_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_address_line1')->change();
            $table->string('shipping_city')->change();
            $table->string('shipping_postal_code')->change();
        });
    }
};
