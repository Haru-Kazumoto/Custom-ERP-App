<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    AlertTriangle,
    ArrowDownToLine,
    Boxes,
    ClipboardList,
    FileCheck2,
    PackageCheck,
} from "lucide-vue-next";

interface WarehouseSummary {
    productCount: number;
    purchaseOrderCount: number;
    receivedUnits: number;
    discrepancyCount: number;
}
    
interface DeliveryOrderRow {
    id: number;
    code: string;
    date: string;
    status: string;
    itemsCount: number;
}

interface ReceivingRow {
    id: number;
    purchaseOrderCode: string;
    date: string;
    status: string;
    itemsCount: number;
    receivedUnits: number;
}

const props = defineProps<{
    summary: WarehouseSummary;
    deliveryOrders: DeliveryOrderRow[];
    recentReceivings: ReceivingRow[];
}>();

const stats = [
    {
        label: "Produk terdaftar",
        key: "productCount",
        description: "Jumlah SKU di katalog produk",
        icon: Boxes,
        iconClass: "bg-blue-50 text-blue-600",
    },
    {
        label: "Purchase Order",
        key: "purchaseOrderCount",
        description: "PO yang tercatat di sistem",
        icon: ClipboardList,
        iconClass: "bg-violet-50 text-violet-600",
    },
    {
        label: "Unit diterima",
        key: "receivedUnits",
        description: "Akumulasi jumlah pada penerimaan",
        icon: ArrowDownToLine,
        iconClass: "bg-emerald-50 text-emerald-600",
    },
    {
        label: "Selisih tercatat",
        key: "discrepancyCount",
        description: "Catatan selisih penerimaan",
        icon: AlertTriangle,
        iconClass: "bg-amber-50 text-amber-600",
    },
] as const;

function formatNumber(value: number): string {
    return new Intl.NumberFormat("id-ID").format(value);
}

function formatDate(value: string): string {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "-";

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
}

function statusLabel(value: string): string {
    return value
        .toLowerCase()
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
}

function statusClass(value: string): string {
    const status = value.toUpperCase();
    if (["APPROVED", "COMPLETED", "DELIVERED", "RECEIVED"].includes(status)) {
        return "bg-emerald-50 text-emerald-700";
    }
    if (["PENDING", "IN_PROGRESS", "PROCESSING"].includes(status)) {
        return "bg-amber-50 text-amber-700";
    }
    return "bg-slate-100 text-slate-600";
}
</script>

<template>
    <AppLayout page-name="Dashboard Warehouse">
        <div class="mx-auto max-w-7xl space-y-6">
            <header>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Dashboard Warehouse
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan stok, penerimaan barang, dan dokumen pengiriman.
                </p>
            </header>

            <section
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                aria-label="Statistik warehouse"
            >
                <article
                    v-for="stat in stats"
                    :key="stat.key"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                {{ stat.label }}
                            </p>
                            <p
                                class="mt-2 text-3xl font-bold tracking-tight text-slate-900"
                            >
                                {{ formatNumber(props.summary[stat.key]) }}
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            :class="stat.iconClass"
                        >
                            <component :is="stat.icon" class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">
                        {{ stat.description }}
                    </p>
                </article>
            </section>

            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                aria-labelledby="delivery-orders-title"
            >
                <div
                    class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            id="delivery-orders-title"
                            class="text-base font-semibold text-slate-900"
                        >
                            Surat jalan terbaru
                        </h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            Dokumen Delivery Order yang tercatat di transaksi.
                        </p>
                    </div>
                    <div
                        class="inline-flex w-fit items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700"
                    >
                        <FileCheck2 class="h-3.5 w-3.5" />
                        {{ deliveryOrders.length }} dokumen
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[650px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">
                                    Nomor surat jalan
                                </th>
                                <th class="px-5 py-3 font-medium">Tanggal</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Baris barang
                                </th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="document in deliveryOrders"
                                :key="document.id"
                                class="hover:bg-slate-50/70"
                            >
                                <td
                                    class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-800"
                                >
                                    {{ document.code }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">
                                    {{ formatDate(document.date) }}
                                </td>
                                <td class="px-5 py-3.5 text-right text-slate-600">
                                    {{ formatNumber(document.itemsCount) }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusClass(document.status)"
                                    >
                                        {{ statusLabel(document.status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="deliveryOrders.length === 0">
                                <td
                                    colspan="4"
                                    class="px-5 py-10 text-center text-sm text-slate-400"
                                >
                                    Belum ada data surat jalan pada transaksi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                aria-labelledby="receivings-title"
            >
                <div
                    class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            id="receivings-title"
                            class="text-base font-semibold text-slate-900"
                        >
                            Penerimaan barang terbaru
                        </h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            Barang masuk yang sudah dicatat berdasarkan
                            purchase order.
                        </p>
                    </div>
                    <div
                        class="inline-flex w-fit items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"
                    >
                        <PackageCheck class="h-3.5 w-3.5" />
                        {{ recentReceivings.length }} penerimaan
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">
                                    Purchase Order
                                </th>
                                <th class="px-5 py-3 font-medium">
                                    Tanggal diterima
                                </th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Baris barang
                                </th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Unit diterima
                                </th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="receiving in recentReceivings"
                                :key="receiving.id"
                                class="hover:bg-slate-50/70"
                            >
                                <td
                                    class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-800"
                                >
                                    {{ receiving.purchaseOrderCode }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">
                                    {{ formatDate(receiving.date) }}
                                </td>
                                <td class="px-5 py-3.5 text-right text-slate-600">
                                    {{ formatNumber(receiving.itemsCount) }}
                                </td>
                                <td
                                    class="px-5 py-3.5 text-right font-medium text-slate-700"
                                >
                                    {{ formatNumber(receiving.receivedUnits) }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusClass(receiving.status)"
                                    >
                                        {{ statusLabel(receiving.status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="recentReceivings.length === 0">
                                <td
                                    colspan="5"
                                    class="px-5 py-10 text-center text-sm text-slate-400"
                                >
                                    Belum ada penerimaan barang yang tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
