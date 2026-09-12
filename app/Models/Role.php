<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('roles', key: 'id', keyType: 'int', incrementing: true)]
#[Fillable(['name', 'code'])]
class Role extends Model
{    
    public function subRoles()
    {
        return $this->hasMany(SubRole::class);
    }
}
