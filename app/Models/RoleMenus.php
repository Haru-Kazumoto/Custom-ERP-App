<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable('menu_id', 'role_id')]
#[Table('role_menus')]
class RoleMenus extends Model
{
    //
}
