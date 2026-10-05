<script setup lang="ts">
import { computed, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { NAlert, NButton, NCard, NIcon, NTag } from "naive-ui";
import { Pencil } from "lucide-vue-next";
import { formatDate, formatRupiah } from "@/utils/format";
import { useApprovals } from "@/composables/useApprovals";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import Back from "@/Components/Common/Back.vue";
import ShowItemList from "@/Components/Feature/PurchaseOrder/ShowItemList.vue";
import ApprovalTimeline from "@/Components/Common/ApprovalTimeline.vue";
import ApprovalDecisionPanel from "@/Components/Feature/Approval/ApprovalDecisionPanel.vue";
import type { PoHead } from "@/types/purchase-order";
import type { ApprovalDecisionContext } from "@/types/approval";

const props = defineProps<{
    purchaseOrder: PoHead;
    // `can_decide` dihitung server dari `transaction_approvals`, jadi frontend
    // tidak menentukan sendiri dokumen mana yang boleh diputuskan.
    approvalContext?: ApprovalDecisionContext | null;
    auth: { user: { id: number; name: string } };
}>();

const { approvals, loading, fetchApprovals } = useApprovals(
    props.purchaseOrder.id,
);

onMounted(fetchApprovals);

const usePpn = props.purchaseOrder.details.ppn === "true";

/** Status terminal yang berarti dokumen menunggu diperbaiki oleh pembuatnya. */
const NEED_REVISION = "NEED_REVISION";

/**
 * Dokumen perlu revisi kalau langkah approval yang sedang berjalan berstatus
 * `NEED_REVISION`. Statusnya dibaca dari context approval, bukan dari
 * `transactions`, karena view memang menandai terminal di situ — jadi
 * frontend tidak perlu menebak dokumen mana yang sedang "macet".
 */
const needsRevision = computed(
    () => props.approvalContext?.current?.status === NEED_REVISION,
);

/**
 * Revisi hanya boleh oleh pembuat dokumen. `RevisePurchaseOrderAction`
 * menegakkan aturan yang sama (dan ditambah syarat status), jadi ini hanya
 * menentukan apakah tombolnya ditampilkan.
 */
const isCreator = computed(
    () => props.purchaseOrder.created_by === props.auth.user.id,
);

const canRevise = computed(() => needsRevision.value && isCreator.value);

/** Alasan revisi terakhir, untuk ditampilkan di banner. */
const revisionReason = computed(
    () =>
        props.approvalContext?.steps.find(
            (step) => step.status === NEED_REVISION,
        )?.description ?? null,
);

/** Riwayat approval yang sudah diarsipkan oleh revisi sebelumnya. */
const revisionHistory = computed(
    () => props.approvalContext?.revision_history ?? [],
);

function revisePo() {
    router.get(route("purchase-order.revise", props.purchaseOrder.id));
}

// item baris info detail (label → value) biar template ringkas
const detailRows = [
    { label: "Pemasok", value: props.purchaseOrder.details.pemasok },
    { label: "Perusahaan", value: props.purchaseOrder.details.alokasi },
    {
        label: "Jenis Pengiriman",
        value: props.purchaseOrder.details.jenis_pengiriman,
    },
    {
        label: "Tanggal PO",
        value: formatDate(props.purchaseOrder.details.tanggal_po),
    },
    {
        label: "Tanggal Kirim",
        value: formatDate(props.purchaseOrder.details.tanggal_kirim),
    },
    { label: "Ekspedisi", value: props.purchaseOrder.details.transportasi },
    { label: "Nomor Polisi", value: props.purchaseOrder.details.nomor_polisi },
    {
        label: "Harga Angkutan",
        value: formatRupiah(Number(props.purchaseOrder.details.harga_angkutan)),
    },
];
</script>

<template>
    <Head :title="`Purchase Order ${purchaseOrder.transaction_code}`" />

    <AppLayout>
        <div class="flex flex-col gap-5">
            <HeaderPage
                :title="purchaseOrder.transaction_code"
                subTitle="Detail Purchase Order"
            >
                <template #action>
                    <!--
                        Aksi revisi hanya untuk pemilik dokumen yang statusnya
                        NEED_REVISION. Approver yang memberi revisi dan creator
                        lain tidak melihat tombol ini.
                    -->
                    <NButton
                        v-if="canRevise"
                        type="primary"
                        class="bg-[#0284c7] hover:bg-[#0369a1]"
                        @click="revisePo"
                    >
                        <template #icon>
                            <NIcon :component="Pencil" />
                        </template>

                        Revisi PO
                    </NButton>
                </template>
            </HeaderPage>

                <Back
                    :to="
                        needsRevision
                            ? route('purchase-order.revisions')
                            : route('purchase-order.index')
                    "
                    :options="{
                        preserveScroll: true,
                        preserveState: true,
                    }"
                />

                <!--
                    Banner revisi. Alasan dari approver ditampilkan apa adanya
                    karena itu instruksi yang harus dikerjakan creator; diulang
                    supaya tidak harus membuka halaman approval.
                -->
                <NAlert
                    v-if="needsRevision"
                    type="warning"
                    :bordered="false"
                    class="rounded-xl"
                >
                    <template #header>
                        <span class="font-semibold"
                            >Purchase Order perlu direvisi</span
                        >
                    </template>

                    <p v-if="revisionReason" class="text-sm">
                        “{{ revisionReason }}”
                    </p>
                    <p v-else class="text-sm">
                        Approver meminta dokumen ini diperbaiki.
                    </p>

                    <p v-if="canRevise" class="mt-2 text-sm">
                        Perbaiki isian yang salah, lalu kirim ulang. Nomor PO
                        tetap sama dan approval dimulai ulang dari Finance.
                    </p>
                    <p v-else class="mt-2 text-sm">
                        Hanya pembuat dokumen yang dapat merevisi.
                    </p>
                </NAlert>

                <!-- Grid: konten kiri (2 kolom), approval kanan (1 kolom) -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- ===== Kiri ===== -->
                <div class="flex flex-col gap-5 lg:col-span-2">
                    <!-- Info dokumen -->
                    <NCard :bordered="true" class="border-slate-200 shadow-sm">
                        <template #header>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    class="text-base font-semibold text-slate-800"
                                >
                                    Informasi Dokumen
                                </h3>
                                <NTag
                                    size="small"
                                    :bordered="false"
                                    :class="
                                        usePpn
                                            ? 'bg-emerald-50 text-emerald-600'
                                            : 'bg-rose-50 text-rose-600'
                                    "
                                >
                                    {{ usePpn ? "PPN" : "NON-PPN" }}
                                </NTag>
                            </div>
                        </template>

                        <div
                            class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2"
                        >
                            <div>
                                <p class="text-xs text-slate-400">
                                    Kode Transaksi
                                </p>
                                <p class="text-sm font-medium text-slate-700">
                                    {{ purchaseOrder.transaction_code }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400">
                                    Jatuh Tempo
                                </p>
                                <p class="text-sm font-medium text-slate-700">
                                    {{
                                        formatDate(
                                            purchaseOrder.due_date,
                                            false,
                                        )
                                    }}
                                </p>
                            </div>
                            <div v-for="row in detailRows" :key="row.label">
                                <p class="text-xs text-slate-400">
                                    {{ row.label }}
                                </p>
                                <p class="text-sm font-medium text-slate-700">
                                    {{ row.value || "-" }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-slate-400">Catatan</p>
                                <p class="text-sm text-slate-700">
                                    {{ purchaseOrder.description || "-" }}
                                </p>
                            </div>
                        </div>
                    </NCard>

                    <!-- Item -->
                    <NCard :bordered="true" class="border-slate-200 shadow-sm">
                        <template #header>
                            <h3 class="text-base font-semibold text-slate-800">
                                Daftar Barang ({{ purchaseOrder.items.length }})
                            </h3>
                        </template>
                        <ShowItemList :items="purchaseOrder.items" />
                    </NCard>

                    <!-- Ringkasan total -->
                    <NCard :bordered="true" class="border-slate-200 shadow-sm">
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-slate-500">Sub Total</span>
                            <span class="font-medium text-slate-700">{{
                                formatRupiah(Number(purchaseOrder.sub_total))
                            }}</span>
                        </div>
                        <div
                            v-if="Number(purchaseOrder.total_discount) > 0"
                            class="flex justify-between py-2 text-sm"
                        >
                            <span class="text-slate-500">Diskon</span>
                            <span class="font-medium text-amber-600"
                                >−
                                {{
                                    formatRupiah(
                                        Number(purchaseOrder.total_discount),
                                    )
                                }}</span
                            >
                        </div>
                        <div
                            v-if="usePpn"
                            class="flex justify-between py-2 text-sm"
                        >
                            <span class="text-slate-500">PPN 11%</span>
                            <span class="font-medium text-slate-700">{{
                                formatRupiah(Number(purchaseOrder.tax_amount))
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between border-t border-slate-100 py-2.5 text-sm font-bold"
                        >
                            <span class="text-slate-700">Grand Total</span>
                            <span class="text-[#0284c7]">{{
                                formatRupiah(Number(purchaseOrder.grand_total))
                            }}</span>
                        </div>
                    </NCard>
                </div>

                <!-- ===== Kanan: Approval ===== -->
                <div class="flex flex-col gap-5 lg:col-span-1">
                    <NCard
                        :bordered="true"
                        class="border-slate-200 shadow-sm"
                    >
                        <ApprovalDecisionPanel
                            :transaction-id="purchaseOrder.id"
                            :context="approvalContext ?? null"
                            @decided="fetchApprovals"
                        />
                    </NCard>

                    <NCard :bordered="true" class="border-slate-200 shadow-sm">
                        <ApprovalTimeline
                            :approvals="approvals"
                            :loading="loading"
                        />
                    </NCard>

                    <!--
                        Riwayat approval yang sudah diarsipkan. Tanpa ini, begitu
                        PO direvisi, approval sebelumnya hilang dari halaman dan
                        tanpa ada jejak kenapa dokumen diubah berapa kali.
                    -->
                    <NCard
                        v-if="revisionHistory.length > 0"
                        :bordered="true"
                        class="border-slate-200 shadow-sm"
                    >
                        <template #header>
                            <h3
                                class="text-base font-semibold text-slate-800"
                            >
                                Riwayat Revisi
                            </h3>
                        </template>

                        <ol class="flex flex-col gap-3">
                            <li
                                v-for="entry in revisionHistory"
                                :key="`${entry.archived_at}-${entry.order}`"
                                class="border-l-2 border-slate-100 pl-3"
                            >
                                <p
                                    class="text-sm font-medium text-slate-700"
                                >
                                    {{ entry.role ?? "Tanpa role" }} —
                                    {{ entry.status }}
                                </p>
                                <p
                                    v-if="entry.description"
                                    class="text-xs text-slate-500"
                                >
                                    {{ entry.description }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ entry.proceed_by ?? "System" }},
                                    {{ formatDate(entry.proceed_at) }} ·
                                    diarsipkan
                                    {{ formatDate(entry.archived_at) }}
                                </p>
                            </li>
                        </ol>
                    </NCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
