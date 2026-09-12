<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('transportation', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class Transportation extends Model
{}
