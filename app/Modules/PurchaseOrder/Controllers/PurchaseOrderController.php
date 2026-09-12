<?php

namespace App\Modules\PurchaseOrder\Controllers;

use App\Enum\TransactionType;
use App\Http\Controllers\Controller;
use App\Modules\PurchaseOrder\DTOs\CreatePurchaseOrderDTO;
use App\Modules\PurchaseOrder\Queries\FindPurchaseOrder;
use App\Modules\PurchaseOrder\Queries\FindPurchaseOrderQuery;
use App\Modules\PurchaseOrder\Queries\GetPurchaseOrderApprovalsQuery;
use App\Modules\PurchaseOrder\Queries\GetPurchaseOrderByDocumentCodeQuery;
use App\Modules\PurchaseOrder\Queries\GetPurchaseOrdersQuery;
use App\Modules\PurchaseOrder\Queries\GetPurchaseOrderTransactionCodes;
use App\Modules\PurchaseOrder\Workflows\CreatePurchaseOrderWorkflow;
use App\Modules\Transportation\Queries\GetTransportationsQuery;
use App\Modules\Vendor\Queries\GetVendorsQuery;
use App\Utils\GenerateDocumentNumber;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function __construct() {}

    public function index(Request $request, GetPurchaseOrdersQuery $purchase_orders)
    {
        return Inertia::render('PurchaseOrder/Index', [
            'purchaseOrders' => $purchase_orders->execute([])
        ]);
    }

    public function indexRevisions()
    {
        return Inertia::render('PurchaseOrder/IndexRevisions');
    }

    public function create(GetVendorsQuery $vendors, GetTransportationsQuery $transportations)
    {
        return Inertia::render('PurchaseOrder/Create', [
            'po_number'     => GenerateDocumentNumber::generate(TransactionType::PurchaseOrder),
            'suppliers'     => $vendors->execute(),
            'transports'    => $transportations->execute()
        ]);
    }

    public function store(Request $request, CreatePurchaseOrderWorkflow $workflow)
    {
        $workflow->execute(CreatePurchaseOrderDTO::fromRequest($request));

        return redirect(route('purchase-order.index'))->with('success', 'Purchase order berhasil dibuat!');
    }

    public function show(int $id, FindPurchaseOrderQuery $query)
    {
        return Inertia::render('PurchaseOrder/Show', [
            'purchaseOrder' => $query->execute($id, true)
        ]);
    }

    public function getApprovals(int $id, GetPurchaseOrderApprovalsQuery $get_approvals)
    {
        return response()->json($get_approvals->execute($id));
    }

    public function getTransactionCodes(GetPurchaseOrderTransactionCodes $query)
    {
        return response()->json($query->execute());
    }

    public function getByTransactionCode(Request $request, GetPurchaseOrderByDocumentCodeQuery $query)
    {
        $transaction_code = $request->query('transaction_code');
        
        return response()->json($query->execute($transaction_code));
    }
}
