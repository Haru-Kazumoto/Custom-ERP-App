<script setup lang="ts">
import { FileX2 } from "lucide-vue-next";
import type { SsoItem } from "@/types/sub-sales-order";
import { NButton } from "naive-ui";

defineProps<{
    items: SsoItem[];
    disabled?: boolean;
}>();
const emit = defineEmits<{
    remove: [index: number];
}>();

function deleteItem(index: number) {
    emit("remove", index);
}
</script>

<template>
    <div>
        <!-- Desktop -->
        <div
            class="hidden overflow-x-auto rounded-xl border border-slate-100 sm:block"
        >
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">#</th>
                        <th class="px-4 py-2.5 font-medium">Kode Barang</th>
                        <th class="px-4 py-2.5 font-medium">Nama Barang</th>
                        <th class="px-4 py-2.5 text-center font-medium">
                            Jumlah
                        </th>
                        <th class="px-4 py-2.5 font-medium">Kemasan</th>
                        <th class="px-4 py-2.5 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(item, i) in items"
                        :key="item.id"
                        class="border-t border-slate-50"
                    >
                        <td class="px-4 py-3 text-slate-400">{{ i + 1 }}</td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ item.product_code }}
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-700">
                            {{ item.product_name }}
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600">
                            {{ item.quantity }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ item.product_unit }}
                        </td>
                        <td class="px-4 py-3">
                            <NButton
                                size="small"
                                type="error"
                                :disabled="disabled"
                                @click="() => deleteItem(i)"
                            >
                                Hapus
                            </NButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile -->
        <div class="space-y-2 sm:hidden">
            <div
                v-for="(item, i) in items"
                :key="item.id"
                class="rounded-xl border border-slate-100 p-3"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-700">
                            {{ item.product_name }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ item.product_code }}
                        </p>
                    </div>
                    <span class="shrink-0 text-xs text-slate-400"
                        >#{{ i + 1 }}</span
                    >
                </div>
                <div
                    class="mt-2 flex items-center gap-3 border-t border-slate-50 pt-2 text-xs text-slate-500"
                >
                    <span>{{ item.quantity }} {{ item.product_unit }}</span>
                    <NButton
                        size="small"
                        type="error"
                        :disabled="disabled"
                        @click="deleteItem(i)"
                    >
                        Hapus
                    </NButton>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div
            v-if="!items.length"
            class="flex flex-col items-center justify-center gap-2 py-12 text-slate-300"
        >
            <FileX2 class="h-10 w-10" />
            <p class="text-sm text-slate-400">
                No Data - pilih PO lalu klik Proses
            </p>
        </div>
    </div>
</template>
