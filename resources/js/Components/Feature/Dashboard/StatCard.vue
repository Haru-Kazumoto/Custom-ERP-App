<script setup lang="ts">
import { computed } from "vue";
import * as icons from "lucide-vue-next";
import { ArrowUpRight, ArrowDownRight } from "lucide-vue-next";

const props = defineProps<{
    label: string;
    value: string;
    icon: string;
    delta?: number;
}>();

const IconComp = computed(
    () => (icons as Record<string, unknown>)[props.icon] ?? icons.Circle,
);
const up = computed(() => (props.delta ?? 0) >= 0);
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-[#0284c7]"
            >
                <component :is="IconComp" class="h-5 w-5" />
            </div>
            <span
                v-if="delta !== undefined"
                class="flex items-center gap-0.5 text-xs font-medium"
                :class="up ? 'text-emerald-600' : 'text-rose-500'"
            >
                <component
                    :is="up ? ArrowUpRight : ArrowDownRight"
                    class="h-3.5 w-3.5"
                />
                {{ Math.abs(delta) }}%
            </span>
        </div>
        <p class="mt-4 text-2xl font-bold text-slate-800">{{ value }}</p>
        <p class="text-sm text-slate-400">{{ label }}</p>
    </div>
</template>
