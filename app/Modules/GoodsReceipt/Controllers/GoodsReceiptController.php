<?php

namespace App\Modules\GoodsReceipt\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Modules\GoodsReceipt\Actions\CreateGoodsReceiptAction;
use App\Modules\GoodsReceipt\DTOs\CreateGoodsReceiptDTO;
use App\Modules\GoodsReceipt\Queries\GetSsoByTransactionCodeQuery;
use App\Modules\GoodsReceipt\Queries\GetSsoTransactionCodesQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GoodsReceiptController extends Controller
{
    public function index()
    {
        return Inertia::render('Items/Entry', [
            // Default company masih di-hardcode ke DNP; pemilihan company
            // menyusul setelah alurnya jelas.
            'defaultCompanyId' => Company::where('code', 'DNP')->value('id'),
        ]);
    }

    public function getTransactionCodes(GetSsoTransactionCodesQuery $query)
    {
        return response()->json($query->execute());
    }

    public function getByTransactionCode(Request $request, GetSsoByTransactionCodeQuery $query)
    {
        $validated = $request->validate([
            'transaction_code' => ['required', 'string', 'max:255'],
        ]);

        $subSalesOrder = $query->execute($validated['transaction_code']);

        abort_if($subSalesOrder === null, 404, 'SSO tidak ditemukan atau sudah pernah dicatat penerimaannya.');

        return response()->json($subSalesOrder);
    }

    public function store(Request $request, CreateGoodsReceiptAction $store)
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'integer', Rule::exists('transactions', 'id')],
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'noted' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.transaction_items_id' => ['required', 'integer', 'distinct'],
            'items.*.received_qty' => ['required', 'integer', 'min:0'],
            // Boleh kosong saat qty diterima 0 — semua barang masih tertunda
            // (GRADUALLY), belum ada yang datang untuk dipecah.
            'items.*.splits' => ['present', 'array'],
            'items.*.splits.*.batch_code' => ['required', 'string', 'max:255'],
            'items.*.splits.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.splits.*.expiry_date' => ['nullable', 'date'],
            'items.*.splits.*.stagnation_limit_date' => ['nullable', 'date'],
            'items.*.discrepancies' => ['nullable', 'array'],
            'items.*.discrepancies.*.type' => [
                'required',
                Rule::in(['SHORTAGE', 'DAMAGED', 'LOST', 'GRADUALLY', 'OTHER']),
            ],
            'items.*.discrepancies.*.remaining_qty' => ['required', 'integer', 'min:1'],
            'items.*.discrepancies.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $store->execute(
            CreateGoodsReceiptDTO::fromValidated(
                $validated,
                (int) $request->user()->id,
            ),
        );

        return redirect(route('goods-receipt.index'))->with('success', 'Barang berhasil diterima di warehouse.');
    }
}
