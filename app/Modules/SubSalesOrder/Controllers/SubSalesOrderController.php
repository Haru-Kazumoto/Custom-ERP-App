<?php

namespace App\Modules\SubSalesOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubSalesOrder\Queries\GetSubSalesOrderQuery;
use App\Modules\SubSalesOrder\Actions\CreateSubSalesOrderAction;
use App\Modules\SubSalesOrder\DTOs\CreateSubSalesOrderDTO;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubSalesOrderController extends Controller
{
    public function __construct()
    {}

    public function index(Request $request, GetSubSalesOrderQuery $query)
    {
        return Inertia::render('SubSalesOrder/Index', [
            'subSalesOrders' => $query->execute([])
        ]);
    }

    public function create()
    {
        return Inertia::render('SubSalesOrder/Create', [
            'purchaseOrders' => []
        ]);
    }

    public function store(Request $request, CreateSubSalesOrderAction $store)
    {
        $store->execute(CreateSubSalesOrderDTO::fromRequest($request));

        return redirect(route('sub-sales-order.index'))->with('success', 'SSO Berhasil terbuat!.');
    }
    
}
