<script setup lang="ts">
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import { NEmpty, NPagination, NTag } from "naive-ui";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import StockFilterBar from "@/Components/Feature/Stocks/StockFilterBar.vue";
import type { Paginated } from "@/types/product";
import type { CompanyRow, StockBatch, StockFilters } from "@/types/stock";

defineProps<{
    stocks: Paginated<StockBatch>;
    filters: StockFilters;
    companies: CompanyRow[];
}>();

const filterBar = ref<InstanceType<typeof StockFilterBar>>();

function goToPage(target: number) {
    filterBar.value?.apply(target);
}

function formatDate(value: string | null) {
    if (!value) return "-";
    return value.slice(0, 10);
}
</script>

<template>
    <Head title="Daftar Barang Stagnan" />

    <AppLayout pageName="Daftar Barang Stagnan">
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Daftar Barang Stagnan"
                subTitle="Batch yang sudah melewati batas stagnasi dan masih menyimpan stok"
            />

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-4">
                    <StockFilterBar
                        ref="filterBar"
                        :companies="companies"
                        :filters="filters"
                        route-name="stocks.index.stagnations"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table
                        v-if="stocks.data.length"
                        class="w-full min-w-[900px] text-left text-sm"
                    >
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Kode Barang
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Produk
                                </th>
                                <th class="h-10 px-4 text-right text-xs font-medium text-slate-500">
                                    Qty Stok
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Batas Stagnasi
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Expired
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Terakhir Masuk
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="batch in stocks.data"
                                :key="`${batch.product_id}-${batch.batch_code}`"
                                class="border-b border-slate-50 hover:bg-slate-50"
                            >
                                <td class="px-4 py-3">
                                    <NTag size="small" :bordered="false" type="primary">
                                        {{ batch.batch_code }}
                                    </NTag>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium text-slate-700">
                                        {{ batch.name }}
                                    </span>
                                    <span class="ml-2 font-mono text-xs text-slate-400">
                                        {{ batch.code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-700">
                                    {{ batch.stock }}
                                    <span class="text-xs font-normal text-slate-400">
                                        {{ batch.unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <NTag size="small" :bordered="false" type="error">
                                        {{ formatDate(batch.stagnation_limit_date) }}
                                    </NTag>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ formatDate(batch.expiry_date) }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ formatDate(batch.last_received_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <NEmpty
                        v-else
                        class="py-12"
                        description="Tidak ada barang stagnan dengan filter ini."
                    />
                </div>

                <div
                    v-if="stocks.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-100 p-4"
                >
                    <span class="text-xs text-slate-400">
                        Menampilkan {{ stocks.from }}–{{ stocks.to }} dari
                        {{ stocks.total }} barang stagnan
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
    </AppLayout>
</template>
