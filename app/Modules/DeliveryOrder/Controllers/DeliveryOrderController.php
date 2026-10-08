<?php

namespace App\Modules\DeliveryOrder\Controllers;

use App\Enum\TransactionType;
use App\Http\Controllers\Controller;
use App\Modules\Approval\DTOs\DecideApprovalDTO;
use App\Modules\Approval\Queries\GetApprovalDecisionContextQuery;
use App\Modules\DeliveryOrder\Actions\ReviseDeliveryOrderAction;
use App\Modules\DeliveryOrder\DTOs\CreateDeliveryOrderDTO;
use App\Modules\DeliveryOrder\DTOs\ReviseDeliveryOrderDTO;
use App\Modules\DeliveryOrder\Queries\FindDeliveryOrderQuery;
use App\Modules\DeliveryOrder\Queries\GetDeliveryOrderApprovalsQuery;
use App\Modules\DeliveryOrder\Queries\GetDeliveryOrderFormOptionsQuery;
use App\Modules\DeliveryOrder\Queries\GetDeliveryOrdersQuery;
use App\Modules\DeliveryOrder\Queries\GetDeliveryOrderProductsQuery;
use App\Modules\DeliveryOrder\Workflows\CreateDeliveryOrderWorkflow;
use App\Modules\Roles\Queries\GetOneRoleFromUserQuery;
use App\Utils\GenerateDocumentNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class DeliveryOrderController extends Controller
{
    /**
     * Segmen yang bisa dipilih di form. Daftar ini sama dengan pemetaan
     * `DeliveryOrderPriceResolver::SEGMENT_COLUMNS`.
     */
    private const SEGMENTS = ['RETAIL', 'WHOLESALE', 'GROSIR', 'END_USER', 'ALL_SEGMENT'];

    /**
     * Daftar dokumen DO. Menu `delivery-order.index` menunjuk ke
     * `/delivery-order/documents`; path `/delivery-order` sendiri hanya
     * redirect supaya link lama tetap hidup.
     */
    public function index(Request $request, GetDeliveryOrdersQuery $delivery_orders)
    {
        return Inertia::render('DeliveryOrder/Index', [
            'deliveryOrders' => $delivery_orders->execute([
                'search' => $request->query('search'),
                'status' => $request->query('status'),
            ]),
            'filters' => $request->only(['search', 'status']),
            'statusOptions' => GetDeliveryOrdersQuery::documentStatusOptions(),
        ]);
    }

    public function create(GetDeliveryOrderFormOptionsQuery $options): Response
    {
        return Inertia::render('DeliveryOrder/Create', [
            'do_number' => GenerateDocumentNumber::generate(TransactionType::DeliveryOrder),
            'options' => $options->execute(),
        ]);
    }

    /**
     * Daftar DO yang dikembalikan approver untuk diperbaiki.
     *
     * Tanpa `statusOptions`: filter-nya dikunci ke `NEED_REVISION` di bawah,
     * jadi menampilkan pilihan status lain hanya akan mengembalikan dokumen
     * yang tidak bisa direvisi.
     */
    public function indexRevisions(Request $request, GetDeliveryOrdersQuery $delivery_orders)
    {
        return Inertia::render('DeliveryOrder/IndexRevisions', [
            'deliveryOrders' => $delivery_orders->execute([
                'search' => $request->query('search'),
                'status' => DecideApprovalDTO::STATUS_NEED_REVISION,
            ]),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Aturan validasi `store`.
     *
     * Nominal tidak divalidasi di sini karena tidak dikirim frontend: harga,
     * diskon promo, dan total dihitung ulang server dari `product_prices`,
     * `assigned_products_promo`, dan quantity. Yang divalidasi hanya bentuk
     * payload dan keberadaan record yang dirujuk.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(Request $request): array
    {
        return [
            'document_code' => [
                'required',
                'string',
                'max:255',
                // Mencegah DO ganda saat tombol submit ter-klik dua kali.
                Rule::unique('transactions', 'transaction_code'),
            ],
            'delivery_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],

            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'shipping_id' => ['required', 'integer', Rule::exists('shippings', 'id')],
            // Sub pengiriman harus milik pengiriman yang dipilih — exists saja
            // bisa dipakai untuk menyelipkan sub milik jenis pengiriman lain.
            'sub_shipping_id' => [
                'nullable',
                'integer',
                Rule::exists('sub_shippings', 'id')->where('shipping_id', (int) $request->input('shipping_id')),
            ],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            // Segmen dipilih manual di form (default mengikuti pelanggan) dan
            // menentukan kolom harga `product_prices`, jadi divalidasi terhadap
            // daftar segmen yang dikenal — nilai bebas tidak bisa lolos ke
            // resolver harga.
            'segment' => ['required', 'string', Rule::in(self::SEGMENTS)],

            'transaction_details' => ['required', 'array', 'min:1'],
            'transaction_details.*.name' => ['required', 'string', 'max:255'],
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
            // Mode harga berlaku per barang. Harga manual divalidasi lebih
            // longgar di sini (cukup numerik); pembatasan "tidak boleh lebih
            // rendah dari harga daftar" ditegakkan `DeliveryOrderCalculator`
            // terhadap harga `product_prices` milik segmen terpilih.
            'transaction_items.*.use_manual_price' => ['sometimes', 'boolean'],
            'transaction_items.*.unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'transaction_items.*.promo_product_id' => [
                'nullable',
                'integer',
                Rule::exists('promo_products', 'id'),
            ],
        ];
    }

    /**
     * Frontend memilih id di NSelect sebagai string (value opsi di-build
     * dengan `String(id)`). Formatkan ke integer di boundary request
     * sebelum validasi `integer`/`exists` dan pemetaan ke DTO, supaya
     * tipe yang beredar di backend konsisten integer.
     */
    private function normalizeIds(Request $request): void
    {
        foreach (['company_id', 'shipping_id', 'customer_id'] as $field) {
            $value = $request->input($field);
            if ($value !== null && $value !== '') {
                $request->merge([$field => (int) $value]);
            }
        }

        $sub = $request->input('sub_shipping_id');
        $request->merge([
            'sub_shipping_id' => $sub !== null && $sub !== '' ? (int) $sub : null,
        ]);

        $items = $request->input('transaction_items');
        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach (['product_id', 'promo_product_id'] as $field) {
                    $value = $item[$field] ?? null;
                    $items[$index][$field] = $value !== null && $value !== '' ? (int) $value : null;
                }
            }
            $request->merge(['transaction_items' => $items]);
        }
    }

    /**
     * Syarat langkah Business Development di rantai approval — persis
     * kondisi legacy `storeCustomerOrderApproval`: ada baris dengan diskon
     * manual (`manual_discount_type`) ATAU harga manual (`use_manual_price`).
     *
     * Form DO sekarang belum mengirim `manual_discount_type`, tapi ceknya
     * tetap dipertahankan supaya form yang menambah kolom itu nanti otomatis
     * menyalakan approval BD tanpa mengubah kode di sini.
     */
    private function needsBdApproval(Request $request): bool
    {
        return collect($request->input('transaction_items', []))
            ->contains(fn ($item) => ! empty($item['manual_discount_type'] ?? null)
                || ! empty($item['use_manual_price'] ?? null));
    }

    public function store(Request $request, CreateDeliveryOrderWorkflow $workflow)
    {
        $this->normalizeIds($request);

        $request->validate($this->rules($request));

        // Termin dan jatuh tempo diambil dari pelanggan di database — tidak
        // bisa dikirim lewat request supaya tanggal tidak bisa dimanipulasi
        // tanpa mengubah data pelanggan. Segmen berbeda: memang dipilih manual
        // di form (default mengikuti pelanggan) karena menentukan kolom harga.
        $customer = DB::table('customers')
            ->where('id', (int) $request->input('customer_id'))
            ->first(['term_payment']);

        $payment_term = (int) ($customer->term_payment ?? 0);
        $delivery_date = substr((string) $request->input('delivery_date'), 0, 10);
        $due_date = date('Y-m-d', strtotime($delivery_date . " +{$payment_term} days"));

        try {
            $workflow->execute(
                CreateDeliveryOrderDTO::fromRequest(
                    $request,
                    $payment_term,
                    $due_date,
                    strtoupper(trim((string) $request->input('segment'))),
                ),
                (int) $request->user()->id,
                $this->needsBdApproval($request),
            );
        } catch (RuntimeException $exception) {
            // Harga hilang, promo tidak eligible, atau stok kurang: pesannya
            // memang untuk pengguna, jadi dikembalikan sebagai error form
            // alih-alih error 500.
            return back()->withErrors(['delivery_order' => $exception->getMessage()]);
        }

        return redirect(route('delivery-order.index'))
            ->with('success', 'Delivery order berhasil dibuat!');
    }

    /**
     * Aturan revisi: seluruh aturan `store` kecuali `document_code`.
     *
     * Nomor DO tidak boleh berubah, jadi tidak ikut divalidasi (nilai lama
     * dikirim ulang oleh form dan `ReviseDeliveryOrderDTO` membuangnya) —
     * memvalidasinya berarti `Rule::unique` menolak nomor dokumen itu
     * sendiri.
     *
     * @return array<string, array<int, mixed>>
     */
    private function reviseRules(Request $request): array
    {
        return Arr::except($this->rules($request), ['document_code']);
    }

    /**
     * Form revisi untuk satu DO.
     *
     * `do_number` diisi dari dokumen yang direvisi, bukan
     * `GenerateDocumentNumber`: nomor itu harus tetap sama, jadi generate
     * angka baru hanya akan menampilkan nomor yang salah di layar.
     */
    public function revise(
        Request $request,
        int $id,
        FindDeliveryOrderQuery $query,
        GetDeliveryOrderFormOptionsQuery $options,
    ): Response {
        $delivery_order = $query->execute($id);

        abort_if(
            $delivery_order === null
                || $delivery_order->transaction_type !== TransactionType::DeliveryOrder->value,
            404,
            'Delivery order tidak ditemukan.',
        );

        // Form revisi hanya boleh dibuka pembuat dokumen.
        // `ReviseDeliveryOrderAction` sudah menegakkan ini saat submit, tapi
        // tanpa guard di sini user lain bisa membuka form milik orang lain
        // dan baru gagal setelah mengisi semuanya.
        abort_unless(
            (int) $delivery_order->created_by === (int) $request->user()->id,
            403,
            'Hanya pembuat dokumen yang dapat merevisi delivery order ini.',
        );

        return Inertia::render('DeliveryOrder/Create', [
            'do_number' => $delivery_order->transaction_code,
            'deliveryOrder' => $delivery_order,
            'options' => $options->execute(),
        ]);
    }

    public function updateRevision(
        Request $request,
        int $id,
        ReviseDeliveryOrderAction $revise_delivery_order,
    ) {
        $this->normalizeIds($request);

        $request->validate($this->reviseRules($request));

        // Termin & jatuh tempo dihitung ulang dari pelanggan di database,
        // sama seperti `store()` — angka lama tidak dipakai ulang karena
        // pelanggan bisa saja sudah berganti termin sejak dokumen dibuat.
        $customer = DB::table('customers')
            ->where('id', (int) $request->input('customer_id'))
            ->first(['term_payment']);

        $payment_term = (int) ($customer->term_payment ?? 0);
        $delivery_date = substr((string) $request->input('delivery_date'), 0, 10);
        $due_date = date('Y-m-d', strtotime($delivery_date . " +{$payment_term} days"));

        try {
            $revise_delivery_order->execute(
                $id,
                ReviseDeliveryOrderDTO::fromRequest(
                    $request,
                    $payment_term,
                    $due_date,
                    strtoupper(trim((string) $request->input('segment'))),
                ),
                (int) $request->user()->id,
                $this->needsBdApproval($request),
            );
        } catch (RuntimeException $exception) {
            // Harga hilang, promo tidak eligible, atau stok kurang: pesannya
            // memang untuk pengguna, jadi dikembalikan sebagai error form
            // alih-alih error 500.
            return back()->withErrors(['delivery_order' => $exception->getMessage()]);
        }

        return redirect(route('delivery-order.show', $id))
            ->with('success', 'Revisi delivery order tersimpan dan approval dimulai ulang dari awal.');
    }

    public function show(
        Request $request,
        int $id,
        FindDeliveryOrderQuery $query,
        GetApprovalDecisionContextQuery $approval_context,
        GetOneRoleFromUserQuery $roles,
    ) {
        $delivery_order = $query->execute($id);

        abort_if(
            $delivery_order === null
                || $delivery_order->transaction_type !== TransactionType::DeliveryOrder->value,
            404,
            'Delivery order tidak ditemukan.',
        );

        return Inertia::render('DeliveryOrder/Show', [
            'deliveryOrder' => $delivery_order,
            'approvalContext' => $approval_context->execute(
                $id,
                (int) $roles->execute((int) $request->user()->id)->id,
                TransactionType::DeliveryOrder->value,
                $request->user()->sub_role_id !== null
                    ? (int) $request->user()->sub_role_id
                    : null,
            ),
        ]);
    }

    public function getApprovals(int $id, GetDeliveryOrderApprovalsQuery $get_approvals): JsonResponse
    {
        return response()->json($get_approvals->execute($id));
    }

    /**
     * Pencarian barang untuk form — payload sesuai filter form (gudang,
     * pengiriman, pelanggan), bukan payload global `/api/product`.
     */
    public function getProducts(Request $request, GetDeliveryOrderProductsQuery $query): JsonResponse
    {
        return response()->json($query->execute([
            'search' => $request->string('search')->toString(),
            'company_id' => (int) $request->input('company_id'),
            'shipping_id' => (int) $request->input('shipping_id'),
            'sub_shipping_id' => $request->input('sub_shipping_id') !== null
                ? (int) $request->input('sub_shipping_id')
                : null,
            'customer_id' => (int) $request->input('customer_id'),
            'segment' => $request->input('segment'),
        ]));
    }

    public function getFormOptions(GetDeliveryOrderFormOptionsQuery $query): JsonResponse
    {
        return response()->json($query->execute());
    }
}
