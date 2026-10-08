<?php

namespace App\Modules\DeliveryOrder\Queries;

use Illuminate\Support\Facades\DB;

/**
 * Satu dokumen DO lengkap untuk halaman detail — `transaction_details`
 * di-decode jadi map `{snake_case_name: value}` plus daftar item.
 */
class FindDeliveryOrderQuery
{
    public function __construct(private GetDeliveryOrderItemsQuery $get_items) {}

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

        if ($transaction === null) {
            return null;
        }

        // LEFT JOIN menghasilkan satu objek null untuk dokumen tanpa detail;
        // difilter sebelum di-decode supaya Show tidak fatal error.
        $decode_details = json_decode((string) $transaction->details, true);

        $transaction->details = collect($decode_details ?: [])
            ->filter(fn ($detail) => is_array($detail) && isset($detail['name']))
            ->mapWithKeys(fn ($detail) => [
                strtolower(str_replace(' ', '_', $detail['name'])) => $detail['value'],
            ]);

        // `transactions` tidak punya kolom pelanggan - identitas pelanggan DO
        // hanya ada di `transaction_details` sebagai nama. Nama itu diambil
        // dari `GetDeliveryOrderFormOptionsQuery` (satu-satunya sumber pilihan
        // pelanggan di form), jadi pencocokan persis aman dipakai di sini.
        $transaction->items = $this->get_items->execute($id, $this->resolveCustomerId($transaction->details));

        return $transaction;
    }

    /**
     * Id pelanggan dari nama yang tersimpan di detail; null kalau tidak ada
     * atau namanya sudah tidak ada di tabel `customers`.
     */
    private function resolveCustomerId(\Illuminate\Support\Collection $details): ?int
    {
        $name = trim((string) $details->get('customer', ''));

        if ($name === '') {
            return null;
        }

        $customer_id = DB::table('customers')->where('name', $name)->value('id');

        return $customer_id === null ? null : (int) $customer_id;
    }
}
