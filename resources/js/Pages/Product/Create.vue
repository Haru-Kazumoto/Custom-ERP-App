<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    NAlert,
    NButton,
    NForm,
    NFormItem,
    NIcon,
    NInput,
    NInputNumber,
    NSelect,
    NSpin,
    useMessage,
} from "naive-ui";
import { ArrowLeft, Save } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import { fmtRupiah } from "@/lib/utils";
import type { ProductOption } from "@/types/product";

const props = defineProps<{
    productTypes: ProductOption[];
    productSubTypes: ProductOption[];
    vendors: ProductOption[];
}>();

const message = useMessage();
const submitting = ref(false);

const form = reactive({
    name: "",
    code: "",
    category: "",
    unit: "",
    // `price` nullable di DB, jadi `null` berarti belum diisi — bukan 0.
    price: null as number | null,
    product_type_id: null as number | null,
    product_sub_type_id: null as number | null,
    vendor_id: null as number | null,
});

/**
 * `product_type` dan `product_sub_type` masih kosong di database. NSelect
 * menampilkan placeholder yang menjelaskan itu supaya user tidak mengira formnya
 * rusak — dan field tetap opsional karena FK-nya nullable.
 */
function optionsWithHint(options: ProductOption[], hint: string) {
    return options.length ? options : [{ label: hint, value: -1 }];
}

const typeOptions = computed(() =>
    optionsWithHint(props.productTypes, "Belum ada data tipe"),
);
const subTypeOptions = computed(() =>
    optionsWithHint(props.productSubTypes, "Belum ada data sub-tipe"),
);

const canSubmit = computed(
    () => !!form.name && !!form.code && !!form.category && !!form.unit,
);

function submit() {
    if (!canSubmit.value) return;

    submitting.value = true;

    router.post(
        route("products.store"),
        {
            ...form,
            // NSelect mengirim `null` untuk "tidak dipilih", yang sudah cocok
            // dengan kolom nullable — tidak perlu konversi ke string kosong.
        },
        {
            preserveScroll: true,
            onSuccess: () => message.success(`Produk "${form.name}" berhasil ditambahkan`),
            onError: (errors) => {
                const first = Object.values(errors)[0] as string | undefined;
                message.error(first ?? "Gagal menambah produk");
            },
            onFinish: () => (submitting.value = false),
        },
    );
}
</script>

<template>
    <Head title="Tambah Produk" />

    <AppLayout pageName="Tambah Produk">
        <div class="space-y-4 lg:space-y-6">
            <HeaderPage
                title="Tambah Produk"
                sub-title="Isi data produk. Harga di sini adalah harga dasar yang ditempel pada produk."
            >
                <template #action>
                    <div class="flex gap-2">
                        <Link :href="route('products.index')">
                            <NButton secondary strong>
                                <template #icon>
                                    <NIcon :component="ArrowLeft" />
                                </template>
                                Kembali
                            </NButton>
                        </Link>
                    </div>
                </template>
            </HeaderPage>

            <NSpin :show="submitting">
                <NForm label-placement="top">
                    <div class="grid gap-4 lg:grid-cols-3 lg:gap-6">
                        <div class="space-y-4 lg:col-span-2 lg:space-y-6">
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <h2 class="mb-4 text-sm font-semibold text-slate-900">
                                    Informasi Produk
                                </h2>

                                <NFormItem
                                    label="Kode Produk"
                                    required
                                    :feedback="
                                        'Kode harus unik dan dipakai sebagai identitas produk di dokumen'
                                    "
                                >
                                    <NInput
                                        v-model:value="form.code"
                                        placeholder="Contoh: BRG-001"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem label="Nama Produk" required>
                                    <NInput
                                        v-model:value="form.name"
                                        placeholder="Contoh: Binding Machine LX-2"
                                        maxlength="255"
                                        show-count
                                    />
                                </NFormItem>

                                <NFormItem label="Kategori" required>
                                    <NInput
                                        v-model:value="form.category"
                                        placeholder="Contoh: ATK, Elektronik, Sparepart"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem label="Unit" required>
                                    <NInput
                                        v-model:value="form.unit"
                                        placeholder="Contoh: Pcs, Lusin, Kg, Box"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem
                                    label="Harga Dasar"
                                    :feedback="
                                        'Kosongkan bila harga belum ditetapkan'
                                    "
                                >
                                    <NInputNumber
                                        v-model:value="form.price"
                                        :min="0"
                                        :precision="2"
                                        placeholder="0"
                                        class="w-full"
                                    >
                                        <template #prefix>Rp</template>
                                    </NInputNumber>
                                </NFormItem>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <h2 class="mb-1 text-sm font-semibold text-slate-900">
                                    Kategorisasi
                                </h2>
                                <p class="mb-4 text-xs text-slate-400">
                                    Semua field di bagian ini opsional. Tipe dan
                                    sub-tipe belum punya data awal, jadi tabel
                                    referensi
                                    <code>product_type</code> &amp;
                                    <code>product_sub_type</code> perlu diisi dulu.
                                </p>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <NFormItem label="Tipe Produk">
                                        <NSelect
                                            v-model:value="form.product_type_id"
                                            :options="typeOptions"
                                            placeholder="Tanpa tipe"
                                            filterable
                                        />
                                    </NFormItem>

                                    <NFormItem label="Sub-Tipe Produk">
                                        <NSelect
                                            v-model:value="form.product_sub_type_id"
                                            :options="subTypeOptions"
                                            placeholder="Tanpa sub-tipe"
                                            filterable
                                        />
                                    </NFormItem>
                                </div>

                                <NFormItem label="Vendor">
                                    <NSelect
                                        v-model:value="form.vendor_id"
                                        :options="vendors"
                                        :placeholder="
                                            vendors.length
                                                ? 'Pilih vendor'
                                                : 'Belum ada vendor'
                                        "
                                        filterable
                                        clearable
                                    />
                                </NFormItem>

                                <NAlert type="info" :show-icon="true">
                                    Harga dinamis per kota belum diisi di sini —
                                    itu ditangani tabel
                                    <code>product_prices</code> pada modul
                                    terpisah.
                                </NAlert>
                            </div>
                        </div>

                        <div class="space-y-4 lg:space-y-6">
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <h2 class="mb-4 text-sm font-semibold text-slate-900">
                                    Ringkasan
                                </h2>

                                <dl class="space-y-2.5 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">Kode</dt>
                                        <dd
                                            class="truncate font-medium text-slate-800"
                                        >
                                            {{ form.code || "-" }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">Nama</dt>
                                        <dd
                                            class="truncate font-medium text-slate-800"
                                        >
                                            {{ form.name || "-" }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">Kategori</dt>
                                        <dd
                                            class="truncate font-medium text-slate-800"
                                        >
                                            {{ form.category || "-" }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">Unit</dt>
                                        <dd
                                            class="truncate font-medium text-slate-800"
                                        >
                                            {{ form.unit || "-" }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">Harga dasar</dt>
                                        <dd
                                            class="font-medium text-slate-800"
                                        >
                                            {{
                                                form.price === null
                                                    ? "-"
                                                    : fmtRupiah(form.price)
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <NButton
                                    type="primary"
                                    strong
                                    block
                                    :loading="submitting"
                                    :disabled="!canSubmit"
                                    @click="submit"
                                >
                                    <template #icon>
                                        <NIcon :component="Save" />
                                    </template>
                                    Simpan Produk
                                </NButton>

                                <NButton
                                    class="mt-2"
                                    block
                                    secondary
                                    :disabled="submitting"
                                    @click="router.get(route('products.index'))"
                                >
                                    Batal
                                </NButton>
                            </div>
                        </div>
                    </div>
                </NForm>
            </NSpin>
        </div>
    </AppLayout>
</template>