<script setup lang="ts">
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import * as icons from "lucide-vue-next";
import { cn } from "@/lib/utils";
import type { MenuItem } from "@/types/menu";

const props = defineProps<{ item: MenuItem; active?: boolean }>();

// Resolve nama icon (string dari DB) -> komponen lucide, fallback Circle.
const IconComp = computed(
    () => (icons as Record<string, unknown>)[props.item.icon] ?? icons.Circle,
);
</script>

<template>
    <Link
        :href="item.url"
        :class="
            cn(
                'group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                active
                    ? 'bg-[#18a058] text-white'
                    : 'text-slate-600 hover:bg-[#e9f6ee] hover:text-[#18a058]',
            )
        "
    >
        <component :is="IconComp" class="h-[18px] w-[18px] shrink-0" />
        <span class="truncate">{{ item.name }}</span>
    </Link>
</template>
