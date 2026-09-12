<script setup lang="ts">
import { computed } from "vue";
import { FileX2 } from "lucide-vue-next";
import { FlexRender, getCoreRowModel, useVueTable } from "@tanstack/vue-table";
import { buildItemColumns } from "./itemColumns";
import type { TransactionItem } from "@/types/purchase-order";

const props = defineProps<{ items: TransactionItem[] }>();
const emit = defineEmits<{ (e: "remove", index: number): void }>();

const columns = buildItemColumns((i) => emit("remove", i));

const table = useVueTable({
    get data() {
        return props.items;
    },
    get columns() {
        return columns;
    },
    getCoreRowModel: getCoreRowModel(),
});

const isEmpty = computed(() => props.items.length === 0);
</script>

<template>
    <div class="overflow-x-auto rounded-xl border border-slate-100">
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
                        class="h-10 whitespace-nowrap px-4 text-left align-middle font-medium text-slate-500"
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
                <template v-if="!isEmpty">
                    <tr
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="border-b border-slate-50 hover:bg-slate-50"
                    >
                        <td
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            class="whitespace-nowrap px-4 py-3 align-middle"
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
                        class="h-40 px-4 align-middle"
                    >
                        <div
                            class="flex flex-col items-center justify-center gap-2 text-slate-300"
                        >
                            <FileX2 class="h-10 w-10" />
                            <p class="text-sm text-slate-400">
                                Belum ada barang ditambahkan
                            </p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
