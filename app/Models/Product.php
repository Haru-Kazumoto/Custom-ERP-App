<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('products', key: 'id', keyType: 'int', incrementing: true)]
#[Fillable([
    'name',
    'code',
    'unit',
    'category',
    'price',
    'product_type_id',
    'product_sub_type_id',
    'vendor_id',
])]
class Product extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // `decimal:2` menjaga format dua desimal saat serialisasi ke
            // frontend, jadi 150000 tidak muncul sebagai 1.5E+5 di JSON.
            'price' => 'decimal:2',
        ];
    }

    /**
     * Vendor pemasok. Nullable di DB (`nullOnDelete`), jadi relasi boleh null
     * ketika vendor dihapus — form harus tetap bisa menyimpan produk.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function productSubType(): BelongsTo
    {
        return $this->belongsTo(ProductSubType::class, 'product_sub_type_id');
    }
}
