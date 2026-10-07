<?php

namespace App\Modules\DeliveryOrder\Services;

use App\Models\ProductJournal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Alokasi stok FEFO (First-Expired-First-Out) untuk keluaran Delivery Order.
 *
 * Batch diambil dari `product_journals` milik satu company, diurutkan dari
 * expired date paling dekat, lalu dipakai menutup quantity baris sampai
 * habis. Baris OUT dibuat per batch supaya jejak batch yang dikirim ke
 * pelanggan tetap bisa ditelusuri.
 *
 * Validasi stok berlaku untuk SEMUA jenis pengiriman (walaupun form DEPO
 * saja yang menampilkan stok): dokumen tidak boleh dibuat kalau stok
 * gudang tidak mencukupi.
 */
class StockAllocator
{
    /**
     * @param  array<int, array{transaction_items_id: int, product_id: int, quantity: int}>  $lines
     *
     * @throws RuntimeException kalau stok total salah satu produk kurang.
     */
    public function allocate(int $transactionId, int $companyId, array $lines): void
    {
        $names = DB::table('products')
            ->whereIn('id', collect($lines)->pluck('product_id')->unique()->all())
            ->pluck('name', 'id');

        foreach ($lines as $line) {
            $product = $names[$line['product_id']] ?? '(tidak dikenal)';
            $batches = $this->batches($line['product_id'], $companyId);
            $available = (int) collect($batches)->sum('stock');

            if ($available < $line['quantity']) {
                throw new RuntimeException(
                    "Stok \"{$product}\" tidak mencukupi: tersedia {$available}, diminta {$line['quantity']}."
                );
            }

            $remaining = $line['quantity'];

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, (int) $batch->stock);

                ProductJournal::create([
                    'quantity' => $take,
                    'action' => ProductJournal::ACTION_OUT,
                    'batch_code' => $batch->batch_code,
                    'expiry_date' => $batch->expiry_date,
                    'stagnation_limit_date' => $batch->stagnation_limit_date,
                    'product_id' => $line['product_id'],
                    'company_id' => $companyId,
                    'transaction_id' => $transactionId,
                    'transaction_items_id' => $line['transaction_items_id'],
                ]);

                $remaining -= $take;
            }
        }
    }

    /**
     * Batch dengan stok positif untuk satu produk di satu company, FEFO.
     *
     * `lockForUpdate()` mengunci baris journal yang terbaca supaya dua DO
     * yang diproses bersamaan tidak menghitung ulang batch yang sama dari
     * snapshot lama.
     *
     * @return array<int, object>
     */
    private function batches(int $productId, int $companyId): array
    {
        return DB::table('product_journals')
            ->where('product_id', $productId)
            ->where('company_id', $companyId)
            ->groupBy('batch_code', 'expiry_date', 'stagnation_limit_date')
            ->selectRaw("
                batch_code,
                expiry_date,
                stagnation_limit_date,
                SUM(CASE WHEN action = 'IN' THEN quantity ELSE -quantity END) as stock
            ")
            ->havingRaw("SUM(CASE WHEN action = 'IN' THEN quantity ELSE -quantity END) > 0")
            // FEFO: expired date terdekat duluan; batch tanpa expired date
            // diurutkan paling akhir karena tidak tahu mana yang lebih dulu.
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderBy('batch_code')
            ->lockForUpdate()
            ->get()
            ->all();
    }
}
