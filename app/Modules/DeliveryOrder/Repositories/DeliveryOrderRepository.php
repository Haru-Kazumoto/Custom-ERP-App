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
     * Siapkan alur approval untuk DO yang baru dibuat — rantai yang sama
     * dengan PO (Finance → Marketing).
     *
     * Role yang tidak ada di tabel `roles` dilewati, bukan di-index tanpa
     * mengecek dulu: `Role::pluck('id','code')` tidak punya key untuk kode
     * yang hilang, dan akses langsung ke sana membatalkan pembuatan dokumen.
     */
    public function generateApprovals(int $delivery_order_id): void
    {
        $roles = Role::whereIn('code', ['finance', 'marketing'])->pluck('id', 'code');
        $flow_definitions = [
            ['order' => 1, 'role_code' => 'finance'],
            ['order' => 2, 'role_code' => 'marketing'],
        ];

        $data = collect($flow_definitions)
            ->filter(fn ($step) => isset($roles[$step['role_code']]))
            ->map(fn ($step) => [
                'order' => $step['order'],
                'transaction_id' => $delivery_order_id,
                'status' => 'PENDING',
                'role_id' => $roles[$step['role_code']],
                'sub_role_id' => null,
                'proceed_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($data === []) {
            return;
        }

        TransactionApprovals::insert($data);
    }
}
