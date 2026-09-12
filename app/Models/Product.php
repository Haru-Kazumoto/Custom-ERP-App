<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('products', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class Product extends Model
{}
