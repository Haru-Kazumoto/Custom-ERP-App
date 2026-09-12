<script setup>
import { h } from "vue";
import { MoreVertical, Eye, Pencil, Printer, Trash2 } from "lucide-vue-next";
import { NButton, NCheckbox, NDropdown } from "naive-ui";
import ApprovalChain from "./ApprovalChain.vue";
import ExpeditionBadge from "./ExpeditionBadge.vue";

/**
 * po (Purchase Order) — bentuk objek yang diharapkan dari backend, lihat
 * contoh lengkap pada komentar di DaftarDokumen.vue
 */
const props = defineProps({
    po: {
        type: Object,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(["toggle-select", "view", "edit", "print", "delete"]);

// NDropdown: item lewat array `options` + satu handler @select.
// Separator: { type: 'divider' }. Ikon dirender via fungsi render.
const menuOptions = [
    {
        label: "Lihat Detail",
        key: "view",
        icon: () => h(Eye, { class: "h-4 w-4" }),
    },
    { label: "Edit", key: "edit", icon: () => h(Pencil, { class: "h-4 w-4" }) },
    {
        label: "Cetak PDF",
        key: "print",
        icon: () => h(Printer, { class: "h-4 w-4" }),
    },
    { type: "divider", key: "d1" },
    {
        label: "Hapus",
        key: "delete",
        icon: () => h(Trash2, { class: "h-4 w-4" }),
        props: { style: "color:#dc2626" },
    },
];

function handleAction(key) {
    if (key === "view") emit("view", props.po);
    else if (key === "edit") emit("edit", props.po);
    else if (key === "print") emit("print", props.po);
    else if (key === "delete") emit("delete", props.po);
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <NCheckbox
                    :checked="selected"
                    class="mt-1"
                    @update:checked="emit('toggle-select', po.id)"
                />
                <div>
                    <p class="font-semibold text-slate-900">{{ po.transaction_code }}</p>
                    <p class="text-xs text-slate-500">
                        {{ po.detail.pemasok ?? "-" }}
                    </p>
                </div>
            </div>

            <NDropdown
                trigger="click"
                placement="bottom-end"
                :options="menuOptions"
                @select="handleAction"
            >
                <NButton
                    quaternary
                    circle
                    size="small"
                    class="h-8 w-8 -mr-2 -mt-1"
                >
                    <MoreVertical class="h-4 w-4 text-slate-500" />
                </NButton>
            </NDropdown>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-xs text-slate-400">Total PO</p>
                <p class="font-medium text-slate-900">
                    {{ formatCurrency(po.grand_total) }}
                </p>
            </div>
            <div>
                <p class="text-xs text-slate-400">Pengirim</p>
                <ExpeditionBadge
                    :nama="po.detail.transportasi ?? '-'"
                    :mode="po.detail.nomor_polisi"
                />
            </div>
        </div>

        <div class="mt-4 border-t border-slate-100 pt-3">
            <p class="text-xs text-slate-400 mb-1.5">Persetujuan</p>
            <ApprovalChain
                :approvers="
                    po.persetujuan?.approvers ?? [
                        {
                            id: 1,
                            nama: 'Able Anthony',
                            avatar_url: null,
                            status: 'disetujui',
                            urutan: 1,
                        },
                        {
                            id: 2,
                            nama: 'Bamasaye Mobolaji',
                            avatar_url: null,
                            status: 'disetujui',
                            urutan: 2,
                        },
                        {
                            id: 3,
                            nama: 'Bamasaye Mobolaji',
                            avatar_url: null,
                            status: 'disetujui',
                            urutan: 3,
                        },
                        {
                            id: 4,
                            nama: 'Bamasaye Mobolaji',
                            avatar_url: null,
                            status: 'disetujui',
                            urutan: 4,
                        },
                        {
                            id: 5,
                            nama: 'Bamasaye Mobolaji',
                            avatar_url: null,
                            status: 'menunggu',
                            urutan: 5,
                        },
                        {
                            id: 6,
                            nama: 'Bamasaye Mobolaji',
                            avatar_url: null,
                            status: 'menunggu',
                            urutan: 6,
                        },
                        {
                            id: 7,
                            nama: 'Haru Kazumoto',
                            avatar_url: null,
                            status: 'menunggu',
                            urutan: 7,
                        },
                    ]
                "
            />
        </div>
    </div>
</template>
