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
        Schema::create('transacton_item_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_item_id')
                ->constrained('transaction_items')
                ->cascadeOnDelete();
            $table->integer('sequence')->default(1);
            $table->string('discount_type');
            $table->decimal('discount_value', 15, 2);
            $table->string('source')->nullable()->comment("Source of the discount, e.g., 'MANUAL', 'SYSTEM', etc.");
            $table->decimal('amount_before_discount', 15, 2)->comment('Final price after applying this discount');
            $table->decimal('amount_after_discount', 15, 2)->comment('Final price after applying this discount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacton_item_discounts');
    }
};
