<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import {
    NAlert,
    NCard,
    NSelect,
    NButton,
    NInput,
    NDatePicker,
} from "naive-ui";
import Swal from "sweetalert2";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import SsoItemTable from "@/Components/Feature/SubSalesOrder/SsoItemTable.vue";
import { useSubSalesOrder } from "@/composables/useSubSalesOrder";
import { useDocumentNumbers } from "@/composables/useDocumentNumbers";

defineProps<{
    auth: { user: { name: string } };
}>();

const {
    form,
    poDetail,
    processing,
    error,
    processPo,
    clearProcessedPo,
} = useSubSalesOrder();
const {
    options: poSelectOptions,
    loading: poLoading,
    error: poOptionsError,
    fetchOptions,
} = useDocumentNumbers();

onMounted(() => {
    void fetchOptions();
});

const selectedPoNumber = ref<string | null>(null);

watch(selectedPoNumber, () => {
    clearProcessedPo();
    error.value = null;
});

async function onProcess() {
    if (!selectedPoNumber.value) {
        Swal.fire({
            icon: "warning",
            title: "Pilih No PO dulu",
            timer: 1400,
            showConfirmButton: false,
        });
        return;
    }
    await processPo(selectedPoNumber.value);
}

function onSubmit() {
    if (!poDetail.value || !form.items.length) {
        Swal.fire({
            icon: "warning",
            title: "Proses PO dan pastikan ada barang yang dipilih",
        });
        return;
    }

    if (!form.no_bukti.trim() || !form.no_so.trim() || !form.tanggal_kirim) {
        Swal.fire({
            icon: "warning",
            title: "Lengkapi data wajib",
            text: "No Bukti, No SO, dan Tanggal Kirim harus diisi.",
        });
        return;
    }

    form.post(route("sub-sales-order.store"), {
        preserveScroll: true,
        onSuccess: () =>
            Swal.fire({
                icon: "success",
                title: "SSO tersimpan",
                timer: 1600,
                showConfirmButton: false,
            }),
        onError: (errors) =>
            Swal.fire({
                icon: "error",
                title: "Gagal menyimpan Sub Sales Order",
                text:
                    Object.values(errors).join("\n") ||
                    "Periksa kembali data yang diisi.",
            }),
    });
}

function removeItem(index: number) {
    form.items = form.items.filter((_, itemIndex) => itemIndex !== index);
}
</script>

<template>
    <Head title="Sub Sales Order" />

    <AppLayout>
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Sub Sales Order"
                subTitle="Pembuatan Tanda Terima Barang dari PO"
            />

            <!-- ===== Form utama ===== -->
            <NCard :bordered="true" class="border-slate-200 shadow-sm">
                <!-- Baris pilih PO + Proses -->
                <div class="flex flex-col gap-3 items-end sm:flex-row">
                    <div class="flex-1 space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            No PO <span class="text-rose-500">*</span>
                        </label>
                        <NSelect
                            v-model:value="selectedPoNumber"
                            :options="poSelectOptions"
                            filterable
                            clearable
                            :loading="poLoading"
                            :disabled="processing || form.processing"
                            placeholder="Pilih nomor PO…"
                        />
                    </div>
                    <NButton
                        class="bg-[#0284c7] text-white hover:bg-[#0369a1] sm:w-auto"
                        :loading="processing"
                        :disabled="
                            !selectedPoNumber || processing || form.processing
                        "
                        @click="onProcess"
                    >
                        Proses
                    </NButton>
                </div>

                <NAlert
                    v-if="poOptionsError"
                    class="mt-3"
                    type="error"
                    :show-icon="true"
                >
                    {{ poOptionsError }}
                </NAlert>
                <NAlert
                    v-if="error"
                    class="mt-3"
                    type="error"
                    :show-icon="true"
                >
                    {{ error }}
                </NAlert>

                <!-- Ringkasan PO (muncul setelah Proses) -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                >
                    <div
                        v-if="poDetail"
                        class="mt-4 rounded-xl border border-sky-100 bg-sky-50/50 p-4"
                    >
                        <div
                            class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2"
                        >
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >Tanggal PO</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    poDetail.tanggal_po
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >Alokasi</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    poDetail.alokasi
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >Pemasok</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    poDetail.pemasok
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >Nama Ekspedisi</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    poDetail.transportasi
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >Jenis Pengiriman</span
                                >
                                <span class="text-sm text-slate-700">{{
                                    poDetail.jenis_pengiriman
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-600"
                                    >PIC</span
                                >
                                <span class="text-sm text-[#0284c7]">{{
                                    auth.user.name
                                }}</span>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Input SSO (No Bukti, No SO, Tanggal Kirim, Catatan) -->
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            No Bukti <span class="text-rose-500">*</span>
                        </label>
                        <NInput
                            v-model:value="form.no_bukti"
                            placeholder="Masukkan no bukti"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            No SO <span class="text-rose-500">*</span>
                        </label>
                        <NInput
                            v-model:value="form.no_so"
                            placeholder="Masukkan no SO"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-slate-700">
                            Tanggal Kirim <span class="text-rose-500">*</span>
                        </label>
                        <NDatePicker
                            v-model:formatted-value="form.tanggal_kirim"
                            value-format="yyyy-MM-dd HH:mm:ss"
                            type="datetime"
                            class="w-full"
                        />
                    </div>
                    <div class="space-y-1.5 md:col-span-3">
                        <label class="block text-sm font-medium text-slate-700"
                            >Catatan</label
                        >
                        <NInput
                            v-model:value="form.description"
                            type="textarea"
                            :rows="2"
                            placeholder="Catatan tambahan…"
                        />
                    </div>
                </div>
            </NCard>

            <!-- ===== Tabel item dari PO; baris yang tidak dikirim dapat dihapus ===== -->
            <NCard :bordered="true" class="border-slate-200 shadow-sm">
                <SsoItemTable
                    :items="form.items"
                    :disabled="form.processing"
                    @remove="removeItem"
                />
            </NCard>

            <!-- ===== Submit ===== -->
            <div class="flex justify-end">
                <NButton
                    class="bg-[#0284c7] text-white hover:bg-[#0369a1]"
                    :loading="form.processing"
                    :disabled="
                        !poDetail ||
                        !form.items.length ||
                        form.processing ||
                        processing
                    "
                    @click="onSubmit"
                >
                    Submit
                </NButton>
            </div>
        </div>
    </AppLayout>
</template>
