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
            $table->string('order_number')->unique()->after('id');
            $table->foreignId('shipping_address_id')->constrained('addresses')->onDelete('cascade')->after('user_id');
            $table->foreignId('billing_address_id')->constrained('addresses')->onDelete('cascade')->after('shipping_address_id');
            $table->decimal('subtotal', 10, 2)->after('total_amount');
            $table->decimal('tax', 10, 2)->default(0)->after('subtotal');
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('tax');
            $table->decimal('discount', 10, 2)->default(0)->after('shipping_cost');
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null')->after('discount');
            $table->text('notes')->nullable()->after('coupon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['order_number', 'shipping_address_id', 'billing_address_id', 'subtotal', 'tax', 'shipping_cost', 'discount', 'coupon_id', 'notes']);
        });
    }
};
