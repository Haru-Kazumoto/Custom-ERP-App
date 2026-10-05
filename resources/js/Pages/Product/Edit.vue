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
import type { ProductOption, ProductRow } from "@/types/product";

const props = defineProps<{
    product: ProductRow | null;
    productTypes: ProductOption[];
    productSubTypes: ProductOption[];
    vendors: ProductOption[];
}>();

const message = useMessage();
const submitting = ref(false);

if (!props.product) {
    router.get(route("products.index"), {}, { replace: true });
}

/**
 * Kolom select harus tetap berisi nilai produk yang sedang diedit. Kalau tabel
 * referensi kosong, sisipkan label placeholder supaya produk yang punya tipe
 * lama tidak tampil sebagai "tidak dipilih" dan tidak ikut terkirim `null`.
 */
function optionsWithCurrent(
    options: ProductOption[],
    currentValue: number | null,
    hint: string,
) {
    const list = options.length ? [...options] : [];

    if (
        currentValue !== null &&
        !list.some((option) => option.value === currentValue)
    ) {
        list.unshift({ label: hint, value: currentValue });
    }

    return list.length ? list : [{ label: hint, value: -1 }];
}

const form = reactive({
    name: props.product?.name ?? "",
    code: props.product?.code ?? "",
    category: props.product?.category ?? "",
    unit: props.product?.unit ?? "",
    price: props.product?.price ?? null,
    product_type_id: props.product?.product_type_id ?? null,
    product_sub_type_id: props.product?.product_sub_type_id ?? null,
    vendor_id: props.product?.vendor_id ?? null,
});

const typeOptions = computed(() =>
    optionsWithCurrent(
        props.productTypes,
        props.product?.product_type_id ?? null,
        `ID ${props.product?.product_type_id} (tipe tidak ada)`,
    ),
);
const subTypeOptions = computed(() =>
    optionsWithCurrent(
        props.productSubTypes,
        props.product?.product_sub_type_id ?? null,
        `ID ${props.product?.product_sub_type_id} (sub-tipe tidak ada)`,
    ),
);

const canSubmit = computed(
    () => !!form.name && !!form.code && !!form.category && !!form.unit,
);

function submit() {
    if (!canSubmit.value || !props.product) return;

    submitting.value = true;

    router.put(
        route("products.update", props.product.id),
        { ...form },
        {
            preserveScroll: true,
            onSuccess: () =>
                message.success(`Produk "${form.name}" berhasil diperbarui`),
            onError: (errors) => {
                const first = Object.values(errors)[0] as string | undefined;
                message.error(first ?? "Gagal memperbarui produk");
            },
            onFinish: () => (submitting.value = false),
        },
    );
}
</script>

<template>
    <Head title="Edit Produk" />

    <AppLayout pageName="Edit Produk">
        <div class="space-y-4 lg:space-y-6">
            <HeaderPage
                :title="`Edit ${product?.name ?? 'Produk'}`"
                sub-title="Perbarui data produk. Kode produk harus tetap unik."
            >
                <template #action>
                    <div class="flex gap-2">
                        <Link
                            v-if="product"
                            :href="route('products.show', product.id)"
                        >
                            <NButton secondary strong>Detail</NButton>
                        </Link>
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

                                <NFormItem label="Kode Produk" required>
                                    <NInput
                                        v-model:value="form.code"
                                        placeholder="Contoh: BRG-001"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem label="Nama Produk" required>
                                    <NInput
                                        v-model:value="form.name"
                                        maxlength="255"
                                        show-count
                                    />
                                </NFormItem>

                                <NFormItem label="Kategori" required>
                                    <NInput
                                        v-model:value="form.category"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem label="Unit" required>
                                    <NInput
                                        v-model:value="form.unit"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem label="Harga Dasar">
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
                                <h2 class="mb-4 text-sm font-semibold text-slate-900">
                                    Kategorisasi
                                </h2>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <NFormItem label="Tipe Produk">
                                        <NSelect
                                            v-model:value="form.product_type_id"
                                            :options="typeOptions"
                                            placeholder="Tanpa tipe"
                                            filterable
                                            clearable
                                        />
                                    </NFormItem>

                                    <NFormItem label="Sub-Tipe Produk">
                                        <NSelect
                                            v-model:value="form.product_sub_type_id"
                                            :options="subTypeOptions"
                                            placeholder="Tanpa sub-tipe"
                                            filterable
                                            clearable
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

                                <NAlert
                                    v-if="
                                        props.product?.price !== null &&
                                        form.price === null
                                    "
                                    type="warning"
                                    :show-icon="true"
                                >
                                    Harga dasar dikosongkan. Nilai lama
                                    {{ fmtRupiah(props.product?.price ?? 0) }}
                                    akan hilang.
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
                                        <dt class="text-slate-500">
                                            Harga dasar
                                        </dt>
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
                                    :disabled="!canSubmit || !product"
                                    @click="submit"
                                >
                                    <template #icon>
                                        <NIcon :component="Save" />
                                    </template>
                                    Simpan Perubahan
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