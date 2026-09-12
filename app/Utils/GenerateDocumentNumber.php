<?php

namespace App\Utils;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

class GenerateDocumentNumber
{
    public static function generate(TransactionType $transactionType): string
    {
        $typeValue = $transactionType->value;
        $yearMonth = now()->format('ym'); // 2601

        $latestDocument = DB::table('transactions')
            ->where('transaction_type', $typeValue)
            ->where('transaction_code', 'like', "{$typeValue}-{$yearMonth}-%")
            ->orderByDesc('transaction_code')
            ->value('transaction_code');

        if ($latestDocument) {
            $lastIncrement = (int) substr($latestDocument, -6);
            $increment = str_pad($lastIncrement + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $increment = '000001';
        }

        return "{$typeValue}-{$yearMonth}-{$increment}";
    }
}
