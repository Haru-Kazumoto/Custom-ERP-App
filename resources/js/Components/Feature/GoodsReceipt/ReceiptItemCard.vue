<script setup lang="ts">
import { computed } from "vue";
import {
    NButton,
    NDatePicker,
    NInput,
    NInputNumber,
    NSelect,
} from "naive-ui";
import { Plus, Trash2 } from "lucide-vue-next";
import type { ReceiptItemForm } from "@/types/goods-receipt";

const props = defineProps<{
    item: ReceiptItemForm;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    addSplit: [];
    removeSplit: [index: number];
    addDiscrepancy: [];
    removeDiscrepancy: [index: number];
}>();

const discrepancyTypeOptions = [
    { label: "Rusak", value: "DAMAGED" },
    { label: "Hilang", value: "LOST" },
    { label: "Kurang", value: "SHORTAGE" },
    { label: "Bertahap", value: "GRADUALLY" },
    { label: "Lainnya", value: "OTHER" },
];

const totalSplit = computed(() =>
    props.item.splits.reduce((sum, split) => sum + (split.quantity ?? 0), 0),
);

const splitMismatch = computed(
    () => totalSplit.value !== (props.item.received_qty ?? 0),
);

// Qty yang masih menyusul datang (kondisi Bertahap/GRADUALLY).
const gradualPending = computed(() =>
    props.item.discrepancies
        .filter((discrepancy) => discrepancy.type === "GRADUALLY")
        .reduce(
            (sum, discrepancy) => sum + (discrepancy.remaining_qty ?? 0),
            0,
        ),
);

const shortageQty = computed(() => {
    const received = props.item.received_qty ?? 0;
    const notYetReceived = props.item.ordered_qty - received;
    const shortage = notYetReceived - gradualPending.value;

    return shortage > 0 ? shortage : 0;
});
</script>

<template>
    <div class="rounded-xl border border-slate-200 p-4">
        <!-- Header item -->
        <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-700">
                    {{ item.product_name }}
                </p>
                <p class="text-xs text-slate-400">
                    {{ item.product_code }} &middot; Qty SSO:
                    {{ item.ordered_qty }} {{ item.product_unit }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-slate-500"
                    >Qty Diterima</label
                >
                <NInputNumber
                    v-model:value="item.received_qty"
                    :min="0"
                    :max="item.ordered_qty"
                    :disabled="disabled"
                    class="w-28"
                />
            </div>
        </div>

        <!-- Peringatan kekurangan otomatis -->
        <div
            v-if="shortageQty > 0"
            class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700"
        >
            Kekurangan {{ shortageQty }} {{ item.product_unit }} dari qty SSO
            — akan dicatat otomatis sebagai discrepancy SHORTAGE.
        </div>
        <div
            v-if="gradualPending > 0"
            class="mt-3 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-700"
        >
            {{ gradualPending }} {{ item.product_unit }} tertunda (Bertahap)
            — belum masuk stok, dicatat di /stocks/gradually dan menyusul
            datang.
        </div>

        <!-- Pemecahan kode barang — hanya untuk yang benar-benar datang -->
        <div
            v-if="(item.received_qty ?? 0) > 0"
            class="mt-4"
        >
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Pemecahan Kode Barang
                </p>
                <NButton
                    size="tiny"
                    tertiary
                    type="primary"
                    :disabled="disabled"
                    @click="emit('addSplit')"
                >
                    <template #icon>
                        <Plus class="h-3.5 w-3.5" />
                    </template>
                    Tambah
                </NButton>
            </div>

            <div class="mt-2 space-y-2">
                <div
                    v-for="(split, splitIndex) in item.splits"
                    :key="splitIndex"
                    class="grid grid-cols-1 gap-2 rounded-lg bg-slate-50 p-3 sm:grid-cols-[1fr_7rem_9rem_9rem_auto]"
                >
                    <div class="space-y-1">
                        <label class="text-xs text-slate-400">Kode Barang</label>
                        <NInput
                            v-model:value="split.batch_code"
                            size="small"
                            :disabled="disabled"
                            placeholder="mis. TPG-AA1"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-slate-400">Qty</label>
                        <NInputNumber
                            v-model:value="split.quantity"
                            size="small"
                            :min="1"
                            :disabled="disabled"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-slate-400">Expired</label>
                        <NDatePicker
                            v-model:formatted-value="split.expiry_date"
                            value-format="yyyy-MM-dd"
                            type="date"
                            size="small"
                            :disabled="disabled"
                            class="w-full"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-slate-400"
                            >Batas Stagnasi</label
                        >
                        <NDatePicker
                            v-model:formatted-value="split.stagnation_limit_date"
                            value-format="yyyy-MM-dd"
                            type="date"
                            size="small"
                            :disabled="disabled"
                            class="w-full"
                        />
                    </div>
                    <div class="flex items-end justify-end">
                        <NButton
                            size="small"
                            type="error"
                            quaternary
                            :disabled="disabled || item.splits.length <= 1"
                            @click="emit('removeSplit', splitIndex)"
                        >
                            <template #icon>
                                <Trash2 class="h-4 w-4" />
                            </template>
                        </NButton>
                    </div>
                </div>
            </div>

            <p
                class="mt-2 text-xs"
                :class="
                    splitMismatch ? 'text-rose-500' : 'text-slate-400'
                "
            >
                Total pecahan: {{ totalSplit }} / diterima:
                {{ item.received_qty ?? 0 }}
                <span v-if="splitMismatch"
                    >— harus sama persis</span
                >
            </p>
        </div>

        <!-- Kondisi / kekurangan manual -->
        <div class="mt-4 border-t border-slate-100 pt-3">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Catatan Kondisi Barang
                </p>
                <NButton
                    size="tiny"
                    tertiary
                    :disabled="disabled"
                    @click="emit('addDiscrepancy')"
                >
                    <template #icon>
                        <Plus class="h-3.5 w-3.5" />
                    </template>
                    Tambah
                </NButton>
            </div>

            <div
                v-if="!item.discrepancies.length"
                class="mt-2 text-xs text-slate-300"
            >
                Tidak ada catatan kondisi tambahan.
            </div>

            <div class="mt-2 space-y-2">
                <div
                    v-for="(discrepancy, discrepancyIndex) in item.discrepancies"
                    :key="discrepancyIndex"
                    class="grid grid-cols-1 gap-2 rounded-lg bg-rose-50/50 p-3 sm:grid-cols-[10rem_7rem_1fr_auto]"
                >
                    <div class="space-y-1">
                        <label class="text-xs text-slate-400">Kondisi</label>
                        <NSelect
                            v-model:value="discrepancy.type"
                            size="small"
                            :options="discrepancyTypeOptions"
                            :disabled="disabled"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-slate-400">Qty</label>
                        <NInputNumber
                            v-model:value="discrepancy.remaining_qty"
                            size="small"
                            :min="1"
                            :disabled="disabled"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-slate-400">Keterangan</label>
                        <NInput
                            v-model:value="discrepancy.description"
                            size="small"
                            :disabled="disabled"
                            placeholder="mis. kemasan sobek"
                        />
                    </div>
                    <div class="flex items-end justify-end">
                        <NButton
                            size="small"
                            type="error"
                            quaternary
                            :disabled="disabled"
                            @click="emit('removeDiscrepancy', discrepancyIndex)"
                        >
                            <template #icon>
                                <Trash2 class="h-4 w-4" />
                            </template>
                        </NButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
