<script setup lang="ts">
import { formatRupiah, formatDate } from "@/utils/format";

/**
 * Ringkasan total form DO. Semua angka sudah dihitung komposable dari harga
 * bruto + cascading promo + aturan PPN — sama seperti `DeliveryOrderCalculator`.
 *
 * Saat ada diskon promo, "Total Sebelum Diskon" (Σ harga awal × qty) dan
 * "Total Setelah Diskon" (grand total) ditampilkan berurutan supaya
 * nilainya jelas; tanpa diskon baris keduanya tetap "Total Harga".
 */
defineProps<{
    subTotal: number;
    totalDiscount: number;
    /** Total bruto sebelum diskon promo — hanya dipakai bila ada diskon. */
    grossBeforeDiscount: number;
    taxAmount: number;
    total: number;
    useTax: boolean;
    paymentTerm: number | string;
    dueDate: string | null;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div
            v-if="totalDiscount > 0"
            class="flex justify-between py-2 text-sm"
        >
            <span class="text-slate-500">Total Sebelum Diskon</span>
            <span class="font-medium text-slate-700">{{
                formatRupiah(grossBeforeDiscount)
            }}</span>
        </div>
        <div v-if="totalDiscount > 0" class="flex justify-between py-2 text-sm">
            <span class="text-slate-500">Diskon Promo</span>
            <span class="font-medium text-amber-600"
                >− {{ formatRupiah(totalDiscount) }}</span
            >
        </div>
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
            class="flex justify-between border-y border-slate-100 py-2.5 text-lg font-bold"
        >
            <span class="text-slate-700">
                {{ totalDiscount > 0 ? "Total Setelah Diskon" : "Total Harga" }}
            </span>
            <span class="text-emerald-500">{{ formatRupiah(total) }}</span>
        </div>
        <div class="mt-3 flex flex-col gap-2 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Termin Pelanggan</span>
                <span class="font-semibold text-slate-700"
                    >{{ paymentTerm }} HARI</span
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
