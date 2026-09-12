<?php

namespace App\Modules\Vendor\Repositories;

use Illuminate\Support\Facades\DB;

class VendorRepository
{
    public function getAll()
    {
        return DB::table('vendor')->get();
    }
}
