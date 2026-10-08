<?php

namespace App\Modules\Account\Repositories;

use Illuminate\Support\Facades\DB;
use stdClass;

class AccountRepository
{
    public function getInformationAccountById(int $user_id): stdClass
    {
        $account = DB::table('users as u')
            ->select(
                'u.id',
                'u.name',
                'u.emp_id',
                'u.email',
                'u.profile_photo_path',
                'u.sub_role_id',
                'r.name as role',
                'sr.name as sub_role'
            )
            ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
            ->leftJoin('sub_roles as sr', 'sr.id', '=', 'u.sub_role_id')
            ->where('u.id', $user_id)
            ->first();

        if ($account === null) {
            return $account;
        }

        // Rantai hierarki sub-role (sub_role -> induknya -> dst.) untuk
        // ditampilkan di halaman Profile. Dibaca dari `sub_roles.parent_id`
        // supaya struktur organisasi cukup diubah lewat data. Cycle guard:
        // parent bisa diedit manual dan rantai yang berputar akan mengunci
        // halaman profile kalau tidak dibatasi.
        $account->sub_role_hierarchy = $this->subRoleHierarchy($account->sub_role_id);

        return $account;
    }

    /**
     * Daftar hierarki dari sub-role user ke puncak, `[{id, name, code}, ...]`.
     * Kosong kalau user tanpa sub-role atau sub-role-nya di puncak.
     *
     * @return array<int, object>
     */
    private function subRoleHierarchy(?int $sub_role_id): array
    {
        if ($sub_role_id === null) {
            return [];
        }

        $rows = DB::table('sub_roles')
            ->get(['id', 'name', 'code', 'parent_id'])
            ->keyBy('id');

        $chain = [];
        $visited = [];
        $current = $sub_role_id;

        while ($current !== null && isset($rows[$current]) && ! isset($visited[$current])) {
            $visited[$current] = true;
            $row = $rows[$current];
            $chain[] = ['id' => (int) $row->id, 'name' => $row->name, 'code' => $row->code];
            $current = $row->parent_id !== null ? (int) $row->parent_id : null;
        }

        return $chain;
    }
}
