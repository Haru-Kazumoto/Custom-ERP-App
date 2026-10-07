<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { NAlert, NButton, NCard, NInput, NSelect } from "naive-ui";
import Swal from "sweetalert2";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import ReceiptItemCard from "@/Components/Feature/GoodsReceipt/ReceiptItemCard.vue";
import { useGoodsReceipt } from "@/composables/useGoodsReceipt";
import axios from "axios";

const props = defineProps<{
    defaultCompanyId: number | null;
}>();

const {
    form,
    ssoDetail,
    processing,
    error,
    processSso,
    addSplit,
    removeSplit,
    addDiscrepancy,
    removeDiscrepancy,
    validate,
} = useGoodsReceipt(props.defaultCompanyId ?? 1);

const ssoOptions = ref<{ label: string; value: string }[]>([]);
const ssoLoading = ref(false);
const ssoListError = ref<string | null>(null);

const selectedSsoNumber = ref<string | null>(null);

async function fetchSsoOptions() {
    ssoLoading.value = true;
    ssoListError.value = null;

    try {
        const { data } = await axios.get<
            { id: number; transaction_code: string }[]
        >("/goods-receipt/transaction-codes");

        ssoOptions.value = data.map((sso) => ({
            label: sso.transaction_code,
            value: sso.transaction_code,
        }));
    } catch (e: unknown) {
        ssoListError.value = axios.isAxiosError(e)
            ? e.response?.data?.message ?? "Gagal memuat daftar SSO"
            : "Gagal memuat daftar SSO";
    } finally {
        ssoLoading.value = false;
    }
}

onMounted(() => {
    void fetchSsoOptions();
});

watch(selectedSsoNumber, () => {
    form.reset();
    form.company_id = props.defaultCompanyId ?? 1;
    error.value = null;
});

async function onProcess() {
    if (!selectedSsoNumber.value) {
        Swal.fire({
            icon: "warning",
            title: "Pilih No SSO dulu",
            timer: 1400,
            showConfirmButton: false,
        });
        return;
    }

    await processSso(selectedSsoNumber.value);
}

// Item dikelompokkan per kode barang untuk tampilan.
const groupedItems = computed(() => {
    const groups = new Map<
        string,
        {
            product_code: string;
            product_name: string;
            product_unit: string;
            ordered_qty: number;
            items: (typeof form.items)[number][];
        }
    >();

    for (const item of form.items) {
        const group = groups.get(item.product_code) ?? {
            product_code: item.product_code,
            product_name: item.product_name,
            product_unit: item.product_unit,
            ordered_qty: 0,
            items: [],
        };

        group.ordered_qty += item.ordered_qty;
        group.items.push(item);
        groups.set(item.product_code, group);
    }

    return Array.from(groups.values());
});

function onSubmit() {
    const validationError = validate();

    if (validationError) {
        Swal.fire({ icon: "warning", title: validationError });
        return;
    }

    form.post(route("goods-receipt.store"), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: "success",
                title: "Barang diterima",
                timer: 1600,
                showConfirmButton: false,
            });
            selectedSsoNumber.value = null;
            form.reset();
            form.company_id = props.defaultCompanyId ?? 1;
            void fetchSsoOptions();
        },
        onError: (errors) =>
            Swal.fire({
                icon: "error",
                title: "Gagal menyimpan penerimaan barang",
                text:
                    Object.values(errors).join("\n") ||
                    "Periksa kembali data yang diisi.",
            }),
    });
}
</script>

<template>
    <Head title="Entry Barang" />

    <AppLayout>
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Entry Barang"
                subTitle="Penerimaan barang dari SSO di warehouse"
            />

            <!-- ===== Pilih SSO ===== -->
            <NCard :bordered="true" class="border-slate-200 shadow-sm">
                <div class="flex flex-col gap-3 items-end sm:flex-row">
                    <div class="flex-1 space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            No SSO <span class="text-rose-500">*</span>
                        </label>
                        <NSelect
                            v-model:value="selectedSsoNumber"
                            :options="ssoOptions"
                            filterable
                            clearable
                            :loading="ssoLoading"
                            :disabled="processing || form.processing"
                            placeholder="Pilih nomor SSO…"
                        />
                    </div>
                    <NButton
                        class="bg-[#0284c7] text-white hover:bg-[#0369a1] sm:w-auto"
                        :loading="processing"
                        :disabled="
                            !selectedSsoNumber || processing || form.processing
                        "
                        @click="onProcess"
                    >
                        Proses
                    </NButton>
                </div>

                <NAlert
                    v-if="ssoListError"
                    class="mt-3"
                    type="error"
                    :show-icon="true"
                >
                    {{ ssoListError }}
                </NAlert>
                <NAlert
                    v-if="error"
                    class="mt-3"
                    type="error"
                    :show-icon="true"
                >
                    {{ error }}
                </NAlert>

                <!-- Ringkasan SSO -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                >
                    <div
                        v-if="ssoDetail"
                        class="mt-4 rounded-xl border border-sky-100 bg-sky-50/50 p-4"
                    >
                        <div
                            class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2"
                        >
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >No SSO</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    ssoDetail.transaction_code
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >No SO</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    ssoDetail.details["no_so"] ?? "-"
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >Pemasok</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    ssoDetail.details["pemasok"] ?? "-"
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >Tanggal Kirim</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    ssoDetail.details["tanggal_kirim"] ?? "-"
                                }}</span>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Catatan penerimaan -->
                <div v-if="ssoDetail" class="mt-5 space-y-1.5">
                    <label class="block text-sm font-medium text-slate-700"
                        >Catatan</label
                    >
                    <NInput
                        v-model:value="form.noted"
                        type="textarea"
                        :rows="2"
                        :disabled="form.processing"
                        placeholder="Catatan penerimaan…"
                    />
                </div>
            </NCard>

            <!-- ===== Item per kode barang ===== -->
            <template v-if="ssoDetail">
                <NCard
                    v-for="group in groupedItems"
                    :key="group.product_code"
                    :bordered="true"
                    class="border-slate-200 shadow-sm"
                >
                    <template #header>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-700">{{
                                group.product_code
                            }}</span>
                            <span class="text-sm text-slate-400"
                                >— {{ group.product_name }} (Total
                                {{ group.ordered_qty }}
                                {{ group.product_unit }})</span
                            >
                        </div>
                    </template>

                    <div class="flex flex-col gap-4">
                        <ReceiptItemCard
                            v-for="item in group.items"
                            :key="item.transaction_items_id"
                            :item="item"
                            :disabled="form.processing"
                            @add-split="addSplit(item)"
                            @remove-split="
                                (index) => removeSplit(item, index)
                            "
                            @add-discrepancy="addDiscrepancy(item)"
                            @remove-discrepancy="
                                (index) => removeDiscrepancy(item, index)
                            "
                        />
                    </div>
                </NCard>
            </template>

            <!-- ===== Submit ===== -->
            <div v-if="ssoDetail" class="flex justify-end">
                <NButton
                    class="bg-[#0284c7] text-white hover:bg-[#0369a1]"
                    :loading="form.processing"
                    :disabled="form.processing || processing"
                    @click="onSubmit"
                >
                    Submit
                </NButton>
            </div>
        </div>
    </AppLayout>
</template>
