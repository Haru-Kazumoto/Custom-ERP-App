<script setup>
import { computed } from "vue";
import { Truck, Sailboat, Plane, Package } from "lucide-vue-next";

/**
 * Props:
 * - nama: nama ekspedisi/pengirim, contoh "JNE Logistics"
 * - mode: 'darat' | 'laut' | 'udara' (opsional, untuk menentukan ikon & warna)
 */
const props = defineProps({
    nama: {
        type: String,
        default: "-",
    },
    mode: {
        type: String,
        default: null,
    },
});

const modeConfig = {
    darat: {
        icon: Truck,
        classes: "bg-blue-50 text-blue-700",
    },
    laut: {
        icon: Sailboat,
        classes: "bg-emerald-50 text-emerald-700",
    },
    udara: {
        icon: Plane,
        classes: "bg-violet-50 text-violet-700",
    },
};

const config = computed(
    () =>
        modeConfig[props.mode] ?? {
            icon: Package,
            classes: "bg-slate-100 text-slate-600",
        },
);
</script>

<template>
    <div class="flex items-center gap-2">
        <span
            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
            :class="config.classes"
        >
            <component :is="config.icon" class="h-3.5 w-3.5" />
        </span>
        <span class="text-sm text-slate-700 truncate">{{ nama }}</span>
    </div>
</template>
