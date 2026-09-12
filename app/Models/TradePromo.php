<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

#[Table('trade_promo', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class TradePromo extends Model
{
    public function scopeDeactive(Builder $query)
    {
        return $query->where('is_active', false);
    }
}
