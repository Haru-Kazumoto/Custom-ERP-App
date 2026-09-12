<?php

namespace App\Modules\PurchaseOrder\Repositories;

use App\Enum\TransactionType;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionApprovals;
use App\Models\TransactionDetail;
use App\Models\TransactionItem;
use App\Modules\PurchaseOrder\DTOs\CreatePurchaseOrderDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderRepository
{
    /**
     * This action will creating to 3 table in a row, such as transactions, transaction_items, transaction_details
     */
    public function create(CreatePurchaseOrderDTO $dto): Transaction
    {
        $transaction = Transaction::create([
            'correlation_id' => (string) rand(000000, 999999),
            'transaction_code' => $dto->document_code,
            'payment_term' => $dto->term_of_payment,
            'due_date' => $dto->due_date,
            'description' => $dto->description,
            'sub_total' => $dto->sub_total,
            'tax_amount' => $dto->tax_amount,
            'grand_total' => $dto->total,
            'transaction_type' => TransactionType::PurchaseOrder->value
        ]);

        // Inserting transaction details
        collect($dto->details)->each(function ($detail) use ($transaction) {
            TransactionDetail::create([
                'name' => $detail->name,
                'type' => $detail->data_type,
                'value' => $detail->value,
                'transaction_id' => $transaction->id
            ]);
        });

        // Inserting transaction items
        collect($dto->items)->each(function ($item) use ($transaction) {
            TransactionItem::create([
                'product_id' => $item->product_id,
                // 'unit' => $item->unit,
                'quantity' => (int) $item->quantity,
                'base_price' => $item->amount,
                'trade_promo_id' => $item->trade_promo_id,
                'total_price' => $item->total_price,
                'transaction_id' => $transaction->id
            ]);
        });

        return $transaction;
    }

    public function generateApprovals(int $purchase_order_id)
    {
        $role_codes = ['finance', 'marketing'];
        $roles = Role::whereIn('code', $role_codes)->pluck('id', 'code');
        $flow_definitions = [
            ['order' => 1, 'role_code' => 'finance', 'sub_role_code' => null],
            ['order' => 2, 'role_code' => 'marketing', 'sub_role_code' => null]
        ];

        $data = collect($flow_definitions)->map(function($step) use ($roles, $purchase_order_id) {
            return [
                'order'             => $step['order'],
                'transaction_id'    => $purchase_order_id,
                'status'            => 'PENDING',
                'role_id'           => $roles[$step['role_code']],
                'sub_role_id'       => null,
                'proceed_by'        => null,
                'created_at'        => now(),
                'updated_at'        => now()
            ];
        })
        ->toArray();

        TransactionApprovals::insert($data);
    }

    public function processApproval(int $purchase_order_id, string $status, ?string $description)
    {
        return true;
    }

}
