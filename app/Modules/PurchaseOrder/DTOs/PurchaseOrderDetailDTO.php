<?php

namespace App\Modules\PurchaseOrder\DTOs;

class PurchaseOrderDetailDTO
{
    /**
     * @param  string  $type  Kunci semantik, mis. `SUPPLIER`, `PO_DATE`, `USE_TAX`.
     *                        Ini yang ditulis ke kolom `transaction_details.type`
     *                        supaya detailnya bisa dicari/kelompokkan.
     * @param  mixed  $value  Selalu disimpan sebagai teks — kolom `value` bertipe string.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly mixed $value,
    ) {}
}
