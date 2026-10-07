<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data demo untuk pengujian Delivery Order:
 *
 *  - Stok awal (journal IN, 2 batch per produk) untuk beberapa produk yang
 *    sudah punya baris `product_prices`. Tanpa stok, form DO tidak menampilkan
 *    barang apa pun karena picker hanya menerima produk dengan stok > 0.
 *    Baris disimpan mengikuti bentuk legacy: journal terikat ke satu
 *    transaksi + item (kolom `transaction_id` / `transaction_items_id` NOT
 *    NULL), jadi dibuat satu transaksi SSO demo sebagai wadahnya.
 *
 *  - Dua program promo: satu cascading (persentase bertingkat + manual VALUE)
 *    dan satu FLUSH_OUT untuk menguji pembebasan range quantity.
 *
 * Keduanya bersifat idempoten: kalau datanya sudah ada, migration dilewati.
 */
return new class extends Migration
{
    private const DEMO_TRANSACTION_CODE = 'SO-DEMO-STOCK';

    private const DEMO_STOCK = [
        // product_id => [batches]
        22 => [
            ['batch_code' => 'TPG-SEDANG-A1', 'expiry_date' => '2027-01-31', 'stagnation_limit_date' => '2026-12-31', 'quantity' => 60],
            ['batch_code' => 'TPG-SEDANG-A2', 'expiry_date' => '2027-07-31', 'stagnation_limit_date' => '2027-06-30', 'quantity' => 40],
        ],
        23 => [
            ['batch_code' => 'TPK-A1', 'expiry_date' => '2026-12-15', 'stagnation_limit_date' => '2026-11-30', 'quantity' => 120],
        ],
        24 => [
            ['batch_code' => 'TPB-A1', 'expiry_date' => '2027-05-31', 'stagnation_limit_date' => '2027-04-30', 'quantity' => 90],
        ],
        // Batch tanpa expired date: FEFO harus memakainya paling akhir.
        25 => [
            ['batch_code' => 'GULA-B1', 'expiry_date' => null, 'stagnation_limit_date' => null, 'quantity' => 200],
            ['batch_code' => 'GULA-B2', 'expiry_date' => '2027-03-31', 'stagnation_limit_date' => '2027-02-28', 'quantity' => 75],
        ],
        26 => [
            ['batch_code' => 'MYK-A1', 'expiry_date' => '2027-08-31', 'stagnation_limit_date' => '2027-07-31', 'quantity' => 55],
        ],
        27 => [
            ['batch_code' => 'GRT-A1', 'expiry_date' => '2027-02-28', 'stagnation_limit_date' => '2027-01-31', 'quantity' => 30],
        ],
    ];

    public function up(): void
    {
        $this->seedStock();
        $this->seedPromos();
    }

    private function seedStock(): void
    {
        $productIds = array_keys(self::DEMO_STOCK);

        // Sudah ada stok untuk produk demo → seeding pernah jalan.
        $existing = DB::table('product_journals')
            ->whereIn('product_id', $productIds)
            ->where('company_id', 1)
            ->exists();

        if ($existing) {
            return;
        }

        DB::transaction(function () use ($productIds) {
            $now = now();
            $companyId = (int) DB::table('companies')->where('code', 'DNP')->value('id');

            $transactionId = DB::table('transactions')->insertGetId([
                'correlation_id' => (string) rand(100000, 999999),
                'transaction_code' => self::DEMO_TRANSACTION_CODE,
                'created_by' => 1,
                'payment_term' => 0,
                'aging_days' => 0,
                'transaction_type' => 'SSO',
                'company_id' => $companyId,
                'description' => 'Stok awal demo (seed migration Delivery Order)',
                'sub_total' => 0,
                'total_discount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (self::DEMO_STOCK as $productId => $batches) {
                $total = array_sum(array_column($batches, 'quantity'));

                $itemId = DB::table('transaction_items')->insertGetId([
                    'product_id' => $productId,
                    'quantity' => $total,
                    'base_price' => 0,
                    'total_price' => 0,
                    'transaction_id' => $transactionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($batches as $batch) {
                    DB::table('product_journals')->insert([
                        'quantity' => $batch['quantity'],
                        'action' => 'IN',
                        'batch_code' => $batch['batch_code'],
                        'expiry_date' => $batch['expiry_date'],
                        'stagnation_limit_date' => $batch['stagnation_limit_date'],
                        'product_id' => $productId,
                        'company_id' => $companyId,
                        'transaction_id' => $transactionId,
                        'transaction_items_id' => $itemId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    private function seedPromos(): void
    {
        if (DB::table('promo_products')->exists()) {
            return;
        }

        DB::transaction(function () {
            $now = now();

            // Cascading: A →10%→ A1 →5%→ A2 →MIN(500, A2)→ A3.
            $weekend = DB::table('promo_products')->insertGetId([
                'name' => 'Promo Weekend Diskon',
                'description' => 'Diskon bertingkat untuk produk terpilih',
                'code' => 'P-WKD',
                'division' => 'MARKETING',
                'type' => 'NORMAL',
                'start_date' => $now->copy()->subDays(7),
                'end_date' => $now->copy()->addDays(30),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('assigned_products_promo')->insert([
                'assigned_customer_promo_id' => null,
                'product_id' => 21,
                'promo_product_id' => $weekend,
                'min_qty' => 5,
                'max_qty' => 100,
                'base_quota' => 50,
                'percentage_1' => 10,
                'percentage_2' => 5,
                'percentage_3' => null,
                'manual_type' => 'VALUE',
                'manual_percentage' => null,
                'manual_value' => 500,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // FLUSH_OUT: quantity di luar range min/max tetap diperbolehkan.
            $flush = DB::table('promo_products')->insertGetId([
                'name' => 'Promo Flush Out Gudang',
                'description' => 'Program buang stok, tanpa batas quantity',
                'code' => 'P-FLS',
                'division' => 'MARKETING',
                'type' => 'FLUSH_OUT',
                'start_date' => $now->copy()->subDays(7),
                'end_date' => $now->copy()->addDays(30),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('assigned_products_promo')->insert([
                'assigned_customer_promo_id' => null,
                'product_id' => 22,
                'promo_product_id' => $flush,
                'min_qty' => 10,
                'max_qty' => 20,
                'base_quota' => null,
                'percentage_1' => 7,
                'percentage_2' => null,
                'percentage_3' => null,
                'manual_type' => null,
                'manual_percentage' => null,
                'manual_value' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $transactionId = DB::table('transactions')
                ->where('transaction_code', self::DEMO_TRANSACTION_CODE)
                ->value('id');

            if ($transactionId) {
                $itemIds = DB::table('transaction_items')
                    ->where('transaction_id', $transactionId)
                    ->pluck('id');

                DB::table('product_journals')
                    ->where('transaction_id', $transactionId)
                    ->delete();
                DB::table('transacton_item_discounts')
                    ->whereIn('transaction_item_id', $itemIds)
                    ->delete();
                DB::table('transaction_items')
                    ->where('transaction_id', $transactionId)
                    ->delete();
                DB::table('transactions')
                    ->where('id', $transactionId)
                    ->delete();
            }

            DB::table('assigned_products_promo')
                ->whereIn('promo_product_id', DB::table('promo_products')->select('id'))
                ->delete();
            DB::table('promo_products')->delete();
        });
    }
};
