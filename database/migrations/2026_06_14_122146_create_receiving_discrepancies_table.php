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
        Schema::create('receiving_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_items_id')
                ->constrained('receiving_items')
                ->cascadeOnDelete();
            $table->string('type')->comment('e.g., damaged, lost, gradually, etc.');
            $table->integer('remaining_qty');
            $table->string('status');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_discrepancies');
    }
};
