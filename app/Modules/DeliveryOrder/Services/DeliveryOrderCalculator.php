<?php

namespace App\Modules\DeliveryOrder\Services;

use App\Modules\DeliveryOrder\DTOs\CreateDeliveryOrderDTO;
use App\Modules\DeliveryOrder\Queries\GetEligiblePromosQuery;
use RuntimeException;

/**
 * Sumber tunggal perhitungan nominal Delivery Order.
 *
 * Mengikuti aturan PPN `PurchaseOrderCalculator` (harga satuan bruto):
 *
 *   item.bruto = harga_akhir_satuan × quantity
 *   item.net   = useTax ? round(item.bruto / 1.11, 2) : item.bruto
 *
 *   transaction_items:
 *     base_price  = harga_akhir_satuan bersih   → `base_price`
 *     total_price = bruto baris                  → `total_price`
 *
 *   transactions:
 *     sub_total      = Σ item.net
 *     grand_total    = Σ item.bruto
 *     tax_amount     = grand_total − sub_total
 *     total_discount = Σ (harga awal − harga akhir) × quantity
 *
 * Selisih harga awal vs akhir persis jumlah diskon cascading promo per unit
 * (DELIVERY_ORDER.md §22), jadi `total_discount` tidak perlu dihitung dua
 * kali dari stage — hanya diturunkan dari harga.
 *
 * Semua angka dihitung di server; frontend hanya mengirim quantity, promo
 * pilihan, dan (kalau mode manual) harga manual.
 */
class DeliveryOrderCalculator
{
    /** Pengali PPN 11% (Indonesia). */
    private const PPN_DIVISOR = 1.11;

    public function __construct(
        private DeliveryOrderPriceResolver $prices,
        private DeliveryOrderPromoCalculator $promos,
        private GetEligiblePromosQuery $eligible_promos,
    ) {}

    /**
     * @param  string|null  $customer_segment  `customers.segment` pelanggan dokumen.
     *
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
     *         promo_product_id: int|null,
     *         use_manual_price: bool,
     *         unit_price_before_discount: float,
     *         unit_price_after_discount: float,
     *         stages: array<int, array<string, mixed>>
     *     }>
     * }
     *
     * @throws RuntimeException kalau harga tidak tersedia atau promo tidak
     *                          eligible untuk quantity yang dikirim.
     */
    public function compute(CreateDeliveryOrderDTO $dto, ?string $customer_segment): array
    {
        $productIds = collect($dto->items)->pluck('product_id')->unique()->values()->all();
        $promoConfigs = $this->eligible_promos->execute($productIds, $dto->customer_id);

        $lines = [];
        $subTotal = 0.0;
        $grandTotal = 0.0;
        $totalDiscount = 0.0;

        foreach ($dto->items as $item) {
            $product = $this->productName($item->product_id);

            // Harga daftar (product_prices) selalu dihitung: menjadi harga
            // jual di mode otomatis, dan lantai di mode manual — harga
            // manual wajib sama dengan atau lebih tinggi dari harga daftar,
            // tidak boleh turun darinya.
            $catalog = $this->prices->resolve(
                $item->product_id,
                $dto->shipping_id,
                $dto->sub_shipping_id,
                $customer_segment,
                $product,
            );

            $priceBefore = $item->use_manual_price
                ? $this->manualPrice($item->unit_price, $catalog, $product)
                : $catalog;

            $finalUnit = $priceBefore;
            $stages = [];

            if ($item->promo_product_id !== null) {
                $promo = $promoConfigs[$item->product_id.':'.$item->promo_product_id]
                    ?? throw new RuntimeException(
                        "Promo tidak berlaku untuk produk \"{$product}\" pada pelanggan ini."
                    );

                $applied = $this->promos->apply($promo, $priceBefore, $item->quantity);
                $finalUnit = $applied['final_unit_price'];
                $stages = $applied['stages'];
            }

            $gross = $this->round($finalUnit * $item->quantity);
            $net = $dto->use_tax ? $this->round($gross / self::PPN_DIVISOR) : $gross;
            $basePrice = $dto->use_tax
                ? $this->round($finalUnit / self::PPN_DIVISOR)
                : $finalUnit;

            $subTotal += $net;
            $grandTotal += $gross;
            $totalDiscount += $this->round(($priceBefore - $finalUnit) * $item->quantity);

            $lines[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'base_price' => $basePrice,
                'total_price' => $gross,
                'promo_product_id' => $item->promo_product_id,
                // Disimpan per baris supaya form revisi bisa menampilkan
                // kembali mode harga yang sama seperti saat dokumen dibuat.
                'use_manual_price' => $item->use_manual_price,
                'unit_price_before_discount' => $this->round($priceBefore),
                'unit_price_after_discount' => $this->round($finalUnit),
                'stages' => $stages,
            ];
        }

        return [
            'sub_total' => $this->round($subTotal),
            'tax_amount' => $this->round(max(0, $grandTotal - $subTotal)),
            'grand_total' => $this->round($grandTotal),
            'total_discount' => $this->round(max(0, $totalDiscount)),
            'lines' => $lines,
        ];
    }

    /**
     * Harga manual sebuah baris: wajib terisi dan tidak boleh lebih rendah
     * dari harga daftar untuk segmen/pengiriman yang sama — form hanya
     * mengizinkan harga naik, dan aturan yang sama ditegakkan di sini
     * supaya payload hasil suntingan tidak bisa menurunkan harga jual.
     */
    private function manualPrice(float $unitPrice, float $catalogPrice, string $product): float
    {
        $price = $this->round($unitPrice);

        if ($price <= 0) {
            throw new RuntimeException("Harga manual untuk produk \"{$product}\" belum diisi.");
        }

        if ($price < $this->round($catalogPrice)) {
            $min = number_format($catalogPrice, 2, ',', '.');

            throw new RuntimeException(
                "Harga manual untuk produk \"{$product}\" di bawah harga daftar (minimal Rp{$min}).",
            );
        }

        return $price;
    }

    private function productName(int $productId): string
    {
        static $names = [];

        return $names[$productId] ??= (string) \Illuminate\Support\Facades\DB::table('products')
            ->where('id', $productId)
            ->value('name');
    }

    private function round(float $value): float
    {
        return round($value, 2);
    }
}
