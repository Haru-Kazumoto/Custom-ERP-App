<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('receiving_discrepancies', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class ReceivingDiscrepancy extends Model
{
    public function receivingItem(): BelongsTo
    {
        return $this->belongsTo(ReceivingItem::class, 'receiving_items_id');
    }
}
