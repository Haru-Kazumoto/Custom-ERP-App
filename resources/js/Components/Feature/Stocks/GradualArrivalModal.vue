<script setup lang="ts">
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
    NButton,
    NDatePicker,
    NInput,
    NInputNumber,
    NModal,
    NAlert,
} from "naive-ui";
import Swal from "sweetalert2";
import type { GradualItem } from "@/types/stock";

const props = defineProps<{
    show: boolean;
    item: GradualItem | null;
}>();

const emit = defineEmits<{
    "update:show": [value: boolean];
    saved: [];
}>();

const form = useForm({
    batch_code: "",
    quantity: null as number | null,
    expiry_date: null as string | null,
    stagnation_limit_date: null as string | null,
});

const title = computed(() =>
    props.item
        ? `Barang Datang — ${props.item.product_code} (sisa ${props.item.remaining_qty})`
        : "Barang Datang",
);

// Isi ulang form setiap kali modal dibuka untuk satu baris tertunda.
watch(
    () => props.show,
    (open) => {
        if (open && props.item) {
            form.reset();
            form.clearErrors();
            form.quantity = props.item.remaining_qty;
        }
    },
);

function close() {
    emit("update:show", false);
}

function submit() {
    if (!props.item) return;

    if (!form.batch_code.trim()) {
        Swal.fire({ icon: "warning", title: "Kode barang wajib diisi" });
        return;
    }

    if (!form.quantity || form.quantity < 1) {
        Swal.fire({ icon: "warning", title: "Qty minimal 1" });
        return;
    }

    if (form.quantity > props.item.remaining_qty) {
        Swal.fire({
            icon: "warning",
            title: `Qty melebihi sisa tertunda (${props.item.remaining_qty})`,
        });
        return;
    }

    form.post(route("stocks.gradually.arrive", props.item.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: "success",
                title: "Kedatangan dicatat",
                timer: 1600,
                showConfirmButton: false,
            });
            emit("saved");
            close();
        },
        onError: (errors) =>
            Swal.fire({
                icon: "error",
                title: "Gagal mencatat kedatangan",
                text:
                    Object.values(errors).join("\n") ||
                    "Periksa kembali data yang diisi.",
            }),
    });
}
</script>

<template>
    <NModal
        :show="show"
        preset="card"
        :title="title"
        class="w-[520px] max-w-[92vw]"
        @update:show="(value: boolean) => emit('update:show', value)"
    >
        <div class="flex flex-col gap-4">
            <NAlert type="info" :show-icon="true">
                Qty yang datang dicatat sebagai stok masuk (IN) dengan kode
                barang baru. Sisa tertunda otomatis berkurang.
            </NAlert>

            <div v-if="item" class="space-y-1 text-sm text-slate-600">
                <p>
                    <span class="font-medium">Barang:</span>
                    {{ item.product_name }} ({{ item.product_code }})
                </p>
                <p>
                    <span class="font-medium">SSO Asal:</span>
                    {{ item.sso_code }}
                </p>
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-700">
                    Kode Barang <span class="text-rose-500">*</span>
                </label>
                <NInput
                    v-model:value="form.batch_code"
                    :disabled="form.processing"
                    placeholder="mis. TPG-AA1"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-slate-700">
                        Qty Datang <span class="text-rose-500">*</span>
                    </label>
                    <NInputNumber
                        v-model:value="form.quantity"
                        :min="1"
                        :max="item?.remaining_qty"
                        :disabled="form.processing"
                        class="w-full"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-slate-700"
                        >Expired</label
                    >
                    <NDatePicker
                        v-model:formatted-value="form.expiry_date"
                        value-format="yyyy-MM-dd"
                        type="date"
                        :disabled="form.processing"
                        class="w-full"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-slate-700"
                        >Batas Stagnasi</label
                    >
                    <NDatePicker
                        v-model:formatted-value="form.stagnation_limit_date"
                        value-format="yyyy-MM-dd"
                        type="date"
                        :disabled="form.processing"
                        class="w-full"
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <NButton :disabled="form.processing" @click="close">
                    Batal
                </NButton>
                <NButton
                    type="primary"
                    class="bg-[#0284c7] text-white hover:bg-[#0369a1]"
                    :loading="form.processing"
                    @click="submit"
                >
                    Simpan
                </NButton>
            </div>
        </template>
    </NModal>
</template>
