<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('sub_roles', key: 'id', keyType: 'int', incrementing: true)]
#[Fillable(['name', 'code', 'role_id', 'parent_id'])]
class SubRole extends Model
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
