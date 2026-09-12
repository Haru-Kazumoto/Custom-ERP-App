<?php

namespace App\Modules\PurchaseOrder\Queries;

use Illuminate\Support\Facades\DB;

class GetPurchaseOrdersQuery
{
    public function __construct() {}

    public function execute(array $filter)
    {
        $query = DB::table('v_get_purchase_orders_with_approval')->latest('created_at');

        if (!empty($filter['transaction_type'])) {
            $query->where('transaction_type', $filter['transaction_type']);
        }

        if (!empty($filter['status'])) {
            $query->where('current_approval_status', $filter['status']);
        }

        if (!empty($filter['date_from'])) {
            $query->whereDate('created_at', '>=', $filter['date_from']);
        }

        $paginator = $query->paginate(20);

        $paginator->getCollection()->transform(function ($item) {
            $item->detail = $this->transformDetails(json_decode($item->details));
            $item->is_fully_approved = $item->current_approval_status !== 'PENDING';

            return $item;
        });

        return $paginator;
    }

    private function transformDetails(array $details): array
    {
        return collect($details)
            ->mapWithKeys(function ($detail) {
                return [strtolower(str_replace(" ", "_", $detail->name)) => $detail->value];
            })
            ->toArray();
    }
}
