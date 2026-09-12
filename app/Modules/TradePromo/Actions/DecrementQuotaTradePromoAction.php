<?php

namespace App\Modules\TradePromo\Actions;

use App\Modules\TradePromo\Repositories\TradePromoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecrementQuotaTradePromoAction
{
    public function __construct(private TradePromoRepository $repository)
    {}

    public function execute(int $trade_promo_id, int $quota_requested)
    {
        // Start the transaction
        return DB::transaction(function() use ($trade_promo_id, $quota_requested) {
            // lock first before the updating quota, prevent the race condition
            $promo = $this->repository->lockById($trade_promo_id);

            // Validating the current quota to requested quota
            if($promo->quota < $quota_requested) {
                throw ValidationException::withMessages([
                    'quota' => 'Kuota trade promo tidak mencukupi, gunakan trade promo lain.'
                ]);
            }

            // Decrement the quota if save
            $this->repository->decrementQuota($trade_promo_id, $quota_requested);

            return $promo->refresh();
        });
    }
}