<?php

namespace App\Modules\SubSalesOrder\Actions;

use App\Modules\SubSalesOrder\DTOs\CreateSubSalesOrderDTO;
use App\Modules\SubSalesOrder\Repositories\SubSalesOrderRepository;
use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSubSalesOrderAction
{
    public function __construct(protected SubSalesOrderRepository $repository)
    {}

    public function execute(CreateSubSalesOrderDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $purchaseOrder = DB::table('transactions')
                ->where('id', $dto->purchase_order_id)
                ->lockForUpdate()
                ->first();

            if (
                $purchaseOrder === null
                || $purchaseOrder->transaction_type !== TransactionType::PurchaseOrder->value
            ) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Purchase Order yang dipilih tidak ditemukan.',
                ]);
            }

            $hasOpenApproval = DB::table('transaction_approvals')
                ->where('transaction_id', $purchaseOrder->id)
                ->whereIn('status', ['PENDING', 'NEED_REVISION', 'REJECTED'])
                ->exists();

            if ($hasOpenApproval) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Purchase Order belum disetujui atau tidak dapat digunakan.',
                ]);
            }

            $items = DB::table('transaction_items')
                ->where('transaction_id', $purchaseOrder->id)
                ->whereIn('id', $dto->purchase_order_item_ids)
                ->get();

            if ($items->count() !== count($dto->purchase_order_item_ids)) {
                throw ValidationException::withMessages([
                    'items' => 'Barang harus berasal dari Purchase Order yang dipilih.',
                ]);
            }

            $sourceDetails = DB::table('transaction_details')
                ->where('transaction_id', $purchaseOrder->id)
                ->get(['name', 'type', 'value'])
                ->reduce(function (array $details, object $detail) {
                    $details[strtoupper($detail->type)] = $detail->value;
                    $details[strtoupper(str_replace(' ', '_', $detail->name))] = $detail->value;

                    return $details;
                }, []);

            $supplier = $this->sourceDetail($sourceDetails, 'SUPPLIER', 'PEMASOK');
            $allocation = $this->sourceDetail($sourceDetails, 'COMPANY', 'ALOKASI');
            $transportation = $this->sourceDetail($sourceDetails, 'TRANSPORTATION', 'TRANSPORTASI');
            $deliveryType = $this->sourceDetail($sourceDetails, 'DELIVERY_TYPE', 'JENIS_PENGIRIMAN');

            $data = [
                'no_bukti' => $dto->no_bukti,
                'correlation_id' => $purchaseOrder->correlation_id,
                'description' => $dto->description,
                'created_by' => $dto->created_by,
                'items' => $items,
                'details' => [
                    ['name' => 'No Bukti', 'value' => $dto->no_bukti, 'type' => 'string'],
                    ['name' => 'No SO', 'value' => $dto->no_so, 'type' => 'string'],
                    ['name' => 'Tanggal Kirim', 'value' => $dto->tanggal_kirim, 'type' => 'datetime'],
                    ['name' => 'ID Purchase Order', 'value' => (string) $purchaseOrder->id, 'type' => 'integer'],
                    ['name' => 'Nomor Purchase Order', 'value' => $purchaseOrder->transaction_code, 'type' => 'string'],
                    ['name' => 'Pemasok', 'value' => $supplier, 'type' => 'string'],
                    ['name' => 'Alokasi', 'value' => $allocation, 'type' => 'string'],
                    ['name' => 'Nama Ekspedisi', 'value' => $transportation, 'type' => 'string'],
                    ['name' => 'Jenis Pengiriman', 'value' => $deliveryType, 'type' => 'string'],
                    ['name' => 'PIC', 'value' => DB::table('users')->where('id', $dto->created_by)->value('name'), 'type' => 'string'],
                ],
            ];

            return $this->repository->create($data);
        });
    }

    private function sourceDetail(array $details, string $type, string $name): string
    {
        $value = $details[$type] ?? $details[$name] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'Detail Purchase Order tidak lengkap sehingga tidak dapat digunakan.',
            ]);
        }

        return $value;
    }
}
