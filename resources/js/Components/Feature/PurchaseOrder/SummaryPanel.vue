<script setup lang="ts">
import { formatRupiah, formatDate } from "@/utils/format";

defineProps<{
    subTotal: number;
    taxAmount: number;
    total: number;
    useTax: boolean;
    termOfPayment: number | string;
    dueDate: string | null;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex justify-between py-2 text-sm">
            <span class="text-slate-500">Sub Total</span>
            <span class="font-medium text-slate-700">{{
                formatRupiah(subTotal)
            }}</span>
        </div>
        <div v-if="useTax" class="flex justify-between py-2 text-sm">
            <span class="text-slate-500">PPN 11%</span>
            <span class="font-medium text-slate-700">{{
                formatRupiah(taxAmount)
            }}</span>
        </div>
        <div
            class="flex justify-between border-y border-slate-100 py-2.5 text-sm font-bold"
        >
            <span class="text-slate-700">Total Harga</span>
            <span class="text-[#0284c7]">{{ formatRupiah(total) }}</span>
        </div>
        <div class="mt-3 flex flex-col gap-2 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Term of Payment</span>
                <span class="font-semibold text-slate-700"
                    >{{ termOfPayment }} HARI</span
                >
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Jatuh Tempo</span>
                <span class="font-semibold text-slate-700">{{
                    dueDate ? formatDate(dueDate, false) : "-"
                }}</span>
            </div>
        </div>
        <slot />
    </div>
</template>
