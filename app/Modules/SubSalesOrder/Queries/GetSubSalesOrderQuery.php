<?php

namespace App\Modules\SubSalesOrder\Queries;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

class GetSubSalesOrderQuery
{
    public function execute(?array $filters)
    {
        $query = DB::table("transactions as tx")
            ->selectRaw("
                tx.id, 
                tx.transaction_code,
                tx.created_at,
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'name', td.name, 
                        'value', td.value
                    )
                ) AS detail
            ")
            ->leftJoin('transaction_details as td', 'tx.id', '=', 'td.transaction_id')
            ->where('tx.transaction_type', '=', TransactionType::SubSalesOrder->value)
            ->groupByRaw("tx.id, tx.transaction_code, tx.created_at")
            ->latest('tx.created_at')
            ->paginate(15);

        $query->getCollection()->transform(function ($item) {
            $data = (object) array_merge(
                (array) $item,
                $this->transformDetails(json_decode($item->detail, true) ?? [])
            );

            unset($data->detail);

            return $data;
        });

        return $query;
    }

    private function transformDetails(array $details): array
    {
        return collect($details)
            ->mapWithKeys(function ($detail) {
                if (!is_array($detail) || !isset($detail['name'])) {
                    return [];
                }

                return [
                    strtolower(str_replace(' ', '_', $detail['name'])) => $detail['value'] ?? null,
                ];
            })
            ->toArray();
    }
}
