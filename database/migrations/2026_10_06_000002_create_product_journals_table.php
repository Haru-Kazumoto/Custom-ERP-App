<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jurnal pergerakan barang sekaligus tempat pemecahan kode barang.
     * Satu baris = satu batch hasil pecahan (batch_code) dari satu item
     * dokumen, dengan action IN (masuk) / OUT (keluar).
     */
    public function up(): void
    {
        Schema::create('product_journals', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
            $table->string('action')->comment('IN = masuk, OUT = keluar');
            $table->string('batch_code')->comment('kode barang hasil pecahan');
            $table->date('expiry_date')->nullable();
            $table->date('stagnation_limit_date')->nullable();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->foreignId('transaction_items_id')
                ->constrained('transaction_items')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_journals');
    }
};
