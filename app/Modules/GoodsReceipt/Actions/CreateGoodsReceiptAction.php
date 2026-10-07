<?php

namespace App\Modules\GoodsReceipt\Actions;

use App\Enum\TransactionType;
use App\Models\ProductJournal;
use App\Modules\GoodsReceipt\DTOs\CreateGoodsReceiptDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGoodsReceiptAction
{
    public const RECEIVING_STATUS_RECEIVED = 'RECEIVED';

    public const DISCREPANCY_STATUS_OPEN = 'OPEN';

    public const DISCREPANCY_TYPE_SHORTAGE = 'SHORTAGE';

    public function execute(CreateGoodsReceiptDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $transaction = DB::table('transactions')
                ->where('id', $dto->transaction_id)
                ->lockForUpdate()
                ->first();

            if (
                $transaction === null
                || $transaction->transaction_type !== TransactionType::SubSalesOrder->value
            ) {
                throw ValidationException::withMessages([
                    'transaction_id' => 'Sub Sales Order yang dipilih tidak ditemukan.',
                ]);
            }

            $alreadyReceived = DB::table('receivings')
                ->where('transaction_id', $transaction->id)
                ->exists();

            if ($alreadyReceived) {
                throw ValidationException::withMessages([
                    'transaction_id' => 'SSO ini sudah pernah dicatat penerimaannya di warehouse.',
                ]);
            }

            $sourceItems = DB::table('transaction_items')
                ->where('transaction_id', $transaction->id)
                ->get()
                ->keyBy('id');

            $submittedIds = array_column($dto->items, 'transaction_items_id');

            if (count($submittedIds) !== count(array_unique($submittedIds))) {
                throw ValidationException::withMessages([
                    'items' => 'Terdapat item duplikat pada entry.',
                ]);
            }

            foreach ($dto->items as $index => $item) {
                if (! $sourceItems->has($item['transaction_items_id'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.transaction_items_id" => 'Item harus berasal dari SSO yang dipilih.',
                    ]);
                }

                $this->validateItem($index, $item, (int) $sourceItems[$item['transaction_items_id']]->quantity);
            }

            $receivingId = DB::table('receivings')->insertGetId([
                'transaction_id' => $transaction->id,
                'received_at' => now(),
                'received_by' => $dto->received_by,
                'status' => self::RECEIVING_STATUS_RECEIVED,
                'noted' => $dto->noted,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($dto->items as $item) {
                $sourceItem = $sourceItems[$item['transaction_items_id']];
                $orderedQty = (int) $sourceItem->quantity;
                $receivedQty = $item['received_qty'];

                $receivingItemId = DB::table('receiving_items')->insertGetId([
                    'receiving_id' => $receivingId,
                    'received_qty' => $receivedQty,
                    'transaction_items_id' => $item['transaction_items_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Kekurangan terhadap qty SSO dicatat otomatis sebagai discrepancy.
                // Qty bertahap (GRADUALLY) bukan kekurangan permanen — barangnya
                // masih akan datang — jadi ditanggalkan dari hitungan shortage.
                $gradualPending = array_sum(array_map(
                    fn (array $discrepancy) => $discrepancy['type'] === 'GRADUALLY'
                        ? $discrepancy['remaining_qty']
                        : 0,
                    $item['discrepancies'],
                ));

                $shortfall = $orderedQty - $receivedQty - $gradualPending;

                if ($shortfall > 0) {
                    DB::table('receiving_discrepancies')->insert([
                        'receiving_items_id' => $receivingItemId,
                        'type' => self::DISCREPANCY_TYPE_SHORTAGE,
                        'remaining_qty' => $shortfall,
                        'status' => self::DISCREPANCY_STATUS_OPEN,
                        'description' => 'Kekurangan dari qty SSO (' . $orderedQty . ').',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($item['discrepancies'] as $discrepancy) {
                    DB::table('receiving_discrepancies')->insert([
                        'receiving_items_id' => $receivingItemId,
                        'type' => $discrepancy['type'],
                        'remaining_qty' => $discrepancy['remaining_qty'],
                        'status' => self::DISCREPANCY_STATUS_OPEN,
                        'description' => $discrepancy['description'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Pemecahan kode barang: satu baris jurnal per batch hasil
                // pecahan — hanya untuk barang yang benar-benar datang.
                if ($receivedQty > 0) {
                    foreach ($item['splits'] as $split) {
                        ProductJournal::create([
                            'quantity' => $split['quantity'],
                            'action' => ProductJournal::ACTION_IN,
                            'batch_code' => $split['batch_code'],
                            'expiry_date' => $split['expiry_date'],
                            'stagnation_limit_date' => $split['stagnation_limit_date'],
                            'product_id' => $sourceItem->product_id,
                            'company_id' => $dto->company_id,
                            'transaction_id' => $transaction->id,
                            'transaction_items_id' => $item['transaction_items_id'],
                        ]);
                    }
                }
            }

            return $receivingId;
        });
    }

    /**
     * Validasi per item: qty diterima tidak boleh melebihi qty SSO, total qty
     * pecahan harus persis sama dengan qty diterima supaya jurnal tidak
     * menyimpang dari penerimaan, dan qty bertahap (GRADUALLY) tidak boleh
     * melebihi qty yang belum datang.
     *
     * Qty diterima 0 diperbolehkan dengan splits kosong — seluruh barang
     * masih tertunda, belum ada yang dipecah.
     *
     * @param  array{
     *     received_qty: int,
     *     splits: array<int, array{batch_code: string, quantity: int}>,
     *     discrepancies: array<int, array{type: string, remaining_qty: int}>
     * }  $item
     */
    private function validateItem(int $index, array $item, int $orderedQty): void
    {
        if ($item['received_qty'] > $orderedQty) {
            throw ValidationException::withMessages([
                "items.{$index}.received_qty" => 'Qty diterima tidak boleh melebihi qty SSO (' . $orderedQty . ').',
            ]);
        }

        // Qty diterima 0: belum ada yang datang — splits diabaikan (semua
        // barang tertunda, tidak ada yang dipecah masuk stok).
        if ($item['received_qty'] === 0) {
            $this->validateGradual($index, $item, $orderedQty);

            return;
        }

        if (count($item['splits']) === 0) {
            throw ValidationException::withMessages([
                "items.{$index}.splits" => 'Barang harus dipecah menjadi minimal satu kode barang.',
            ]);
        }

        $totalSplit = array_sum(array_column($item['splits'], 'quantity'));

        if ($totalSplit !== $item['received_qty']) {
            throw ValidationException::withMessages([
                "items.{$index}.splits" => 'Total qty pecahan (' . $totalSplit . ') harus sama dengan qty diterima (' . $item['received_qty'] . ').',
            ]);
        }

        foreach ($item['splits'] as $splitIndex => $split) {
            if ($split['batch_code'] === '') {
                throw ValidationException::withMessages([
                    "items.{$index}.splits.{$splitIndex}.batch_code" => 'Kode barang hasil pecahan wajib diisi.',
                ]);
            }

            if ($split['quantity'] < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.splits.{$splitIndex}.quantity" => 'Qty pecahan minimal 1.',
                ]);
            }
        }

        $this->validateGradual($index, $item, $orderedQty);
    }

    /**
     * Qty bertahap tidak boleh melebihi qty yang belum datang — sisanya justru
     * jadi kekurangan permanen.
     *
     * @param  array{received_qty: int, discrepancies: array<int, array{type: string, remaining_qty: int}>}  $item
     */
    private function validateGradual(int $index, array $item, int $orderedQty): void
    {
        $gradualPending = array_sum(array_map(
            fn (array $discrepancy) => $discrepancy['type'] === 'GRADUALLY'
                ? $discrepancy['remaining_qty']
                : 0,
            $item['discrepancies'],
        ));

        $notYetReceived = $orderedQty - $item['received_qty'];

        if ($gradualPending > $notYetReceived) {
            throw ValidationException::withMessages([
                "items.{$index}.discrepancies" => 'Qty bertahap (' . $gradualPending . ') melebihi qty yang belum diterima (' . $notYetReceived . ').',
            ]);
        }
    }
}
