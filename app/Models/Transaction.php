<?php

namespace App\Models;

use App\Enum\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

#[Table('transactions', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class Transaction extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (Auth::check()) {
                $transaction->created_by = Auth::id();
            }
        });

        static::updating(function (Transaction $transaction) {
            if (Auth::check()) {
                $transaction->updated_by = Auth::id();
            }
        });
    }

    public function scopeFromType(Builder $query, TransactionType $type)
    {
        return $query->where('transaction_type', '=', $type->value);
    }
}
