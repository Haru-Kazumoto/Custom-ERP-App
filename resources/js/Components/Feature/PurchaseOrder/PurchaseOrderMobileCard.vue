<script setup lang="ts">
import { h } from "vue";
import { MoreVertical, Eye, Pencil } from "lucide-vue-next";
import { NButton, NCheckbox, NDropdown, NTag } from "naive-ui";
import ApprovalChain from "./ApprovalChain.vue";
import ExpeditionBadge from "./ExpeditionBadge.vue";
import type { PurchaseOrderSummary } from "@/types/purchase-order";

/**
 * Kartu Purchase Order untuk tampilan mobile — pasangan tabel desktop di
 * `Pages/PurchaseOrder/Index.vue`.
 *
 * `po` mengikuti `PurchaseOrderSummary` (`GetPurchaseOrdersQuery`), bukan model
 * `PurchaseOrder`: status approval sudah digabung jadi satu langkah
 * (`current_approval_*`), bukan array approver. Karena itu `ApprovalChain`
 * menerima `lastApproval`/`statusApproval`, bukan `approvers`.
 */
const props = defineProps({
    po: {
        type: Object as () => PurchaseOrderSummary,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
    formatCurrency: {
        type: Function as () => (value: number | string) => string,
        required: true,
    },
    /** Id user yang sedang login; penentu ditampilkan atau tidaknya aksi Revisi. */
    currentUserId: {
        type: Number,
        default: null,
    },
    /** Status yang menandai dokumen sedang menunggu revisi. */
    revisionStatus: {
        type: String,
        default: "NEED_REVISION",
    },
});

const emit = defineEmits(["toggle-select", "view", "revise"]);

/**
 * Aksi menu dibuat per kartu. "Revisi" hanya untuk dokumen berstatus revisi milik
 * pembuatnya; `RevisePurchaseOrderAction` menegakkan syarat yang sama di server.
 */
function menuOptions() {
    const options = [
        {
            label: "Lihat Detail",
            key: "view",
            icon: () => h(Eye, { class: "h-4 w-4" }),
        },
    ];

    if (canRevise(props.po)) {
        options.push({
            label: "Revisi",
            key: "revise",
            icon: () => h(Pencil, { class: "h-4 w-4" }),
        });
    }

    return options;
}

function canRevise(po: PurchaseOrderSummary) {
    return (
        po.current_approval_status === props.revisionStatus &&
        po.created_by === props.currentUserId
    );
}

function handleAction(key: string) {
    if (key === "view") emit("view", props.po);
    else if (key === "revise") emit("revise", props.po);
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
                    <p class="font-semibold text-slate-900">
                        {{ po.transaction_code }}
                    </p>
                    <p class="text-xs text-slate-500">
                        {{ po.detail?.pemasok ?? "-" }}
                    </p>
                </div>
            </div>

            <NDropdown
                trigger="click"
                placement="bottom-end"
                :options="menuOptions()"
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
                    :nama="po.detail?.transportasi ?? '-'"
                    :mode="po.detail?.nomor_polisi"
                />
            </div>
        </div>

        <div class="mt-4 border-t border-slate-100 pt-3">
            <p class="text-xs text-slate-400 mb-1.5">Persetujuan</p>
            <ApprovalChain
                :last-approval="po.current_approval_proceed_by"
                :status-approval="po.current_approval_status"
                :proceed-by="
                    po.current_approval_role
                        ? `Tahap ${po.current_approval_order}: ${po.current_approval_role}`
                        : '-'
                "
            />
        </div>

        <div
            v-if="po.current_approval_description"
            class="mt-3 rounded-lg bg-slate-50 px-2 py-1.5 text-xs text-slate-600"
        >
            {{ po.current_approval_description }}
        </div>

        <NTag
            v-if="
                !canRevise(po) && po.current_approval_status === revisionStatus
            "
            size="small"
            :bordered="false"
            class="mt-3 bg-slate-100 text-slate-500"
        >
            Hanya pembuat dokumen yang bisa merevisi
        </NTag>
    </div>
</template>
