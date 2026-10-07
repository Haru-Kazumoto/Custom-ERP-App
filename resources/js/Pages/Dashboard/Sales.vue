<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    AlertCircle,
    ArrowDownRight,
    ArrowUpRight,
    Boxes,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    FileCheck2,
    Package,
    Target,
    TrendingUp,
} from "lucide-vue-next";

interface SalesSummary {
    deliveryOrdersThisMonth: number;
    deliveryOrdersNeedRevision: number;
    monthlyTarget: number;
    salesAchieved: number;
    salesOrderCount: number;
    salesOrderUnits: number;
    targetIsSet: boolean;
}

interface DeliveryOrderRow {
    id: number;
    code: string;
    date: string;
    total: number;
    status: string;
}

interface SalesOrderRow {
    id: number;
    code: string;
    date: string;
    lineCount: number;
    total: number;
}

interface ProductRow {
    id: number;
    name: string;
    code: string;
    unit: string;
    quantity: number;
    total: number;
}

const props = defineProps<{
    summary: SalesSummary;
    recentDeliveryOrders: DeliveryOrderRow[];
    recentSalesOrders: SalesOrderRow[];
    topProducts: ProductRow[];
}>();

const numberFormat = new Intl.NumberFormat("id-ID");
const currencyFormat = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
});
const monthLabel = new Intl.DateTimeFormat("id-ID", {
    month: "long",
    year: "numeric",
}).format(new Date());

const targetProgress = props.summary.monthlyTarget > 0
    ? (props.summary.salesAchieved / props.summary.monthlyTarget) * 100
    : 0;
const progressWidth = Math.min(targetProgress, 100);

const stats = [
    {
        label: "DO terbit bulan ini",
        value: numberFormat.format(props.summary.deliveryOrdersThisMonth),
        description: "Delivery Order dibuat bulan berjalan",
        icon: FileCheck2,
        tone: "bg-sky-50 text-sky-600",
    },
    {
        label: "DO butuh revisi",
        value: numberFormat.format(props.summary.deliveryOrdersNeedRevision),
        description: "Dokumen dengan status perlu revisi",
        icon: AlertCircle,
        tone: "bg-amber-50 text-amber-600",
    },
    {
        label: "Target bulanan",
        value: props.summary.targetIsSet
            ? currencyFormat.format(props.summary.monthlyTarget)
            : "Belum diatur",
        description: "Target personal untuk bulan ini",
        icon: Target,
        tone: "bg-violet-50 text-violet-600",
    },
    {
        label: "Penjualan tercapai",
        value: currencyFormat.format(props.summary.salesAchieved),
        description: "Nilai item SSO bulan berjalan",
        icon: TrendingUp,
        tone: "bg-emerald-50 text-emerald-600",
    },
];

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
    if (value === "TERBIT") return "Terbit";

    return value
        .toLowerCase()
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
}

function statusClass(value: string): string {
    const status = value.toUpperCase();
    if (status === "APPROVED" || status === "TERBIT") {
        return "bg-emerald-50 text-emerald-700";
    }
    if (status === "NEED_REVISION") return "bg-amber-50 text-amber-700";
    if (status === "PENDING") return "bg-sky-50 text-sky-700";

    return "bg-slate-100 text-slate-600";
}
</script>

<template>
    <AppLayout page-name="Dashboard Sales">
        <div class="mx-auto max-w-7xl space-y-6">
            <header
                class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="mb-1 text-xs font-semibold uppercase tracking-[0.14em] text-sky-700"
                    >
                        Performa penjualan
                    </p>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Dashboard Sales
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Pantau Delivery Order, pencapaian target, dan penjualan
                        bulan ini.
                    </p>
                </div>
                <div
                    class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600"
                >
                    <CalendarDays class="h-4 w-4 text-slate-400" />
                    {{ monthLabel }}
                </div>
            </header>

            <section
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                aria-label="Statistik penjualan"
            >
                <article
                    v-for="stat in stats"
                    :key="stat.label"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-500">
                                {{ stat.label }}
                            </p>
                            <p
                                class="mt-2 truncate text-2xl font-bold tracking-tight text-slate-900"
                            >
                                {{ stat.value }}
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            :class="stat.tone"
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
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
                aria-labelledby="target-progress-title"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                        >
                            <Target class="h-5 w-5" />
                        </div>
                        <div>
                            <h2
                                id="target-progress-title"
                                class="text-base font-semibold text-slate-900"
                            >
                                Progres target bulanan
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ monthLabel }} · berdasarkan nilai item SSO
                                yang dibuat bulan ini
                            </p>
                        </div>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xl font-bold text-slate-900">
                            {{ targetProgress.toFixed(1) }}%
                        </p>
                        <p class="text-xs text-slate-400">target tercapai</p>
                    </div>
                </div>

                <div
                    class="mt-5 h-2.5 overflow-hidden rounded-full bg-slate-100"
                    role="progressbar"
                    :aria-valuenow="Math.round(progressWidth)"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    :aria-label="`Pencapaian target ${targetProgress.toFixed(1)} persen`"
                >
                    <div
                        class="h-full rounded-full bg-emerald-500 transition-all"
                        :style="{ width: `${progressWidth}%` }"
                    ></div>
                </div>

                <div
                    class="mt-3 flex flex-col gap-1 text-xs text-slate-500 sm:flex-row sm:justify-between"
                >
                    <span>
                        Tercapai:
                        <strong class="font-semibold text-slate-700">
                            {{ currencyFormat.format(summary.salesAchieved) }}
                        </strong>
                    </span>
                    <span v-if="summary.targetIsSet">
                        Target:
                        <strong class="font-semibold text-slate-700">
                            {{ currencyFormat.format(summary.monthlyTarget) }}
                        </strong>
                    </span>
                    <span v-else class="text-amber-700">
                        Target bulan ini belum ditetapkan untuk akun Anda.
                    </span>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                <article
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    aria-labelledby="delivery-orders-title"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                    >
                        <div>
                            <h2
                                id="delivery-orders-title"
                                class="text-base font-semibold text-slate-900"
                            >
                                Delivery Order terbaru
                            </h2>
                            <p class="mt-0.5 text-sm text-slate-500">
                                Dokumen DO milik Anda.
                            </p>
                        </div>
                        <span
                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"
                        >
                            {{ recentDeliveryOrders.length }} dokumen
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">
                                        Nomor DO
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Tanggal
                                    </th>
                                    <th class="px-5 py-3 text-right font-medium">
                                        Nilai
                                    </th>
                                    <th class="px-5 py-3 text-right font-medium">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="order in recentDeliveryOrders"
                                    :key="order.id"
                                    class="hover:bg-slate-50/70"
                                >
                                    <td
                                        class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-800"
                                    >
                                        {{ order.code }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-5 py-3.5 text-slate-600"
                                    >
                                        {{ formatDate(order.date) }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-5 py-3.5 text-right text-slate-600"
                                    >
                                        {{ currencyFormat.format(order.total) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="statusClass(order.status)"
                                        >
                                            {{ statusLabel(order.status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recentDeliveryOrders.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-5 py-10 text-center text-sm text-slate-400"
                                    >
                                        Belum ada data DO. Modul penerbitan DO
                                        belum tersedia di aplikasi.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    aria-labelledby="recent-sales-title"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                    >
                        <div>
                            <h2
                                id="recent-sales-title"
                                class="text-base font-semibold text-slate-900"
                            >
                                Aktivitas penjualan
                            </h2>
                            <p class="mt-0.5 text-sm text-slate-500">
                                SSO yang dibuat bulan ini.
                            </p>
                        </div>
                        <span
                            class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700"
                        >
                            {{ numberFormat.format(summary.salesOrderCount) }}
                            SSO
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[500px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">
                                        Nomor SSO
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Tanggal
                                    </th>
                                    <th class="px-5 py-3 text-right font-medium">
                                        Baris
                                    </th>
                                    <th class="px-5 py-3 text-right font-medium">
                                        Nilai penjualan
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="order in recentSalesOrders"
                                    :key="order.id"
                                    class="hover:bg-slate-50/70"
                                >
                                    <td
                                        class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-800"
                                    >
                                        {{ order.code }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-5 py-3.5 text-slate-600"
                                    >
                                        {{ formatDate(order.date) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-slate-600">
                                        {{ numberFormat.format(order.lineCount) }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-5 py-3.5 text-right font-medium text-slate-700"
                                    >
                                        {{ currencyFormat.format(order.total) }}
                                    </td>
                                </tr>
                                <tr v-if="recentSalesOrders.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-5 py-10 text-center text-sm text-slate-400"
                                    >
                                        Belum ada SSO yang dibuat bulan ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                aria-labelledby="top-products-title"
            >
                <div
                    class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            id="top-products-title"
                            class="text-base font-semibold text-slate-900"
                        >
                            Produk terjual bulan ini
                        </h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            Produk teratas berdasarkan kuantitas pada SSO Anda.
                        </p>
                    </div>
                    <div
                        class="inline-flex w-fit items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700"
                    >
                        <Boxes class="h-3.5 w-3.5" />
                        Top produk
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Produk</th>
                                <th class="px-5 py-3 font-medium">Kode</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Kuantitas
                                </th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Nilai penjualan
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="product in topProducts"
                                :key="product.id"
                                class="hover:bg-slate-50/70"
                            >
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                                        >
                                            <Package class="h-4 w-4" />
                                        </span>
                                        <span class="font-medium text-slate-800">
                                            {{ product.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-500">
                                    {{ product.code }}
                                </td>
                                <td class="px-5 py-3.5 text-right text-slate-600">
                                    {{ numberFormat.format(product.quantity) }}
                                    {{ product.unit }}
                                </td>
                                <td
                                    class="px-5 py-3.5 text-right font-medium text-slate-700"
                                >
                                    {{ currencyFormat.format(product.total) }}
                                </td>
                            </tr>
                            <tr v-if="topProducts.length === 0">
                                <td
                                    colspan="4"
                                    class="px-5 py-10 text-center text-sm text-slate-400"
                                >
                                    Belum ada data produk terjual bulan ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div
                v-if="summary.deliveryOrdersNeedRevision > 0"
                class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                role="status"
            >
                <ArrowDownRight class="mt-0.5 h-4 w-4 shrink-0" />
                <p>
                    Ada
                    <strong>{{ summary.deliveryOrdersNeedRevision }}</strong>
                    Delivery Order yang perlu direvisi.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
