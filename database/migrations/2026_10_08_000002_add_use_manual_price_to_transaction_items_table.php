<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mode harga per baris barang.
 *
 * Form DO memilih harga per barang (harga daftar vs harga manual), tapi
 * pilihan itu hanya hidup di request saat dokumen dibuat - tidak pernah
 * disimpan. Selama hanya ada tombol "buat", tidak ada yang membaca ulang
 * dokumen, jadi ketiadaannya tidak terasa.
 *
 * Revisi mengubah itu: form harus menampilkan kembali setiap baris dalam
 * mode yang sama seperti saat dibuat. Tanpa kolom ini satu-satunya jalan
 * adalah menebak dari selisih harga tersimpan vs harga daftar saat ini,
 * dan tebakan itu salah setiap kali `product_prices` berubah setelah
 * dokumen disetujui - angka yang sudah disetujui approver bisa berganti
 * diam-diam hanya karena form dibuka ulang.
 *
 * Baris lama (termasuk semua PO) memakai default `false`: PO tidak punya
 * konsep harga daftar/harga manual, dan DO yang sudah ada sebelum migrasi
 * ini memakai harga daftar di form revisi. Belum ada backfill karena
 * menentukan mode asli butuh harga daftar pada saat pembuatan, yang sudah
 * tidak tersimpan di mana pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->boolean('use_manual_price')
                ->default(false)
                ->after('promo_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn('use_manual_price');
        });
    }
};
