<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

#[Table('transaction_approvals', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class TransactionApprovals extends Model
{
    protected static function booted(): void
    {
        static::updating(function(TransactionApprovals $approval) {
            $approval->proceed_by = Auth::id();
            $approval->proceed_at = now();
        });

        // DEFAULT VALUE
        static::creating(function(TransactionApprovals $approval) {
            $approval->status = 'PENDING';
        });
    }
}
