<?php

namespace App\Modules\Transportation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transportation\Queries\GetTransportationsQuery;

class TransportationController extends Controller
{
    public function __construct(
        private GetTransportationsQuery $getTransportations
    )
    {}
}
