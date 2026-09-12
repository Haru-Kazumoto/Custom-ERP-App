<script setup lang="ts">
import { onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import { NCard, NTag } from "naive-ui";
import { formatDate, formatRupiah } from "@/utils/format";
import { useApprovals } from "@/composables/useApprovals";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import Back from "@/Components/Common/Back.vue";
import ShowItemList from "@/Components/Feature/PurchaseOrder/ShowItemList.vue";
import ApprovalTimeline from "@/Components/Common/ApprovalTimeline.vue";
import type { PoHead } from "@/types/purchase-order";

const props = defineProps<{ purchaseOrder: PoHead }>();

const { approvals, loading, fetchApprovals } = useApprovals(
    props.purchaseOrder.id,
);

onMounted(fetchApprovals);

const usePpn = props.purchaseOrder.details.ppn === "true";

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
            />

            <Back :to="route('purchase-order.index')" :options="{ preserveScroll: true, preserveState: true }" />

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
                <div class="lg:col-span-1">
                    <NCard
                        :bordered="true"
                        class="border-slate-200 shadow-sm lg:sticky lg:top-6"
                    >
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
