<?php

namespace App\Modules\PurchaseOrder\Repositories;

use App\Enum\TransactionType;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionApprovals;
use App\Models\TransactionDetail;
use App\Models\TransactionItem;
use App\Modules\PurchaseOrder\DTOs\CreatePurchaseOrderDTO;
use App\Modules\PurchaseOrder\Services\PurchaseOrderCalculator;

class PurchaseOrderRepository
{
    /**
     * Menyimpan satu Purchase Order: `transactions`, `transaction_details`, dan
     * `transaction_items`.
     *
     * Nominal tidak pernah diambil dari DTO. Semua dihitung
     * `PurchaseOrderCalculator` dari harga satuan bruto per baris supaya
     * `transactions.sub_total` dijamin sama dengan penjumlahan
     * `transaction_items.total_price`.
     */
    public function create(CreatePurchaseOrderDTO $dto): Transaction
    {
        $totals = app(PurchaseOrderCalculator::class)->compute($dto);

        $transaction = Transaction::create([
            'correlation_id' => (string) rand(000000, 999999),
            'transaction_code' => $dto->document_code,
            'payment_term' => $dto->term_of_payment,
            'due_date' => $dto->due_date,
            'description' => $dto->description,
            'sub_total' => $totals['sub_total'],
            'total_discount' => $totals['total_discount'],
            'tax_amount' => $totals['tax_amount'],
            'grand_total' => $totals['grand_total'],
            'transaction_type' => TransactionType::PurchaseOrder->value,
        ]);

        // Inserting transaction details
        collect($dto->details)->each(function ($detail) use ($transaction) {
            TransactionDetail::create([
                'name' => $detail->name,
                // `type` diisi kunci semantik (SUPPLIER, PO_DATE, USE_TAX, ...),
                // bukan `data_type`. Sebelumnya kedua kolom tertukar sehingga
                // tersimpan "string"/"float" dan kunci semantiknya hilang.
                'type' => $detail->type,
                // Kolom `transaction_details.value` bertipe string, jadi bool dan
                // angka harus dibungkus string agar tidak terpotong jadi "1".
                'value' => is_bool($detail->value)
                    ? ($detail->value ? 'true' : 'false')
                    : (string) $detail->value,
                'transaction_id' => $transaction->id,
            ]);
        });

        // Inserting transaction items
        collect($totals['lines'])->each(function ($line) use ($transaction) {
            TransactionItem::create([
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'base_price' => $line['base_price'],
                'trade_promo_id' => $line['trade_promo_id'],
                'total_price' => $line['total_price'],
                'transaction_id' => $transaction->id,
            ]);
        });

        return $transaction;
    }

    /**
     * Siapkan alur approval untuk PO yang baru dibuat.
     *
     * Role yang tidak ada di tabel `roles` dilewati, bukan di-index tanpa mengecek
     * dulu — `Role::pluck('id','code')` tidak punya key untuk kode yang hilang, dan
     * akses langsung ke sana menghasilkan "Undefined array key" yang membatalkan
     * seluruh pembuatan PO.
     */
    public function generateApprovals(int $purchase_order_id)
    {
        $role_codes = ['finance', 'marketing'];
        $roles = Role::whereIn('code', $role_codes)->pluck('id', 'code');
        $flow_definitions = [
            ['order' => 1, 'role_code' => 'finance'],
            ['order' => 2, 'role_code' => 'marketing'],
        ];

        $data = collect($flow_definitions)
            ->filter(fn ($step) => isset($roles[$step['role_code']]))
            ->map(fn ($step) => [
                'order' => $step['order'],
                'transaction_id' => $purchase_order_id,
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
