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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();

            $table->decimal('base_price', 18, 2)->default(0);
            $table->decimal('retail_price', 18, 2)->default(0);
            $table->decimal('wholesale_price', 18, 2)->default(0);
            $table->decimal('end_user_price', 18, 2)->default(0);
            $table->decimal('all_segment_price', 18, 2)->default(0);

            $table->decimal('base_percentage', 5, 2)->default(0);

            $table->decimal('transportation_cost', 18, 2)->default(0);
            $table->decimal('overhead_cost', 18, 2)->default(0);
            $table->decimal('marketing_budget_cost', 18, 2)->default(0);
            $table->decimal('bad_debt_cost', 18, 2)->default(0);
            $table->decimal('saving_cost', 18, 2)->default(0);

            $table->decimal('rounded_price', 18, 2)->default(0);

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('shipping_id')
                ->nullable()
                ->constrained('shippings')
                ->nullOnDelete();

            $table->foreignId('sub_shipping_id')
                ->nullable()
                ->constrained('sub_shippings')
                ->nullOnDelete();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
