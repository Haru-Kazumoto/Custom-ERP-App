<script setup lang="ts">
import { ref, computed, watch } from "vue";
import * as icons from "lucide-vue-next";
import { ChevronDown } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import type { MenuItem } from "@/types/menu";
import NavItem from "./NavItem.vue";

const props = defineProps<{ item: MenuItem; activeKey: string | null }>();

const hasChildren = computed(() => (props.item.children?.length ?? 0) > 0);

const isSelfActive = computed(() => props.item.key === props.activeKey);
const childActive = computed(
    () => props.item.children?.some((c) => c.key === props.activeKey) ?? false,
);

// group dianggap "menyala" kalau dirinya sendiri match ATAU salah satu anaknya match
const groupActive = computed(() => isSelfActive.value || childActive.value);

const open = ref(groupActive.value);

// supaya tetap sinkron saat navigasi SPA (Inertia) tanpa remount komponen
watch(groupActive, (val) => {
    if (val) open.value = true;
});

const IconComp = computed(
    () => (icons as Record<string, unknown>)[props.item.icon] ?? icons.Circle,
);
</script>

<template>
    <NavItem
        v-if="!hasChildren"
        :item="item"
        :active="item.key === activeKey"
    />

    <div v-else>
        <button
            type="button"
            :class="
                cn(
                    'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                    groupActive
                        ? 'text-[#18a058]'
                        : 'text-slate-600 hover:bg-[#e9f6ee] hover:text-[#18a058]',
                )
            "
            @click="open = !open"
        >
            <component :is="IconComp" class="h-[18px] w-[18px] shrink-0" />
            <span class="flex-1 truncate text-left">{{ item.name }}</span>
            <ChevronDown
                class="h-4 w-4 transition-transform"
                :class="open && 'rotate-180'"
            />
        </button>

        <div
            v-show="open"
            class="ml-4 mt-1 space-y-1 border-l border-[#e9f6ee] pl-3"
        >
            <NavItem
                v-for="child in item.children"
                :key="child.id"
                :item="child"
                :active="child.key === activeKey"
            />
        </div>
    </div>
</template>
