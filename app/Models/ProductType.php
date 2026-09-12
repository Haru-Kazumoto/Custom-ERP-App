<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('product_type', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class ProductType extends Model
{}
