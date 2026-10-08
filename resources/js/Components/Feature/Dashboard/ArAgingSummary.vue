<script setup lang="ts">
import FinanceSectionCard from "./FinanceSectionCard.vue";
import { fmtRupiah } from "@/lib/utils";
import type { ArAgingBucket, ArAgingKey } from "@/types/ar-controller";

/**
 * Umur invoice yang belum lunas, dikelompokkan ke lima bucket.
 *
 * Angkanya datang dari `transactions.aging_days` yang diisi proses terjadwal;
 * selama kolom itu masih nol semua bucket tampil dengan nilai 0 supaya
 * tampilannya tidak perlu diubah lagi ketika prosesnya mulai berjalan.
 */
defineProps<{ buckets: ArAgingBucket[] }>();

const tone: Record<ArAgingKey, string> = {
    not_due: "border-slate-200 bg-slate-50 text-slate-600",
    d1_30: "border-amber-200 bg-amber-50 text-amber-700",
    d31_60: "border-orange-200 bg-orange-50 text-orange-700",
    d61_90: "border-rose-200 bg-rose-50 text-rose-700",
    d90_plus: "border-red-200 bg-red-50 text-red-700",
};
</script>

<template>
    <FinanceSectionCard
        title="Umur Invoice"
        subtitle="Faktur yang belum lunas, dikelompokkan dari kolom aging_days"
        badge="Read only"
    >
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
            <div
                v-for="bucket in buckets"
                :key="bucket.key"
                class="rounded-xl border p-4"
                :class="tone[bucket.key]"
            >
                <p class="text-xs font-medium opacity-80">
                    {{ bucket.label }}
                </p>
                <p class="mt-2 text-lg font-bold">
                    {{ fmtRupiah(bucket.value) }}
                </p>
                <p class="mt-0.5 text-xs opacity-70">
                    {{ bucket.count }} faktur
                </p>
            </div>
        </div>
    </FinanceSectionCard>
</template>
