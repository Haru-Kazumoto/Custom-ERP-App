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
        Schema::create('promo_claim', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number');
            $table->string('month');
            $table->string('distributor_name');
            $table->string('area');
            $table->string('program');
            $table->decimal('sub_total', 15, 2);
            $table->decimal('grand_total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_claim');
    }
};
