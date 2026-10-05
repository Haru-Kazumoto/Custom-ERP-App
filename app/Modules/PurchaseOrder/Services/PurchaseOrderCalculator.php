<?php

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\DTOs\CreatePurchaseOrderDTO;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal untuk semua perhitungan nominal Purchase Order.
 *
 * Frontend mengirim harga satuan BRUTO (termasuk PPN) per baris; semua total
 * dihitung di sini, dengan aturan:
 *
 *   item.bruto = unit_price × quantity
 *   item.net   = useTax ? round(item.bruto / 1.11) : item.bruto
 *
 *   transaction_items:
 *     base_price  = round(unit_price / 1.11)  → harga satuan bersih
 *     total_price = unit_price × quantity      → total BRUTO baris
 *
 *   transactions:
 *     sub_total      = Σ item.net
 *     grand_total    = Σ item.bruto
 *     tax_amount     = grand_total − sub_total
 *     total_discount = Σ (harga katalog − harga terpakai) × quantity
 *
 * `sub_total + tax_amount = grand_total` selalu berlaku karena `tax_amount`
 * diturunkan dari `grand_total`, bukan dihitung terpisah.
 *
 * Hitungan ini wajib berada di server: kalau `sub_total` dipercaya dari
 * kiriman browser, nilainya bisa tidak cocok dengan
 * `Σ transaction_items.total_price`.
 */
class PurchaseOrderCalculator
{
    /** Pengali PPN 11% (Indonesia). */
    private const PPN_DIVISOR = 1.11;

    /**
     * @return array{
     *     sub_total: float,
     *     tax_amount: float,
     *     grand_total: float,
     *     total_discount: float,
     *     lines: array<int, array{
     *         product_id: int,
     *         quantity: int,
     *         base_price: float,
     *         total_price: float,
     *         trade_promo_id: int|null
     *     }>
     * }
     */
    public function compute(CreatePurchaseOrderDTO $dto): array
    {
        $useTax = $dto->use_tax;

        // Harga katalog diambil dari DB, bukan dari browser, supaya nilai diskon
        // tidak bisa direkayasa lewat request.
        $catalogPrices = $this->catalogPrices($dto);

        $lines = [];
        $subTotal = 0.0;
        $grandTotal = 0.0;
        $totalDiscount = 0.0;

        foreach ($dto->items as $item) {
            $quantity = $item->quantity;
            $gross = $item->unit_price * $quantity;
            $net = $useTax ? round($gross / self::PPN_DIVISOR, 2) : $gross;

            $basePrice = $useTax
                ? round($item->unit_price / self::PPN_DIVISOR, 2)
                : $item->unit_price;

            $subTotal += $net;
            $grandTotal += $gross;

            // Diskon hanya bermakna untuk baris yang memakai trade promo.
            if ($item->trade_promo_id !== null) {
                $catalogPrice = $catalogPrices[$item->product_id] ?? $item->unit_price;
                $totalDiscount += max(0, $catalogPrice - $item->unit_price) * $quantity;
            }

            $lines[] = [
                'product_id' => $item->product_id,
                'quantity' => $quantity,
                'base_price' => $basePrice,
                'total_price' => $gross,
                'trade_promo_id' => $item->trade_promo_id,
            ];
        }

        return [
            'sub_total' => round($subTotal, 2),
            'tax_amount' => round(max(0, $grandTotal - $subTotal), 2),
            'grand_total' => round($grandTotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'lines' => $lines,
        ];
    }

    /**
     * Harga katalog produk yang muncul di PO, keyed by product_id.
     *
     * @return array<int, float>
     */
    private function catalogPrices(CreatePurchaseOrderDTO $dto): array
    {
        $ids = collect($dto->items)
            ->pluck('product_id')
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('products')
            ->whereIn('id', $ids->all())
            ->pluck('price', 'id')
            ->map(fn ($price) => (float) $price)
            ->all();
    }
}
