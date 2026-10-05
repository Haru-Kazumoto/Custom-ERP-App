<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { NButton, NCard, NTag } from "naive-ui";
import { ExternalLink } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import Back from "@/Components/Common/Back.vue";
import { formatDate, formatRupiah } from "@/utils/format";

type SubSalesOrderItem = {
    id: number;
    product_id: number;
    quantity: number;
    base_price: number | string | null;
    total_price: number | string | null;
    product_code: string | null;
    product_name: string | null;
    product_unit: string | null;
};

type SubSalesOrder = {
    id: number;
    transaction_code: string;
    correlation_id: string;
    description: string | null;
    created_at: string;
    created_by_name: string | null;
    details: Record<string, string | null>;
    items: SubSalesOrderItem[];
    purchase_order: { id: number; transaction_code: string } | null;
};

const props = defineProps<{ subSalesOrder: SubSalesOrder }>();

const detailRows = [
    { label: "Nomor Bukti", key: "no_bukti" },
    { label: "Nomor SO", key: "no_so" },
    { label: "Tanggal Kirim", key: "tanggal_kirim", date: true },
    { label: "Pemasok", key: "pemasok" },
    { label: "Alokasi", key: "alokasi" },
    { label: "Ekspedisi", key: "nama_ekspedisi" },
    { label: "Jenis Pengiriman", key: "jenis_pengiriman" },
    { label: "PIC", key: "pic" },
] as const;

function openPurchaseOrder() {
    if (props.subSalesOrder.purchase_order) {
        router.visit(
            route("purchase-order.show", props.subSalesOrder.purchase_order.id),
        );
    }
}
</script>

<template>
    <Head :title="`Sub Sales Order ${subSalesOrder.transaction_code}`" />

    <AppLayout>
        <div class="flex flex-col gap-5">
            <HeaderPage
                :title="subSalesOrder.transaction_code"
                subTitle="Detail Sub Sales Order"
            >
                <template #action>
                    <NButton
                        v-if="subSalesOrder.purchase_order"
                        type="primary"
                        @click="openPurchaseOrder"
                    >
                        <template #icon>
                            <ExternalLink class="h-4 w-4" />
                        </template>
                        Lihat Purchase Order
                    </NButton>
                </template>
            </HeaderPage>

            <Back
                :to="route('sub-sales-order.index')"
                :options="{ preserveScroll: true, preserveState: true }"
            />

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="flex flex-col gap-5 lg:col-span-2">
                    <NCard
                        :bordered="true"
                        class="border-slate-200 shadow-sm"
                    >
                        <template #header>
                            <div class="flex items-center gap-2">
                                <h2
                                    class="text-base font-semibold text-slate-800"
                                >
                                    Informasi Dokumen
                                </h2>
                                <NTag
                                    size="small"
                                    :bordered="false"
                                    class="bg-sky-50 text-sky-700"
                                >
                                    SSO
                                </NTag>
                            </div>
                        </template>

                        <div
                            class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2"
                        >
                            <div>
                                <p class="text-xs text-slate-400">
                                    Nomor Sub Sales Order
                                </p>
                                <p class="text-sm font-medium text-slate-700">
                                    {{ subSalesOrder.transaction_code }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400">
                                    Purchase Order Asal
                                </p>
                                <button
                                    v-if="subSalesOrder.purchase_order"
                                    type="button"
                                    class="text-sm font-medium text-sky-700 hover:underline"
                                    @click="openPurchaseOrder"
                                >
                                    {{
                                        subSalesOrder.purchase_order
                                            .transaction_code
                                    }}
                                </button>
                                <p v-else class="text-sm text-slate-500">
                                    Tidak tersedia
                                </p>
                            </div>

                            <div
                                v-for="row in detailRows"
                                :key="row.key"
                            >
                                <p class="text-xs text-slate-400">
                                    {{ row.label }}
                                </p>
                                <p class="text-sm font-medium text-slate-700">
                                    {{
                                        row.date
                                            ? formatDate(
                                                  subSalesOrder.details[
                                                      row.key
                                                  ],
                                              ) || "-"
                                            : subSalesOrder.details[
                                                  row.key
                                              ] || "-"
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Dibuat Pada
                                </p>
                                <p class="text-sm font-medium text-slate-700">
                                    {{ formatDate(subSalesOrder.created_at) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400">
                                    Dibuat Oleh
                                </p>
                                <p class="text-sm font-medium text-slate-700">
                                    {{ subSalesOrder.created_by_name || "-" }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-slate-400">Catatan</p>
                                <p class="whitespace-pre-wrap text-sm text-slate-700">
                                    {{ subSalesOrder.description || "-" }}
                                </p>
                            </div>
                        </div>
                    </NCard>

                    <NCard
                        :bordered="true"
                        class="border-slate-200 shadow-sm"
                    >
                        <template #header>
                            <h2 class="text-base font-semibold text-slate-800">
                                Daftar Barang ({{ subSalesOrder.items.length }})
                            </h2>
                        </template>

                        <div class="overflow-x-auto rounded-xl border border-slate-100">
                            <table class="w-full text-sm">
                                <thead
                                    class="bg-slate-50 text-left text-xs text-slate-500"
                                >
                                    <tr>
                                        <th class="px-4 py-3 font-medium">#</th>
                                        <th class="px-4 py-3 font-medium">
                                            Kode Barang
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            Nama Barang
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right font-medium"
                                        >
                                            Jumlah
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            Kemasan
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right font-medium"
                                        >
                                            Harga Baris
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in subSalesOrder.items"
                                        :key="item.id"
                                        class="border-t border-slate-100"
                                    >
                                        <td class="px-4 py-3 text-slate-400">
                                            {{ index + 1 }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            {{ item.product_code || "-" }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-medium text-slate-700"
                                        >
                                            {{ item.product_name || "Barang tidak tersedia" }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right text-slate-700"
                                        >
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            {{ item.product_unit || "-" }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right text-slate-700"
                                        >
                                            {{
                                                item.total_price === null
                                                    ? "-"
                                                    : formatRupiah(
                                                          Number(
                                                              item.total_price,
                                                          ),
                                                      )
                                            }}
                                        </td>
                                    </tr>
                                    <tr v-if="!subSalesOrder.items.length">
                                        <td
                                            colspan="6"
                                            class="px-4 py-10 text-center text-slate-500"
                                        >
                                            Belum ada barang pada dokumen ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </NCard>
                </div>

                <NCard
                    :bordered="true"
                    class="h-fit border-slate-200 shadow-sm"
                >
                    <template #header>
                        <h2 class="text-base font-semibold text-slate-800">
                            Ringkasan
                        </h2>
                    </template>
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-slate-500">Jumlah Jenis Barang</span>
                        <span class="font-semibold text-slate-700">
                            {{ subSalesOrder.items.length }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-slate-500">Total Kuantitas</span>
                        <span class="font-semibold text-slate-700">
                            {{
                                subSalesOrder.items.reduce(
                                    (sum, item) =>
                                        sum + Number(item.quantity || 0),
                                    0,
                                )
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-slate-500">Tanggal Dokumen</span>
                        <span class="text-right font-medium text-slate-700">
                            {{ formatDate(subSalesOrder.created_at, false) }}
                        </span>
                    </div>
                </NCard>
            </div>
        </div>
    </AppLayout>
</template>
