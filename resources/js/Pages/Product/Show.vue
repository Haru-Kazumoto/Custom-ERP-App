<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { NButton, NEmpty, NIcon, NTag } from "naive-ui";
import { ArrowLeft, Pencil, Package } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import { fmtDate, fmtRupiah } from "@/lib/utils";
import type { ProductRow } from "@/types/product";

const props = defineProps<{
    product: ProductRow | null;
}>();

function formatPrice(value: number | null) {
    return value === null ? "-" : fmtRupiah(value);
}

/**
 * Detail bertingkat memakai daftar `[label, value]` supaya template tetap pendek
 * dan urutan kolomnya mudah dipindah.
 */
const productFields = () => [
    { label: "Kode Produk", value: props.product?.code ?? "-" },
    { label: "Kategori", value: props.product?.category ?? "-" },
    { label: "Unit", value: props.product?.unit ?? "-" },
    { label: "Harga Dasar", value: formatPrice(props.product?.price ?? null) },
    { label: "Tipe Produk", value: props.product?.type ?? "-" },
    { label: "Sub-Tipe Produk", value: props.product?.sub_type ?? "-" },
    { label: "Vendor", value: props.product?.vendor ?? "-" },
];
</script>

<template>
    <Head :title="product ? `Detail ${product.name}` : 'Detail Produk'" />

    <AppLayout pageName="Detail Produk">
        <div class="flex flex-col gap-5">
            <HeaderPage
                :title="product?.name ?? 'Produk tidak ditemukan'"
                :sub-title="
                    product
                        ? `Kode ${product.code}`
                        : 'Produk yang Anda cari tidak ada atau sudah dihapus.'
                "
            >
                <template #action>
                    <div class="flex gap-2">
                        <Link
                            v-if="product"
                            :href="route('products.edit', product.id)"
                        >
                            <NButton type="primary" size="large">
                                <template #icon>
                                    <NIcon :component="Pencil" />
                                </template>
                                Edit Produk
                            </NButton>
                        </Link>
                        <Link :href="route('products.index')">
                            <NButton secondary size="large">
                                <template #icon>
                                    <NIcon :component="ArrowLeft" />
                                </template>
                                Kembali
                            </NButton>
                        </Link>
                    </div>
                </template>
            </HeaderPage>

            <div
                v-if="product"
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <!-- Ringkasan utama -->
                <div
                    class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center"
                >
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-[#e9f6ee] text-[#18a058]"
                    >
                        <NIcon :component="Package" size="28" />
                    </div>

                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-slate-900">
                            {{ product.name }}
                        </h2>
                        <p class="font-mono text-xs text-slate-400">
                            {{ product.code }}
                        </p>
                    </div>

                    <div class="sm:ml-auto sm:text-right">
                        <p class="text-xs text-slate-400">Harga dasar</p>
                        <p class="text-xl font-semibold text-[#18a058]">
                            {{ formatPrice(product.price) }}
                        </p>
                    </div>
                </div>

                <!-- Field detail -->
                <div class="p-5">
                    <h3
                        class="mb-4 text-sm font-semibold text-slate-900"
                    >
                        Detail Produk
                    </h3>

                    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="field in productFields()"
                            :key="field.label"
                            class="rounded-xl bg-slate-50 p-3"
                        >
                            <dt class="text-xs text-slate-400">
                                {{ field.label }}
                            </dt>
                            <dd
                                class="mt-1 break-words text-sm font-medium text-slate-800"
                            >
                                {{ field.value }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Jejak audit -->
                <div
                    class="flex flex-col gap-2 border-t border-slate-100 px-5 py-4 text-xs text-slate-400 sm:flex-row sm:gap-6"
                >
                    <span>
                        Dibuat:
                        {{ product.created_at ? fmtDate(product.created_at) : "-" }}
                    </span>
                    <span>
                        Diperbarui:
                        {{
                            product.updated_at
                                ? fmtDate(product.updated_at)
                                : "-"
                        }}
                    </span>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border border-slate-200 bg-white py-12 shadow-sm"
            >
                <NEmpty description="Produk tidak ditemukan.">
                    <template #extra>
                        <Link :href="route('products.index')">
                            <NButton>Kembali ke daftar</NButton>
                        </Link>
                    </template>
                </NEmpty>
            </div>

            <div
                v-if="product"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <h3 class="mb-1 text-sm font-semibold text-slate-900">
                    Harga Dinamis
                </h3>
                <p class="text-sm text-slate-500">
                    Harga per kota/segmen belum ditampilkan. Modul
                    <code>product_prices</code> punya CRUD sendiri dan belum
                    diimplementasikan.
                </p>

                <NTag size="small" :bordered="false" class="mt-3">
                    Belum diimplementasikan
                </NTag>
            </div>
        </div>
    </AppLayout>
</template>