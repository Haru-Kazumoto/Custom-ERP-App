<?php

namespace App\Modules\Account\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class AccountRepository
{
    public function getInformationAccountById(int $user_id): stdClass
    {
        return DB::table('users as u')
            ->select(
                'u.id',
                'u.name',
                'u.emp_id',
                'u.email',
                'u.profile_photo_path',
                'r.name as role',
                'sr.name as sub_role'
            )
            ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
            ->leftJoin('sub_roles as sr', 'sr.id', '=', 'u.sub_role_id')
            ->where('u.id', $user_id)
            ->first();
    }
}
