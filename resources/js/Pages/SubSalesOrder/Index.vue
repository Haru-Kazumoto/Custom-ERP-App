<template>
    <AppLayout>
        <div class="flex flex-col gap-5">
            <!-- Header halaman -->
            <HeaderPage
                title="Daftar Dokumen "
                subTitle="Pantau seluruh dokumen Sub Sales Order beserta status
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
                            Sub Sales Order
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                        >
                            {{
                                subSalesOrders.meta?.total ??
                                subSalesOrders.data.length
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
                                    Nomor SSO
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Nomor Bukti
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Pemasok
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
                            <tr v-if="subSalesOrders.data.length === 0">
                                <td
                                    colspan="6"
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
                                            Dokumen Sub Sales Order yang dibuat
                                            akan muncul di sini
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="sso in subSalesOrders.data"
                                :key="sso.id"
                                class="cursor-pointer border-b border-slate-50 hover:bg-slate-50"
                                @click="viewSso(sso)"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium text-slate-900">
                                        {{ sso.transaction_code }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{
                                            formatDate(sso.created_at, true)
                                        }}
                                    </p>
                                </td>

                                <td
                                    class="px-4 py-3 align-middle text-slate-700"
                                >
                                    {{ sso.no_so ?? "-" }}
                                </td>

                                <td
                                    class="px-4 py-3 align-middle text-slate-700"
                                >
                                    {{ sso.pemasok }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ExpeditionBadge
                                        :nama="sso.nama_ekspedisi ?? '-'"
                                        :mode="sso.nomor_polisi"
                                    />
                                </td>

                                <td class="px-4 py-3 align-middle" @click.stop>
                                    <NDropdown
                                        trigger="click"
                                        placement="bottom-end"
                                        :options="rowMenuOptions"
                                        @select="
                                            (key) => handleRowAction(key, sso)
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
                <!-- 
                <div class="md:hidden p-4">
                    <div
                        v-if="subSalesOrders.data.length === 0"
                        class="flex flex-col items-center gap-2 py-12"
                    >
                        <FileX class="h-8 w-8 text-slate-300" />
                        <p class="font-medium text-slate-700">
                            Belum ada dokumen
                        </p>
                        <p class="text-sm text-slate-400 text-center">
                            Dokumen Sub Sales Order yang dibuat akan muncul di
                            sini
                        </p>
                    </div>

                    <div v-else class="flex flex-col gap-3">
                        <PurchaseOrderMobileCard
                            v-for="sso in subSalesOrders.data"
                            :key="sso.id"
                            :po="sso"
                            :selected="selectedIds.includes(sso.id)"
                            :format-currency="formatRupiah"
                            @toggle-select="toggleSelect"
                            @view="viewSso"
                            @edit="editSso"
                            @print="printSso"
                            @delete="deleteSso"
                        />
                    </div>
                </div>
                -->

                <!-- ── Footer: info halaman + pagination ──────────────────────-->
                <div
                    v-if="subSalesOrders.data.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-slate-500">
                        Menampilkan {{ subSalesOrders.meta?.from }}–{{
                            subSalesOrders.meta?.to
                        }}
                        dari {{ subSalesOrders.meta?.total }} dokumen
                    </p>

                    <div class="flex flex-wrap items-center gap-1">
                        <NButton
                            v-for="(link, idx) in subSalesOrders.links"
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
    subSalesOrders: {
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
    router.get(route("sub-sales-order.create"));
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
        props.subSalesOrders.data.length > 0 &&
        selectedIds.value.length === props.subSalesOrders.data.length,
);

function toggleSelectAll(checked) {
    selectedIds.value = checked
        ? props.subSalesOrders.data.map((po) => po.id)
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
    router.visit(route("sub-sales-order.show", po.id));
}

function editPo(po) {
    if (po.edit_url) router.visit(po.edit_url);
}

function printPo(po) {
    if (po.detail_url) window.open(`${po.detail_url}/cetak`, "_blank");
}

function deletePo(po) {
    if (!confirm(`Hapus Sub Sales Order ${po.no_po}?`)) return;
    router.delete(`/sub-sales-order/${po.id}`, { preserveScroll: true });
}

// ── Util ──────────────────────────────────────────────────────────────
const currencyFormatter = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
});
</script>
