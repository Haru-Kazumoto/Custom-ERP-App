<script setup lang="ts">
import { BadgePercent } from "lucide-vue-next";
import { formatRupiah } from "@/utils/format";
import type { DeliveryOrderItemRow } from "@/types/delivery-order";

defineProps<{ items: DeliveryOrderItemRow[] }>();

/** Chip label per tahap diskon tersimpan (`transacton_item_discounts`). */
function stageLabel(stage: {
    discount_type: string;
    discount_value: string;
}): string {
    if (stage.discount_type.toUpperCase() === "PERCENTAGE") {
        return `${Number(stage.discount_value)}%`;
    }

    return formatRupiah(Number(stage.discount_value));
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

/** Diskon nominal per baris = (harga sebelum − sesudah) × qty. */
function lineDiscount(item: DeliveryOrderItemRow): number {
    if (item.unit_price_before === null || item.unit_price_after === null) {
        return 0;
    }

    return round2(
        (item.unit_price_before - item.unit_price_after) *
            Number(item.quantity),
    );
}
</script>

<template>
    <div>
        <!-- Desktop: tabel compact -->
        <div
            class="hidden overflow-x-auto rounded-xl border border-slate-100 sm:block"
        >
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Produk</th>
                        <th class="px-4 py-2.5 font-medium">Jumlah</th>
                        <th class="px-4 py-2.5 font-medium">Harga Satuan</th>
                        <th class="px-4 py-2.5 font-medium">Diskon</th>
                        <th class="px-4 py-2.5 text-right font-medium">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in items"
                        :key="item.id"
                        class="border-t border-slate-50"
                    >
                        <!-- Produk: kode + nama + tag -->
                        <td class="px-4 py-3">
                            <p class="text-xs text-slate-400">
                                {{ item.product_code ?? "-" }}
                            </p>
                            <p class="font-medium text-slate-700">
                                {{ item.product_name ?? "-" }}
                            </p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <span
                                    v-if="item.promo_name"
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600"
                                >
                                    <BadgePercent class="h-3 w-3" />
                                    {{ item.promo_name }}
                                </span>
                            </div>
                        </td>

                        <!-- Jumlah -->
                        <td class="px-4 py-3 text-slate-600">
                            {{ item.quantity }}
                            <span class="text-xs text-slate-400">
                                {{ item.product_unit }}
                            </span>
                        </td>

                        <!-- Harga satuan (final; coret = sebelum promo) -->
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-700">
                                {{
                                    formatRupiah(
                                        Number(item.unit_price_after ??
                                            item.unit_price),
                                    )
                                }}
                            </span>
                            <p
                                v-if="item.unit_price_before !== null"
                                class="text-xs text-amber-600"
                            >
                                normal:
                                <span class="line-through">
                                    {{ formatRupiah(item.unit_price_before) }}
                                </span>
                            </p>
                        </td>

                        <!-- Diskon: persentase per tahap + nominal -->
                        <td class="px-4 py-3">
                            <template v-if="item.discounts.length">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="stage in item.discounts"
                                        :key="stage.sequence"
                                        class="rounded bg-rose-50 px-1.5 py-0.5 text-xs font-medium text-rose-600"
                                    >
                                        {{ stageLabel(stage) }}
                                    </span>
                                </div>
                                <p
                                    class="mt-1 text-xs font-medium text-rose-600"
                                >
                                    − {{ formatRupiah(lineDiscount(item)) }}
                                </p>
                            </template>
                            <span v-else class="text-slate-300">—</span>
                        </td>

                        <!-- Total -->
                        <td
                            class="px-4 py-3 text-right font-semibold text-slate-800"
                        >
                            {{ formatRupiah(Number(item.total_price)) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile -->
        <div class="space-y-2 sm:hidden">
            <div
                v-for="item in items"
                :key="item.id"
                class="rounded-xl border border-slate-100 p-3"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-700">
                            {{ item.product_name ?? "-" }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ item.product_code ?? "-" }}
                        </p>
                    </div>
                    <span class="shrink-0 text-sm font-semibold text-slate-800"
                        >{{ formatRupiah(Number(item.total_price)) }}</span
                    >
                </div>

                <div class="mt-2 flex flex-wrap gap-1">
                    <span
                        v-if="item.promo_name"
                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600"
                    >
                        <BadgePercent class="h-3 w-3" /> {{ item.promo_name }}
                    </span>
                    <span
                        v-for="stage in item.discounts"
                        :key="stage.sequence"
                        class="rounded bg-rose-50 px-1.5 py-0.5 text-xs font-medium text-rose-600"
                    >
                        {{ stageLabel(stage) }}
                    </span>
                </div>

                <div
                    class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-50 pt-2 text-xs text-slate-500"
                >
                    <span>{{ item.quantity }} {{ item.product_unit }}</span>
                    <span>×</span>
                    <span>{{
                        formatRupiah(
                            Number(item.unit_price_after ?? item.unit_price),
                        )
                    }}</span>
                    <span
                        v-if="lineDiscount(item) > 0"
                        class="font-medium text-rose-600"
                    >
                        diskon − {{ formatRupiah(lineDiscount(item)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
