<script setup lang="ts">
import { computed } from "vue";
import { NSpin, NSteps, NStep } from "naive-ui";
import type { StepsProps } from "naive-ui";
import DOMPurify from "dompurify";
import { normalizeApproval, overallApproval } from "@/utils/approval";
import { formatDate } from "@/utils/format";
import type { TransactionApproval } from "@/types/purchase-order";

// Theme override khusus n-steps di komponen ini saja.
// Tidak perlu NConfigProvider — prop `theme-overrides` per-komponen
// hanya berlaku untuk instance yang menerimanya.
// Indicator pakai warna biru brand, icon tetap bawaan n-step
// (check untuk finish, silang untuk error, nomor untuk wait/process).
const stepsThemeOverrides = {
    indicatorColorProcess: "#0284c7",
    indicatorColorFinish: "#0284c7",
    indicatorColorError: "#0284c7",
    indicatorColorWait: "#0284c7",
    indicatorBorderColorProcess: "#0284c7",
    indicatorBorderColorFinish: "#0284c7",
    indicatorBorderColorError: "#0284c7",
    indicatorBorderColorWait: "#0284c7",
    titleTextColorProcess: "#334155", // slate-700
    titleTextColorFinish: "#334155",
    titleTextColorError: "#334155",
    titleTextColorWait: "#94a3b8", // slate-400
    descriptionTextColorProcess: "#475569", // slate-600
    descriptionTextColorFinish: "#475569",
    descriptionTextColorError: "#475569",
    descriptionTextColorWait: "#94a3b8",
    splitorColor: "#e2e8f0", // slate-200, garis penghubung antar step
};

const props = defineProps<{
    approvals: TransactionApproval[];
    loading: boolean;
}>();

const toneText = {
    emerald: "text-emerald-700",
    amber: "text-amber-700",
    red: "text-red-700",
    violet: "text-violet-700",
    slate: "text-slate-500",
} as const;

// Background box untuk description (instruksi reject/revisi), warnanya
// mengikuti tone status biar keliatan konteksnya (merah = reject, dst).
const toneSoftBg = {
    emerald: "border-emerald-100 bg-emerald-50",
    amber: "border-amber-100 bg-amber-50",
    red: "border-red-100 bg-red-50",
    violet: "border-violet-100 bg-violet-50",
    slate: "border-slate-100 bg-slate-50",
} as const;

// description dari Quill = HTML, wajib disanitize sebelum di-render
// pakai v-html biar aman dari tag/script yang tidak diinginkan.
function sanitizeHtml(html?: string | null) {
    if (!html) return "";
    return DOMPurify.sanitize(html);
}

// Mapping status internal kita -> status bawaan n-step (wait | process | finish | error)
// Tiap n-step diberi status eksplisit per-item, jadi tidak mengandalkan logic `current` bawaan NSteps.
const stepStatusMap: Record<string, NonNullable<StepsProps["status"]>> = {
    APPROVED: "finish",
    PENDING: "process",
    NEED_REVISION: "error",
    UNKNOWN: "wait",
};

function stepStatus(status: TransactionApproval["status"]) {
    return stepStatusMap[normalizeApproval(status).key] ?? "wait";
}

const overall = computed(() => overallApproval(props.approvals));

function roleLabel(a: TransactionApproval) {
    if (!a.role) return "-";
    return a.sub_role ? `${a.role} – ${a.sub_role}` : a.role;
}
</script>

<template>
    <div>
        <!-- Header + status keseluruhan -->
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-800">
                Riwayat Persetujuan
            </h3>
            <span
                class="rounded-full px-3 py-1 text-xs font-medium"
                :class="[
                    toneText[overall.tone]
                ]"
            >
                {{ overall.label }}
            </span>
        </div>

        <div v-if="loading" class="flex justify-center py-8">
            <NSpin size="small" />
        </div>

        <div
            v-else-if="!approvals.length"
            class="rounded-xl border border-dashed border-slate-200 py-8 text-center text-sm text-slate-400"
        >
            Belum ada data persetujuan.
        </div>

        <!-- n-steps -->
        <NSteps
            v-else
            vertical
            :current="approvals.length"
        >
            <NStep
                v-for="a in approvals"
                :key="a.order"
                :status="stepStatus(a.status)"
            >
                <template #title>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                        <span class="text-xs font-medium text-slate-400">
                            Tahap {{ a.order }}
                        </span>
                        <span
                            class="text-sm font-semibold"
                            :class="toneText[normalizeApproval(a.status).tone]"
                        >
                            {{ normalizeApproval(a.status).label }}
                        </span>
                    </div>
                </template>

                <template #default>
                    <p class="truncate text-sm text-slate-700">
                        {{ roleLabel(a) }}
                    </p>
                    <p
                        v-if="a.proceed_by"
                        class="truncate text-xs text-slate-600"
                    >
                        Diproses oleh {{ a.proceed_by }}
                    </p>
                    <p v-if="a.proceed_at" class="text-xs text-slate-400">
                        {{ formatDate(a.proceed_at) }}
                    </p>

                    <!-- Isi Quill (HTML) berisi catatan/instruksi, misal alasan reject atau apa yang perlu direvisi -->
                    <div
                        v-if="a.description"
                        class="ql-content mt-2 rounded-lg border px-3 py-2 text-xs leading-relaxed text-slate-700"
                        :class="toneSoftBg[normalizeApproval(a.status).tone]"
                        v-html="sanitizeHtml(a.description)"
                    />
                </template>
            </NStep>
        </NSteps>
    </div>
</template>

<style scoped>
/*
  Fine-tuning layout yang tidak ada di token theme-overrides
  (ukuran indicator, jarak antar step, dsb).
  `scoped` memastikan style ini cuma berlaku di komponen ini,
  bukan global ke semua n-steps di aplikasi.
*/
:deep(.n-step-indicator) {
    width: 28px;
    height: 28px;
}

:deep(.n-step-content) {
    padding-bottom: 4px;
}

:deep(.n-step-splitor) {
    /* garis penghubung sedikit lebih tebal biar match desain lama */
    width: 1px;
}

/* Styling dasar buat HTML mentah dari Quill (biasanya tanpa class). */
.ql-content :deep(p) {
    margin-bottom: 0.25rem;
}
.ql-content :deep(p:last-child) {
    margin-bottom: 0;
}
.ql-content :deep(ul),
.ql-content :deep(ol) {
    margin: 0.25rem 0;
    padding-left: 1.1rem;
}
.ql-content :deep(ul) {
    list-style: disc;
}
.ql-content :deep(ol) {
    list-style: decimal;
}
.ql-content :deep(a) {
    text-decoration: underline;
}
.ql-content :deep(strong) {
    font-weight: 600;
}
</style>
