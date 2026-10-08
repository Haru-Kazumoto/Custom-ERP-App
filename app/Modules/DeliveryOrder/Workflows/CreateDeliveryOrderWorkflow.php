<?php

namespace App\Modules\DeliveryOrder\Workflows;

use App\Modules\DeliveryOrder\Actions\CreateDeliveryOrderAction;
use App\Modules\DeliveryOrder\Actions\GenerateDeliveryOrderApprovalsAction;
use App\Modules\DeliveryOrder\DTOs\CreateDeliveryOrderDTO;
use App\Modules\DeliveryOrder\Services\StockAllocator;
use Illuminate\Support\Facades\DB;

class CreateDeliveryOrderWorkflow
{
    public function __construct(
        private CreateDeliveryOrderAction $create_action,
        private StockAllocator $stock_allocator,
        private GenerateDeliveryOrderApprovalsAction $generate_approval_action,
    ) {}

    /**
     * Satu transaksi atomik: dokumen dibuat, stok keluar dialokasikan per
     * batch FEFO, lalu rantai approval digenerate. Kalau salah satu tahap
     * gagal (stok kurang, promo tidak eligible, harga hilang), seluruh
     * dokumen dibatalkan — tidak ada DO setengah jadi yang sudah mengurangi
     * stok.
     *
     * @param  int  $creator_id  Pembuat; menentukan rantai approval sales
     *                            (via sub-role pembuat) dan penomoran langkah.
     * @param  bool  $needs_bd_approval  Ada baris harga/diskon manual —
     *                                    menyalakan langkah Business
     *                                    Development di rantai.
     *
     * @return \App\Models\Transaction
     */
    public function execute(CreateDeliveryOrderDTO $dto, int $creator_id, bool $needs_bd_approval)
    {
        return DB::transaction(function () use ($dto, $creator_id, $needs_bd_approval) {
            $created = $this->create_action->execute($dto);

            $this->stock_allocator->allocate(
                $created['transaction']->id,
                $dto->company_id,
                $created['lines'],
            );

            $this->generate_approval_action->execute(
                $created['transaction']->id,
                $creator_id,
                $needs_bd_approval,
            );

            return $created['transaction'];
        });
    }
}
