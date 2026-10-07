<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('companies', key: 'id', keyType: 'int', incrementing: true)]
#[Guarded([])]
class Company extends Model
{
}
