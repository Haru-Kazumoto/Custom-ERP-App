<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('receiving_items', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class ReceivingItem extends Model
{
    public function discrepancies(): HasMany
    {
        return $this->hasMany(ReceivingDiscrepancy::class, 'receiving_items_id');
    }

    public function receiving(): BelongsTo
    {
        return $this->belongsTo(Receiving::class, 'receiving_id');
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class, 'transaction_items_id');
    }
}
