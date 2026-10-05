<?php

namespace App\Modules\PurchaseOrder\Workflows;

use App\Modules\PurchaseOrder\Actions\CreatePurchaseOrderAction;
use App\Modules\PurchaseOrder\Actions\GeneratePurchaseOrderApprovalsAction;
use App\Modules\PurchaseOrder\DTOs\CreatePurchaseOrderDTO;
use App\Modules\TradePromo\Actions\DecrementQuotaTradePromoAction;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrderWorkflow
{
    public function __construct(
        private DecrementQuotaTradePromoAction $decrement_trade_promo,
        private CreatePurchaseOrderAction $create_action,
        private GeneratePurchaseOrderApprovalsAction $generate_approval_action,
    ) {}

    public function execute(CreatePurchaseOrderDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            $created_transaction = $this->create_action->execute($dto);

            // Decrementing the trade promo if some item using it
            collect($dto->items)->each(function ($item) {
                if ($item->trade_promo_id) {
                    $this->decrement_trade_promo->execute($item->trade_promo_id, $item->quantity);
                }
            });

            $this->generate_approval_action->execute($created_transaction->id);
        });
    }
}
