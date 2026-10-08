<?php

namespace App\Modules\DeliveryOrder\Repositories;

use App\Enum\TransactionType;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionApprovals;
use App\Models\TransactionDetail;
use App\Models\TransactionItem;
use App\Modules\DeliveryOrder\DTOs\CreateDeliveryOrderDTO;
use App\Modules\DeliveryOrder\Services\DeliveryOrderCalculator;
use Illuminate\Support\Facades\DB;

class DeliveryOrderRepository
{
    /**
     * Menyimpan satu Delivery Order: `transactions`, `transaction_details`,
     * `transaction_items`, dan rincian diskon per tahap promo.
     *
     * Nominal tidak pernah diambil dari DTO — semua dihitung
     * `DeliveryOrderCalculator` dari harga yang diselesaikan server, sehingga
     * angka di `transactions` selalu sama dengan jumlah baris item.
     *
     * @return array{transaction: Transaction, lines: array<int, array{transaction_items_id: int, product_id: int, quantity: int}>}
     */
    public function create(CreateDeliveryOrderDTO $dto): array
    {
        $totals = app(DeliveryOrderCalculator::class)->compute($dto, $dto->customer_segment);

        $transaction = Transaction::create([
            'correlation_id' => (string) rand(000000, 999999),
            'transaction_code' => $dto->document_code,
            'payment_term' => $dto->payment_term,
            'due_date' => $dto->due_date,
            'description' => $dto->description,
            'sub_total' => $totals['sub_total'],
            'total_discount' => $totals['total_discount'],
            'tax_amount' => $totals['tax_amount'],
            'grand_total' => $totals['grand_total'],
            'transaction_type' => TransactionType::DeliveryOrder->value,
            // Gudang pengirim ikut disimpan di transaksi: stok keluar dan
            // riwayat barang dilacak per company, bukan per pelanggan.
            'company_id' => $dto->company_id,
        ]);

        collect($dto->details)->each(function ($detail) use ($transaction) {
            TransactionDetail::create([
                'name' => $detail->name,
                // `type` = kunci semantik (DELIVERY, CUSTOMER, USE_TAX, ...).
                'type' => $detail->type,
                // Kolom `value` bertipe string, jadi bool harus dibungkus
                // string agar tidak terpotong jadi "1".
                'value' => is_bool($detail->value)
                    ? ($detail->value ? 'true' : 'false')
                    : (string) $detail->value,
                'transaction_id' => $transaction->id,
            ]);
        });

        $lines = [];

        foreach ($totals['lines'] as $line) {
            $item = TransactionItem::create([
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'base_price' => $line['base_price'],
                'total_price' => $line['total_price'],
                'promo_product_id' => $line['promo_product_id'],
                // Mode harga per baris; hanya form revisi yang membacanya.
                'use_manual_price' => $line['use_manual_price'] ?? false,
                'transaction_id' => $transaction->id,
            ]);

            $this->insertDiscounts($item->id, $line['stages']);

            $lines[] = [
                'transaction_items_id' => $item->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
            ];
        }

        return ['transaction' => $transaction, 'lines' => $lines];
    }

    /**
     * Menyimpan revisi satu Delivery Order di tempat — nominal dihitung
     * ulang, lalu `transaction_details` dan `transaction_items` diganti
     * sepenuhnya.
     *
     * Dua hal yang tidak boleh berubah saat revisi:
     *
     *  - `transaction_code` tidak pernah ditulis. Nomor DO adalah identitas
     *    dokumen; menggantinya membuat approval lama dan jejak auditnya
     *    menggantung.
     *  - `created_by` dibiarkan. Pembuat adalah penentu rantai approval sales
     *    dan satu-satunya orang yang boleh merevisi.
     *
     * Penghapusan `transaction_items` berantai ke `product_journals`
     * (baris OUT milik dokumen ini) lewat foreign key `cascadeOnDelete`,
     * jadi stok yang pernah dikeluarkan otomatis kembali sebelum
     * `StockAllocator` mengalokasikan ulang untuk isi yang baru — tidak ada
     * langkah penghapusan journal manual, dan tidak mungkin sisa journal
     * yatim dari baris lama.
     *
     * @return array{transaction: Transaction, lines: array<int, array{transaction_items_id: int, product_id: int, quantity: int}>}
     *
     * @throws \RuntimeException kalau harga, promo, atau stok tidak mencukupi
     *                          untuk isi revisi.
     */
    public function revise(int $transaction_id, CreateDeliveryOrderDTO $dto): array
    {
        $totals = app(DeliveryOrderCalculator::class)->compute($dto, $dto->customer_segment);

        $transaction = Transaction::query()
            ->where('id', $transaction_id)
            ->lockForUpdate()
            ->firstOrFail();

        $transaction->update([
            'payment_term' => $dto->payment_term,
            'due_date' => $dto->due_date,
            'description' => $dto->description,
            // Gudang bisa berubah saat revisi; stok keluar berikutnya harus
            // dihitung dari gudang yang baru.
            'company_id' => $dto->company_id,
            'sub_total' => $totals['sub_total'],
            'total_discount' => $totals['total_discount'],
            'tax_amount' => $totals['tax_amount'],
            'grand_total' => $totals['grand_total'],
        ]);

        DB::table('transaction_details')->where('transaction_id', $transaction_id)->delete();

        collect($dto->details)->each(function ($detail) use ($transaction_id) {
            DB::table('transaction_details')->insert([
                'transaction_id' => $transaction_id,
                'name' => $detail->name,
                'type' => $detail->type,
                // Kolom `value` bertipe string; bool harus dibungkus supaya
                // tidak terpotong jadi "1".
                'value' => is_bool($detail->value)
                    ? ($detail->value ? 'true' : 'false')
                    : (string) $detail->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::table('transaction_items')->where('transaction_id', $transaction_id)->delete();

        $lines = [];

        foreach ($totals['lines'] as $line) {
            $item = TransactionItem::create([
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'base_price' => $line['base_price'],
                'total_price' => $line['total_price'],
                'promo_product_id' => $line['promo_product_id'],
                'use_manual_price' => $line['use_manual_price'] ?? false,
                'transaction_id' => $transaction_id,
            ]);

            $this->insertDiscounts($item->id, $line['stages']);

            $lines[] = [
                'transaction_items_id' => $item->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
            ];
        }

        return ['transaction' => $transaction, 'lines' => $lines];
    }

    /**
     * Rincian diskon per tahap promo untuk satu baris item.
     *
     * `amount_after_discount` disimpan apa adanya dari kalkulator (harga
     * setelah tahap ke-N), bukan nominal diskonnya: pembaca lama membaca
     * kolom itu sebagai harga hasil tahap tersebut (DELIVERY_ORDER.md §18).
     * Tanpa baris ini, riwayat diskon per tahap hilang begitu dokumen
     * dibuat — `transaction_items` hanya menyimpan harga akhir.
     *
     * @param  array<int, array<string, mixed>>  $stages
     */
    private function insertDiscounts(int $transactionItemId, array $stages): void
    {
        if ($stages === []) {
            return;
        }

        $now = now();

        foreach ($stages as $stage) {
            DB::table('transacton_item_discounts')->insert([
                'transaction_item_id' => $transactionItemId,
                'sequence' => $stage['sequence'],
                'discount_type' => $stage['discount_type'],
                'discount_value' => $stage['discount_value'],
                'source' => $stage['source'],
                'amount_before_discount' => $stage['amount_before_discount'],
                'amount_after_discount' => $stage['amount_after_discount'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Siapkan alur approval untuk satu Delivery Order.
     *
     * Urutan langkah (semuanya PENDING sejak awal, `order` nomor ulang 1..n):
     *
     *  1. Rantai sales — hanya kalau pembuat berada di role `sales`:
     *     berjalan naik mengikuti `sub_roles.parent_id` (salesman →
     *     sales supervisor → sales manager). Hierarki dibaca dari data,
     *     bukan kode, jadi perubahan struktur organisasi cukup lewat tabel
     *     `sub_roles`. Pembuat tingkat tertinggi (parent null) tidak
     *     menghasilkan langkah — rantai "lompat" ke role berikutnya.
     *  2. Business Development — hanya kalau `$needs_bd_approval` (ada baris
     *     dengan harga manual / diskon manual di form).
     *  3. AR Controller.
     *  4. Finance.
     *  5. Marketing, dengan `sub_role_id` mengikuti company dokumen
     *     (DNP → marketing_dnp, DKU → marketing_dku); kalau tidak ada
     *     sub-role yang cocok, langkah tetap dibuat tanpa sub-role.
     *
     * Role yang tidak ada di tabel `roles` dilewati, bukan di-index tanpa
     * mengecek dulu: `Role::pluck('id','code')` tidak punya key untuk kode
     * yang hilang, dan akses langsung ke sana membatalkan pembuatan dokumen.
     * Penomoran `order` dilakukan SETELAH filter supaya tidak ada nomor
     * bolong saat sebuah role dilewati.
     *
     * @throws \RuntimeException kalau pembuat tidak ditemukan.
     */
    public function generateApprovals(int $delivery_order_id, int $creator_id, bool $needs_bd_approval): void
    {
        $roles = Role::whereIn('code', [
            'sales',
            'business_development',
            'ar_controller',
            'finance',
            'marketing',
        ])->pluck('id', 'code');

        $steps = $this->salesChainSteps($creator_id, $roles);

        if ($needs_bd_approval) {
            $steps[] = ['role_code' => 'business_development', 'sub_role_id' => null];
        }

        $steps[] = ['role_code' => 'ar_controller', 'sub_role_id' => null];
        $steps[] = ['role_code' => 'finance', 'sub_role_id' => null];
        $steps[] = [
            'role_code' => 'marketing',
            'sub_role_id' => $this->marketingSubRoleId($delivery_order_id, $roles),
        ];

        $data = collect($steps)
            ->filter(fn ($step) => isset($roles[$step['role_code']]))
            ->values()
            ->map(fn (array $step, int $index) => [
                'order' => $index + 1,
                'transaction_id' => $delivery_order_id,
                'status' => 'PENDING',
                'role_id' => $roles[$step['role_code']],
                'sub_role_id' => $step['sub_role_id'],
                'proceed_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($data === []) {
            return;
        }

        TransactionApprovals::insert($data);
    }

    /**
     * Langkah approval sales: daftar superior (sub-role induk) dari sub-role
     * pembuat sampai puncak hierarki, berurutan dari yang terdekat.
     *
     * Pembuat di luar role `sales` atau tanpa sub-role tidak menghasilkan
     * langkah apa pun — rantai langsung mulai dari BD/AR/Finance/Marketing.
     *
     * @param  array<string, int>  $roles
     * @return array<int, array{role_code: string, sub_role_id: int}>
     */
    private function salesChainSteps(int $creator_id, $roles): array
    {
        if (! isset($roles['sales'])) {
            return [];
        }

        $creator = DB::table('users')
            ->where('id', $creator_id)
            ->first(['role_id', 'sub_role_id']);

        if ($creator === null
            || (int) $creator->role_id !== (int) $roles['sales']
            || $creator->sub_role_id === null
        ) {
            return [];
        }

        $parents = DB::table('sub_roles')->pluck('parent_id', 'id');

        $steps = [];
        $visited = [(int) $creator->sub_role_id];
        $current = (int) $creator->sub_role_id;

        while (($parent_id = $parents->get($current)) !== null) {
            $parent_id = (int) $parent_id;

            // Cycle guard: data hierarki bisa diedit manual lewat DB, dan
            // rantai yang berputar akan membuat langkah approval tanpa akhir.
            if (in_array($parent_id, $visited, true)) {
                break;
            }

            $steps[] = ['role_code' => 'sales', 'sub_role_id' => $parent_id];
            $visited[] = $parent_id;
            $current = $parent_id;
        }

        return $steps;
    }

    /**
     * Sub-role marketing untuk satu dokumen, mengikuti company DO:
     * code `DNP` → `marketing_dnp`, `DKU` → `marketing_dku`. Null kalau
     * dokumen tanpa company atau sub-role tujuan tidak ada — langkah
     * marketing tetap dibuat tanpa sub-role supaya rantai tidak putus.
     *
     * @param  array<string, int>  $roles
     */
    private function marketingSubRoleId(int $delivery_order_id, $roles): ?int
    {
        if (! isset($roles['marketing'])) {
            return null;
        }

        $company_code = DB::table('transactions as tx')
            ->leftJoin('companies as c', 'c.id', '=', 'tx.company_id')
            ->where('tx.id', $delivery_order_id)
            ->value('c.code');

        if (blank($company_code)) {
            return null;
        }

        return (int) DB::table('sub_roles')
            ->where('role_id', $roles['marketing'])
            ->where('code', 'marketing_'.strtolower((string) $company_code))
            ->value('id') ?: null;
    }
}
