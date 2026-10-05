<script setup lang="ts" generic="TData, TValue">
import { ref } from "vue";
import type { ColumnDef, SortingState } from "@tanstack/vue-table";
import {
    FlexRender,
    getCoreRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from "@tanstack/vue-table";
import { NButton } from "naive-ui";

const props = withDefaults(
    defineProps<{
        columns: ColumnDef<TData, TValue>[];
        data: TData[];
        pageSize?: number;
        /**
         * Pesan saat tabel kosong.
         *
         * Defaults-nya "Belum ada dokumen." supaya pemanggil lama tidak berubah.
         * Modul yang belum ada perlu pesan berbeda — "Modul DO belum tersedia"
         *rogencry honest, sedangkan "Belum ada dokumen" menyiratkan data hilang
         * padahal tabelnya memang belum punya sumber.
         */
        emptyText?: string;
    }>(),
    { emptyText: "Belum ada dokumen." },
);

const sorting = ref<SortingState>([]);

const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    onSortingChange: (updater) => {
        sorting.value =
            typeof updater === "function" ? updater(sorting.value) : updater;
    },
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    initialState: {
        pagination: { pageSize: props.pageSize ?? 5 },
    },
});
</script>

<template>
    <div>
        <div class="overflow-hidden rounded-xl border border-slate-100">
            <table class="w-full caption-bottom text-sm">
                <thead>
                    <tr
                        v-for="hg in table.getHeaderGroups()"
                        :key="hg.id"
                        class="border-b border-slate-100 hover:bg-transparent"
                    >
                        <th
                            v-for="header in hg.headers"
                            :key="header.id"
                            class="h-10 px-4 text-left align-middle text-xs font-medium text-slate-500"
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="table.getRowModel().rows.length">
                        <tr
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            class="border-b border-slate-50 hover:bg-slate-50"
                        >
                            <td
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                                class="px-4 py-3 align-middle"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td
                            :colspan="columns.length"
                            class="h-24 px-4 text-center text-sm text-slate-400"
                        >
{{ emptyText }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="table.getPageCount() > 1"
            class="flex items-center justify-between pt-3"
        >
            <span class="text-xs text-slate-400">
                Halaman {{ table.getState().pagination.pageIndex + 1 }} dari
                {{ table.getPageCount() }}
            </span>
            <div class="flex gap-2">
                <NButton
                    size="small"
                    class="h-8 text-xs"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"
                >
                    Sebelumnya
                </NButton>
                <NButton
                    size="small"
                    class="h-8 text-xs"
                    :disabled="!table.getCanNextPage()"
                    @click="table.nextPage()"
                >
                    Selanjutnya
                </NButton>
            </div>
        </div>
    </div>
</template>
