<script setup>
import { computed } from "vue";
import { NAvatar, NTooltip } from "naive-ui";
import { Check, Clock, X, Minus } from "lucide-vue-next";

/**
 * Data head hasil merge SQL (bukan array chain):
 *   last_approval  : nama approver terakhir yang bertindak (string, boleh null)
 *   status_approval: 'disetujui' | 'menunggu' | 'ditolak' | 'belum'
 */
const props = defineProps({
    lastApproval: {
        type: String,
        default: null,
    },
    statusApproval: {
        type: String,
        default: "PENDING",
    },
    proceedBy: {
        type: String,
        default: "User Proceeded",
    },
});

const meta = {
    APPROVED: {
        ring: "ring-emerald-500",
        dot: "bg-emerald-500",
        label: "text-emerald-700",
        icon: Check,
    },
    PENDING: {
        ring: "ring-amber-400",
        dot: "bg-amber-400",
        label: "text-amber-700",
        icon: Clock,
    },
    REJECTED: {
        ring: "ring-red-500",
        dot: "bg-red-500",
        label: "text-red-700",
        icon: X,
    },
    NEED_REVISION: {
        ring: "ring-violet-500",
        dot: "bg-violet-500",
        label: "text-violet-700",
        icon: Minus,
    },
    belum: {
        ring: "ring-slate-200",
        dot: "bg-slate-300",
        label: "text-slate-500",
        icon: Minus,
    },
};

const current = computed(() => meta[props.statusApproval] ?? meta.belum);

const statusLabel = computed(() => {
    switch (props.statusApproval) {
        case "APPROVED":
            return props.lastApproval
                ? `Disetujui oleh ${props.lastApproval}`
                : "Disetujui";
        case "REJECTED":
            return props.lastApproval
                ? `Ditolak oleh ${props.lastApproval}`
                : "Ditolak";
        case "PENDING":
            return props.lastApproval
                ? `Menunggu ${props.lastApproval}`
                : "Menunggu persetujuan";
        case "NEED_REVISION":
            return props.lastApproval
                ? `Perlu revisi dari ${props.lastApproval}`
                : "Perlu revisi";
        default:
            return "Belum ada persetujuan";
    }
});

function initials(nama) {
    if (!nama) return "?";
    return nama
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();
}
</script>

<template>
    <div class="flex items-center gap-2">
        <NTooltip :delay="150">
            <template #trigger>
                <div
                    :class="[
                        'relative rounded-full ring-2 ring-offset-1 ring-offset-white',
                        current.ring,
                        statusApproval === 'PENDING' ? 'opacity-50' : '',
                    ]"
                >
                    <NAvatar
                        round
                        :size="28"
                        class="border border-white bg-slate-100 text-[10px] font-medium text-slate-600"
                    >
                        {{ initials(lastApproval) }}
                    </NAvatar>
                    <span
                        class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center rounded-full border border-white"
                        :class="current.dot"
                    >
                        <component
                            :is="current.icon"
                            class="h-2 w-2 text-white"
                        />
                    </span>
                </div>
            </template>

            <p class="font-medium">{{ lastApproval || "—" }}</p>
            <p class="text-xs text-slate-500 capitalize">
                {{ statusApproval }}
            </p>
        </NTooltip>

        <div class="flex flex-col">
            <span class="text-sm font-medium" :class="current.label">
                {{ statusLabel }}
            </span>

            <span class="text-xs text-slate-500">{{ proceedBy }}</span>
        </div>
    </div>
</template>
