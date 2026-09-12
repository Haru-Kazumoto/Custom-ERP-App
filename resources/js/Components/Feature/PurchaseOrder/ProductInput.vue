<script setup lang="ts">
import { computed } from "vue";
import { NCard, NSelect, NInputNumber, NButton } from "naive-ui";
import {
    Package,
    Tag,
    Building2,
    Box,
    Plus,
    Minus,
    BadgePercent,
} from "lucide-vue-next";
import { formatRupiah } from "@/utils/format";
import type {
    ProductOption,
    TradePromoOption,
} from "@/composables/usePurchaseOrder";

const props = defineProps<{
    options: ProductOption[];
    loading: boolean;
    selectedId: number | null;
    selectedProduct: ProductOption | null;
    unit: string;
    quantity: number | null;
    amount: number | null;
    lineTotal: number;
    usePromo: boolean; // ← ganti dari showPrice
    tradePromoOptions: TradePromoOption[];
    tradePromoId: number | null;
    amountDiscount: number | null;
    quotaTradePromo: number | null;
}>();

const emit = defineEmits<{
    (e: "update:selectedId", v: number | null): void;
    (e: "update:quantity", v: number | null): void;
    (e: "update:amount", v: number | null): void;
    (e: "update:tradePromoId", v: number | null): void;
    (e: "search", q: string): void;
    (e: "add"): void;
}>();

const selectOptions = computed(() =>
    props.options.map((o) => ({ label: o.label, value: o.value })),
);

const tradePromoSelectOptions = computed(() =>
    props.tradePromoOptions.map((o) => ({ label: o.label, value: o.value })),
);

const hasPromo = computed(() => props.tradePromoOptions.length > 0);

const canAdd = computed(() => {
    const priceOk = props.usePromo
        ? props.amountDiscount != null
        : !!props.amount;
    return !!props.selectedId && !!props.quantity && priceOk;
});
</script>

<template>
    <NCard :bordered="true" class="border-slate-200 shadow-sm">
        <template #header>
            <div>
                <h3 class="text-base font-semibold text-slate-800">
                    Penginputan Barang
                </h3>
                <p class="text-sm font-normal text-slate-400">
                    Cari dan pilih barang, lalu tentukan jumlah serta harga.
                </p>
            </div>
        </template>

        <!-- Step 1: cari produk -->
        <div class="space-y-1.5">
            <label class="block text-sm font-medium text-slate-700">
                Cari Barang <span class="text-rose-500">*</span>
            </label>
            <NSelect
                :value="selectedId"
                :options="selectOptions"
                :loading="loading"
                remote
                filterable
                clearable
                placeholder="Ketik nama atau kode barang…"
                :clear-filter-after-select="false"
                @search="(q) => emit('search', q)"
                @update:value="(v) => emit('update:selectedId', v)"
            />
            <p class="text-xs text-slate-400">
                Mulai mengetik untuk memuat barang dari katalog.
            </p>
        </div>

        <!-- Step 2: detail + input (muncul setelah produk dipilih) -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
        >
            <div v-if="selectedProduct" class="mt-5">
                <!-- Kartu detail produk -->
                <div class="rounded-xl border border-sky-100 bg-sky-50/50 p-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0284c7] text-white"
                        >
                            <Package class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-800">
                                {{ selectedProduct.name }}
                            </p>
                            <div
                                class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500"
                            >
                                <span class="inline-flex items-center gap-1"
                                    ><Tag class="h-3.5 w-3.5" />
                                    {{ selectedProduct.code }}</span
                                >
                                <span class="inline-flex items-center gap-1"
                                    ><Box class="h-3.5 w-3.5" />
                                    {{ selectedProduct.unit }}</span
                                >
                                <span class="inline-flex items-center gap-1"
                                    ><Building2 class="h-3.5 w-3.5" />
                                    {{ selectedProduct.vendor }}</span
                                >
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span
                                class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-[#0284c7]"
                            >
                                {{ selectedProduct.category }}
                            </span>
                            <span
                                v-if="hasPromo"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-600"
                            >
                                <BadgePercent class="h-3.5 w-3.5" /> Trade Promo
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Input grid -->
                <div
                    class="mt-4 grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <!-- Jumlah dengan stepper -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            Jumlah <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <NButton
                                quaternary
                                circle
                                @click="
                                    emit(
                                        'update:quantity',
                                        Math.max(1, (quantity || 1) - 1),
                                    )
                                "
                            >
                                <template #icon
                                    ><Minus class="h-4 w-4"
                                /></template>
                            </NButton>
                            <NInputNumber
                                :value="quantity"
                                :min="1"
                                :show-button="false"
                                class="flex-1 text-center"
                                placeholder="0"
                                @update:value="
                                    (v) => emit('update:quantity', v)
                                "
                            />
                            <NButton
                                quaternary
                                circle
                                @click="
                                    emit('update:quantity', (quantity || 0) + 1)
                                "
                            >
                                <template #icon
                                    ><Plus class="h-4 w-4"
                                /></template>
                            </NButton>
                        </div>
                        <p class="text-xs text-slate-400">
                            Satuan: {{ unit || "-" }}
                        </p>
                    </div>

                    <!-- Harga Satuan (saat TIDAK ada promo) -->
                    <!-- Harga Satuan (selalu tampil; di-disable saat promo aktif) -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            Harga Satuan <span class="text-rose-500">*</span>
                        </label>
                        <NInputNumber
                            :value="amount"
                            :min="0"
                            :show-button="false"
                            :disabled="usePromo"
                            placeholder="0"
                            :class="
                                usePromo ? 'bg-slate-50 line-through-hint' : ''
                            "
                            @update:value="(v) => emit('update:amount', v)"
                        >
                            <template #prefix
                                ><span class="text-xs text-slate-400"
                                    >Rp</span
                                ></template
                            >
                        </NInputNumber>
                        <p class="text-xs text-slate-400">
                            {{
                                usePromo
                                    ? "Dinonaktifkan karena promo dipakai"
                                    : "Termasuk PPN · bisa diubah"
                            }}
                        </p>
                    </div>

                    <!-- Pelanggan (Trade Promo) — OPSIONAL, hanya bila produk punya promo -->
                    <div v-if="tradePromoOptions.length" class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            Pelanggan (Trade Promo)
                            <span class="font-normal text-slate-400"
                                >· opsional</span
                            >
                        </label>
                        <NSelect
                            :value="tradePromoId"
                            :options="tradePromoSelectOptions"
                            clearable
                            filterable
                            placeholder="Tanpa promo (harga asli)"
                            @update:value="
                                (v) => emit('update:tradePromoId', v)
                            "
                        />
                        <p
                            v-if="usePromo"
                            class="text-xs"
                            :class="
                                quotaTradePromo === 0
                                    ? 'text-rose-500'
                                    : 'text-slate-400'
                            "
                        >
                            Kuota tersisa: {{ quotaTradePromo ?? "-" }}
                        </p>
                        <p v-else class="text-xs text-slate-400">
                            Kosongkan untuk memakai harga asli.
                        </p>
                    </div>

                    <!-- Harga Trade Promo (read-only, hanya saat promo aktif) -->
                    <div v-if="usePromo" class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700"
                            >Harga Trade Promo</label
                        >
                        <NInputNumber
                            :value="amountDiscount"
                            :show-button="false"
                            disabled
                            class="bg-amber-50"
                        >
                            <template #prefix
                                ><span class="text-xs text-slate-400"
                                    >Rp</span
                                ></template
                            >
                        </NInputNumber>
                        <p class="text-xs text-amber-600">
                            Harga ini menggantikan harga asli.
                        </p>
                    </div>

                    <!-- Harga Trade Promo (saat promo dipilih, read-only) -->
                    <div v-else class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            Harga Trade Promo
                        </label>
                        <NInputNumber
                            :value="amountDiscount"
                            :show-button="false"
                            disabled
                            class="bg-slate-50"
                        >
                            <template #prefix
                                ><span class="text-xs text-slate-400"
                                    >Rp</span
                                ></template
                            >
                        </NInputNumber>
                        <p class="text-xs text-slate-400">
                            Harga khusus dari promo terpilih
                        </p>
                    </div>

                    <!-- Preview subtotal item -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700"
                            >Subtotal Item</label
                        >
                        <div
                            class="flex h-[34px] items-center rounded-lg bg-slate-50 px-3"
                        >
                            <span class="font-semibold text-slate-800">{{
                                formatRupiah(lineTotal)
                            }}</span>
                        </div>
                        <p class="text-xs text-slate-400">
                            Harga × jumlah (exclude PPN)
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <NButton
                        class="text-white"
                        :class="
                            canAdd
                                ? 'bg-[#0284c7] hover:bg-[#0369a1]'
                                : 'bg-slate-300'
                        "
                        :disabled="!canAdd"
                        @click="emit('add')"
                    >
                        <template #icon><Plus class="h-4 w-4" /></template>
                        Tambah Produk
                    </NButton>
                </div>
            </div>

            <!-- Empty hint -->
            <div
                v-else
                class="mt-5 flex flex-col items-center gap-2 rounded-xl border border-dashed border-slate-200 py-8 text-center"
            >
                <Package class="h-8 w-8 text-slate-300" />
                <p class="text-sm text-slate-400">
                    Belum ada barang dipilih. Cari barang di atas untuk mulai.
                </p>
            </div>
        </transition>
    </NCard>
</template>
