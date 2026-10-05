<?php

namespace App\Modules\PurchaseOrder\Queries;

use Illuminate\Support\Facades\DB;

class FindPurchaseOrderQuery
{
    public function __construct(private GetPurchaseOrderItemsQuery $get_order_items) {}

    public function execute(int $id, bool $with_approvals = false)
    {
        $transaction = DB::table('transactions as tx')
            ->select(
                'tx.id',
                'tx.transaction_code',
                'tx.transaction_type',
                'tx.correlation_id',
                'tx.due_date',
                'tx.payment_term',
                'tx.created_by',
                'tx.updated_at',
                'tx.file_attachment',
                'tx.description',
                'tx.sub_total',
                'tx.total_discount',
                'tx.tax_amount',
                'tx.grand_total',
                DB::raw("
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'name', td.name,
                            'value', td.value,
                            'type', td.type
                        )
                    ) AS details
                ")
            )
            ->leftJoin('transaction_details as td', 'td.transaction_id', '=', 'tx.id')
            ->where('tx.id', $id)
            ->groupBy(
                'tx.id',
                'tx.transaction_code',
                'tx.transaction_type',
                'tx.correlation_id',
                'tx.due_date',
                'tx.payment_term',
                'tx.created_by',
                'tx.updated_at',
                'tx.file_attachment',
                'tx.description',
                'tx.sub_total',
                'tx.total_discount',
                'tx.tax_amount',
                'tx.grand_total'
            )
            ->first();

        // PO yang belum punya detail tidak mungkin terjadi lewat alur create
        // (detail divalidasi min:1), tapi `first()` bisa tetap null kalau id-nya
        // tidak ada. Dicek di sini supaya Show tidak fatal error.
        if ($transaction === null) {
            return null;
        }

        // LEFT JOIN membuat `details` berisi satu objek null untuk PO tanpa
        // detail, jadi harus difilter sebelum di-decode.
        $decode_details = json_decode((string) $transaction->details, true);

        $transaction->details = collect($decode_details ?: [])
            ->filter(fn ($detail) => is_array($detail) && isset($detail['name']))
            ->mapWithKeys(function ($detail) {
                return [
                    strtolower(str_replace(' ', '_', $detail['name'])) => $detail['value'],
                ];
            });

        $transaction->items = $this->get_order_items->execute($id);

        return $transaction;
    }
}
