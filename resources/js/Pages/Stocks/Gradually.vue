<script setup lang="ts">
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { NButton, NEmpty, NPagination, NTag } from "naive-ui";
import { PackageCheck } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import StockFilterBar from "@/Components/Feature/Stocks/StockFilterBar.vue";
import GradualArrivalModal from "@/Components/Feature/Stocks/GradualArrivalModal.vue";
import type { Paginated } from "@/types/product";
import type { CompanyRow, GradualItem, StockFilters } from "@/types/stock";

defineProps<{
    stocks: Paginated<GradualItem>;
    filters: StockFilters;
    companies: CompanyRow[];
}>();

const filterBar = ref<InstanceType<typeof StockFilterBar>>();
const arrivalOpen = ref(false);
const activeItem = ref<GradualItem | null>(null);

function goToPage(target: number) {
    filterBar.value?.apply(target);
}

function openArrival(item: GradualItem) {
    activeItem.value = item;
    arrivalOpen.value = true;
}

function onSaved() {
    // Muat ulang daftar dengan filter tetap berlaku.
    router.reload({ only: ["stocks"], preserveScroll: true });
}

function formatDate(value: string | null) {
    if (!value) return "-";
    return value.slice(0, 10);
}
</script>

<template>
    <Head title="Daftar Barang Tertunda" />

    <AppLayout pageName="Daftar Barang Tertunda">
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Daftar Barang Tertunda"
                subTitle="Barang berstatus Bertahap yang masih menunggu kedatangan menyusul"
            />

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-4">
                    <StockFilterBar
                        ref="filterBar"
                        :companies="companies"
                        :filters="filters"
                        route-name="stocks.index.gradually"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table
                        v-if="stocks.data.length"
                        class="w-full min-w-[960px] text-left text-sm"
                    >
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Kode Produk
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Nama Barang
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    SSO Asal
                                </th>
                                <th class="h-10 px-4 text-right text-xs font-medium text-slate-500">
                                    Qty Tertunda
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Tgl Terima
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Keterangan
                                </th>
                                <th class="h-10 px-4 text-right text-xs font-medium text-slate-500">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in stocks.data"
                                :key="item.id"
                                class="border-b border-slate-50 hover:bg-slate-50"
                            >
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs font-medium text-slate-600">
                                        {{ item.product_code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-700">
                                    {{ item.product_name }}
                                </td>
                                <td class="px-4 py-3">
                                    <NTag size="small" :bordered="false">
                                        {{ item.sso_code }}
                                    </NTag>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-semibold text-slate-700">
                                        {{ item.remaining_qty }}
                                    </span>
                                    <span class="text-xs font-normal text-slate-400">
                                        {{ item.product_unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ formatDate(item.received_at) }}
                                </td>
                                <td class="px-4 py-3 text-slate-500">
                                    {{ item.description || "-" }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <NButton
                                        size="small"
                                        type="primary"
                                        secondary
                                        :disabled="item.remaining_qty <= 0"
                                        @click="openArrival(item)"
                                    >
                                        <template #icon>
                                            <PackageCheck class="h-4 w-4" />
                                        </template>
                                        Barang Datang
                                    </NButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <NEmpty
                        v-else
                        class="py-12"
                        description="Tidak ada barang tertunda dengan filter ini."
                    />
                </div>

                <div
                    v-if="stocks.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-100 p-4"
                >
                    <span class="text-xs text-slate-400">
                        Menampilkan {{ stocks.from }}–{{ stocks.to }} dari
                        {{ stocks.total }} barang tertunda
                    </span>
                    <NPagination
                        :page="stocks.current_page"
                        :page-count="stocks.last_page"
                        size="small"
                        @update:page="goToPage"
                    />
                </div>
            </div>
        </div>

        <GradualArrivalModal
            v-model:show="arrivalOpen"
            :item="activeItem"
            @saved="onSaved"
        />
    </AppLayout>
</template>
