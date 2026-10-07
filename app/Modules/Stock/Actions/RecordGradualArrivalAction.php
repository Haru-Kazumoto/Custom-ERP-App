<?php

namespace App\Modules\Stock\Actions;

use App\Models\Company;
use App\Models\ProductJournal;
use App\Modules\Stock\DTOs\RecordGradualArrivalDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordGradualArrivalAction
{
    public function execute(RecordGradualArrivalDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $discrepancy = DB::table('receiving_discrepancies')
                ->where('id', $dto->discrepancy_id)
                ->lockForUpdate()
                ->first([
                    'id',
                    'receiving_items_id',
                    'type',
                    'status',
                    'remaining_qty',
                ]);

            if ($discrepancy === null || $discrepancy->type !== 'GRADUALLY') {
                throw ValidationException::withMessages([
                    'batch_code' => 'Barang bertahap tidak ditemukan.',
                ]);
            }

            if ($discrepancy->status !== 'OPEN') {
                throw ValidationException::withMessages([
                    'batch_code' => 'Barang bertahap ini sudah selesai diterima.',
                ]);
            }

            if ($dto->quantity > (int) $discrepancy->remaining_qty) {
                throw ValidationException::withMessages([
                    'quantity' => 'Qty datang melebihi sisa tertunda ('
                        . $discrepancy->remaining_qty . ').',
                ]);
            }

            $receivingItem = DB::table('receiving_items')
                ->where('id', $discrepancy->receiving_items_id)
                ->first(['receiving_id', 'transaction_items_id']);

            $transactionItem = DB::table('transaction_items')
                ->where('id', $receivingItem->transaction_items_id)
                ->first(['transaction_id', 'product_id']);

            // Kedatangan bertahap dicatat sebagai journal IN baru dengan kode
            // barang pilihan user — IN hanya untuk barang yang benar-benar datang.
            ProductJournal::create([
                'quantity' => $dto->quantity,
                'action' => ProductJournal::ACTION_IN,
                'batch_code' => $dto->batch_code,
                'expiry_date' => $dto->expiry_date,
                'stagnation_limit_date' => $dto->stagnation_limit_date,
                'product_id' => $transactionItem->product_id,
                'company_id' => $this->resolveCompanyId($receivingItem->transaction_items_id),
                'transaction_id' => $transactionItem->transaction_id,
                'transaction_items_id' => $receivingItem->transaction_items_id,
            ]);

            $remaining = (int) $discrepancy->remaining_qty - $dto->quantity;

            DB::table('receiving_discrepancies')
                ->where('id', $discrepancy->id)
                ->update([
                    'remaining_qty' => $remaining,
                    'status' => $remaining <= 0 ? 'RESOLVED' : $discrepancy->status,
                    'updated_at' => now(),
                ]);

            return $remaining;
        });
    }

    /**
     * Company journal baru mengikuti company journal penerimaan awal item
     * yang sama; fallback ke DNP kalau belum ada (belum pernah masuk stok).
     */
    private function resolveCompanyId(int $transactionItemsId): int
    {
        $existing = DB::table('product_journals')
            ->where('transaction_items_id', $transactionItemsId)
            ->value('company_id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) Company::where('code', 'DNP')->value('id');
    }
}
