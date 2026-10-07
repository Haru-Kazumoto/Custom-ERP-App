<?php

namespace App\Modules\DeliveryOrder\Queries;

use App\Modules\Approval\DTOs\DecideApprovalDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Daftar dokumen Delivery Order (`/delivery-order/documents`).
 *
 * Bentuk baris dan filter identik dengan `GetPurchaseOrdersQuery`, tapi
 * membaca view `v_get_delivery_orders_with_approval` — view PO tetap
 * `WHERE transaction_type = 'PO'`, jadi memakai view PO di sini tidak akan
 * pernah mengembalikan baris DO.
 */
class GetDeliveryOrdersQuery
{
    public function execute(array $filter = []): LengthAwarePaginator
    {
        $query = DB::table('v_get_delivery_orders_with_approval as v')
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
                'tx.total_discount',
                'tx.created_by',
                'tx.updated_at as last_updated_at',
                'tx.description as document_description',
            ])
            ->latest('v.created_at');

        $status = (string) ($filter['status'] ?? '');
        if ($status !== '') {
            $query->where('v.current_approval_status', $status);
        }

        if (($search = trim((string) ($filter['search'] ?? ''))) !== '') {
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
            $decoded = json_decode((string) $item->details, true);
            $item->detail = $this->transformDetails(is_array($decoded) ? $decoded : []);
            // Kolom yang dipakai tabel daftar DO: pelanggan dan jenis
            // pengiriman. PO memakai `supplier` di slot yang sama.
            $item->detail['customer'] ??= $this->detailByType($decoded, 'CUSTOMER');
            $item->detail['delivery'] ??= $this->detailByType($decoded, 'DELIVERY');

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
     * Status yang bisa difilter di dropdown daftar DO — daftar yang sama
     * dengan PO karena nilai `current_approval_status` identik.
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
