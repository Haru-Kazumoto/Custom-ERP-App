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
        Schema::create('assigned_products_promo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_customer_promo_id')
                ->nullable()
                ->constrained('assigned_customer_promo')
                ->nullOnDelete();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
            $table->foreignId('promo_product_id')
                ->nullable()
                ->constrained('promo_products')
                ->nullOnDelete();
            $table->integer('min_qty');
            $table->integer('max_qty');
            $table->integer('base_quota')->nullable();
            $table->decimal('percentage_1', 5, 2)->nullable();
            $table->decimal('percentage_2', 5, 2)->nullable();
            $table->decimal('percentage_3', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assigned_products_promo');
    }
};
