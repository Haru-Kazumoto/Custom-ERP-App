<?php

namespace App\Modules\TradePromo\Repositories;

use App\Models\TradePromo;
use Illuminate\Support\Facades\DB;

class TradePromoRepository
{
    public function getAll(array $filter)
    {
        return array();
    }

    public function create(array $data)
    {
        return [];
    }

    public function decrementQuota(int $trade_promo_id, int $quota_requested)   
    {
        return DB::table('trade_promo')
            ->where('id', $trade_promo_id)
            ->where('quota', '>=', $quota_requested)
            ->decrement('quota', $quota_requested);
    }

    public function deactive(int $trade_promo_id)
    {
        return TradePromo::where('id', $trade_promo_id)->deactive();
    }

    public function lockById(int $id)
    {
        return TradePromo::where('id', $id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
