<?php

namespace App\Modules\Roles\Queries;

use Illuminate\Support\Facades\DB;

class GetOneRoleFromUserQuery
{
    public function execute(int $user_id)
    {
        return DB::table('users', 'u')
            ->select(['r.*'])
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->where('u.id', $user_id)
            ->firstOrFail();
    }
}
