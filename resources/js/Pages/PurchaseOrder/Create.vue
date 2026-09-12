<script setup lang="ts">
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import { capitalize, formatDate, formatRupiah } from "@/utils/format";
import { usePurchaseOrder } from "@/composables/usePurchaseOrder";
import AppLayout from "@/Layouts/AppLayout.vue";
import ItemTable from "@/Components/Feature/PurchaseOrder/ItemTable.vue";
import SummaryPanel from "@/Components/Feature/PurchaseOrder/SummaryPanel.vue";
import ProductInput from "@/Components/Feature/PurchaseOrder/ProductInput.vue";
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
    type FormInst,
    type FormRules,
    useNotification,
} from "naive-ui";
import HeaderPage from "@/Components/Common/HeaderPage.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    po_number: string;
    suppliers: any[];
    transports: any[];
    auth: { user: { name: string } };
}>();

const {
    form,
    productOptions,
    productLoading,
    selectedProductId,
    selectedProduct,
    onProductSearch,
    draft,
    draftLineTotal,
    usePromo,
    tradePromoOptions,
    quotaTradePromo,
    addProduct,
    removeProduct,
    validateQuota,
    subTotal,
    taxAmount,
    total,
} = usePurchaseOrder({
    poNumber: props.po_number,
    fullname: props.auth.user.fullname,
});

// ---- select options ----
const supplierOptions = props.suppliers.map((s: any) => ({
    label: s.name,
    value: s.name,
}));
const transportOptions = props.transports.map((t: any) => ({
    label: t.name,
    value: t.name,
}));
const storeLocationOptions = [
    { label: "DNP", value: "DNP" },
    { label: "DKU", value: "DKU" },
];
const deliveryTypeOptions = [
    { label: "DEPO", value: "DEPO" },
    { label: "DIRECT", value: "DIRECT" },
    { label: "DIRECT DEPO", value: "DIRECT_DEPO" },
    { label: "DO", value: "DO" },
];
const ppnOptions = [
    { label: "GUNAKAN PPN (produk non gula)", value: "true" },
    { label: "TIDAK MENGGUNAKAN PPN (produk gula)", value: "false" },
];

const formRef = ref<FormInst | null>(null);
const notification = useNotification();

// Rules: 'blur' untuk input/select, 'change' supaya date/select revalidate saat dipilih.
const rules: FormRules = {
    "transaction_detail.supplier": {
        required: true,
        message: "Principal harus diisi",
        trigger: ["blur", "change"],
    },
    "transaction_detail.delivery_type": {
        required: true,
        message: "Jenis pengiriman harus diisi",
        trigger: ["blur", "change"],
    },
    "transaction_detail.located": {
        required: true,
        message: "Perusahaan harus diisi",
        trigger: ["blur", "change"],
    },
    "transaction_detail.purchase_order_date": {
        required: true,
        message: "Tanggal PO harus diisi",
        trigger: ["blur", "change"],
    },
    "transaction_detail.send_date": {
        required: true,
        message: "Tanggal kirim harus diisi",
        trigger: ["blur", "change"],
    },
    "transaction_detail.sender": {
        required: true,
        message: "Nama ekspedisi harus diisi",
        trigger: ["blur", "change"],
    },
};

// NSelect untuk trade promo butuh value string; productOptions sudah punya value sendiri.
const tradePromoSelectOptions = computed(() =>
    tradePromoOptions.value.map((o: any) => ({
        label: o.label,
        value: String(o.value),
    })),
);

// ---- form errors (sederhana, untuk required check) ----
const errors = ref<Record<string, string>>({});
function validateDetail(): boolean {
    const e: Record<string, string> = {};
    const d = form.transaction_detail;
    if (!d.supplier) e.supplier = "Principal harus diisi";
    if (!d.delivery_type) e.delivery_type = "Jenis pengiriman harus diisi";
    if (!d.located) e.located = "Perusahaan harus diisi";
    if (!d.purchase_order_date)
        e.purchase_order_date = "Tanggal PO harus diisi";
    if (!d.send_date) e.send_date = "Tanggal kirim harus diisi";
    if (!d.sender) e.sender = "Nama ekspedisi harus diisi";
    errors.value = e;
    return Object.keys(e).length === 0;
}

// ---- handle tambah produk ----
function onAddProduct() {
    const quotaErr = validateQuota(draft.value.quantity);
    if (quotaErr) {
        Swal.fire({
            icon: "error",
            title: "Kuota tidak cukup!",
            text: quotaErr,
        });
        return;
    }
    const err = addProduct();
    if (err) {
        Swal.fire({
            icon: "error",
            title: err,
            timer: 1500,
            showConfirmButton: false,
        });
        return;
    }

    notification.success({
        title: "Barang berhasil dimasukan!",
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
            if (validationErrors) return; // ada error → berhenti, biarkan NForm yang menandai

            if (!form.transaction_items.length) {
                // ini bukan field form, jadi tetap perlu notifikasi terpisah
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
            // validate() reject saat invalid → scroll ke field error pertama
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

function buildTransactionDetails() {
    const d = form.transaction_detail;
    form.transaction_details = [
        {
            name: "Pemasok",
            type: "SUPPLIER",
            value: d.supplier,
            data_type: "string",
        },
        {
            name: "Alokasi",
            type: "COMPANY",
            value: d.located,
            data_type: "string",
        },
        {
            name: "Tanggal PO",
            type: "PO_DATE",
            value: d.purchase_order_date,
            data_type: "datetime",
        },
        {
            name: "Tanggal Kirim",
            type: "DELIVERY_DATE",
            value: d.send_date,
            data_type: "datetime",
        },
        {
            name: "Transportasi",
            type: "TRANSPORTATION",
            value: d.sender,
            data_type: "string",
        },
        {
            name: "Jenis Pengiriman",
            type: "DELIVERY_TYPE",
            value: d.delivery_type,
            data_type: "string",
        },
        {
            name: "Harga Angkutan",
            type: "TRANSPORTATION_COST",
            value: d.transportation_cost || "0",
            data_type: "float",
        },
        {
            name: "PPN",
            type: "USE_TAX",
            value: String(d.use_tax),
            data_type: "boolean",
        },
        {
            name: "Nomor Polisi",
            type: "NUMBER_PLATE",
            value: String(d.transportation),
            data_type: "string"
        }
    ];
}

function handleSubmit() {
    confirmOpen.value = false;
    buildTransactionDetails();

    Swal.fire({
        title: "Memproses Purchase Order...",
        text: "Data sedang disimpan",
        didOpen: () => Swal.showLoading(),
        // allowOutsideClick: false,
    });

    form.post(route("purchase-order.store"), {
        preserveScroll: true,
        onSuccess: (page: any) => {
            Swal.close();
            notification.success({
                title: page.props.flash.success,
                meta: "Data tersimpan",
                closable: true,
                duration: 3000
            });
            form.reset("transaction_items", "description", "due_date");
            form.transaction_items = [];
        },
        onError: () => {
            Swal.fire({
                icon: "error",
                title: "Gagal membuat PO",
                text: "Cek kembali isian form",
            });
        },
    });
}

// helper: bind use_tax (boolean) ke Select string
const useTaxValue = computed({
    get: () => String(form.transaction_detail.use_tax),
    set: (v: string) => {
        form.transaction_detail.use_tax = v === "true";
    },
});

// helper untuk trade promo select (string <-> number)
const tradePromoValue = computed({
    get: () =>
        draft.value.trade_promo_id ? String(draft.value.trade_promo_id) : null,
    set: (v: string | null) => {
        draft.value.trade_promo_id = v ? Number(v) : (null as any);
    },
});

// Bridge string <-> timestamp untuk NDatePicker.
// form menyimpan string (kontrak backend tetap sama); NDatePicker pakai ms number.
function toTimestamp(v: string | null | undefined): number | null {
    if (!v) return null;
    const t = new Date(v).getTime();
    return Number.isNaN(t) ? null : t;
}

// Format string yang disimpan ke form. Sesuaikan kalau backend mengharapkan
// format lain. Default di sini: 'YYYY-MM-DDTHH:mm' (sama seperti datetime-local).
function toFormString(ts: number | null): string {
    if (ts == null) return "";
    const d = new Date(ts);
    const pad = (n: number) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const purchaseOrderDateTs = computed<number | null>({
    get: () => toTimestamp(form.transaction_detail.purchase_order_date),
    set: (ts) => {
        form.transaction_detail.purchase_order_date = toFormString(ts);
    },
});

const sendDateTs = computed<number | null>({
    get: () => toTimestamp(form.transaction_detail.send_date),
    set: (ts) => {
        form.transaction_detail.send_date = toFormString(ts);
    },
});
</script>

<template>
    <Head title="Purchase Order" />

    <div class="flex flex-col gap-5">
        <HeaderPage
            title="Purchase Order"
            subTitle="Pembuatan Purchase Order Baru"
        />

        <!-- ===== Detail PO ===== -->
        <NCard
            class="border-slate-100 shadow-md rounded-2xl overflow-hidden"
            :bordered="false"
        >
            <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div
                    class="flex items-center gap-4 rounded-xl bg-slate-50 border border-slate-100 p-4 transition-all hover:shadow-sm"
                >
                    <div class="p-3 bg-sky-100/70 rounded-lg text-sky-600">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
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
                            class="text-xs font-semibold tracking-wide uppercase text-slate-400"
                        >
                            Nomor Purchase Order
                        </p>
                        <p
                            class="text-xl font-extrabold text-slate-800 tracking-tight mt-0.5"
                        >
                            {{ form.document_code }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-4 rounded-xl bg-slate-50 border border-slate-100 p-4 transition-all hover:shadow-sm"
                >
                    <div
                        class="p-3 bg-emerald-100/70 rounded-lg text-emerald-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
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
                            class="text-xs font-semibold tracking-wide uppercase text-slate-400"
                        >
                            Person In Charge (PIC)
                        </p>
                        <p
                            class="text-xl font-extrabold text-slate-800 tracking-tight mt-0.5"
                        >
                            {{ auth.user.name }}
                        </p>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100 mb-8" />

            <NForm
                ref="formRef"
                :model="form"
                :rules="rules"
                :show-label="true"
                label-placement="top"
                require-mark-placement="right-hanging"
            >
                <div class="space-y-0">
                    <div>
                        <h3
                            class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2"
                        >
                            <span
                                class="w-1.5 h-4 bg-sky-500 rounded-full"
                            ></span>
                            Informasi Utama
                        </h3>
                        <div
                            class="grid grid-cols-1 gap-x-3 gap-y-1 md:grid-cols-3"
                        >
                            <NFormItem
                                label="Principal"
                                path="transaction_detail.supplier"
                            >
                                <NSelect
                                    v-model:value="
                                        form.transaction_detail.supplier
                                    "
                                    :options="supplierOptions"
                                    placeholder="Pilih principal"
                                    clearable
                                />
                            </NFormItem>

                            <NFormItem
                                label="Perusahaan"
                                path="transaction_detail.located"
                            >
                                <NSelect
                                    v-model:value="
                                        form.transaction_detail.located
                                    "
                                    :options="storeLocationOptions"
                                    placeholder="Pilih perusahaan"
                                    clearable
                                />
                            </NFormItem>

                            <NFormItem
                                label="Tanggal PO"
                                path="transaction_detail.purchase_order_date"
                            >
                                <NDatePicker
                                    v-model:value="purchaseOrderDateTs"
                                    type="datetime"
                                    class="w-full"
                                    placeholder="Pilih tanggal & waktu"
                                    clearable
                                />
                            </NFormItem>
                        </div>
                    </div>

                    <div>
                        <h3
                            class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2"
                        >
                            <span
                                class="w-1.5 h-4 bg-indigo-500 rounded-full"
                            ></span>
                            Logistik & Pengiriman
                        </h3>
                        <div
                            class="grid grid-cols-1 gap-x-3 gap-y-1 md:grid-cols-3"
                        >
                            <NFormItem
                                label="Jenis Pengiriman"
                                path="transaction_detail.delivery_type"
                            >
                                <NSelect
                                    v-model:value="
                                        form.transaction_detail.delivery_type
                                    "
                                    :options="deliveryTypeOptions"
                                    placeholder="Pilih jenis"
                                    clearable
                                />
                            </NFormItem>

                            <NFormItem
                                label="Nama Ekspedisi"
                                path="transaction_detail.sender"
                            >
                                <NSelect
                                    v-model:value="
                                        form.transaction_detail.sender
                                    "
                                    :options="transportOptions"
                                    placeholder="Pilih ekspedisi"
                                    clearable
                                />
                            </NFormItem>

                            <NFormItem label="Nomor Polisi Ekspedisi">
                                <NInput
                                    :value="
                                        form.transaction_detail.transportation
                                    "
                                    placeholder="Contoh: B 1234 ABC"
                                    @update:value="
                                        (v) =>
                                            (form.transaction_detail.transportation =
                                                capitalize(String(v)))
                                    "
                                    clearable
                                />
                            </NFormItem>

                            <NFormItem
                                label="Tanggal Kirim"
                                path="transaction_detail.send_date"
                            >
                                <NDatePicker
                                    v-model:value="sendDateTs"
                                    type="datetime"
                                    class="w-full"
                                    placeholder="Pilih tanggal & waktu"
                                    clearable
                                />
                            </NFormItem>
                        </div>
                    </div>

                    <div>
                        <h3
                            class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2"
                        >
                            <span
                                class="w-1.5 h-4 bg-emerald-500 rounded-full"
                            ></span>
                            Keuangan & Catatan Tambahan
                        </h3>
                        <div
                            class="grid grid-cols-1 gap-x-3 gap-y-1 md:grid-cols-3"
                        >
                            <NFormItem label="Term Pembayaran">
                                <NInput
                                    :value="String(form.term_of_payment)"
                                    placeholder="0"
                                    @update:value="
                                        (v) =>
                                            (form.term_of_payment =
                                                Number(
                                                    String(v).replace(
                                                        /\D/g,
                                                        '',
                                                    ),
                                                ) || 0)
                                    "
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
                                        form.due_date
                                            ? formatDate(form.due_date, false)
                                            : ''
                                    "
                                    readonly
                                    placeholder="Otomatis terisi"
                                    class="bg-slate-50/80 text-slate-500 cursor-not-allowed font-medium"
                                />
                            </NFormItem>

                            <NFormItem label="PPN">
                                <NSelect
                                    v-model:value="useTaxValue"
                                    :options="ppnOptions"
                                    placeholder="Pilih status pajak"
                                />
                            </NFormItem>

                            <NFormItem label="Harga Angkutan">
                                <NInput
                                    :value="
                                        form.transaction_detail
                                            .transportation_cost ?? ''
                                    "
                                    placeholder="0"
                                    @update:value="
                                        (v) =>
                                            (form.transaction_detail.transportation_cost =
                                                String(v).replace(/\D/g, ''))
                                    "
                                    clearable
                                >
                                    <template #prefix>
                                        <span
                                            class="text-xs font-bold text-slate-400 mr-1"
                                            >Rp</span
                                        >
                                    </template>
                                </NInput>
                            </NFormItem>

                            <NFormItem
                                label="Catatan / Keterangan"
                                class="md:col-span-3"
                            >
                                <NInput
                                    type="textarea"
                                    v-model:value="form.description"
                                    :rows="3"
                                    placeholder="Masukkan catatan atau instruksi khusus di sini..."
                                    :maxlength="255"
                                    show-count
                                />
                            </NFormItem>
                        </div>
                    </div>
                </div>
            </NForm>
        </NCard>

        <!-- ===== Penginputan Barang ===== -->
        <ProductInput
            :options="productOptions"
            :loading="productLoading"
            :selected-id="selectedProductId"
            :selected-product="selectedProduct"
            :unit="draft.unit"
            :quantity="draft.quantity"
            :amount="draft.amount"
            :line-total="draftLineTotal"
            :use-promo="usePromo"
            :trade-promo-options="tradePromoOptions"
            :trade-promo-id="draft.trade_promo_id"
            :amount-discount="draft.amount_discount"
            :quota-trade-promo="quotaTradePromo"
            @search="onProductSearch"
            @update:selected-id="(v) => (selectedProductId = v)"
            @update:quantity="(v) => (draft.quantity = v as number)"
            @update:amount="(v) => (draft.amount = v as number)"
            @update:trade-promo-id="(v) => (draft.trade_promo_id = v as number)"
            @add="onAddProduct"
        />

        <!-- ===== Tabel Barang ===== -->
        <NCard class="border-slate-200 shadow-sm" :bordered="true">
            <ItemTable
                :items="form.transaction_items"
                @remove="removeProduct"
            />
        </NCard>

        <!-- ===== Ringkasan ===== -->
        <SummaryPanel
            :sub-total="subTotal"
            :tax-amount="taxAmount"
            :total="total"
            :use-tax="form.transaction_detail.use_tax"
            :term-of-payment="form.term_of_payment"
            :due-date="form.due_date"
        >
            <div class="mt-4 flex justify-end">
                <NButton
                    class="bg-[#0284c7] hover:bg-[#0369a1] text-white"
                    :disabled="form.processing"
                    @click="openConfirm"
                >
                    Submit PO
                </NButton>
            </div>
        </SummaryPanel>
    </div>

    <!-- ===== Dialog Konfirmasi ===== -->
    <NModal
        v-model:show="confirmOpen"
        preset="card"
        class="w-[95vw] max-w-3xl p-4 rounded-2xl"
        content-style="padding: 0;"
        :bordered="false"
    >
        <template #header>
            <div class="flex flex-col">
                <span class="text-base font-semibold text-slate-800"
                    >Konfirmasi Purchase Order</span
                >
                <span class="text-sm font-normal text-slate-400">{{
                    form.document_code
                }}</span>
            </div>
        </template>

        <div class="max-h-[70vh] overflow-y-auto px-1 py-1 sm:px-2">
            <!-- ringkasan singkat di atas (chip) -->
            <div class="mb-4 flex flex-wrap gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-[#0284c7]"
                >
                    {{ form.transaction_detail.supplier || "-" }}
                </span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"
                >
                    {{ form.transaction_detail.delivery_type || "-" }}
                </span>
                <NTag
                    size="small"
                    :bordered="false"
                    :class="
                        form.transaction_detail.use_tax
                            ? 'bg-emerald-50 text-emerald-600'
                            : 'bg-rose-50 text-rose-600'
                    "
                >
                    {{ form.transaction_detail.use_tax ? "PPN" : "NON-PPN" }}
                </NTag>
            </div>

            <!-- Seksi: Informasi Pengiriman -->
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
                        <p class="text-xs text-slate-400">Perusahaan</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ form.transaction_detail.located || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Tipe Pengiriman</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ form.transaction_detail.delivery_type || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Tanggal PO</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{
                                formatDate(
                                    form.transaction_detail.purchase_order_date,
                                ) || "-"
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Tanggal Pengiriman</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{
                                formatDate(form.transaction_detail.send_date) ||
                                "-"
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Ekspedisi</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ form.transaction_detail.sender || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Nomor Polisi</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ form.transaction_detail.transportation || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Harga Pengiriman</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{
                                formatRupiah(
                                    Number(
                                        form.transaction_detail
                                            .transportation_cost ?? 0,
                                    ),
                                )
                            }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Seksi: Pembayaran -->
            <section class="mb-4">
                <h4
                    class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                >
                    Pembayaran
                </h4>
                <div
                    class="grid grid-cols-1 gap-x-4 gap-y-3 rounded-xl border border-slate-100 p-4 sm:grid-cols-2"
                >
                    <div>
                        <p class="text-xs text-slate-400">Term of Payment</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ form.term_of_payment }} HARI
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Jatuh Tempo</p>
                        <p class="text-sm font-medium text-slate-700">
                            {{ formatDate(form.due_date, false) || "-" }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-slate-400">Catatan</p>
                        <p class="text-sm text-slate-700">
                            {{ form.description || "-" }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Seksi: Barang -->
            <section class="mb-4">
                <h4
                    class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
                >
                    Barang ({{ form.transaction_items.length }})
                </h4>

                <!-- Desktop: tabel -->
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
                                <th class="px-3 py-2 font-medium">Unit</th>
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
                                <td class="px-3 py-2 text-slate-700">
                                    {{ item.product.name }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ item.unit }}
                                </td>
                                <td
                                    class="px-3 py-2 text-center text-slate-600"
                                >
                                    {{ item.quantity }}
                                </td>
                                <td class="px-3 py-2 text-right text-slate-600">
                                    {{ formatRupiah(item.amount) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-right font-semibold text-slate-800"
                                >
                                    {{ formatRupiah(item.total_price) }}
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
                                >{{ formatRupiah(item.total_price) }}</span
                            >
                        </div>
                        <div
                            class="mt-2 flex items-center gap-3 border-t border-slate-50 pt-2 text-xs text-slate-500"
                        >
                            <span>{{ item.quantity }} {{ item.unit }}</span>
                            <span>×</span>
                            <span>{{ formatRupiah(item.amount) }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ringkasan total -->
            <SummaryPanel
                :sub-total="subTotal"
                :tax-amount="taxAmount"
                :total="total"
                :use-tax="form.transaction_detail.use_tax"
                :term-of-payment="form.term_of_payment"
                :due-date="form.due_date"
            />
        </div>

        <template #footer>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <NButton class="w-full sm:w-auto" @click="confirmOpen = false"
                    >Tutup</NButton
                >
                <NButton
                    class="w-full bg-[#0284c7] text-white hover:bg-[#0369a1] sm:w-auto"
                    :loading="form.processing"
                    :disabled="form.processing"
                    @click="handleSubmit"
                >
                    Simpan Purchase Order
                </NButton>
            </div>
        </template>
    </NModal>
</template>
