<script setup lang="ts">
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import { NEmpty, NPagination } from "naive-ui";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import StockFilterBar from "@/Components/Feature/Stocks/StockFilterBar.vue";
import type { Paginated } from "@/types/product";
import type { CompanyRow, StockFilters, StockItem } from "@/types/stock";

defineProps<{
    stocks: Paginated<StockItem>;
    filters: StockFilters;
    companies: CompanyRow[];
}>();

const filterBar = ref<InstanceType<typeof StockFilterBar>>();

function goToPage(target: number) {
    filterBar.value?.apply(target);
}
</script>

<template>
    <Head title="Daftar Semua Barang" />

    <AppLayout pageName="Daftar Semua Barang">
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Daftar Semua Barang"
                subTitle="Barang yang tersedia di gudang, dihitung per produk"
            />

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-4">
                    <StockFilterBar
                        ref="filterBar"
                        :companies="companies"
                        :filters="filters"
                        route-name="stocks.index"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table
                        v-if="stocks.data.length"
                        class="w-full min-w-[720px] text-left text-sm"
                    >
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Kode
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Nama Barang
                                </th>
                                <th class="h-10 px-4 text-xs font-medium text-slate-500">
                                    Unit
                                </th>
                                <th class="h-10 px-4 text-right text-xs font-medium text-slate-500">
                                    Total Stok
                                </th>
                                <th class="h-10 px-4 text-right text-xs font-medium text-slate-500">
                                    Jumlah Batch
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="stock in stocks.data"
                                :key="stock.product_id"
                                class="border-b border-slate-50 hover:bg-slate-50"
                            >
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs font-medium text-slate-600">
                                        {{ stock.code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-700">
                                    {{ stock.name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ stock.unit }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-700">
                                    {{ stock.stock }}
                                </td>
                                <td class="px-4 py-3 text-right text-slate-600">
                                    {{ stock.batch_count }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <NEmpty
                        v-else
                        class="py-12"
                        description="Tidak ada barang tersedia dengan filter ini."
                    />
                </div>

                <div
                    v-if="stocks.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-100 p-4"
                >
                    <span class="text-xs text-slate-400">
                        Menampilkan {{ stocks.from }}–{{ stocks.to }} dari
                        {{ stocks.total }} barang
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
