<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name'); // Ex: "Rouge - L"
            $table->json('attributes'); // {color: "rouge", size: "L"}
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->text('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};