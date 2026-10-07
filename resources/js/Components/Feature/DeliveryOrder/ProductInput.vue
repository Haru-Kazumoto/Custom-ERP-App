<script setup lang="ts">
import { computed } from "vue";
import {
    NCard,
    NSelect,
    NInputNumber,
    NButton,
    NAlert,
    NRadioGroup,
    NRadio,
} from "naive-ui";
import {
    Package,
    Tag,
    Box,
    Layers,
    Plus,
    Minus,
    BadgePercent,
    Warehouse,
} from "lucide-vue-next";
import { formatRupiah } from "@/utils/format";
import type { DeliveryProduct } from "@/types/delivery-order";

/**
 * Input barang form Delivery Order.
 *
 * Berbeda dengan PO (katalog global `/api/product`), opsi di sini hasil
 * pencarian `GET /delivery-order/products` yang sudah difilter gudang
 * (stock > 0) + pengiriman + pelanggan, dan sudah menyertakan harga yang
 * berlaku serta promo yang eligible.
 */
const props = defineProps<{
    options: DeliveryProduct[];
    loading: boolean;
    selectedId: number | null;
    selectedProduct: DeliveryProduct | null;
    quantity: number | null;
    promoId: number | null;
    unitPrice: number | null;
    /** Harga satuan akhir setelah promo (preview). */
    finalUnit: number;
    /** Total bruto baris draft (preview). */
    lineTotal: number;
    /** Alasan draft tidak valid; null = boleh ditambahkan. */
    error: string | null;
    /**
     * Mode harga draft ini: false = harga daftar (otomatis), true = manual
     * (dibatasi tidak boleh melebihi harga daftar).
     */
    useManual: boolean;
    showStock: boolean;
    /** True saat filter gudang/pengiriman belum lengkap. */
    waitingForFilters: boolean;
}>();

const emit = defineEmits<{
    (e: "search", q: string): void;
    (e: "update:selectedId", v: number | null): void;
    (e: "update:quantity", v: number | null): void;
    (e: "update:promoId", v: number | null): void;
    (e: "update:unitPrice", v: number | null): void;
    (e: "update:useManual", v: boolean): void;
    (e: "add"): void;
}>();

const selectOptions = computed(() =>
    props.options.map((p) => ({
        label: `${p.code} — ${p.name}`,
        value: p.id,
    })),
);

const promoOptions = computed(() =>
    (props.selectedProduct?.promos ?? []).map((promo) => ({
        label: `${promo.name} (${promo.code})`,
        value: promo.promo_product_id,
    })),
);

const hasPromo = computed(() => promoOptions.value.length > 0);

/** Stok menipis → peringatan kuning di kartu produk. */
const lowStock = computed(
    () => props.showStock && (props.selectedProduct?.stock ?? 0) < 10,
);

const canAdd = computed(
    () =>
        !!props.selectedId && !!props.quantity && props.error === null,
);

/** Harga daftar (batas atas mode manual). */
const priceCap = computed(() =>
    props.selectedProduct?.has_price
        ? Number(props.selectedProduct.price)
        : null,
);
</script>

<template>
    <NCard :bordered="true" class="border-slate-200 shadow-sm">
        <template #header>
            <div>
                <h3 class="text-base font-semibold text-slate-800">
                    Penginputan Barang
                </h3>
                <p class="text-sm font-normal text-slate-400">
                    Barang yang ditawarkan sesuai gudang, pengiriman, dan
                    pelanggan yang dipilih.
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
                @search="(q: string) => emit('search', q)"
                @update:value="(v: number | null) => emit('update:selectedId', v)"
            />
            <p v-if="waitingForFilters" class="text-xs text-slate-400">
                Pilih perusahaan/gudang terlebih dahulu untuk memuat daftar
                barang.
            </p>
            <p v-else class="text-xs text-slate-400">
                Hanya barang dengan stok di gudang terpilih yang ditampilkan.
            </p>
        </div>

        <!-- Step 2: detail + input -->
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
                                <span class="inline-flex items-center gap-1">
                                    <Tag class="h-3.5 w-3.5" />
                                    {{ selectedProduct.code }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <Box class="h-3.5 w-3.5" />
                                    {{ selectedProduct.unit }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <Layers class="h-3.5 w-3.5" />
                                    {{ selectedProduct.category }}
                                </span>
                                <span
                                    v-if="showStock"
                                    class="inline-flex items-center gap-1"
                                >
                                    <Warehouse class="h-3.5 w-3.5" />
                                    Stok {{ selectedProduct.stock }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span
                                class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-[#0284c7]"
                            >
                                {{
                                    selectedProduct.has_price
                                        ? formatRupiah(selectedProduct.price)
                                        : "Harga tidak tersedia"
                                }}
                            </span>
                            <span
                                v-if="hasPromo"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-600"
                            >
                                <BadgePercent class="h-3.5 w-3.5" /> Promo
                            </span>
                        </div>
                    </div>

                    <p
                        v-if="showStock && lowStock"
                        class="mt-3 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700"
                    >
                        Stok menipis — tersisa {{ selectedProduct.stock }}
                    </p>
                </div>

                <!-- Input grid -->
                <div
                    class="mt-4 grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <!-- Jumlah -->
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
                                    (v: number | null) => emit('update:quantity', v)
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
                            Satuan: {{ selectedProduct.unit || "-" }}
                        </p>
                    </div>

                    <!-- Harga satuan (mode dipilih per barang) -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <label
                                class="block text-sm font-medium text-slate-700"
                            >
                                Harga Satuan <span class="text-rose-500"
                                    >*</span
                                >
                            </label>
                            <NRadioGroup
                                size="small"
                                :value="useManual ? 'manual' : 'auto'"
                                @update:value="
                                    (v: string) =>
                                        emit('update:useManual', v === 'manual')
                                "
                            >
                                <NRadio value="auto" size="small">
                                    Otomatis
                                </NRadio>
                                <NRadio value="manual" size="small">
                                    Manual
                                </NRadio>
                            </NRadioGroup>
                        </div>
                        <NInputNumber
                            :value="unitPrice"
                            :min="0"
                            :max="useManual ? (priceCap ?? undefined) : undefined"
                            :show-button="false"
                            :disabled="!useManual"
                            placeholder="0"
                            class="bg-slate-50"
                            @update:value="
                                (v: number | null) => emit('update:unitPrice', v)
                            "
                        >
                            <template #prefix
                                ><span class="text-xs text-slate-400"
                                    >Rp</span
                                ></template
                            >
                        </NInputNumber>
                        <p
                            class="text-xs"
                            :class="
                                useManual
                                    ? 'text-amber-600'
                                    : 'text-slate-400'
                            "
                        >
                            {{
                                useManual
                                    ? `Maksimal ${formatRupiah(priceCap)} — hanya bisa turun dari harga daftar.`
                                    : "Otomatis dari daftar harga sesuai segmen."
                            }}
                        </p>
                    </div>

                    <!-- Promo (opsional) -->
                    <div v-if="hasPromo" class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            Promo <span class="font-normal text-slate-400">· opsional</span>
                        </label>
                        <NSelect
                            :value="promoId"
                            :options="promoOptions"
                            clearable
                            filterable
                            placeholder="Tanpa promo (harga normal)"
                            @update:value="
                                (v: number | null) => emit('update:promoId', v)
                            "
                        />
                        <p class="text-xs text-slate-400">
                            Diskon cascading dihitung dari harga satuan.
                        </p>
                    </div>

                    <!-- Harga akhir setelah promo -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700"
                            >Harga Akhir Satuan</label
                        >
                        <div
                            class="flex h-[34px] items-center rounded-lg bg-slate-50 px-3"
                        >
                            <span class="font-semibold text-slate-800">{{
                                formatRupiah(finalUnit)
                            }}</span>
                        </div>
                        <p class="text-xs text-slate-400">
                            {{
                                promoId
                                    ? "Setelah diskon promo"
                                    : "Sebelum diskon"
                            }}
                        </p>
                    </div>

                    <!-- Preview total baris -->
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
                            Harga akhir × jumlah (sebelum PPN)
                        </p>
                    </div>
                </div>

                <!-- Alasan draft tidak valid -->
                <NAlert
                    v-if="error"
                    type="error"
                    :bordered="false"
                    class="mt-4 rounded-xl"
                >
                    {{ error }}
                </NAlert>

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
                        Tambah Barang
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
