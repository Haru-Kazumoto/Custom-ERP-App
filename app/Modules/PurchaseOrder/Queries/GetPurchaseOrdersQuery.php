<?php

namespace App\Modules\PurchaseOrder\Queries;

use App\Modules\Approval\DTOs\DecideApprovalDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GetPurchaseOrdersQuery
{
    /**
     * Halaman daftar dokumen (`/purchase-orders/documents`) dan daftar revisi
     * (`/purchase-orders/revisions`) memakai query yang sama; keduanya bedanya
     * cuma filter.
     *
     * `status` dicocokkan ke `current_approval_status`, yaitu status **langkah
     * approval yang sedang berjalan** menurut view — bukan status dokumen.
     * Konsekuensinya `NEED_REVISION` menandai dokumen yang sedang menunggu
     * diperbaiki, dan itulah yang dipakai halaman Revisi PO.
     */
    public function execute(array $filter = []): LengthAwarePaginator
    {
        $query = DB::table('v_get_purchase_orders_with_approval as v')
            ->join('transactions as tx', 'tx.id', '=', 'v.id')
            ->select([
                'v.id',
                'v.transaction_code',
                'v.transaction_type',
                'v.payment_term',
                'v.sub_total',
                'v.tax_amount',
                'v.grand_total',
                'v.created_at',
                'v.current_approval_order',
                'v.current_approval_status',
                'v.current_approval_description',
                'v.current_approval_role',
                'v.current_approval_proceed_by',
                'v.current_approval_proceed_at',
                'v.details',
                // View tidak mengexpos kolom ini, jadi diambil dari `transactions`.
                'tx.total_discount',
                // Frontend butuh tahu apakah baris ini boleh direvisi. Penentu
                // sebenarnya ada di server (`RevisePurchaseOrderAction`), tapi
                // menyorotkan baris yang tidak bisa diklik membuat user mencari
                // tombol yang memang tidak akan bekerja.
                'tx.created_by',
                'tx.updated_at as last_updated_at',
                'tx.description as document_description',
            ])
            ->latest('v.created_at');

        if (! empty($filter['transaction_type'])) {
            $query->where('v.transaction_type', $filter['transaction_type']);
        }

        $status = (string) ($filter['status'] ?? '');
        if ($status !== '') {
            $query->where('v.current_approval_status', $status);
        }

        if (($search = trim((string) ($filter['search'] ?? ''))) !== '') {
            // `details` di view berupa JSON array dari JSON_ARRAYAGG, jadi
            // pencarian nama cukup mencocokkan isi JSON-nya. Kode dokumen tetap
            // lewat kolom biasa supaya bisa memakai indeks.
            $query->where(function ($inner) use ($search) {
                $inner->where('v.transaction_code', 'like', "%{$search}%")
                    ->orWhere('v.details', 'like', "%{$search}%");
            });
        }

        if (! empty($filter['date_from'])) {
            $query->whereDate('v.created_at', '>=', $filter['date_from']);
        }

        if (! empty($filter['date_to'])) {
            $query->whereDate('v.created_at', '<=', $filter['date_to']);
        }

        $paginator = $query->paginate(20)->withQueryString();

        $paginator->getCollection()->transform(function ($item) {
            // `details` bisa null karena LEFT JOIN di view, dan `json_decode`
            // mengembalikan null untuk payload JSON yang tidak valid.
            $decoded = json_decode((string) $item->details, true);
            $item->detail = $this->transformDetails(is_array($decoded) ? $decoded : []);
            $item->detail['supplier'] ??= $this->detailByType(
                $decoded,
                'SUPPLIER',
            );

            unset($item->details);

            return $item;
        });

        return $paginator;
    }

    private function transformDetails(?array $details): array
    {
        return collect($details ?: [])
            ->filter(fn ($detail) => is_array($detail) && isset($detail['name']))
            ->mapWithKeys(fn ($detail) => [
                strtolower(str_replace(' ', '_', $detail['name'])) => $detail['value'],
            ])
            ->toArray();
    }

    private function detailByType(mixed $details, string $type): ?string
    {
        foreach (is_array($details) ? $details : [] as $detail) {
            if (is_array($detail) && ($detail['type'] ?? null) === $type) {
                return $detail['value'] !== null ? (string) $detail['value'] : null;
            }
        }

        return null;
    }

    /**
     * Status approval yang bisa difilter di UI daftar dokumen.
     *
     * Nilai `value` dikirim apa adanya ke `current_approval_status`, jadi harus
     * memakai huruf besar persis seperti yang tersimpan di database — bukan
     * label bahasa Indonesia.
     *
     * Dikembalikan sebagai daftar `{value, label}`, bukan associative array.
     * Bentuk associative akan menjadi JSON object, sementara `NSelect` di
     * frontend melakukan `.map()` pada prop ini — object akan gagal saat render
     * dengan "statusOptions.map is not a function".
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function documentStatusOptions(): array
    {
        return [
            [
                'value' => DecideApprovalDTO::STATUS_APPROVED,
                'label' => 'Selesai disetujui',
            ],
            [
                'value' => DecideApprovalDTO::STATUS_NEED_REVISION,
                'label' => 'Perlu revisi',
            ],
        ];
    }
}
