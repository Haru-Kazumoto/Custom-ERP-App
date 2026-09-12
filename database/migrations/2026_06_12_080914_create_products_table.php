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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('unit');
            $table->string('category');

            $table->foreignId('product_type_id')->nullable()->constrained('product_type')->nullOnDelete();
            $table->foreignId('product_sub_type_id')->nullable()->constrained('product_sub_type')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendor')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
