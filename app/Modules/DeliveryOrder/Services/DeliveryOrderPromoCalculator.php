<?php

namespace App\Modules\DeliveryOrder\Services;

use RuntimeException;

/**
 * Kalkulator diskon promo Delivery Order — cascading, bukan akumulasi.
 *
 * Aturan legacy yang wajib dipertahankan (DELIVERY_ORDER.md §10–§19):
 *
 *   D1 = A  × P1/100            → A1 = A  − D1
 *   D2 = A1 × P2/100            → A2 = A1 − D2
 *   D3 = A2 × P3/100            → A3 = A2 − D3   (percentage_3)
 *      = A2 × manual_percentage → A3             (manual_type PERCENTAGE)
 *      = MIN(manual_value, A2)  → A3             (manual_type VALUE)
 *
 * Percentage tidak pernah dijumlahkan dulu: tiap tahap dihitung dari harga
 * hasil tahap sebelumnya. `manual_value` dibatasi `A2` supaya harga akhir
 * tidak pernah negatif.
 */
class DeliveryOrderPromoCalculator
{
    /** Pembulatan tiap tahap — kolom `transacton_item_discounts` decimal(15,2). */
    private const DECIMALS = 2;

    /** Jenis promo yang membebaskan diri dari validasi range quantity. */
    private const FLUSH_OUT = 'FLUSH_OUT';

    /**
     * @param  object  $promo  Konfigurasi dari `GetEligiblePromosQuery`.
     * @param  float  $unit_price  Harga satuan awal (A).
     *
     * @return array{
     *     final_unit_price: float,
     *     unit_discount: float,
     *     stages: array<int, array{
     *         sequence: int,
     *         discount_type: string,
     *         discount_value: float,
     *         source: string,
     *         amount_before_discount: float,
     *         amount_after_discount: float
     *     }>
     * }
     *
     * @throws RuntimeException kalau quantity di luar range promo atau
     *                          melebihi `base_quota`.
     */
    public function apply(object $promo, float $unit_price, int $quantity): array
    {
        $this->assertQuantityEligible($promo, $quantity);

        $before = $this->round($unit_price);
        $stages = [];

        // Tahap 1 & 2: murni persentase bertingkat.
        foreach ([1 => 'percentage_1', 2 => 'percentage_2'] as $sequence => $column) {
            $percentage = $promo->{$column} === null ? null : (float) $promo->{$column};

            if ($percentage === null || $percentage <= 0) {
                continue;
            }

            $after = $this->round($before - ($before * $percentage / 100));

            $stages[] = $this->stage($sequence, 'PERCENTAGE', $percentage, $column, $before, $after);
            $before = $after;
        }

        // Tahap 3: percentage_3, lalu manual_type, lalu tidak ada diskon sama sekali.
        $stage3 = $this->stageThree($promo, $before);

        if ($stage3 !== null) {
            $stages[] = $stage3;
            $before = $stage3['amount_after_discount'];
        }

        return [
            'final_unit_price' => $before,
            'unit_discount' => $this->round($unit_price - $before),
            'stages' => $stages,
        ];
    }

    /**
     * Eligibility quantity: FLUSH_OUT melewati range min/max, tapi
     * `base_quota` tetap berlaku untuk semua jenis promo (DELIVERY_ORDER.md §7.4).
     */
    private function assertQuantityEligible(object $promo, int $quantity): void
    {
        $name = (string) ($promo->promo_name ?? 'promo');

        if ($promo->base_quota !== null && $quantity > (int) $promo->base_quota) {
            throw new RuntimeException(
                "Jumlah {$quantity} melebihi kuota promo \"{$name}\" (maksimal {$promo->base_quota})."
            );
        }

        $isFlushOut = strtoupper((string) $promo->promo_type) === self::FLUSH_OUT;

        if ($isFlushOut) {
            return;
        }

        $min = $promo->min_qty === null ? null : (int) $promo->min_qty;
        $max = $promo->max_qty === null ? null : (int) $promo->max_qty;

        if ($min !== null && $quantity < $min) {
            throw new RuntimeException(
                "Jumlah {$quantity} kurang dari minimum promo \"{$name}\" (minimal {$min})."
            );
        }

        if ($max !== null && $quantity > $max) {
            throw new RuntimeException(
                "Jumlah {$quantity} melebihi maksimum promo \"{$name}\" (maksimal {$max})."
            );
        }
    }

    /** Tahap ketiga: percentage_3 → manual PERCENTAGE → manual VALUE → tanpa diskon. */
    private function stageThree(object $promo, float $before): ?array
    {
        if ($promo->percentage_3 !== null && (float) $promo->percentage_3 > 0) {
            $percentage = (float) $promo->percentage_3;

            return $this->stage(
                3,
                'PERCENTAGE',
                $percentage,
                'percentage_3',
                $before,
                $this->round($before - ($before * $percentage / 100)),
            );
        }

        $manualType = strtoupper((string) ($promo->manual_type ?? ''));

        if ($manualType === 'PERCENTAGE' && $promo->manual_percentage !== null) {
            $percentage = (float) $promo->manual_percentage;

            return $this->stage(
                3,
                'PERCENTAGE',
                $percentage,
                'manual_percentage',
                $before,
                $this->round($before - ($before * $percentage / 100)),
            );
        }

        if ($manualType === 'VALUE' && $promo->manual_value !== null) {
            $value = (float) $promo->manual_value;
            // Diskon tidak boleh melebihi harga setelah tahap 2.
            $discount = min($value, $before);

            return $this->stage(
                3,
                'VALUE',
                $value,
                'manual_value',
                $before,
                $this->round($before - $discount),
            );
        }

        return null;
    }

    private function stage(
        int $sequence,
        string $type,
        float $value,
        string $source,
        float $before,
        float $after,
    ): array {
        return [
            'sequence' => $sequence,
            'discount_type' => $type,
            'discount_value' => $this->round($value),
            'source' => $source,
            'amount_before_discount' => $before,
            'amount_after_discount' => $after,
        ];
    }

    private function round(float $value): float
    {
        return round($value, self::DECIMALS);
    }
}
