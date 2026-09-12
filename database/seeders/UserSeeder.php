<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SubRole;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::with('subRoles')->get();

        foreach ($roles as $role) {

            // Jika role memiliki sub role
            if ($role->subRoles->isNotEmpty()) {

                foreach ($role->subRoles as $subRole) {

                    $username = $subRole->code;

                    User::create([
                        'name' => $subRole->name,
                        'username' => $username,
                        'emp_id' => 'EMP-' . str_pad(User::count() + 1, 3, '0', STR_PAD_LEFT),

                        'role_id' => $role->id,
                        'sub_role_id' => $subRole->id,

                        'email' => "{$username}@company.local",

                        'password' => Hash::make('password'),
                    ]);
                }

                continue;
            }

            // Jika role tidak memiliki sub role
            $username = $role->code;

            User::create([
                'name' => $role->name,
                'username' => $username,
                'emp_id' => 'EMP-' . str_pad(User::count() + 1, 3, '0', STR_PAD_LEFT),

                'role_id' => $role->id,
                'sub_role_id' => null,

                'email' => "{$username}@company.local",

                'password' => Hash::make('password'),
            ]);
        }
    }
}
