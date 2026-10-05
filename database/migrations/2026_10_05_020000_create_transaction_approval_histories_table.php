<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arsip keputusan approval yang sudah pernah diambil.
     *
     * Saat PO direvisi, seluruh baris `transaction_approvals` dihapus lalu
     * digenerate ulang dari Finance supaya approval berjalan dari nol. Tanpa
     * arsip, keputusan lama (siapa yang meminta revisi, kapan, dan alasannya)
     * hilang permanen — padahal itu justru informasi yang paling dibutuhkan
     * saat menelaah kenapa sebuah PO direvisi.
     *
     * Tabel ini hanya ditulis, tidak pernah dibaca untuk menentukan alur
     * approval. Sumber kebenaran alur tetap `transaction_approvals`.
     */
    public function up(): void
    {
        Schema::create('transaction_approval_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();

            // Sengaja kolom biasa, bukan foreignId: keputusan lama harus tetap
            // terbaca walaupun role atau user.decider-nya nanti dihapus, jadi
            // arsip tidak ikut terhapus cascade seperti tabel aslinya.
            $table->unsignedInteger('order');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('sub_role_id')->nullable();

            $table->string('status');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('proceed_by')->nullable();
            $table->dateTime('proceed_at')->nullable();

            // Id baris `transaction_approvals` asal, supaya jejaknya masih bisa
            // ditelusuri selama datanya belum dihapus.
            $table->unsignedBigInteger('source_approval_id')->nullable();

            $table->timestamps();

            $table->index(['transaction_id', 'order']);
            $table->index(['transaction_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_approval_histories');
    }
};
