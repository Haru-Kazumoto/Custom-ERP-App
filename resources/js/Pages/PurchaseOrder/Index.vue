<template>
    <AppLayout page-name="Create Purchase Order">
        <div class="flex flex-col gap-5">
            <!-- Header halaman -->
            <HeaderPage title="Daftar Dokumen " subTitle="Pantau seluruh dokumen Purchase Order beserta status
                        persetujuannya">
                <template #action>
                    <div class="flex gap-2 ml-auto">
                        <NButton size="large">
                            <template #icon>
                                <NIcon :component="Settings2" />
                            </template>

                            Kelola Dokumen
                        </NButton>
                        <NButton :loading :disabled="loading" @click="redirectCreateForm()" type="primary" size="large">
                            <template #icon>
                                <NIcon :component="Plus" />
                            </template>

                            PO Baru
                        </NButton>
                    </div>
                </template>
            </HeaderPage>

            <!-- Card pembungkus tabel -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <!-- Toolbar: judul + total + filter -->
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between w-full">
                    <div class="flex items-center gap-2">
                        <h2 class="font-medium text-slate-900">
                            Purchase Order
                        </h2>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                            {{
                                purchaseOrders.meta?.total ??
                                purchaseOrders.data.length
                            }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <NInput v-model:value="form.search" placeholder="Cari no. PO, nama vendor..." class="w-20">
                            <template #prefix>
                                <NIcon :component="Search" />
                            </template>
                        </NInput>

                        <NSelect v-model:value="form.status" :options="selectOptions" placeholder="Semua Status" />

                        <!-- <NButton class="gap-2 justify-between sm:w-auto"> -->
                        <!--     <span class="flex items-center gap-2"> -->
                        <!--         <CalendarRange class="h-4 w-4" /> -->
                        <!--         <span class="hidden sm:inline" -->
                        <!--             >Rentang Tanggal</span -->
                        <!--         > -->
                        <!--     </span> -->
                        <!--     <ChevronDown class="h-4 w-4 text-slate-400" /> -->
                        <!-- </NButton> -->
                    </div>
                </div>

                <!-- ── Tampilan tabel (desktop / tablet) ─────────────────────── -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="h-10 px-4 text-left align-middle font-medium text-slate-500">
                                    No. PO
                                </th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-slate-500">
                                    Vendor
                                </th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-slate-500">
                                    Persetujuan
                                </th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-slate-500">
                                    Total PO
                                </th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-slate-500">
                                    Pengirim
                                </th>
                                <th class="h-10 w-10 px-4 text-left align-middle font-medium text-slate-500" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="purchaseOrders.data.length === 0">
                                <td colspan="7" class="px-4 py-16 text-center align-middle">
                                    <div class="flex flex-col items-center gap-2">
                                        <FileX class="h-8 w-8 text-slate-300" />
                                        <p class="font-medium text-slate-700">
                                            Belum ada dokumen
                                        </p>
                                        <p class="text-sm text-slate-400">
                                            Dokumen Purchase Order yang dibuat
                                            akan muncul di sini
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr v-for="po in purchaseOrders.data" :key="po.id"
                                class="cursor-pointer border-b border-slate-50 hover:bg-slate-50" @click="viewPo(po)">

                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium text-slate-900">
                                        {{ po.transaction_code }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{
                                            formatDate(
                                                po.detail.tanggal_po,
                                                true,
                                            )
                                        }}
                                    </p>
                                </td>

                                <td class="px-4 py-3 align-middle text-slate-700">
                                    {{ po.detail.pemasok }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ApprovalChain :last-approval="po.current_approval_role
                                        " :status-approval="po.current_approval_status
                                            " :proceed-by="po.current_approval_proceed_by
                                                " />
                                </td>

                                <td class="px-4 py-3 align-middle font-medium text-slate-900 whitespace-nowrap">
                                    {{ formatRupiah(po.grand_total) }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ExpeditionBadge :nama="po.detail.transportasi ?? '-'"
                                        :mode="po.detail.nomor_polisi" />
                                </td>

                                <td class="px-4 py-3 align-middle" @click.stop>
                                <NDropdown trigger="click" placement="bottom-end" :options="rowMenuOptions(po)" @select="
                                    (key) => handleRowAction(key, po)
                                ">
                                        <NButton quaternary circle size="small" class="h-8 w-8">
                                            <MoreVertical class="h-4 w-4 text-slate-500" />
                                        </NButton>
                                    </NDropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── Tampilan kartu (mobile) ─────────────────────────────────-->
                <div class="md:hidden p-4">
                    <div v-if="purchaseOrders.data.length === 0" class="flex flex-col items-center gap-2 py-12">
                        <FileX class="h-8 w-8 text-slate-300" />
                        <p class="font-medium text-slate-700">
                            Belum ada dokumen
                        </p>
                        <p class="text-sm text-slate-400 text-center">
                            Dokumen Purchase Order yang dibuat akan muncul di
                            sini
                        </p>
                    </div>

                    <div v-else class="flex flex-col gap-3">
                        <PurchaseOrderMobileCard v-for="po in purchaseOrders.data" :key="po.id" :po="po"
                            :selected="selectedIds.includes(po.id)" :format-currency="formatRupiah"
                            :current-user-id="auth.user.id" @toggle-select="toggleSelect" @view="viewPo"
                            @revise="revisePo" />
                    </div>
                </div>

                <!-- ── Footer: info halaman + pagination ──────────────────────-->
                <div v-if="purchaseOrders.data.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">
                        Menampilkan {{ purchaseOrders.meta?.from }}–{{
                            purchaseOrders.meta?.to
                        }}
                        dari {{ purchaseOrders.meta?.total }} dokumen
                    </p>

                    <div class="flex flex-wrap items-center gap-1">
                        <NButton v-for="(link, idx) in purchaseOrders.links" :key="idx" size="small" :class="link.active
                            ? 'bg-blue-600 hover:bg-blue-700'
                            : ''
                            " :disabled="!link.url" @click="gotoPage(link.url)">
                            <span v-html="link.label" />
                        </NButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
/**
 * Halaman: Daftar Dokumen — Purchase Order
 * Stack: Laravel + Inertia.js + Vue 3 + Tailwind CSS + Naive UI
 *
 * ───────────────────────────────────────────────────────────────────────
 * KONTRAK DATA DARI BACKEND (Inertia props)
 * ───────────────────────────────────────────────────────────────────────
 *
 * purchaseOrders (paginator dari `GetPurchaseOrdersQuery`):
 * {
 *   data: [ PurchaseOrderSummary, ... ],
 *   links: [ { url: null, label: '&laquo; Previous', active: false }, ... ],
 *   meta: { current_page: 1, last_page: 3, per_page: 20, total: 25, from: 1, to: 20 },
 * }
 *
 * `PurchaseOrderSummary` didefinisikan di `types/purchase-order.ts`. Ringkasnya:
 * {
 *   id: 1,
 *   transaction_code: 'PO-2026-0001',
 *   grand_total: 12500000,              // angka mentah, bukan string berformat
 *   created_by: 6,                      // id user pembuat; dasar hak revisi
 *   detail: { pemasok: 'PT vendors', tanggal_po: '...', transportasi: '...' },
 *   current_approval_status: 'NEED_REVISION',
 *   current_approval_role: 'Finance',
 *   current_approval_proceed_by: 'Budi',
 *   current_approval_description: 'Qty salah pada baris gula',
 * }
 *
 * filters: nilai filter aktif yang dikirim balik controller supaya state tetap
 * konsisten setelah reload — { search: '', status: '', date_from: '', date_to: '' }
 *
 * statusOptions: `GetPurchaseOrdersQuery::documentStatusOptions()`, yaitu
 * { APPROVED: 'Selesai disetujui', NEED_REVISION: 'Perlu revisi' }.
 * Nilainya huruf besar karena dikirim apa adanya ke `current_approval_status`.
 *
 * auth: dibagikan Inertia ke semua halaman; dipakai untuk menentukan baris mana
 * yang memunculkan aksi Revisi.
 *
 * Contoh controller (ringkas):
 *   return Inertia::render('PurchaseOrder/Index', [
 *       'purchaseOrders' => $query->paginate(20)->withQueryString(),
 *       'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
 *       'statusOptions' => GetPurchaseOrdersQuery::documentStatusOptions(),
 *   ]);
 */

import { computed, reactive, ref, watch, h } from "vue";
import { router } from "@inertiajs/vue3";
import {
    Search,
    Plus,
    Settings2,
    CalendarRange,
    ChevronDown,
    MoreVertical,
    Eye,
    Pencil,
    FileX,
} from "lucide-vue-next";

import { NButton, NInput, NSelect, NDropdown, NIcon } from "naive-ui";

import ApprovalChain from "@/Components/Feature/PurchaseOrder/ApprovalChain.vue";
import ExpeditionBadge from "@/Components/Feature/PurchaseOrder/ExpeditionBadge.vue";
import PurchaseOrderMobileCard from "@/Components/Feature/PurchaseOrder/PurchaseOrderMobileCard.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import { formatRupiah, formatDate } from "@/utils/format";

const props = defineProps({
    purchaseOrders: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0,
                from: 0,
                to: 0,
            },
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: "", status: "", date_from: "", date_to: "" }),
    },
    statusOptions: {
        // Nilai filter dikirim apa adanya ke `current_approval_status`, jadi
        // harus huruf besar seperti yang tersimpan di database. Default di sini
        // hanya cadangan untuk render tanpa props; `PurchaseOrderController`
        // yang mengisinya lewat `GetPurchaseOrdersQuery::documentStatusOptions()`.
        type: Array,
        default: () => [
            { value: "APPROVED", label: "Selesai disetujui" },
            { value: "NEED_REVISION", label: "Perlu revisi" },
        ],
    },
    // `auth` dibagikan Inertia ke semua halaman; dipakai untuk menentukan baris
    // mana yang memunculkan aksi Revisi.
    auth: {
        type: Object,
        default: () => ({ user: { id: null, name: "" } }),
    },
});

// ── Filter & pencarian ────────────────────────────────────────────────
// Catatan: sentinel 'semua' dipertahankan untuk opsi "tanpa filter status".
// (Sebelumnya wajib karena Radix/shadcn Select melarang value kosong; di
// Naive UI tidak wajib, tapi dipertahankan agar logika applyFilters tetap sama.)
const form = reactive({
    search: props.filters.search ?? "",
    status: props.filters.status || "semua",
});

const loading = ref(false);

// NSelect memakai array `options` ({ label, value }), bukan slot SelectItem.
// Opsi "Semua Status" disisipkan di depan, value-nya sentinel 'semua'.
const selectOptions = computed(() => [
    { label: "Semua Status", value: "semua" },
    ...props.statusOptions.map((o) => ({ label: o.label, value: o.value })),
]);

console.log(selectOptions.value);

// NDropdown memakai array `options` + satu handler @select, bukan slot
// DropdownMenuItem dengan @click masing-masing. Separator: { type: 'divider' }.
//
// Opsi dibuat per baris karena "Revisi" hanya berlaku untuk dokumen
// `NEED_REVISION` milik pembuatnya. Menu lama (Edit/Cetak/Hapus) dihapus:
// `routes/purchase_order.php` tidak punya route untuk ketiganya, jadi
// menampilkannya hanya menawarkan aksi yang pasti gagal.
function rowMenuOptions(po) {
    const options = [
        {
            label: "Lihat Detail",
            key: "view",
            icon: () => h(Eye, { class: "h-4 w-4" }),
        },
    ];

    if (canRevise(po)) {
        options.push({
            label: "Revisi",
            key: "revise",
            icon: () => h(Pencil, { class: "h-4 w-4" }),
        });
    }

    return options;
}

/**
 * Hak revisi hanya milik pembuat dokumen, dan hanya saat statusnya
 * `NEED_REVISION`. `RevisePurchaseOrderAction` menegakkan dua syarat yang sama,
 * jadi ini unpacking UX, bukan pengganti otorisasi.
 */
function canRevise(po) {
    return (
        po.current_approval_status === "NEED_REVISION" &&
        po.created_by === props.auth.user.id
    );
}

function handleRowAction(key, po) {
    if (key === "view") viewPo(po);
    else if (key === "revise") revisePo(po);
}

function redirectCreateForm() {
    router.get(route("purchase-order.create"), {}, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => loading.value = true,
        onFinish: () => loading.value = false
    });
}

function applyFilters() {
    router.get(
        window.location.pathname,
        {
            ...form,
            status: form.status === "semua" ? "" : form.status,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// Debounce pencarian agar tidak request setiap ketikan.
let searchTimeout;
watch(
    () => form.search,
    () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 400);
    },
);

watch(() => form.status, applyFilters);

function gotoPage(url) {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

// ── Seleksi baris ─────────────────────────────────────────────────────
const selectedIds = ref([]);

const allSelected = computed(
    () =>
        props.purchaseOrders.data.length > 0 &&
        selectedIds.value.length === props.purchaseOrders.data.length,
);

function toggleSelectAll(checked) {
    selectedIds.value = checked
        ? props.purchaseOrders.data.map((po) => po.id)
        : [];
}

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(idx, 1);
    }
}

// ── Aksi per baris ────────────────────────────────────────────────────
function viewPo(po) {
    router.visit(route("purchase-order.show", po.id));
}

function revisePo(po) {
    router.get(route("purchase-order.revise", po.id));
}

// ── Util ──────────────────────────────────────────────────────────────
const currencyFormatter = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
});
</script>
