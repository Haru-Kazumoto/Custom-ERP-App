<template>
    <Head title="Revisi Delivery Order" />

    <AppLayout page-name="Revisi Delivery Order">
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Revisi Delivery Order"
                subTitle="Delivery Order yang dikembalikan untuk diperbaiki oleh approver"
            >
                <template #action>
                    <div class="ml-auto flex gap-2">
                        <NButton size="large" @click="createDo()">
                            <template #icon>
                                <NIcon :component="Plus" />
                            </template>

                            DO Baru
                        </NButton>
                    </div>
                </template>
            </HeaderPage>

            <NAlert type="info" :bordered="false" class="rounded-xl">
                Hanya pembuat dokumen yang bisa membuka form revisi. Setelah
                disimpan, nomor DO tetap sama dan approval dimulai ulang dari
                Sales.
            </NAlert>

            <div
                class="rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex w-full flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-2">
                        <h2 class="font-medium text-slate-900">
                            Perlu Direvisi
                        </h2>
                        <span
                            class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700"
                        >
                            {{
                                deliveryOrders.meta?.total ??
                                deliveryOrders.data.length
                            }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <NInput
                            v-model:value="form.search"
                            placeholder="Cari no. DO, nama pelanggan..."
                            class="w-full sm:w-64"
                        >
                            <template #prefix>
                                <NIcon :component="Search" />
                            </template>
                        </NInput>
                    </div>
                </div>

                <!-- ── Tampilan tabel (desktop / tablet) ─────────────────────── -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full caption-bottom text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    No. DO
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Pelanggan
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Persetujuan
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Alasan Revisi
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Total DO
                                </th>
                                <th
                                    class="h-10 w-40 px-4 text-left align-middle font-medium text-slate-500"
                                />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="deliveryOrders.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-16 text-center align-middle"
                                >
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <FileX
                                            class="h-8 w-8 text-slate-300"
                                        />
                                        <p
                                            class="font-medium text-slate-700"
                                        >
                                            Tidak ada dokumen yang perlu
                                            direvisi
                                        </p>
                                        <p class="text-sm text-slate-400">
                                            Delivery Order yang dikembalikan
                                            approver akan muncul di sini
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="doRow in deliveryOrders.data"
                                :key="doRow.id"
                                class="border-b border-slate-50 hover:bg-slate-50"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <button
                                        type="button"
                                        class="text-left"
                                        @click="viewDo(doRow)"
                                    >
                                        <p
                                            class="font-medium text-slate-900 hover:text-blue-600"
                                        >
                                            {{ doRow.transaction_code }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{
                                                formatDate(doRow.created_at, true)
                                            }}
                                        </p>
                                    </button>
                                </td>

                                <td
                                    class="px-4 py-3 align-middle text-slate-700"
                                >
                                    {{ doRow.detail?.customer ?? "-" }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ApprovalChain
                                        :last-approval="
                                            doRow.current_approval_role
                                        "
                                        :status-approval="
                                            doRow.current_approval_status
                                        "
                                        :proceed-by="
                                            doRow.current_approval_proceed_by
                                        "
                                    />
                                </td>

                                <td
                                    class="max-w-xs px-4 py-3 align-middle text-sm text-slate-600"
                                >
                                    <p
                                        v-if="doRow.current_approval_description"
                                        class="line-clamp-3"
                                        :title="
                                            doRow.current_approval_description
                                        "
                                    >
                                        {{
                                            doRow.current_approval_description
                                        }}
                                    </p>
                                    <span v-else class="text-slate-400">-</span>
                                </td>

                                <td
                                    class="whitespace-nowrap px-4 py-3 align-middle font-medium text-slate-900"
                                >
                                    {{ formatRupiah(doRow.grand_total) }}
                                </td>

                                <td class="px-4 py-3 align-middle text-right">
                                    <!--
                                        Backend menolak revisi dari selain
                                        pembuat dokumen. Tombol disembunyikan di
                                        sini supaya aksi yang pasti gagal tidak
                                        pernah diklik; guard di
                                        `DeliveryOrderController::revise()` tetap
                                        ada sebagai otorisasi sebenarnya.
                                    -->
                                    <NButton
                                        v-if="canRevise(doRow)"
                                        type="primary"
                                        size="small"
                                        class="bg-[#0284c7] hover:bg-[#0369a1]"
                                        @click="reviseDo(doRow)"
                                    >
                                        <template #icon>
                                            <NIcon :component="Pencil" />
                                        </template>

                                        Revisi
                                    </NButton>
                                    <NTooltip v-else>
                                        <template #trigger>
                                            <NTag
                                                size="small"
                                                :bordered="false"
                                                class="bg-slate-100 text-slate-500"
                                            >
                                                Hanya pembuat
                                            </NTag>
                                        </template>
                                        Hanya pembuat dokumen yang dapat
                                        merevisi dokumen ini.
                                    </NTooltip>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── Tampilan kartu (mobile) ─────────────────────────────────-->
                <div class="p-4 md:hidden">
                    <div
                        v-if="deliveryOrders.data.length === 0"
                        class="flex flex-col items-center gap-2 py-12"
                    >
                        <FileX class="h-8 w-8 text-slate-300" />
                        <p class="font-medium text-slate-700">
                            Tidak ada dokumen yang perlu direvisi
                        </p>
                    </div>

                    <div v-else class="flex flex-col gap-3">
                        <div
                            v-for="doRow in deliveryOrders.data"
                            :key="doRow.id"
                            class="rounded-xl border border-slate-200 p-4"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p
                                        class="font-medium text-slate-900"
                                        @click="viewDo(doRow)"
                                    >
                                        {{ doRow.transaction_code }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ formatDate(doRow.created_at, true) }}
                                        • {{ doRow.detail?.customer ?? "-" }}
                                    </p>
                                </div>
                                <p
                                    class="whitespace-nowrap text-sm font-medium text-slate-900"
                                >
                                    {{ formatRupiah(doRow.grand_total) }}
                                </p>
                            </div>

                            <div class="mt-3">
                                <ApprovalChain
                                    :last-approval="doRow.current_approval_role"
                                    :status-approval="
                                        doRow.current_approval_status
                                    "
                                    :proceed-by="
                                        doRow.current_approval_proceed_by
                                    "
                                />
                            </div>

                            <p
                                v-if="doRow.current_approval_description"
                                class="mt-2 rounded-lg bg-slate-50 px-2 py-1.5 text-xs text-slate-600"
                            >
                                {{ doRow.current_approval_description }}
                            </p>

                            <div class="mt-3">
                                <NButton
                                    v-if="canRevise(doRow)"
                                    type="primary"
                                    size="small"
                                    block
                                    class="bg-[#0284c7] hover:bg-[#0369a1]"
                                    @click="reviseDo(doRow)"
                                >
                                    Revisi
                                </NButton>
                                <NButton v-else size="small" block disabled>
                                    Hanya pembuat dokumen
                                </NButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Footer: info halaman + pagination ──────────────────────-->
                <div
                    v-if="deliveryOrders.data.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-slate-500">
                        Menampilkan {{ deliveryOrders.meta?.from }}–{{
                            deliveryOrders.meta?.to
                        }}
                        dari {{ deliveryOrders.meta?.total }} dokumen
                    </p>

                    <div class="flex flex-wrap items-center gap-1">
                        <NButton
                            v-for="(link, idx) in deliveryOrders.links"
                            :key="idx"
                            size="small"
                            :class="
                                link.active
                                    ? 'bg-blue-600 hover:bg-blue-700'
                                    : ''
                            "
                            :disabled="!link.url"
                            @click="gotoPage(link.url)"
                        >
                            <span v-html="link.label" />
                        </NButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
/**
 * Halaman: Daftar Delivery Order yang perlu direvisi.
 * Stack: Laravel + Inertia.js + Vue 3 + Tailwind CSS + Naive UI
 *
 * Memakai `GetDeliveryOrdersQuery` yang sama dengan daftar dokumen; hanya
 * `DeliveryOrderController::indexRevisions()` yang mengunci filter status ke
 * `NEED_REVISION`. Karena itu tidak ada dropdown status di sini: menampilkan
 * pilihan lain hanya akan mengembalikan dokumen yang tidak bisa direvisi.
 *
 * Bentuk tiap baris mengikuti `DeliveryOrderSummary` (query, bukan model),
 * dengan `detail` berisi map nama→nilai dari `transaction_details` dan
 * `created_by` sebagai penentu hak revisi.
 */
import { reactive, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Search, Plus, Pencil, FileX } from "lucide-vue-next";

import {
    NAlert,
    NButton,
    NInput,
    NIcon,
    NTag,
    NTooltip,
} from "naive-ui";

import ApprovalChain from "@/Components/Feature/PurchaseOrder/ApprovalChain.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import { formatRupiah, formatDate } from "@/utils/format";
import type { DeliveryOrderSummary } from "@/types/delivery-order";

const props = defineProps<{
    deliveryOrders: {
        data: DeliveryOrderSummary[];
        links: { url: string | null; label: string; active: boolean }[];
        // `LengthAwarePaginator::toArray()` bisa menaruh metadata di level
        // atas maupun di `meta`; keduanya ditangani seperti `Index.vue`.
        total?: number;
        from?: number | null;
        to?: number | null;
        meta?: {
            total?: number;
            from?: number | null;
            to?: number | null;
        };
    };
    filters?: { search?: string };
    auth?: { user: { id: number; name: string } };
}>();

const form = reactive({
    search: props.filters?.search ?? "",
});

/**
 * Hak revisi hanya milik pembuat dokumen — sama seperti
 * `DeliveryOrderController::revise()` yang memakai guard 403.
 */
function canRevise(doRow: DeliveryOrderSummary): boolean {
    return doRow.created_by === (props.auth?.user?.id ?? null);
}

function applyFilters() {
    router.get(
        window.location.pathname,
        { search: form.search },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// Debounce pencarian agar tidak request setiap ketikan.
let searchTimeout: ReturnType<typeof setTimeout> | undefined;
watch(
    () => form.search,
    () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 400);
    },
);

function gotoPage(url: string | null) {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

function viewDo(doRow: DeliveryOrderSummary) {
    router.visit(route("delivery-order.show", doRow.id));
}

function reviseDo(doRow: DeliveryOrderSummary) {
    router.get(route("delivery-order.revise", doRow.id));
}

function createDo() {
    router.get(route("delivery-order.create"), {}, { preserveScroll: true });
}
</script>
