<script setup lang="ts">
import { computed, reactive, watch, h } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { NButton, NInput, NSelect, NDropdown, NIcon } from "naive-ui";
import { Search, Plus, MoreVertical, Eye, FileX } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import ApprovalChain from "@/Components/Feature/PurchaseOrder/ApprovalChain.vue";
import { formatRupiah, formatDate } from "@/utils/format";
import type { DeliveryOrderSummary } from "@/types/delivery-order";

const props = defineProps<{
    deliveryOrders: {
        data: DeliveryOrderSummary[];
        links: { url: string | null; label: string; active: boolean }[];
        // `LengthAwarePaginator::toArray()` menaruh metadata di level atas
        // (bukan `meta`), jadi kedua bentuk ditangani.
        total?: number;
        from?: number | null;
        to?: number | null;
        meta?: { total?: number; from?: number | null; to?: number | null };
    };
    filters?: { search?: string; status?: string };
    statusOptions?: { value: string; label: string }[];
}>();

const form = reactive({
    search: props.filters?.search ?? "",
    status: props.filters?.status || "semua",
});

const items = computed(() => props.deliveryOrders.data ?? []);
const total = computed(
    () =>
        props.deliveryOrders.total ??
        props.deliveryOrders.meta?.total ??
        items.value.length,
);
const rangeStart = computed(
    () =>
        props.deliveryOrders.from ?? props.deliveryOrders.meta?.from ?? 0,
);
const rangeEnd = computed(
    () => props.deliveryOrders.to ?? props.deliveryOrders.meta?.to ?? 0,
);

const selectOptions = computed(() => [
    { label: "Semua Status", value: "semua" },
    ...(props.statusOptions ?? []).map((o) => ({
        label: o.label,
        value: o.value,
    })),
]);

function applyFilters() {
    router.get(
        route("delivery-order.index"),
        {
            search: form.search,
            status: form.status === "semua" ? "" : form.status,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(
    () => form.search,
    () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 400);
    },
);

watch(() => form.status, applyFilters);

function gotoPage(url: string | null) {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

function viewDo(item: DeliveryOrderSummary) {
    router.visit(route("delivery-order.show", item.id));
}

function createDo() {
    router.get(route("delivery-order.create"), {}, {
        preserveScroll: true,
    });
}

function rowMenuOptions() {
    return [
        {
            label: "Lihat Detail",
            key: "view",
            icon: () => h(Eye, { class: "h-4 w-4" }),
        },
    ];
}
</script>

<template>
    <Head title="Daftar Dokumen Delivery Order" />

    <AppLayout>
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Daftar Dokumen"
                subTitle="Pantau seluruh dokumen Delivery Order beserta status persetujuannya"
            >
                <template #action>
                    <div class="ml-auto flex gap-2">
                        <NButton
                            type="primary"
                            size="large"
                            @click="createDo"
                        >
                            <template #icon>
                                <NIcon :component="Plus" />
                            </template>
                            DO Baru
                        </NButton>
                    </div>
                </template>
            </HeaderPage>

            <div
                class="rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <!-- Toolbar -->
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-2">
                        <h2 class="font-medium text-slate-900">
                            Delivery Order
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                        >
                            {{ total }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <NInput
                            v-model:value="form.search"
                            placeholder="Cari no. DO, pelanggan..."
                            class="sm:w-64"
                        >
                            <template #prefix>
                                <NIcon :component="Search" />
                            </template>
                        </NInput>

                        <NSelect
                            v-model:value="form.status"
                            :options="selectOptions"
                            placeholder="Semua Status"
                        />
                    </div>
                </div>

                <!-- Tabel (desktop) -->
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
                                    Pengiriman
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Persetujuan
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Total DO
                                </th>
                                <th
                                    class="h-10 w-10 px-4 text-left align-middle font-medium text-slate-500"
                                />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="items.length === 0">
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
                                        <p class="font-medium text-slate-700">
                                            Belum ada dokumen
                                        </p>
                                        <p class="text-sm text-slate-400">
                                            Dokumen Delivery Order yang dibuat
                                            akan muncul di sini
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="item in items"
                                :key="item.id"
                                class="cursor-pointer border-b border-slate-50 hover:bg-slate-50"
                                @click="viewDo(item)"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium text-slate-900">
                                        {{ item.transaction_code }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        Dibuat {{ formatDate(item.created_at) }}
                                    </p>
                                </td>

                                <td
                                    class="px-4 py-3 align-middle text-slate-700"
                                >
                                    {{ item.detail.customer ?? "-" }}
                                </td>

                                <td
                                    class="px-4 py-3 align-middle text-slate-700"
                                >
                                    {{ item.detail.delivery ?? "-" }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ApprovalChain
                                        :last-approval="
                                            item.current_approval_role
                                        "
                                        :status-approval="
                                            item.current_approval_status
                                        "
                                        :proceed-by="
                                            item.current_approval_proceed_by
                                        "
                                    />
                                </td>

                                <td
                                    class="whitespace-nowrap px-4 py-3 align-middle font-medium text-slate-900"
                                >
                                    {{ formatRupiah(item.grand_total) }}
                                </td>

                                <td class="px-4 py-3 align-middle" @click.stop>
                                    <NDropdown
                                        trigger="click"
                                        placement="bottom-end"
                                        :options="rowMenuOptions()"
                                        @select="
                                            (key: string) =>
                                                key === 'view' && viewDo(item)
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

                <!-- Kartu (mobile) -->
                <div class="p-4 md:hidden">
                    <div
                        v-if="items.length === 0"
                        class="flex flex-col items-center gap-2 py-12"
                    >
                        <FileX class="h-8 w-8 text-slate-300" />
                        <p class="font-medium text-slate-700">
                            Belum ada dokumen
                        </p>
                        <p class="text-center text-sm text-slate-400">
                            Dokumen Delivery Order yang dibuat akan muncul di
                            sini
                        </p>
                    </div>

                    <div v-else class="flex flex-col gap-3">
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="cursor-pointer rounded-xl border border-slate-100 p-3"
                            @click="viewDo(item)"
                        >
                            <div
                                class="flex items-start justify-between gap-2"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-slate-900"
                                    >
                                        {{ item.transaction_code }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ item.detail.customer ?? "-" }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 text-sm font-semibold text-slate-800"
                                >
                                    {{ formatRupiah(item.grand_total) }}
                                </span>
                            </div>
                            <div
                                class="mt-2 border-t border-slate-50 pt-2 text-xs text-slate-500"
                            >
                                {{
                                    item.detail.delivery ?? "-"
                                }}
                                · {{ formatDate(item.created_at) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    v-if="items.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-slate-500">
                        Menampilkan {{ rangeStart }}–{{ rangeEnd }} dari
                        {{ total }} dokumen
                    </p>

                    <div class="flex flex-wrap items-center gap-1">
                        <NButton
                            v-for="(link, idx) in deliveryOrders.links"
                            :key="idx"
                            size="small"
                            :class="
                                link.active ? 'bg-blue-600 hover:bg-blue-700' : ''
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
