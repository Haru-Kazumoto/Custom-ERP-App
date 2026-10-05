<?php

namespace App\Modules\PurchaseOrder\Controllers;

use App\Enum\TransactionType;
use App\Http\Controllers\Controller;
use App\Modules\Approval\DTOs\DecideApprovalDTO;
use App\Modules\Approval\Queries\GetApprovalDecisionContextQuery;
use App\Modules\PurchaseOrder\Actions\RevisePurchaseOrderAction;
use App\Modules\PurchaseOrder\DTOs\CreatePurchaseOrderDTO;
use App\Modules\PurchaseOrder\DTOs\RevisePurchaseOrderDTO;
use App\Modules\PurchaseOrder\Queries\FindPurchaseOrderQuery;
use App\Modules\PurchaseOrder\Queries\GetPurchaseOrderApprovalsQuery;
use App\Modules\PurchaseOrder\Queries\GetPurchaseOrderByDocumentCodeQuery;
use App\Modules\PurchaseOrder\Queries\GetPurchaseOrdersQuery;
use App\Modules\PurchaseOrder\Queries\GetPurchaseOrderTransactionCodes;
use App\Modules\PurchaseOrder\Workflows\CreatePurchaseOrderWorkflow;
use App\Modules\Roles\Queries\GetOneRoleFromUserQuery;
use App\Modules\Transportation\Queries\GetTransportationsQuery;
use App\Modules\Vendor\Queries\GetVendorsQuery;
use App\Utils\GenerateDocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function __construct() {}

    public function index(Request $request, GetPurchaseOrdersQuery $purchase_orders)
    {
        return Inertia::render('PurchaseOrder/Index', [
            'purchaseOrders' => $purchase_orders->execute([
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'transaction_type' => TransactionType::PurchaseOrder->value,
            ]),
            'statusOptions' => GetPurchaseOrdersQuery::documentStatusOptions(),
        ]);
    }

    /**
     * Daftar PO yang menunggu diperbaiki.
     *
     * Filter status dipaksa ke `NEED_REVISION` dan tidak bisa dioverride dari
     * query string: halaman ini hanya masuk akal untuk dokumen yang belum
     * disetujui. Kalau `status` dari URL ikut dihormati, user bisa mendarat di
     * "Revisi PO" lalu melihat daftar dokumen yang justru sedang berjalan di
     * approval — dan form revisinya akan ditolak server.
     */
    public function indexRevisions(Request $request, GetPurchaseOrdersQuery $purchase_orders)
    {
        // Tanpa `statusOptions`: halaman ini tidak punya dropdown status karena
        // filter-nya dikunci ke `NEED_REVISION` di atas.
        return Inertia::render('PurchaseOrder/IndexRevisions', [
            'purchaseOrders' => $purchase_orders->execute([
                'search' => $request->query('search'),
                'status' => DecideApprovalDTO::STATUS_NEED_REVISION,
                'transaction_type' => TransactionType::PurchaseOrder->value,
            ]),
        ]);
    }

    public function create(GetVendorsQuery $vendors, GetTransportationsQuery $transportations)
    {
        return Inertia::render('PurchaseOrder/Create', [
            'po_number' => GenerateDocumentNumber::generate(TransactionType::PurchaseOrder),
            'suppliers' => $vendors->execute(),
            'transports' => $transportations->execute(),
        ]);
    }

    /**
     * Aturan validasi `store`, dipakai bersama oleh store dan update.
     *
     * Sebelumnya `store()` langsung memetakan request ke DTO lalu insert, jadi
     * quantity negatif, `product_id` palsu, atau nomor PO yang sudah terpakai
     * bisa saja tersimpan. Validasi `NForm` di frontend bukan pengaman —
     * request bisa dikirim langsung ke endpoint.
     *
     * Catatan: `sub_total`, `tax_amount`, dan `total` tidak divalidasi karena
     * tidak lagi dikirim frontend — nilainya dihitung server dari
     * `transaction_items[].unit_price`.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'document_code' => [
                'required',
                'string',
                'max:255',
                // Mencegah PO ganda saat tombol submit ter-klik dua kali.
                Rule::unique('transactions', 'transaction_code'),
            ],
            'term_of_payment' => ['required', 'integer', 'min:0', 'max:365'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],

            'transaction_details' => ['required', 'array', 'min:1'],
            'transaction_details.*.name' => ['required', 'string', 'max:255'],
            // Kunci semantik, mis. SUPPLIER / PO_DATE / USE_TAX. Kolom
            // `type` dulu menerima `data_type` sehingga kunci ini hilang.
            'transaction_details.*.type' => ['required', 'string', 'max:255'],
            'transaction_details.*.value' => ['required'],
            'transaction_details.*.data_type' => [
                'required',
                'string',
                Rule::in(['string', 'float', 'boolean', 'datetime']),
            ],

            'transaction_items' => ['required', 'array', 'min:1'],
            'transaction_items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
            ],
            'transaction_items.*.quantity' => ['required', 'integer', 'min:1'],
            // Harga satuan bruto (termasuk PPN) — sumber angka untuk semua total.
            'transaction_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'transaction_items.*.trade_promo_id' => [
                'nullable',
                'integer',
                Rule::exists('trade_promo', 'id'),
            ],
        ];
    }

    public function store(Request $request, CreatePurchaseOrderWorkflow $workflow)
    {
        $request->validate($this->rules());

        $workflow->execute(CreatePurchaseOrderDTO::fromRequest($request));

        return redirect(route('purchase-order.index'))->with('success', 'Purchase order berhasil dibuat!');
    }

    /**
     * Aturan validasi revisi.
     *
     * Sama dengan `rules()` minus `document_code`. Field itu sengaja tidak
     * diterima: nomor PO diambil dari database saat submit, bukan dari form.
     * Kalau form boleh mengirimnya, satu permintaan saja cukup untuk menukar
     * nomor PO dan membuat dokumen yang sudah disetujui kehilangan identitasnya.
     *
     * @return array<string, array<int, mixed>>
     */
    private function reviseRules(): array
    {
        return Arr::except($this->rules(), ['document_code']);
    }

    /**
     * Form revisi untuk satu PO.
     *
     * `po_number` dikirim apa adanya dari dokumen yang direvisi, bukan
     * `GenerateDocumentNumber` seperti di form create. Form create memakai
     * generator supaya nomor baru; di sini nomor itu harus tetap sama, jadi
     * generate angka baru hanya akan menampilkan nomor yang salah di layar.
     */
    public function revise(
        Request $request,
        int $id,
        FindPurchaseOrderQuery $find_purchase_order,
        GetVendorsQuery $vendors,
        GetTransportationsQuery $transportations,
    ) {
        $purchase_order = $find_purchase_order->execute($id);

        abort_if($purchase_order === null || $purchase_order->transaction_type !== TransactionType::PurchaseOrder->value, 404);

        // Form revisi hanya boleh dibuka pembuat dokumen. `RevisePurchaseOrderAction`
        // sudah menegakkan ini saat submit, tapi tanpa guard di sini user lain bisa
        // membuka form milik orang lain dan baru gagal setelah mengisi semuanya.
        abort_unless(
            (int) $purchase_order->created_by === (int) $request->user()->id,
            403,
            'Hanya pembuat dokumen yang dapat merevisi purchase order ini.',
        );

        return Inertia::render('PurchaseOrder/Create', [
            'po_number' => $purchase_order->transaction_code,
            'purchaseOrder' => $purchase_order,
            'suppliers' => $vendors->execute(),
            'transports' => $transportations->execute(),
        ]);
    }

    public function updateRevision(
        Request $request,
        int $id,
        RevisePurchaseOrderAction $revise_purchase_order,
    ) {
        // `document_code` tidak ikut divalidasi, jadi dibuang dari payload
        // lebih dulu supaya tidak pernah terbaca DTO.
        $request->validate($this->reviseRules());

        $revise_purchase_order->execute(
            $id,
            RevisePurchaseOrderDTO::fromRequest($request),
            (int) $request->user()->id,
        );

        return redirect(route('purchase-order.show', $id))
            ->with('success', 'Revisi purchase order tersimpan dan approval dimulai ulang dari Finance.');
    }

    /**
     * `approvalContext` dipakai panel keputusan di halaman detail: `can_decide`
     * dihitung server dari `transaction_approvals`, jadi frontend tidak perlu
     * membandingkan nama role untuk menentukan tombol mana yang boleh tampil.
     */
    public function show(
        Request $request,
        int $id,
        FindPurchaseOrderQuery $query,
        GetApprovalDecisionContextQuery $approval_context,
        GetOneRoleFromUserQuery $roles,
    ) {
        return Inertia::render('PurchaseOrder/Show', [
            'purchaseOrder' => $query->execute($id, true),
            'approvalContext' => $approval_context->execute(
                $id,
                (int) $roles->execute((int) $request->user()->id)->id,
            ),
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
        $validated = $request->validate([
            'transaction_code' => ['required', 'string', 'max:255'],
        ]);
        $purchaseOrder = $query->execute($validated['transaction_code']);

        abort_if(
            $purchaseOrder === null
                || $purchaseOrder->transaction_type !== TransactionType::PurchaseOrder->value,
            404,
            'Purchase Order tidak ditemukan.',
        );

        return response()->json($purchaseOrder);
    }
}
