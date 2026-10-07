<script setup lang="ts">
import { computed, ref } from "vue";
import { NButton, NInput, NModal, NTag } from "naive-ui";
import { useApprovalDecision } from "@/composables/useApprovalDecision";
import { normalizeApproval } from "@/utils/approval";
import type {
    ApprovalDecisionContext,
    ApprovalDecisionStatus,
} from "@/types/approval";

const props = withDefaults(
    defineProps<{
        transactionId: number;
        context: ApprovalDecisionContext | null;
        documentLabel?: string;
        decisionUrl?: string;
        reloadProps?: string[];
        revisionHint?: string;
    }>(),
    {
        documentLabel: "Purchase Order",
        decisionUrl: undefined,
        reloadProps: undefined,
        revisionHint:
            "Pembuat Purchase Order akan menerima permintaan ini di halaman Revisi PO dengan nomor yang sama.",
    },
);

// Setelah keputusan tersimpan, parent wajib memuat ulang `ApprovalTimeline`.
// `router.reload({ only: [...] })` di dalam composable hanya menyegarkan props
// Inertia; timeline diambil lewat axios di `onMounted` sehingga tidak ikut
// ter-refresh dan akan menampilkan data lama.
const emit = defineEmits<{ (e: "decided", status: ApprovalDecisionStatus): void }>();

const { submitting, error, decide } = useApprovalDecision(props.transactionId, {
    decisionUrl: props.decisionUrl,
    reloadProps: props.reloadProps,
});

const mode = ref<ApprovalDecisionStatus | null>(null);
const reason = ref("");
const confirmApprove = ref(false);

const askReason = (status: ApprovalDecisionStatus) => {
    error.value = null;
    reason.value = "";
    mode.value = status;
};

const askApprove = () => {
    error.value = null;
    // `mode` diisi juga di sini supaya `submit()` punya status yang dikirim;
    // `confirmApprove` hanya menentukan modal mana yang tampil.
    mode.value = "APPROVED";
    reason.value = "";
    confirmApprove.value = true;
};

const close = () => {
    mode.value = null;
    confirmApprove.value = false;
    reason.value = "";
};

const modeLabel = computed(() => {
    if (mode.value === "NEED_REVISION")
        return `Minta Revisi ${props.documentLabel}`;
    return `Setujui ${props.documentLabel}`;
});

// `NEED_REVISION` selalu butuh alasan: kalau kosong, pemohon tidak akan tahu
// apa yang harus diperbaiki, dan dokumennya menggantung di daftar revisi.
// `APPROVED` boleh tanpa catatan.
const reasonInvalid = computed(
    () =>
        mode.value === "NEED_REVISION" && reason.value.trim() === "",
);

/** Rantai berhenti: ada langkah yang perlu direvisi. */
const isChainStopped = computed(
    () => props.context?.current?.status === "NEED_REVISION",
);

/** Tidak ada langkah PENDING lagi, jadi tidak ada yang bisa dilanjutkan. */
const isFinalApproval = computed(
    () =>
        isChainStopped.value ||
        !props.context?.steps.some((step) => step.status === "PENDING"),
);

async function submit() {
    if (mode.value === null) return;
    if (reasonInvalid.value) return;

    const ok = await decide(mode.value, reason.value);

    // Modal tetap terbuka kalau gagal supaya pesan server (mis. konflik dengan
    // keputusan orang lain) tetap terlihat.
    if (ok) {
        emit("decided", mode.value);
        close();
    }
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") close();
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <h3 class="text-base font-semibold text-slate-800">
            Keputusan Approval
        </h3>

        <!-- Belum ada konteks approval (dokumen tanpa alur / bukan PO) -->
        <p
            v-if="!context"
            class="rounded-xl border border-dashed border-slate-200 py-6 text-center text-sm text-slate-400"
        >
            Dokumen ini tidak memiliki alur approval.
        </p>

        <template v-else>
            <!-- Giliran milik pemohon -->
            <template v-if="context.can_decide">
                <p class="text-sm text-slate-600">
                    Giliran Anda sebagai
                    <span class="font-semibold text-slate-800">
                        {{ context.current?.role ?? "—" }}
                    </span>
                    untuk Tahap {{ context.current?.order }}.
                </p>

                <div class="flex flex-col gap-2">
                    <NButton
                        type="primary"
                        :loading="submitting === 'APPROVED'"
                        :disabled="submitting !== null"
                        @click="askApprove"
                    >
                        Setujui
                    </NButton>

                    <NButton
                        :disabled="submitting !== null"
                        @click="askReason('NEED_REVISION')"
                    >
                        Perlu Revisi
                    </NButton>
                </div>
            </template>

            <!-- Sudah diputuskan oleh pemohon -->
            <div
                v-else-if="context.my_decision"
                class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <NTag
                        size="small"
                        :bordered="false"
                        :class="{
                            'bg-emerald-50 text-emerald-600':
                                context.my_decision.status === 'APPROVED',
                            'bg-violet-50 text-violet-600':
                                context.my_decision.status === 'NEED_REVISION',
                        }"
                    >
                        {{ normalizeApproval(context.my_decision.status).label }}
                    </NTag>
                    <span class="text-xs text-slate-500">
                        Telah diproses
                    </span>
                </div>

                <p
                    v-if="context.my_decision.description"
                    class="mt-2 text-sm text-slate-600"
                >
                    {{ context.my_decision.description }}
                </p>

                <p
                    v-if="!isFinalApproval"
                    class="mt-2 text-xs text-slate-400"
                >
                    Tidak ada aksi lagi dari Anda pada dokumen ini.
                </p>
            </div>

            <!-- Menunggu role lain -->
            <div
                v-else
                class="rounded-xl border border-dashed border-slate-200 px-3 py-3 text-sm text-slate-500"
            >
                <template v-if="isChainStopped">
                    Rantai approval berhenti di Tahap
                    {{ context.current?.order }} ({{
                        normalizeApproval(context.current?.status).label
                    }}).
                </template>
                <template v-else>
                    Menunggu keputusan
                    <span class="font-semibold text-slate-700">
                        {{ context.current?.role ?? "role lain" }}
                    </span>
                    di Tahap {{ context.current?.order }}.
                </template>
            </div>
        </template>

        <!-- Konfirmasi approve: tanpa alasan, jadi cukup dialog konfirmasi -->
        <NModal
            :show="confirmApprove"
            preset="card"
            :title="modeLabel"
            class="w-full max-w-md"
            :mask-closable="!submitting"
            @keydown="onKeydown"
        >
            <p class="text-sm text-slate-600">
                {{ documentLabel }} akan diteruskan ke tahap approval
                berikutnya. Tindakan ini tidak bisa dibatalkan.
            </p>

            <p
                v-if="error"
                class="mt-3 text-sm text-red-600"
            >
                {{ error }}
            </p>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <NButton :disabled="submitting !== null" @click="close">
                        Batal
                    </NButton>
                    <NButton
                        type="primary"
                        :loading="submitting === 'APPROVED'"
                        @click="submit"
                    >
                        Ya, Setujui
                    </NButton>
                </div>
            </template>
        </NModal>

        <!-- Perlu revisi: alasan wajib -->
        <NModal
            :show="mode !== null && mode !== 'APPROVED'"
            preset="card"
            :title="modeLabel"
            class="w-full max-w-md"
            :mask-closable="!submitting"
            @keydown="onKeydown"
        >
            <p class="mb-3 text-sm text-slate-600">
                Alasan wajib diisi dan akan tampil di riwayat persetujuan.
                {{ revisionHint }}
            </p>

            <NInput
                v-model:value="reason"
                type="textarea"
                :rows="4"
                maxlength="1000"
                show-count
                :disabled="submitting !== null"
                placeholder="Contoh: kuantiti pada baris 2 perlu dikonfirmasi"
                @keydown.enter.exact.prevent="submit"
            />

            <p
                v-if="error"
                class="mt-3 text-sm text-red-600"
            >
                {{ error }}
            </p>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <NButton :disabled="submitting !== null" @click="close">
                        Batal
                    </NButton>
                    <NButton
                        type="warning"
                        :loading="submitting !== null"
                        :disabled="reasonInvalid"
                        @click="submit"
                    >
                        Kirim Permintaan Revisi
                    </NButton>
                </div>
            </template>
        </NModal>
    </div>
</template>