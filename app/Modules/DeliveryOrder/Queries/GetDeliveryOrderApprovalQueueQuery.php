<?php

namespace App\Modules\DeliveryOrder\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Antrean approval Delivery Order untuk satu role.
 *
 * Bentuk dan aturannya identik dengan `GetPurchaseOrderApprovalQueueQuery`
 * (di-clone, lalu dialihkan ke view DO): yang tampil hanya dokumen yang
 * langkahnya sedang berjalan milik role pemohon dan masih `PENDING`, sehingga
 * begitu sebuah langkah diputuskan dokumen berpindah antrean ke role
 * berikutnya tanpa dobel.
 *
 * View `v_get_delivery_orders_with_approval` sudah memfilter
 * `transaction_type = 'DO'`, dan filter tipe tetap ditegakkan eksplisit di
 * sini supaya perubahan view di masa depan tidak diam-diam mencampur DO ke
 * antrean PO (atau sebaliknya).
 */
class GetDeliveryOrderApprovalQueueQuery
{
    public function execute(string $roleName, array $filter = []): LengthAwarePaginator
    {
        $actionable = $this->actionableExpression($roleName);

        $query = DB::table('v_get_delivery_orders_with_approval as v')
            ->join('transactions as tx', 'tx.id', '=', 'v.id')
            ->where('v.transaction_type', 'DO')
            ->whereRaw($actionable[0], $actionable[1]);

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

        $paginator = $query
            ->select([
                'v.id',
                'v.transaction_code',
                'v.transaction_type',
                'v.payment_term',
                'v.created_at',
                'v.current_approval_order',
                'v.current_approval_status',
                'v.current_approval_role',
                'v.current_approval_sub_role',
                'v.current_approval_proceed_by',
                'v.current_approval_proceed_at',
                'v.current_approval_description',
                'v.details',
                'v.sub_total',
                'v.tax_amount',
                'v.grand_total',
                'tx.due_date',
                'tx.description',
                'tx.total_discount',
            ])
            ->selectRaw($actionable[0].' as can_decide', $actionable[1])
            ->orderByDesc('v.created_at')
            ->paginate(20)
            ->withQueryString();

        $paginator->getCollection()->transform(fn ($row) => $this->transform($row));

        return $paginator;
    }

    /**
     * Langkah yang sedang berjalan milik role ini dan belum diputuskan.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function actionableExpression(string $roleName): array
    {
        return [
            '(v.current_approval_role = ? and v.current_approval_status = ?)',
            [$roleName, 'PENDING'],
        ];
    }

    private function transform(object $row): object
    {
        $details = json_decode((string) $row->details, true);

        $row->customer = $this->detailValue($details, 'CUSTOMER');
        $row->delivery = $this->detailValue($details, 'DELIVERY');
        $row->can_decide = (bool) $row->can_decide;
        $row->current_approval_order = (int) $row->current_approval_order;
        $row->sub_total = (float) $row->sub_total;
        $row->tax_amount = (float) $row->tax_amount;
        $row->grand_total = (float) $row->grand_total;
        $row->total_discount = (float) $row->total_discount;

        unset($row->details);

        return $row;
    }

    private function detailValue(mixed $details, string $type): ?string
    {
        foreach (is_array($details) ? $details : [] as $detail) {
            if (is_array($detail) && ($detail['type'] ?? null) === $type) {
                return $detail['value'] !== null ? (string) $detail['value'] : null;
            }
        }

        return null;
    }
}
