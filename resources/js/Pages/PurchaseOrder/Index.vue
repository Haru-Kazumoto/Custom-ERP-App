<template>
    <AppLayout>
        <div class="flex flex-col gap-5">
            <!-- Header halaman -->
            <HeaderPage
                title="Daftar Dokumen "
                subTitle="Pantau seluruh dokumen Purchase Order beserta status
                        persetujuannya"
            >
                <template #action>
                    <div class="flex gap-2 ml-auto">
                        <NButton class="gap-2">
                            <Settings2 class="h-4 w-4" />
                            <span class="inline">Kelola Dokumen</span>
                        </NButton>
                        <NButton
                            class="gap-2 bg-blue-600 hover:bg-blue-700"
                            @click="redirectCreateForm()"
                        >
                            <Plus class="h-4 w-4" />
                            <span class="inline">PO Baru</span>
                        </NButton>
                    </div>
                </template>
            </HeaderPage>

            <!-- Card pembungkus tabel -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <!-- Toolbar: judul + total + filter -->
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-2">
                        <h2 class="font-medium text-slate-900">
                            Purchase Order
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                        >
                            {{
                                purchaseOrders.meta?.total ??
                                purchaseOrders.data.length
                            }}
                        </span>
                    </div>

                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <div class="relative w-full sm:w-64">
                            <Search
                                class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 z-10"
                            />
                            <NInput
                                v-model:value="form.search"
                                placeholder="Cari no. PO, nama vendor..."
                                class="pl-8"
                            />
                        </div>

                        <NSelect
                            v-model:value="form.status"
                            :options="selectOptions"
                            placeholder="Semua Status"
                            class="w-full sm:w-44"
                        />

                        <NButton class="gap-2 justify-between sm:w-auto">
                            <span class="flex items-center gap-2">
                                <CalendarRange class="h-4 w-4" />
                                <span class="hidden sm:inline"
                                    >Rentang Tanggal</span
                                >
                            </span>
                            <ChevronDown class="h-4 w-4 text-slate-400" />
                        </NButton>
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
                                    Total PO
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Pengirim
                                </th>
                                <th
                                    class="h-10 w-10 px-4 text-left align-middle font-medium text-slate-500"
                                />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="purchaseOrders.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-16 text-center align-middle"
                                >
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
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

                            <tr
                                v-for="po in purchaseOrders.data"
                                :key="po.id"
                                class="cursor-pointer border-b border-slate-50 hover:bg-slate-50"
                                @click="viewPo(po)"
                            >

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

                                <td
                                    class="px-4 py-3 align-middle text-slate-700"
                                >
                                    {{ po.detail.pemasok }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ApprovalChain
                                        :last-approval="
                                            po.current_approval_role
                                        "
                                        :status-approval="
                                            po.current_approval_status
                                        "
                                        :proceed-by="
                                            po.current_approval_proceed_by
                                        "
                                    />
                                </td>

                                <td
                                    class="px-4 py-3 align-middle font-medium text-slate-900 whitespace-nowrap"
                                >
                                    {{ formatRupiah(po.grand_total) }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ExpeditionBadge
                                        :nama="po.detail.transportasi ?? '-'"
                                        :mode="po.detail.nomor_polisi"
                                    />
                                </td>

                                <td class="px-4 py-3 align-middle" @click.stop>
                                    <NDropdown
                                        trigger="click"
                                        placement="bottom-end"
                                        :options="rowMenuOptions"
                                        @select="
                                            (key) => handleRowAction(key, po)
                                        "
                                    >
                                        <NButton
                                            quaternary
                                            circle
                                            size="small"
                                            class="h-8 w-8"
                                        >
                                            <MoreVertical
                                                class="h-4 w-4 text-slate-500"
                                            />
                                        </NButton>
                                    </NDropdown>
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
                            Belum ada dokumen
                        </p>
                        <p class="text-sm text-slate-400 text-center">
                            Dokumen Purchase Order yang dibuat akan muncul di
                            sini
                        </p>
                    </div>

                    <div v-else class="flex flex-col gap-3">
                        <PurchaseOrderMobileCard
                            v-for="po in purchaseOrders.data"
                            :key="po.id"
                            :po="po"
                            :selected="selectedIds.includes(po.id)"
                            :format-currency="formatRupiah"
                            @toggle-select="toggleSelect"
                            @view="viewPo"
                            @edit="editPo"
                            @print="printPo"
                            @delete="deletePo"
                        />
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

<script setup>
/**
 * Halaman: Daftar Dokumen — Purchase Order
 * Stack: Laravel + Inertia.js + Vue 3 + Tailwind CSS + Naive UI
 *
 * ───────────────────────────────────────────────────────────────────────
 * KONTRAK DATA DARI BACKEND (Inertia props)
 * ───────────────────────────────────────────────────────────────────────
 *
 * purchaseOrders (object, hasil dari Model::paginate() yang dilempar
 * langsung sebagai prop Inertia):
 * {
 *   data: [
 *     {
 *       id: 1,
 *       no_po: 'PO-2026-0001',
 *       vendor: { id: 10, nama: 'Los Pollos Hermanos' },
 *       total: 12500000,                         // angka mentah (integer/float), bukan string
 *       ekspedisi: { nama: 'JNE Logistics', mode: 'darat' }, // mode: darat | laut | udara
 *       persetujuan: {
 *         approvers: [
 *           { id: 1, nama: 'Able Anthony', avatar_url: null, status: 'disetujui', urutan: 1 },
 *           { id: 2, nama: 'Bamasaye Mobolaji', avatar_url: null, status: 'menunggu', urutan: 2 },
 *         ],
 *       },
 *       dibuat_oleh: { nama: 'Able Anthony' },
 *       tanggal: '2026-06-18',
 *       detail_url: '/purchase-order/1',
 *       edit_url: '/purchase-order/1/edit',
 *     },
 *     // ...
 *   ],
 *   links: [
 *     { url: null, label: '&laquo; Previous', active: false },
 *     { url: '/purchase-order?page=1', label: '1', active: true },
 *     { url: '/purchase-order?page=2', label: '2', active: false },
 *     { url: null, label: 'Next &raquo;', active: false },
 *   ],
 *   meta: { current_page: 1, last_page: 3, per_page: 10, total: 25, from: 1, to: 10 },
 * }
 *
 * filters (object, nilai filter yang sedang aktif — dikirim balik dari
 * controller supaya state filter tetap konsisten setelah reload):
 * { search: '', status: '', date_from: '', date_to: '' }
 *
 * statusOptions (array, daftar opsi status untuk dropdown filter):
 * [{ value: 'menunggu', label: 'Menunggu Persetujuan' }, ...]
 *
 * Contoh controller Laravel (ringkas):
 *   return Inertia::render('PurchaseOrder/DaftarDokumen', [
 *       'purchaseOrders' => $query->paginate(10)->withQueryString(),
 *       'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
 *       'statusOptions' => StatusPersetujuan::options(),
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
    Printer,
    Trash2,
    FileX,
} from "lucide-vue-next";

import { NButton, NInput, NCheckbox, NSelect, NDropdown } from "naive-ui";

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
        type: Array,
        default: () => [
            { value: "menunggu", label: "Menunggu Persetujuan" },
            { value: "disetujui", label: "Disetujui" },
            { value: "ditolak", label: "Ditolak" },
            { value: "selesai", label: "Selesai" },
        ],
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

// NSelect memakai array `options` ({ label, value }), bukan slot SelectItem.
// Opsi "Semua Status" disisipkan di depan, value-nya sentinel 'semua'.
const selectOptions = computed(() => [
    { label: "Semua Status", value: "semua" },
    ...props.statusOptions.map((o) => ({ label: o.label, value: o.value })),
]);

// NDropdown memakai array `options` + satu handler @select, bukan slot
// DropdownMenuItem dengan @click masing-masing. Separator: { type: 'divider' }.
const rowMenuOptions = [
    {
        label: "Lihat Detail",
        key: "view",
        icon: () => h(Eye, { class: "h-4 w-4" }),
    },
    { label: "Edit", key: "edit", icon: () => h(Pencil, { class: "h-4 w-4" }) },
    {
        label: "Cetak PDF",
        key: "print",
        icon: () => h(Printer, { class: "h-4 w-4" }),
    },
    { type: "divider", key: "d1" },
    {
        label: "Hapus",
        key: "delete",
        icon: () => h(Trash2, { class: "h-4 w-4" }),
        props: { style: "color:#dc2626" },
    },
];

function handleRowAction(key, po) {
    if (key === "view") viewPo(po);
    else if (key === "edit") editPo(po);
    else if (key === "print") printPo(po);
    else if (key === "delete") deletePo(po);
}

function redirectCreateForm() {
    router.get(route("purchase-order.create"));
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

function editPo(po) {
    if (po.edit_url) router.visit(po.edit_url);
}

function printPo(po) {
    if (po.detail_url) window.open(`${po.detail_url}/cetak`, "_blank");
}

function deletePo(po) {
    if (!confirm(`Hapus Purchase Order ${po.no_po}?`)) return;
    router.delete(`/purchase-order/${po.id}`, { preserveScroll: true });
}

// ── Util ──────────────────────────────────────────────────────────────
const currencyFormatter = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
});
</script>
