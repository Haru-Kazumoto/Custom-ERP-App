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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_code')->unique();
            $table->string('correlation_id');

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->integer('payment_term')->default(0);

            $table->date('due_date')->nullable();

            $table->integer('aging_days')
                ->default(0)
                ->comment('Age in days');

            $table->string('transaction_type')->comment("Type of document, e.g., 'PURCHASE_ORDER', 'SUB_SALES_ORDER', 'DELIVERY_ORDER', etc.");

            $table->string('file_attachment')->nullable()->comment('Path to the additional attached file, if any');

            $table->text('description')->nullable();

            $table->decimal('sub_total', 18, 2)->default(0);
            $table->decimal('total_discount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
