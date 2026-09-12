<script setup lang="ts">
import { BadgePercent } from "lucide-vue-next";
import { formatRupiah } from "@/utils/format";
import type { PoItem } from "@/types/purchase-order";

defineProps<{ items: PoItem[] }>();
</script>

<template>
    <div>
        <!-- Desktop -->
        <div
            class="hidden overflow-x-auto rounded-xl border border-slate-100 sm:block"
        >
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Produk</th>
                        <th class="px-4 py-2.5 font-medium">Kategori</th>
                        <th class="px-4 py-2.5 text-center font-medium">Qty</th>
                        <th class="px-4 py-2.5 text-right font-medium">
                            Harga Satuan
                        </th>
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
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-700">
                                {{ item.product_name }}
                            </p>
                            <div
                                v-if="item.trade_promo"
                                class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600"
                            >
                                <BadgePercent class="h-3 w-3" />
                                {{ item.trade_promo }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ item.product_category.replace("_", " ") }}
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600">
                            {{ item.quantity }} 
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-medium text-slate-700">{{
                                formatRupiah(Number(item.base_price))
                            }}</span>
                            <p
                                v-if="item.trade_promo_price"
                                class="text-xs text-amber-600"
                            >
                                promo:
                                {{
                                    formatRupiah(Number(item.trade_promo_price))
                                }}
                            </p>
                        </td>
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
                            {{ item.product_name }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ item.product_category.replace("_", " ") }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 text-sm font-semibold text-slate-800"
                        >{{ formatRupiah(Number(item.total_price)) }}</span
                    >
                </div>
                <div
                    v-if="item.trade_promo"
                    class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600"
                >
                    <BadgePercent class="h-3 w-3" /> {{ item.trade_promo }}
                </div>
                <div
                    class="mt-2 flex items-center gap-3 border-t border-slate-50 pt-2 text-xs text-slate-500"
                >
                    <span>{{ item.quantity }}</span>
                    <span>×</span>
                    <span>{{ formatRupiah(Number(item.base_price)) }}</span>
                    <span v-if="item.trade_promo_price" class="text-amber-600">
                        (promo
                        {{ formatRupiah(Number(item.trade_promo_price)) }})
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
