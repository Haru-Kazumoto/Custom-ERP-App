<template>
    <AppLayout page-name="Revisi Purchase Order">
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Revisi Purchase Order"
                subTitle="Purchase Order yang dikembalikan untuk diperbaiki oleh approver"
            >
                <template #action>
                    <div class="flex gap-2 ml-auto">
                        <NButton size="large" @click="redirectCreateForm()">
                            <template #icon>
                                <NIcon :component="Plus" />
                            </template>

                            PO Baru
                        </NButton>
                    </div>
                </template>
            </HeaderPage>

            <NAlert type="info" :bordered="false" class="rounded-xl">
                Hanya pembuat dokumen yang bisa membuka form revisi. Setelah
                disimpan, nomor PO tetap sama dan approval dimulai ulang dari
                Finance.
            </NAlert>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between w-full"
                >
                    <div class="flex items-center gap-2">
                        <h2 class="font-medium text-slate-900">
                            Perlu Direvisi
                        </h2>
                        <span
                            class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700"
                        >
                            {{
                                purchaseOrders.meta?.total ??
                                purchaseOrders.data.length
                            }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <NInput
                            v-model:value="form.search"
                            placeholder="Cari no. PO, nama vendor..."
                            class="w-full sm:w-64"
                        >
                            <template #prefix>
                                <NIcon :component="Search" />
                            </template>
                        </NInput>
                    </div>
                </div>

                <!-- ── Tampilan tabel (desktop / tablet) ─────────────────────── -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    No. PO
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Vendor
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
                                    Total PO
                                </th>
                                <th
                                    class="h-10 w-40 px-4 text-left align-middle font-medium text-slate-500"
                                />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="purchaseOrders.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-16 text-center align-middle"
                                >
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <FileX class="h-8 w-8 text-slate-300" />
                                        <p
                                            class="font-medium text-slate-700"
                                        >
                                            Tidak ada dokumen yang perlu
                                            direvisi
                                        </p>
                                        <p class="text-sm text-slate-400">
                                            Purchase Order yang dikembalikan
                                            approver akan muncul di sini
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="po in purchaseOrders.data"
                                :key="po.id"
                                class="border-b border-slate-50 hover:bg-slate-50"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <button
                                        type="button"
                                        class="text-left"
                                        @click="viewPo(po)"
                                    >
                                        <p
                                            class="font-medium text-slate-900 hover:text-blue-600"
                                        >
                                            {{ po.transaction_code }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{
                                                formatDate(
                                                    po.detail?.tanggal_po,
                                                    true,
                                                )
                                            }}
                                        </p>
                                    </button>
                                </td>

                                <td
                                    class="px-4 py-3 align-middle text-slate-700"
                                >
                                    {{ po.detail?.pemasok ?? "-" }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ApprovalChain
                                        :last-approval="
                                            po.current_approval_proceed_by
                                        "
                                        :status-approval="
                                            po.current_approval_status
                                        "
                                        :proceed-by="
                                            po.current_approval_role
                                                ? `Tahap ${po.current_approval_order}: ${po.current_approval_role}`
                                                : '-'
                                        "
                                    />
                                </td>

                                <td
                                    class="max-w-xs px-4 py-3 align-middle text-sm text-slate-600"
                                >
                                    <p
                                        v-if="po.current_approval_description"
                                        class="line-clamp-3"
                                        :title="
                                            po.current_approval_description
                                        "
                                    >
                                        {{
                                            po.current_approval_description
                                        }}
                                    </p>
                                    <span v-else class="text-slate-400">-</span>
                                </td>

                                <td
                                    class="px-4 py-3 align-middle font-medium text-slate-900 whitespace-nowrap"
                                >
                                    {{ formatRupiah(po.grand_total) }}
                                </td>

                                <td
                                    class="px-4 py-3 align-middle text-right"
                                >
                                    <!--
                                        Backend menolak revisi dari selain
                                        pembuat dokumen. Tombol disembunyikan di
                                        sini supaya aksi yang pasti gagal tidak
                                        pernah diklik; cripple check tetap ada di
                                        `revise()`.
                                    -->
                                    <NButton
                                        v-if="canRevise(po)"
                                        type="primary"
                                        size="small"
                                        class="bg-[#0284c7] hover:bg-[#0369a1]"
                                        @click="revisePo(po)"
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
                <div class="md:hidden p-4">
                    <div
                        v-if="purchaseOrders.data.length === 0"
                        class="flex flex-col items-center gap-2 py-12"
                    >
                        <FileX class="h-8 w-8 text-slate-300" />
                        <p class="font-medium text-slate-700">
                            Tidak ada dokumen yang perlu direvisi
                        </p>
                    </div>

                    <div v-else class="flex flex-col gap-3">
                        <div
                            v-for="po in purchaseOrders.data"
                            :key="po.id"
                            class="rounded-xl border border-slate-200 p-4"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p
                                        class="font-medium text-slate-900"
                                        @click="viewPo(po)"
                                    >
                                        {{ po.transaction_code }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{
                                            formatDate(
                                                po.detail?.tanggal_po,
                                                true,
                                            )
                                        }}
                                        • {{
                                            po.detail?.pemasok ?? "-"
                                        }}
                                    </p>
                                </div>
                                <p
                                    class="text-sm font-medium text-slate-900 whitespace-nowrap"
                                >
                                    {{ formatRupiah(po.grand_total) }}
                                </p>
                            </div>

                            <div class="mt-3">
                                <ApprovalChain
                                    :last-approval="
                                        po.current_approval_proceed_by
                                    "
                                    :status-approval="
                                        po.current_approval_status
                                    "
                                    :proceed-by="
                                        po.current_approval_role
                                            ? `Tahap ${po.current_approval_order}: ${po.current_approval_role}`
                                            : '-'
                                    "
                                />
                            </div>

                            <p
                                v-if="po.current_approval_description"
                                class="mt-2 rounded-lg bg-slate-50 px-2 py-1.5 text-xs text-slate-600"
                            >
                                {{ po.current_approval_description }}
                            </p>

                            <div class="mt-3">
                                <NButton
                                    v-if="canRevise(po)"
                                    type="primary"
                                    size="small"
                                    block
                                    class="bg-[#0284c7] hover:bg-[#0369a1]"
                                    @click="revisePo(po)"
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
                    v-if="purchaseOrders.data.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-slate-500">
                        Menampilkan {{ purchaseOrders.meta?.from }}–{{
                            purchaseOrders.meta?.to
                        }}
                        dari {{ purchaseOrders.meta?.total }} dokumen
                    </p>

                    <div class="flex flex-wrap items-center gap-1">
                        <NButton
                            v-for="(link, idx) in purchaseOrders.links"
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
 * Halaman: Daftar dokumen Purchase Order yang perlu direvisi.
 * Stack: Laravel + Inertia.js + Vue 3 + Tailwind CSS + Naive UI
 *
 * Halaman ini memakai `GetPurchaseOrdersQuery` yang sama dengan daftar dokumen,
 * dan `PurchaseOrderController::indexRevisions()` mengunci filter status ke
 * `NEED_REVISION`. Karena itu tidak ada dropdown status di sini: menampilkan
 * pilihan lain hanya akan mengembalikan dokumen yang tidak bisa direvisi.
 *
 * Bentuk tiap baris (hasil query, bukan model):
 * {
 *   id: 1,
 *   transaction_code: 'PO-2026-0001',
 *   payment_term: 45,
 *   sub_total: 1000000,
 *   tax_amount: 110000,
 *   grand_total: 1110000,
 *   total_discount: 0,
 *   created_at: '2026-06-18 09:00:00',
 *   last_updated_at: '2026-06-20 14:30:00',
 *   document_description: 'PO bahan baku bulan Juni',
 *   created_by: 6,                     // id user pembuat; penentu hak revisi
 *   detail: { pemasok: 'PT vendors', tanggal_po: '...', ... },
 *   current_approval_order: 1,
 *   current_approval_status: 'NEED_REVISION',
 *   current_approval_description: 'Qty salah pada baris gula',
 *   current_approval_role: 'Finance',
 *   current_approval_proceed_by: 'Budi',
 *   current_approval_proceed_at: '2026-06-20 14:30:00',
 * }
 */
import { computed, reactive, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Search, Plus, Pencil, FileX } from "lucide-vue-next";

import {
    NAlert,
    NButton,
    NInput,
    NSelect,
    NIcon,
    NTag,
    NTooltip,
} from "naive-ui";

import ApprovalChain from "@/Components/Feature/PurchaseOrder/ApprovalChain.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import { formatRupiah, formatDate } from "@/utils/format";
import type { PurchaseOrderSummary } from "@/types/purchase-order";

const props = defineProps({
    purchaseOrders: {
        type: Object as () => {
            data: PurchaseOrderSummary[];
            links: { url: string | null; label: string; active: boolean }[];
            meta: {
                current_page: number;
                last_page: number;
                per_page: number;
                total: number;
                from: number | null;
                to: number | null;
            };
        },
        default: () => ({
            data: [],
            links: [],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 20,
                total: 0,
                from: 0,
                to: 0,
            },
        }),
    },
    filters: {
        type: Object as () => { search: string; date_from: string; date_to: string },
        default: () => ({ search: "", date_from: "", date_to: "" }),
    },
    auth: {
        type: Object as () => { user: { id: number; name: string } },
        required: true,
    },
});

const form = reactive({
    search: props.filters.search ?? "",
});

// `auth.user` sudah dibagikan Inertia ke semua halaman, tapi dideklarasikan
// sebagai prop supaya tipe `id`-nya ikut dicek di satu tempat.
const currentUserId = computed(() => props.auth.user.id);

function redirectCreateForm() {
    router.get(route("purchase-order.create"));
}

/**
 * Hak revisi hanya milik pembuat dokumen.
 *
 * Sengaja hanya membandingkan `created_by` dengan user yang login:
 * `RevisePurchaseOrderAction` sudah menegakkan aturan yang sama ditambah
 * syarat status, jadi fungsi ini expedience UX, bukan pengganti otorisasi.
 */
function canRevise(po: PurchaseOrderSummary): boolean {
    return po.created_by === currentUserId.value;
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

function viewPo(po: PurchaseOrderSummary) {
    router.visit(route("purchase-order.show", po.id));
}

function revisePo(po: PurchaseOrderSummary) {
    router.get(route("purchase-order.revise", po.id));
}
</script>
