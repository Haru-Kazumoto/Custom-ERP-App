<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    NButton,
    NEmpty,
    NIcon,
    NInput,
    NPagination,
    NPopconfirm,
    NTag,
    useMessage,
} from "naive-ui";
import { Eye, Package, Pencil, Plus, Search, Trash2 } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import { fmtRupiah } from "@/lib/utils";
import type { Paginated, ProductRow } from "@/types/product";

const props = defineProps<{
    products: Paginated<ProductRow>;
    filters: { search: string };
}>();

const message = useMessage();

const search = ref(props.filters.search ?? "");
const deletingId = ref<number | null>(null);

/**
 * Selalu baca query string sebagai sumber kebenaran supaya tetap sinkron setelah
 * Inertia reload, lalu salin ke input.
 */
watch(
    () => props.filters.search,
    (value) => {
        search.value = value ?? "";
    },
);

function applyFilters() {
    router.get(
        route("products.index"),
        { search: search.value || undefined, page: undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function clearSearch() {
    search.value = "";
    applyFilters();
}

function goToPage(target: number) {
    router.get(
        route("products.index"),
        {
            search: props.filters.search || undefined,
            page: target > 1 ? target : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function destroy(id: number, name: string) {
    deletingId.value = id;

    router.delete(
        route("products.destroy", id),
        {
            preserveScroll: true,
            onSuccess: () => message.success(`Produk "${name}" berhasil dihapus`),
            onError: (errors) => {
                const first = Object.values(errors)[0] as string | undefined;
                message.error(first ?? "Gagal menghapus produk");
            },
            onFinish: () => (deletingId.value = null),
        },
    );
}

/**
 * `price` nullable — tampilkan strip kalau kosong, bukan "Rp 0" yang menyesatkan.
 */
function formatPrice(value: number | null) {
    return value === null ? "-" : fmtRupiah(value);
}

/**
 * Dipisah ke computed karena penanda kutip di dalam atribut HTML tidak bisa
 * ditulis dengan escape `\"` — Vue menutup atribut di kutip pertama.
 */
const emptyDescription = computed(() =>
    props.filters.search
        ? `Tidak ada produk yang cocok dengan "${props.filters.search}"`
        : "Belum ada produk.",
);
</script>

<template>
    <Head title="Daftar Produk" />

    <AppLayout pageName="Daftar Produk">
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Manajemen Produk"
                sub-title="Kelola katalog produk, termasuk harga dasar yang ditempel di produk"
            >
                <template #action>
                    <div class="ml-auto flex gap-2">
                        <Link :href="route('products.create')">
                            <NButton type="primary" size="large">
                                <template #icon>
                                    <NIcon :component="Plus" />
                                </template>
                                Tambah Produk
                            </NButton>
                        </Link>
                    </div>
                </template>
            </HeaderPage>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <!-- Toolbar: judul + total + pencarian -->
                <div
                    class="flex w-full flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-2">
                        <h2 class="font-medium text-slate-900">Produk</h2>
                        <span
                            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                        >
                            {{ products.total ?? products.data.length }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <NInput
                            v-model:value="search"
                            placeholder="Cari nama, kode, atau kategori..."
                            class="w-full sm:w-64"
                            clearable
                            @keyup.enter="applyFilters"
                            @clear="applyFilters"
                        >
                            <template #prefix>
                                <NIcon :component="Search" />
                            </template>
                        </NInput>

                        <NButton @click="applyFilters">Cari</NButton>

                        <NButton
                            v-if="filters.search"
                            quaternary
                            @click="clearSearch"
                        >
                            Reset
                        </NButton>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="overflow-x-auto">
                    <table
                        v-if="products.data.length"
                        class="w-full min-w-[900px] text-left text-sm"
                    >
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th
                                    class="h-10 px-4 text-left text-xs font-medium text-slate-500"
                                >
                                    Kode
                                </th>
                                <th
                                    class="h-10 px-4 text-left text-xs font-medium text-slate-500"
                                >
                                    Nama Produk
                                </th>
                                <th
                                    class="h-10 px-4 text-left text-xs font-medium text-slate-500"
                                >
                                    Kategori
                                </th>
                                <th
                                    class="h-10 px-4 text-left text-xs font-medium text-slate-500"
                                >
                                    Tipe
                                </th>
                                <th
                                    class="h-10 px-4 text-left text-xs font-medium text-slate-500"
                                >
                                    Unit
                                </th>
                                <th
                                    class="h-10 px-4 text-right text-xs font-medium text-slate-500"
                                >
                                    Harga Dasar
                                </th>
                                <th
                                    class="h-10 px-4 text-left text-xs font-medium text-slate-500"
                                >
                                    Vendor
                                </th>
                                <th
                                    class="h-10 px-4 text-right text-xs font-medium text-slate-500"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="border-b border-slate-50 hover:bg-slate-50"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        class="font-mono text-xs font-medium text-slate-600"
                                    >
                                        {{ product.code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400"
                                        >
                                            <Package class="h-4 w-4" />
                                        </div>
                                        <span
                                            class="font-medium text-slate-700"
                                        >
                                            {{ product.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <NTag size="small" :bordered="false">
                                        {{ product.category }}
                                    </NTag>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        v-if="product.type"
                                        class="text-slate-600"
                                    >
                                        {{ product.type }}
                                    </span>
                                    <span v-else class="text-slate-300">-</span>
                                    <span
                                        v-if="product.sub_type"
                                        class="block text-xs text-slate-400"
                                    >
                                        {{ product.sub_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span class="text-slate-600">
                                        {{ product.unit }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-right align-middle font-medium text-slate-700"
                                >
                                    {{ formatPrice(product.price) }}
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <span
                                        v-if="product.vendor"
                                        class="text-slate-600"
                                    >
                                        {{ product.vendor }}
                                    </span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                                <td
                                    class="px-4 py-3 text-right align-middle"
                                >
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="
                                                route('products.show', product.id)
                                            "
                                            title="Lihat detail"
                                        >
                                            <NButton quaternary circle size="small">
                                                <template #icon>
                                                    <NIcon :component="Eye" />
                                                </template>
                                            </NButton>
                                        </Link>

                                        <Link
                                            :href="
                                                route('products.edit', product.id)
                                            "
                                            title="Edit produk"
                                        >
                                            <NButton quaternary circle size="small">
                                                <template #icon>
                                                    <NIcon :component="Pencil" />
                                                </template>
                                            </NButton>
                                        </Link>

                                        <NPopconfirm
                                            positive-text="Hapus"
                                            negative-text="Batal"
                                            @positive-click="
                                                destroy(product.id, product.name)
                                            "
                                        >
                                            <template #trigger>
                                                <NButton
                                                    quaternary
                                                    circle
                                                    size="small"
                                                    type="error"
                                                    title="Hapus produk"
                                                >
                                                    <template #icon>
                                                        <NIcon
                                                            :component="Trash2"
                                                        />
                                                    </template>
                                                </NButton>
                                            </template>
                                            Hapus "{{ product.name }}"? Baris
                                            harga dinamis di `product_prices`
                                            juga ikut terhapus.
                                        </NPopconfirm>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <NEmpty
                        v-else
                        class="py-12"
                        :description="emptyDescription"
                    >
                        <template #extra>
                            <Link :href="route('products.create')">
                                <NButton type="primary" size="small">
                                    Tambah Produk Pertama
                                </NButton>
                            </Link>
                        </template>
                    </NEmpty>
                </div>

                <!-- Pagination -->
                <div
                    v-if="products.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-100 p-4"
                >
                    <span class="text-xs text-slate-400">
                        Menampilkan {{ products.from }}–{{ products.to }} dari
                        {{ products.total }} produk
                    </span>

                    <NPagination
                        :page="products.current_page"
                        :page-count="products.last_page"
                        size="small"
                        @update:page="goToPage"
                    />
                </div>
            </div>

            <p class="text-xs text-slate-400">
                Harga di kolom ini adalah harga dasar yang ditempel pada produk.
                Harga dinamis per kota dikelola terpisah pada modul
                <code>product_prices</code>.
            </p>
        </div>
    </AppLayout>
</template>