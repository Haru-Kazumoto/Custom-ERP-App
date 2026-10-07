<script setup lang="ts">
import { computed, reactive, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { NButton, NDatePicker, NInput, NIcon, NTag } from "naive-ui";
import { Search, FileX, ChevronRight } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import { formatDate, formatRupiah } from "@/utils/format";
import type {
    ApprovalQueueFilters,
    DeliveryOrderApprovalQueueItem,
} from "@/types/approval";

const props = defineProps<{
    queue: {
        data: DeliveryOrderApprovalQueueItem[];
        links: { url: string | null; label: string; active: boolean }[];
        total?: number;
        from?: number | null;
        to?: number | null;
    };
    filters: ApprovalQueueFilters;
}>();

const form = reactive({
    search: props.filters.search ?? "",
    date_from: props.filters.date_from ?? "",
    date_to: props.filters.date_to ?? "",
});

const items = computed(() => props.queue.data ?? []);

const total = computed(() => props.queue.total ?? items.value.length);
const rangeStart = computed(() => props.queue.from ?? 0);
const rangeEnd = computed(() => props.queue.to ?? 0);

const hasFilter = computed(
    () =>
        form.search !== "" || form.date_from !== "" || form.date_to !== "",
);

function applyFilters() {
    router.get(
        route("approvals.index.delivery-orders"),
        {
            search: form.search,
            date_from: form.date_from || "",
            date_to: form.date_to || "",
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

function setDate(key: "date_from" | "date_to", value: string | null) {
    form[key] = value ?? "";
    applyFilters();
}

function resetFilters() {
    form.search = "";
    form.date_from = "";
    form.date_to = "";
    applyFilters();
}

function gotoPage(url: string | null) {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}

function openDetail(item: DeliveryOrderApprovalQueueItem) {
    router.visit(route("delivery-order.show", item.id));
}
</script>

<template>
    <Head title="Approval Delivery Order" />

    <AppLayout>
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Approval Delivery Order"
                subTitle="Hanya dokumen yang sedang menunggu keputusan role Anda. Begitu Anda memutuskan, dokumen langsung berpindah ke role berikutnya"
            />

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <!-- Filter -->
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-end"
                >
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <NInput
                            v-model:value="form.search"
                            placeholder="Cari no. DO, nama pelanggan..."
                            class="sm:w-64"
                        >
                            <template #prefix>
                                <NIcon :component="Search" />
                            </template>
                        </NInput>

                        <NDatePicker
                            type="date"
                            :formatted-value="form.date_from || null"
                            value-format="yyyy-MM-dd"
                            clearable
                            placeholder="Dari tanggal"
                            @update:formatted-value="
                                (v: string | null) => setDate('date_from', v)
                            "
                        />

                        <NDatePicker
                            type="date"
                            :formatted-value="form.date_to || null"
                            value-format="yyyy-MM-dd"
                            clearable
                            placeholder="Sampai tanggal"
                            @update:formatted-value="
                                (v: string | null) => setDate('date_to', v)
                            "
                        />
                    </div>
                </div>

                <!-- Tabel -->
                <div class="overflow-x-auto">
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
                                    Tahap Persetujuan
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Jatuh Tempo
                                </th>
                                <th
                                    class="h-10 px-4 text-left align-middle font-medium text-slate-500"
                                >
                                    Total
                                </th>
                                <th
                                    class="h-10 w-10 px-4 text-left align-middle font-medium text-slate-500"
                                />
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="items.length === 0">
                                <td colspan="6" class="px-4 py-16">
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <FileX
                                            class="h-8 w-8 text-slate-300"
                                        />
                                        <p class="font-medium text-slate-700">
                                            {{
                                                hasFilter
                                                    ? "Tidak ada dokumen yang cocok"
                                                    : "Tidak ada dokumen menunggu Anda"
                                            }}
                                        </p>
                                        <p class="text-sm text-slate-400">
                                            {{
                                                hasFilter
                                                    ? "Coba ubah kata kunci atau rentang tanggal"
                                                    : "Delivery Order yang menunggu keputusan role Anda akan muncul di sini"
                                            }}
                                        </p>
                                        <NButton
                                            v-if="hasFilter"
                                            size="small"
                                            class="mt-2"
                                            @click="resetFilters"
                                        >
                                            Reset filter
                                        </NButton>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="item in items"
                                :key="item.id"
                                class="cursor-pointer border-b border-slate-50 hover:bg-slate-50"
                                @click="openDetail(item)"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <p class="font-medium text-slate-900">
                                        {{ item.transaction_code }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        Dibuat
                                        {{ formatDate(item.created_at) }}
                                    </p>
                                </td>

                                <td
                                    class="px-4 py-3 align-middle text-slate-700"
                                >
                                    {{ item.customer ?? "-" }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <p class="text-slate-700">
                                        Tahap {{ item.current_approval_order }}
                                        ·
                                        {{ item.current_approval_role ?? "-" }}
                                    </p>
                                    <NTag
                                        size="small"
                                        :bordered="false"
                                        class="mt-1 bg-amber-50 text-amber-600"
                                    >
                                        Menunggu keputusan Anda
                                    </NTag>
                                </td>

                                <td
                                    class="whitespace-nowrap px-4 py-3 align-middle text-slate-600"
                                >
                                    {{ formatDate(item.due_date) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-4 py-3 align-middle font-medium text-slate-900"
                                >
                                    {{ formatRupiah(item.grand_total) }}
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <ChevronRight
                                        class="h-4 w-4 text-slate-400"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
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
                            v-for="(link, idx) in queue.links"
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
