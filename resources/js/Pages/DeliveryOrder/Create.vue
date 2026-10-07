<script setup lang="ts">
import { computed, ref } from "vue";
import { Head } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import { formatDate, formatRupiah } from "@/utils/format";
import {
    useDeliveryOrder,
    finalUnitPrice,
    SEGMENT_OPTIONS,
    PAYMENT_TERMS_OPTIONS,
} from "@/composables/useDeliveryOrder";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import ProductInput from "@/Components/Feature/DeliveryOrder/ProductInput.vue";
import ItemTable from "@/Components/Feature/DeliveryOrder/ItemTable.vue";
import SummaryPanel from "@/Components/Feature/DeliveryOrder/SummaryPanel.vue";
import {
    NCard,
    NInput,
    NSelect,
    NButton,
    NTag,
    NModal,
    NDatePicker,
    NForm,
    NFormItem,
    NIcon,
    NInputNumber,
    NCheckboxGroup,
    NCheckbox,
    type FormInst,
    type FormRules,
    useNotification,
} from "naive-ui";
import { Plus } from "lucide-vue-next";
import type {
    DeliveryOrderFormOptions,
    DeliveryOrderItem,
} from "@/types/delivery-order";

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    do_number: string;
    options: DeliveryOrderFormOptions;
    auth: { user: { id: number; name: string } };
}>();

const {
    form,
    useTax,
    selectedShipping,
    selectedSub,
    selectedCustomer,
    selectedCompany,
    paymentTerm,
    dueDate,
    hasSub,
    showStock,
    products,
    productLoading,
    searchQuery,
    selectedProductId,
    selectedProduct,
    draft,
    draftFinalUnit,
    draftLineTotal,
    draftError,
    addItem,
    removeItem,
    subTotal,
    grandTotal,
    taxAmount,
    totalDiscount,
    grossBeforeDiscount,
    hasManualPrice,
    buildTransactionDetails,
} = useDeliveryOrder({
    doNumber: props.do_number,
    options: props.options,
});

// ---- select options ----
const companyOptions = props.options.companies.map((c) => ({
    label: `${c.code} — ${c.name}`,
    value: String(c.id),
}));
const shippingOptions = props.options.shippings.map((s) => ({
    label: `${s.name} (${s.code})`,
    value: String(s.id),
}));
const subShippingOptions = computed(() =>
    (selectedShipping.value?.subs ?? []).map((sub) => ({
        label: sub.name,
        value: String(sub.id),
    })),
);
// Label hanya nama — segmen ditampilkan & dipilih lewat select terpisah.
const customerOptions = props.options.customers.map((c) => ({
    label: c.name,
    value: String(c.id),
}));
const segmentOptions = SEGMENT_OPTIONS.map((s) => ({
    label: s,
    value: s,
}));
const paymentTermOptions = PAYMENT_TERMS_OPTIONS.map((t) => ({
    label: t,
    value: t,
}));
const ppnOptions = [
    { label: "GUNAKAN PPN (produk non gula)", value: "true" },
    { label: "TIDAK MENGGUNAKAN PPN (produk gula)", value: "false" },
];

/**
 * Filter tidak boleh berubah setelah barang masuk: harga katalog, promo, dan
 * stok di tabel bergantung pada kombinasi filter yang dipilih saat itu.
 */
const filtersLocked = computed(() => form.transaction_items.length > 0);

const useTaxValue = computed({
    get: () => String(useTax.value),
    set: (v: string) => {
        useTax.value = v === "true";
    },
});

/** Jumlah baris yang memakai harga manual (info modal konfirmasi). */
const manualPriceCount = computed(
    () =>
        form.transaction_items.filter((item) => item.use_manual_price).length,
);

/** True bila minimal satu field informasi opsional terisi. */
const hasOptionalInfo = computed(
    () =>
        form.customer_po_number.trim() !== "" ||
        (form.cashback_pph_4 !== null && form.cashback_pph_4 > 0) ||
        (form.biaya_bongkar !== null && form.biaya_bongkar > 0) ||
        form.syarat_pembayaran.length > 0,
);

// Bridge string ↔ timestamp untuk NDatePicker.
function toTimestamp(v: string | null | undefined): number | null {
    if (!v) return null;
    const t = new Date(v).getTime();
    return Number.isNaN(t) ? null : t;
}

function toDateString(ts: number): string {
    const d = new Date(ts);
    const pad = (n: number) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

const deliveryDateTs = computed<number | null>({
    get: () => toTimestamp(form.delivery_date),
    set: (ts) => {
        form.delivery_date = ts == null ? null : toDateString(ts);
    },
});

// ---- validasi form ----
const formRef = ref<FormInst | null>(null);

const rules = computed<FormRules>(() => ({
    delivery_date: {
        required: true,
        message: "Tanggal kirim harus diisi",
        trigger: ["blur", "change"],
    },
    company_id: {
        required: true,
        message: "Perusahaan/gudang harus diisi",
        trigger: ["blur", "change"],
    },
    shipping_id: {
        required: true,
        message: "Jenis pengiriman harus diisi",
        trigger: ["blur", "change"],
    },
    // Jenis pengiriman ber-sub wajib memilih subnya: baris harga di
    // `product_prices` terikat pada kombinasi shipping + sub.
    ...(hasSub.value
        ? {
              sub_shipping_id: {
                  required: true,
                  message: "Sub pengiriman harus diisi",
                  trigger: ["blur", "change"],
              },
          }
        : {}),
    customer_id: {
        required: true,
        message: "Pelanggan harus diisi",
        trigger: ["blur", "change"],
    },
    segment: {
        required: true,
        message: "Segmen harga harus diisi",
        trigger: ["blur", "change"],
    },
}));

const notification = useNotification();

// ---- tambah barang ----
function onAddProduct() {
    const error = addItem();
    if (error) return; // pesan sudah tampil sebagai alert di ProductInput

    notification.success({
        title: "Barang berhasil dimasukkan!",
        meta: "Daftar barang bertambah.",
        duration: 3000,
        closable: false,
    });
}

// ---- konfirmasi & submit ----
const confirmOpen = ref(false);

function openConfirm() {
    formRef.value
        ?.validate((validationErrors) => {
            if (validationErrors) return;

            if (!form.transaction_items.length) {
                Swal.fire({
                    icon: "error",
                    title: "Belum ada barang",
                    text: "Tambahkan minimal satu barang",
                });
                return;
            }
            confirmOpen.value = true;
        })
        .catch(() => {
            requestAnimationFrame(() => {
                const el = document.querySelector(
                    ".n-form-item-feedback__line, .n-form-item--error-status",
                );
                if (el) {
                    el.scrollIntoView({ behavior: "smooth", block: "center" });
                } else {
                    window.scrollTo({ top: 0, behavior: "smooth" });
                }
            });
        });
}

function handleSubmit() {
    confirmOpen.value = false;
    buildTransactionDetails();

    Swal.fire({
        title: "Memproses Delivery Order...",
        text: "Data sedang disimpan",
        didOpen: () => Swal.showLoading(),
    });

    form.post(route("delivery-order.store"), {
        preserveScroll: true,
        onSuccess: (page: any) => {
            Swal.close();
            notification.success({
                title:
                    page.props.flash?.success ??
                    "Delivery order berhasil dibuat!",
                meta: "Data tersimpan",
                closable: true,
                duration: 3000,
            });
        },
        onError: (errors) => {
            Swal.close();
            const message =
                errors.delivery_order ??
                (Object.values(errors)[0] as string | undefined) ??
                "Cek kembali isian form";

            Swal.fire({
                icon: "error",
                title: "Gagal membuat Delivery Order",
                text: message,
            });
        },
    });
}

/** Total bruto satu baris (setelah promo) untuk modal konfirmasi. */
function lineTotal(item: DeliveryOrderItem): number {
    return finalUnitPrice(item) * item.quantity;
}
</script>

<template>
    <Head title="Delivery Order" />

    <div class="flex flex-col gap-5">
        <HeaderPage
            title="Delivery Order"
            subTitle="Pembuatan Delivery Order Baru"
        />

        <!-- ===== Info utama ===== -->
        <NCard
            class="rounded-2xl border-slate-100 shadow-md"
            :bordered="false"
        >
            <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div
                    class="flex items-center gap-4 rounded-xl border border-slate-100 bg-slate-50 p-4 transition-all hover:shadow-sm"
                >
                    <div class="rounded-lg bg-sky-100/70 p-3 text-sky-600">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 012-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Nomor Delivery Order
                        </p>
                        <p
                            class="mt-0.5 text-xl font-extrabold tracking-tight text-slate-800"
                        >
                            {{ form.document_code }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-4 rounded-xl border border-slate-100 bg-slate-50 p-4 transition-all hover:shadow-sm"
                >
                    <div class="rounded-lg bg-emerald-100/70 p-3 text-emerald-600">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Person In Charge (PIC)
                        </p>
                        <p
                            class="mt-0.5 text-xl font-extrabold tracking-tight text-slate-800"
                        >
                            {{ auth.user.name }}
                        </p>
                    </div>
                </div>
            </div>

            <hr class="mb-8 border-slate-100" />

            <NForm
                ref="formRef"
                size="large"
                :model="form"
                :rules="rules"
                :show-label="true"
                label-placement="top"
                require-mark-placement="right-hanging"
            >
                <!-- Informasi Utama -->
                <div>
                    <h3
                        class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-700"
                    >
                        <span class="h-4 w-1.5 rounded-full bg-sky-500"></span>
                        Informasi Utama
                    </h3>
                    <div class="grid grid-cols-1 gap-x-3 gap-y-1 md:grid-cols-3">
                        <NFormItem label="Pelanggan" path="customer_id">
                            <NSelect
                                v-model:value="form.customer_id"
                                :options="customerOptions"
                                placeholder="Pilih pelanggan"
                                clearable
                                :disabled="filtersLocked"
                            />
                        </NFormItem>

                        <NFormItem label="Segmen Harga" path="segment">
                            <NSelect
                                v-model:value="form.segment"
                                :options="segmentOptions"
                                placeholder="Pilih segmen harga"
                                :disabled="filtersLocked"
                            />
                        </NFormItem>

                        <NFormItem
                            label="Perusahaan / Gudang"
                            path="company_id"
                        >
                            <NSelect
                                v-model:value="form.company_id"
                                :options="companyOptions"
                                placeholder="Pilih perusahaan"
                                clearable
                                :disabled="filtersLocked"
                            />
                        </NFormItem>

                        <NFormItem label="Tanggal Kirim" path="delivery_date">
                            <NDatePicker
                                v-model:value="deliveryDateTs"
                                type="date"
                                class="w-full"
                                placeholder="Pilih tanggal kirim"
                                clearable
                            />
                        </NFormItem>
                    </div>
                </div>

                <!-- Pengiriman & Pembayaran -->
                <div>
                    <h3
                        class="mb-4 mt-6 flex items-center gap-2 text-sm font-bold text-slate-700"
                    >
                        <span
                            class="h-4 w-1.5 rounded-full bg-indigo-500"
                        ></span>
                        Pengiriman &amp; Pembayaran
                    </h3>
                    <div class="grid grid-cols-1 gap-x-3 gap-y-1 md:grid-cols-3">
                        <NFormItem
                            label="Jenis Pengiriman"
                            path="shipping_id"
                        >
                            <NSelect
                                v-model:value="form.shipping_id"
                                :options="shippingOptions"
                                placeholder="Pilih jenis pengiriman"
                                clearable
                                :disabled="filtersLocked"
                            />
                        </NFormItem>

                        <NFormItem
                            v-if="hasSub"
                            label="Sub Pengiriman"
                            path="sub_shipping_id"
                        >
                            <NSelect
                                v-model:value="form.sub_shipping_id"
                                :options="subShippingOptions"
                                placeholder="Pilih sub pengiriman"
                                clearable
                                :disabled="filtersLocked"
                            />
                        </NFormItem>

                        <NFormItem label="Termin Pelanggan">
                            <NInput
                                :value="String(paymentTerm)"
                                readonly
                                class="cursor-not-allowed bg-slate-50/80 text-slate-500 font-medium"
                            >
                                <template #suffix>
                                    <span
                                        class="text-xs font-bold text-slate-400"
                                        >HARI</span
                                    >
                                </template>
                            </NInput>
                        </NFormItem>

                        <NFormItem label="Tanggal Jatuh Tempo">
                            <NInput
                                :value="
                                    dueDate ? formatDate(dueDate, false) : ''
                                "
                                readonly
                                placeholder="Otomatis: tanggal kirim + termin"
                                class="cursor-not-allowed bg-slate-50/80 font-medium text-slate-500"
                            />
                        </NFormItem>

                        <NFormItem label="PPN">
                            <NSelect
                                v-model:value="useTaxValue"
                                :options="ppnOptions"
                                placeholder="Pilih status pajak"
                            />
                        </NFormItem>

                        <NFormItem label="Catatan / Keterangan" class="md:col-span-3">
                            <NInput
                                v-model:value="form.description"
                                type="textarea"
                                :rows="3"
                                placeholder="Masukkan catatan atau instruksi khusus di sini..."
                                :maxlength="255"
                                show-count
                            />
                        </NFormItem>
                    </div>

                    <p
                        v-if="filtersLocked"
                        class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700"
                    >
                        Filter pelanggan/segmen/gudang/pengiriman terkunci
                        selama ada barang di tabel. Hapus semua barang untuk
                        mengubahnya.
                    </p>
                </div>

                <!-- Informasi Opsional -->
                <div>
                    <h3
                        class="mb-4 mt-6 flex items-center gap-2 text-sm font-bold text-slate-700"
                    >
                        <span class="h-4 w-1.5 rounded-full bg-amber-500"></span>
                        Informasi Opsional
                    </h3>
                    <p class="mb-3 text-xs text-slate-400">
                        Bersifat informatif saja — tidak memengaruhi total
                        pembayaran.
                    </p>
                    <div class="grid grid-cols-1 gap-x-3 gap-y-1 md:grid-cols-3">
                        <NFormItem label="Nomor PO Pelanggan">
                            <NInput
                                v-model:value="form.customer_po_number"
                                placeholder="mis. PO-2026-001"
                                :maxlength="100"
                            />
                        </NFormItem>

                        <NFormItem label="Cashback + PPh 4%">
                            <NInputNumber
                                v-model:value="form.cashback_pph_4"
                                :min="0"
                                :show-button="false"
                                class="w-full"
                                placeholder="0"
                            >
                                <template #prefix
                                    ><span class="text-xs text-slate-400"
                                        >Rp</span
                                    ></template
                                >
                            </NInputNumber>
                        </NFormItem>

                        <NFormItem label="Biaya Bongkar">
                            <NInputNumber
                                v-model:value="form.biaya_bongkar"
                                :min="0"
                                :show-button="false"
                                class="w-full"
                                placeholder="0"
                            >
                                <template #prefix
                                    ><span class="text-xs text-slate-400"
                                        >Rp</span
                                    ></template
                                >
                            </NInputNumber>
                        </NFormItem>

                        <NFormItem
                            label="Syarat Pembayaran"
                            class="md:col-span-3"
                        >
                            <NCheckboxGroup
                                v-model:value="form.syarat_pembayaran"
                            >
                                <div
                                    class="flex flex-wrap gap-x-6 gap-y-2 pt-1"
                                >
                                    <NCheckbox
                                        v-for="opt in paymentTermOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        :label="opt.label"
                                    />
                                </div>
                            </NCheckboxGroup>
                        </NFormItem>
                    </div>
                </div>
            </NForm>
        </NCard>

        <!-- ===== Penginputan barang ===== -->
        <ProductInput
            :options="products"
            :loading="productLoading"
            :selected-id="selectedProductId"
            :selected-product="selectedProduct"
            :quantity="draft.quantity"
            :promo-id="draft.promo_product_id"
            :unit-price="draft.unit_price"
            :final-unit="draftFinalUnit"
            :line-total="draftLineTotal"
            :error="draftError"
            :use-manual="draft.use_manual_price"
            :show-stock="showStock"
            :waiting-for-filters="!form.company_id"
            @search="(q) => (searchQuery = q)"
            @update:selected-id="(v) => (selectedProductId = v)"
            @update:quantity="(v) => (draft.quantity = v)"
            @update:promo-id="(v) => (draft.promo_product_id = v)"
            @update:unit-price="(v) => (draft.unit_price = v)"
            @update:use-manual="(v) => (draft.use_manual_price = v)"
            @add="onAddProduct"
        />

        <!-- ===== Tabel barang ===== -->
        <NCard class="border-slate-200 shadow-sm" :bordered="true">
            <template #header>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-semibold text-slate-800">
                        Daftar Barang
                    </h3>
                    <span
                        class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                    >
                        {{ form.transaction_items.length }}
                    </span>
                    <NTag
                        v-if="showStock"
                        size="small"
                        :bordered="false"
                        class="bg-sky-50 text-sky-600"
                    >
                        Stok DEPO tampil
                    </NTag>
                </div>
            </template>
            <ItemTable
                :items="form.transaction_items"
                :show-stock="showStock"
                @remove="removeItem"
            />
        </NCard>

        <!-- ===== Ringkasan ===== -->
        <SummaryPanel
            :sub-total="subTotal"
            :total-discount="totalDiscount"
            :gross-before-discount="grossBeforeDiscount"
            :tax-amount="taxAmount"
            :total="grandTotal"
            :use-tax="useTax"
            :payment-term="paymentTerm"
            :due-date="dueDate"
        >
            <div class="mt-4 flex justify-end">
                <NButton
                    type="primary"
                    size="large"
                    :disabled="form.processing"
                    @click="openConfirm"
                >
                    Submit DO
                </NButton>
            </div>
        </SummaryPanel>
    </div>

    <!-- ===== Dialog Konfirmasi ===== -->
    <NModal
        v-model:show="confirmOpen"
        preset="card"
        class="w-[95vw] max-w-3xl rounded-2xl p-4"
        content-style="padding: 0;"
        :bordered="false"
    >
        <template #header>
            <div class="flex flex-col">
                <span class="text-base font-semibold text-slate-800"
                    >Konfirmasi Delivery Order</span
                >
                <span class="text-sm font-normal text-slate-400">{{
                    form.document_code
                }}</span>
            </div>
        </template>

        <div class="max-h-[70vh] overflow-y-auto px-1 py-1 sm:px-2">
            <div class="mb-4 flex flex-wrap gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-[#0284c7]"
                >
                    {{ selectedCustomer?.name ?? "-" }}
                </span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"
                >
                    {{ selectedShipping?.name ?? "-" }}
                </span>
                <NTag
                    size="small"
                    :bordered="false"
                    :class="
                        useTax
                            ? 'bg-emerald-50 text-emerald-600'
                            : 'bg-rose-50 text-rose-600'
                    "
                >
                    {{ useTax ? "PPN" : "NON-PPN" }}
                </NTag>
                <NTag
                    size="small"
                    :bordered="false"
                    class="bg-violet-50 text-violet-600"
                >
                    {{
                        hasManualPrice
                            ? `${manualPriceCount} BARANG HARGA MANUAL`
                            : "HARGA OTOMATIS"
                    }}
                </NTag>
            </div>

            <!-- Informasi pengiriman -->
            <section class="mb-4">
                <h4
                    class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                >
                    Informasi Pengiriman
                </h4>
                <div
                    class="grid grid-cols-1 gap-x-4 gap-y-3 rounded-xl border border-slate-100 p-4 sm:grid-cols-2"
                >
                    <div>
                        <p class="text-xs text-slate-400">Pelanggan</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ selectedCustomer?.name ?? "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Segmen</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ form.segment || "ALL_SEGMENT" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">
                            Jenis / Sub Pengiriman
                        </p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ selectedShipping?.name ?? "-" }}
                            <template v-if="selectedSub">
                                / {{ selectedSub.name }}
                            </template>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Gudang</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ selectedCompany?.name ?? "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Tanggal Kirim</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ formatDate(form.delivery_date, false) || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Jatuh Tempo</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ formatDate(dueDate, false) || "-" }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-slate-400">Catatan</p>
                        <p class="text-sm text-slate-700">
                            {{ form.description || "-" }}
                        </p>
                    </div>
                </div>

                <!-- Informasi opsional (hanya bila diisi) -->
                <div
                    v-if="hasOptionalInfo"
                    class="mt-3 rounded-xl border border-amber-100 bg-amber-50/40 p-4"
                >
                    <div class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
                        <div v-if="form.customer_po_number.trim()">
                            <p class="text-xs text-slate-400">
                                Nomor PO Pelanggan
                            </p>
                            <p class="text-sm font-medium text-slate-700">
                                {{ form.customer_po_number }}
                            </p>
                        </div>
                        <div
                            v-if="form.cashback_pph_4 !== null && form.cashback_pph_4 > 0"
                        >
                            <p class="text-xs text-slate-400">
                                Cashback + PPh 4%
                            </p>
                            <p class="text-sm font-medium text-slate-700">
                                {{ formatRupiah(form.cashback_pph_4) }}
                            </p>
                        </div>
                        <div
                            v-if="form.biaya_bongkar !== null && form.biaya_bongkar > 0"
                        >
                            <p class="text-xs text-slate-400">Biaya Bongkar</p>
                            <p class="text-sm font-medium text-slate-700">
                                {{ formatRupiah(form.biaya_bongkar) }}
                            </p>
                        </div>
                        <div v-if="form.syarat_pembayaran.length">
                            <p class="text-xs text-slate-400">
                                Syarat Pembayaran
                            </p>
                            <p class="text-sm font-medium text-slate-700">
                                {{ form.syarat_pembayaran.join(", ") }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Barang -->
            <section class="mb-4">
                <h4
                    class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                >
                    Barang ({{ form.transaction_items.length }})
                </h4>

                <div
                    class="hidden overflow-x-auto rounded-xl border border-slate-100 sm:block"
                >
                    <table class="w-full text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs text-slate-400"
                        >
                            <tr>
                                <th class="px-3 py-2 font-medium">Kode</th>
                                <th class="px-3 py-2 font-medium">
                                    Nama Produk
                                </th>
                                <th class="px-3 py-2 text-center font-medium">
                                    Qty
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Harga Satuan
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, i) in form.transaction_items"
                                :key="i"
                                class="border-t border-slate-50"
                            >
                                <td class="px-3 py-2 text-slate-500">
                                    {{ item.product.code }}
                                </td>
                                <td class="px-3 py-2">
                                    <p class="text-slate-700">
                                        {{ item.product.name }}
                                    </p>
                                    <p
                                        v-if="item.promo"
                                        class="text-xs text-amber-600"
                                    >
                                        promo: {{ item.promo.name }}
                                    </p>
                                </td>
                                <td
                                    class="px-3 py-2 text-center text-slate-600"
                                >
                                    {{ item.quantity }}
                                </td>
                                <td class="px-3 py-2 text-right text-slate-600">
                                    {{
                                        formatRupiah(finalUnitPrice(item))
                                    }}
                                </td>
                                <td
                                    class="px-3 py-2 text-right font-semibold text-slate-800"
                                >
                                    {{
                                        formatRupiah(lineTotal(item))
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile: kartu -->
                <div class="space-y-2 sm:hidden">
                    <div
                        v-for="(item, i) in form.transaction_items"
                        :key="i"
                        class="rounded-xl border border-slate-100 p-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-medium text-slate-700"
                                >
                                    {{ item.product.name }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ item.product.code }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-sm font-semibold text-slate-800"
                                >{{ formatRupiah(lineTotal(item)) }}</span
                            >
                        </div>
                        <div
                            class="mt-2 flex items-center gap-3 border-t border-slate-50 pt-2 text-xs text-slate-500"
                        >
                            <span>{{ item.quantity }} {{ item.product.unit }}</span>
                            <span>×</span>
                            <span>{{ formatRupiah(finalUnitPrice(item)) }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ringkasan total -->
            <SummaryPanel
                :sub-total="subTotal"
                :total-discount="totalDiscount"
                :gross-before-discount="grossBeforeDiscount"
                :tax-amount="taxAmount"
                :total="grandTotal"
                :use-tax="useTax"
                :payment-term="paymentTerm"
                :due-date="dueDate"
            />
        </div>

        <template #footer>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <NButton class="w-full sm:w-auto" @click="confirmOpen = false">
                    Tutup
                </NButton>
                <NButton
                    class="w-full bg-[#0284c7] text-white hover:bg-[#0369a1] sm:w-auto"
                    :loading="form.processing"
                    :disabled="form.processing"
                    @click="handleSubmit"
                >
                    <template #icon>
                        <NIcon :component="Plus" />
                    </template>
                    Simpan Delivery Order
                </NButton>
            </div>
        </template>
    </NModal>
</template>
