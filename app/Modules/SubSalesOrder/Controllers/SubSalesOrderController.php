<?php

namespace App\Modules\SubSalesOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubSalesOrder\Queries\GetSubSalesOrderQuery;
use App\Modules\SubSalesOrder\Queries\FindSubSalesOrderQuery;
use App\Modules\SubSalesOrder\Actions\CreateSubSalesOrderAction;
use App\Modules\SubSalesOrder\DTOs\CreateSubSalesOrderDTO;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SubSalesOrderController extends Controller
{
    public function __construct()
    {}

    public function index(Request $request, GetSubSalesOrderQuery $query)
    {
        return Inertia::render('SubSalesOrder/Index', [
            'subSalesOrders' => $query->execute([]),
        ]);
    }

    public function create()
    {
        return Inertia::render('SubSalesOrder/Create');
    }

    public function show(int $id, FindSubSalesOrderQuery $query)
    {
        $subSalesOrder = $query->execute($id);

        abort_if($subSalesOrder === null, 404);

        return Inertia::render('SubSalesOrder/Show', [
            'subSalesOrder' => $subSalesOrder,
        ]);
    }

    public function store(Request $request, CreateSubSalesOrderAction $store)
    {
        $validated = $request->validate([
            'purchase_order_id' => [
                'required',
                'integer',
                Rule::exists('transactions', 'id'),
            ],
            'no_bukti' => [
                'required',
                'string',
                'max:255',
                Rule::unique('transactions', 'transaction_code'),
            ],
            'no_so' => ['required', 'string', 'max:255'],
            'tanggal_kirim' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
        ]);

        $store->execute(
            CreateSubSalesOrderDTO::fromValidated(
                $validated,
                (int) $request->user()->id,
            ),
        );

        return redirect(route('sub-sales-order.index'))->with('success', 'SSO Berhasil terbuat!.');
    }

}
