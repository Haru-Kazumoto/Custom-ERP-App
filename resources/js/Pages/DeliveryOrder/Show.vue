<script setup lang="ts">
import { computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import { NAlert, NCard, NTag } from "naive-ui";
import { formatDate, formatRupiah } from "@/utils/format";
import { useApprovals } from "@/composables/useApprovals";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import Back from "@/Components/Common/Back.vue";
import ShowItemList from "@/Components/Feature/DeliveryOrder/ShowItemList.vue";
import ApprovalTimeline from "@/Components/Common/ApprovalTimeline.vue";
import ApprovalDecisionPanel from "@/Components/Feature/Approval/ApprovalDecisionPanel.vue";
import type { DeliveryOrderHead } from "@/types/delivery-order";
import type { ApprovalDecisionContext } from "@/types/approval";

const props = defineProps<{
    deliveryOrder: DeliveryOrderHead;
    approvalContext?: ApprovalDecisionContext | null;
    auth: { user: { id: number; name: string } };
}>();

const { approvals, loading, fetchApprovals } = useApprovals(
    props.deliveryOrder.id,
    { url: `/delivery-order/${props.deliveryOrder.id}/approvals` },
);

onMounted(fetchApprovals);

const details = computed(() => props.deliveryOrder.details ?? {});
const usePpn = details.value.ppn === "true";
const useManual = details.value.manual_price === "true";

/** Status terminal: dokumen menunggu diperbaiki pembuatnya. */
const NEED_REVISION = "NEED_REVISION";

const needsRevision = computed(
    () => props.approvalContext?.current?.status === NEED_REVISION,
);

const revisionReason = computed(
    () =>
        props.approvalContext?.steps.find(
            (step) => step.status === NEED_REVISION,
        )?.description ?? null,
);

const detailRows = computed(() => {
    const rows = [
        { label: "Pelanggan", value: details.value.customer },
        {
            label: "Segmen",
            value: details.value.segment ?? "ALL_SEGMENT",
        },
        { label: "Jenis Pengiriman", value: details.value.delivery },
        { label: "Sub Pengiriman", value: details.value.sub_delivery },
        { label: "Perusahaan", value: details.value.company },
        { label: "Gudang", value: details.value.warehouse },
        {
            label: "Tanggal Kirim",
            value: formatDate(details.value.delivery_date ?? null, false),
        },
        {
            label: "Termin",
            value: `${Number(props.deliveryOrder.payment_term) || 0} HARI`,
        },
        {
            label: "Jatuh Tempo",
            value: formatDate(props.deliveryOrder.due_date, false),
        },
    ];

    // Informasi opsional — hanya tampil bila pernah diisi di form.
    if (details.value.nomor_po_pelanggan) {
        rows.push({
            label: "Nomor PO Pelanggan",
            value: details.value.nomor_po_pelanggan,
        });
    }
    if (Number(details.value.cashback_pph_4) > 0) {
        rows.push({
            label: "Cashback + PPh 4%",
            value: formatRupiah(Number(details.value.cashback_pph_4)),
        });
    }
    if (Number(details.value.biaya_bongkar) > 0) {
        rows.push({
            label: "Biaya Bongkar",
            value: formatRupiah(Number(details.value.biaya_bongkar)),
        });
    }
    if (details.value.syarat_pembayaran) {
        rows.push({
            label: "Syarat Pembayaran",
            value: details.value.syarat_pembayaran,
        });
    }

    return rows;
});

/** Total bruto sebelum diskon promo = grand total + diskon tersimpan. */
const grossBeforeDiscount = computed(
    () =>
        Number(props.deliveryOrder.grand_total) +
        Number(props.deliveryOrder.total_discount || 0),
);

const hasDiscount = computed(
    () => Number(props.deliveryOrder.total_discount) > 0,
);
</script>

<template>
    <Head :title="`Delivery Order ${deliveryOrder.transaction_code}`" />

    <AppLayout>
        <div class="flex flex-col gap-5">
            <HeaderPage
                :title="deliveryOrder.transaction_code"
                subTitle="Detail Delivery Order"
            />

            <Back
                :to="route('delivery-order.index')"
                :options="{ preserveScroll: true, preserveState: true }"
            />

            <NAlert
                v-if="needsRevision"
                type="warning"
                :bordered="false"
                class="rounded-xl"
            >
                <template #header>
                    <span class="font-semibold"
                        >Delivery Order perlu direvisi</span
                    >
                </template>

                <p v-if="revisionReason" class="text-sm">
                    “{{ revisionReason }}”
                </p>
                <p v-else class="text-sm">
                    Approver meminta dokumen ini diperbaiki.
                </p>

                <p class="mt-2 text-sm">
                    Modul revisi Delivery Order belum tersedia — dokumen ini
                    ditandai perlu perbaikan oleh pembuat/approver terkait.
                </p>
            </NAlert>

            <!-- Grid: konten kiri (2 kolom), approval kanan (1 kolom) -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- ===== Kiri ===== -->
                <div class="flex flex-col gap-5 lg:col-span-2">
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
                                <NTag
                                    size="small"
                                    :bordered="false"
                                    class="bg-violet-50 text-violet-600"
                                >
                                    {{
                                        useManual ? "HARGA MANUAL" : "HARGA OTOMATIS"
                                    }}
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
                                    {{ deliveryOrder.transaction_code }}
                                </p>
                            </div>
                            <div
                                v-for="row in detailRows"
                                :key="row.label"
                            >
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
                                    {{ deliveryOrder.description || "-" }}
                                </p>
                            </div>
                        </div>
                    </NCard>

                    <!-- Item -->
                    <NCard :bordered="true" class="border-slate-200 shadow-sm">
                        <template #header>
                            <h3 class="text-base font-semibold text-slate-800">
                                Daftar Barang ({{ deliveryOrder.items.length }})
                            </h3>
                        </template>
                        <ShowItemList :items="deliveryOrder.items" />
                    </NCard>

                    <!-- Ringkasan total -->
                    <NCard :bordered="true" class="border-slate-200 shadow-sm">
                        <div
                            v-if="hasDiscount"
                            class="flex justify-between py-2 text-sm"
                        >
                            <span class="text-slate-500">
                                Total Sebelum Diskon
                            </span>
                            <span class="font-medium text-slate-700">{{
                                formatRupiah(grossBeforeDiscount)
                            }}</span>
                        </div>
                        <div
                            v-if="hasDiscount"
                            class="flex justify-between py-2 text-sm"
                        >
                            <span class="text-slate-500">Diskon Promo</span>
                            <span class="font-medium text-amber-600"
                                >−
                                {{
                                    formatRupiah(
                                        Number(deliveryOrder.total_discount),
                                    )
                                }}</span
                            >
                        </div>
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-slate-500">Sub Total</span>
                            <span class="font-medium text-slate-700">{{
                                formatRupiah(Number(deliveryOrder.sub_total))
                            }}</span>
                        </div>
                        <div
                            v-if="usePpn"
                            class="flex justify-between py-2 text-sm"
                        >
                            <span class="text-slate-500">PPN 11%</span>
                            <span class="font-medium text-slate-700">{{
                                formatRupiah(Number(deliveryOrder.tax_amount))
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between border-t border-slate-100 py-2.5 text-sm font-bold"
                        >
                            <span class="text-slate-700">
                                {{
                                    hasDiscount
                                        ? "Total Setelah Diskon"
                                        : "Grand Total"
                                }}
                            </span>
                            <span class="text-[#0284c7]">{{
                                formatRupiah(Number(deliveryOrder.grand_total))
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
                            :transaction-id="deliveryOrder.id"
                            :context="approvalContext ?? null"
                            document-label="Delivery Order"
                            :decision-url="
                                `/approvals/delivery-orders/${deliveryOrder.id}/decision`
                            "
                            :reload-props="['deliveryOrder', 'approvalContext']"
                            revision-hint="Pembuat Delivery Order akan menerima permintaan ini di halaman daftar dokumen (status Perlu revisi)."
                            @decided="fetchApprovals"
                        />
                    </NCard>

                    <NCard :bordered="true" class="border-slate-200 shadow-sm">
                        <ApprovalTimeline
                            :approvals="approvals"
                            :loading="loading"
                        />
                    </NCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
