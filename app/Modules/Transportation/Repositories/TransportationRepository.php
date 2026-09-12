<?php

namespace App\Modules\Transportation\Repositories;

use App\Models\Transportation;
use Illuminate\Support\Facades\DB;

class TransportationRepository
{
    public function getAll()
    {
        return Transportation::all();
    }
}
