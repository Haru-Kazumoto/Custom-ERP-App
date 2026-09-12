<script setup lang="ts">
import { computed } from "vue";
import { X, Box } from "lucide-vue-next";
import type { MenuItem } from "@/types/menu";
import NavGroup from "./NavGroup.vue";
import { NButton } from "naive-ui";
import { LogOut } from "lucide-vue-next";
import { Account } from "@/types/account.js";
import { usePage } from "@inertiajs/vue3";

const props = defineProps<{
    menus: MenuItem[];
    loading?: boolean;
    activeKey: string;
    open: boolean; // drawer mobile
    user: Account;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "logout"): void;
}>();

const isEmpty = computed(() => !props.loading && props.menus.length === 0);
</script>

<template>
    <!-- Backdrop mobile -->
    <div
        v-if="open"
        class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
        @click="emit('close')"
    />

    <aside
        :class="[
            'fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-300 lg:static lg:translate-x-0',
            open ? 'translate-x-0' : '-translate-x-full',
        ]"
    >
        <!-- Brand -->
        <div class="flex items-center justify-between px-5 py-5">
            <div class="flex items-center gap-2.5">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0284c7] text-white"
                >
                    <Box class="h-5 w-5" />
                </div>
                <span class="text-base font-bold text-slate-800"
                    >ERP Suite</span
                >
            </div>
            <button class="text-slate-400 lg:hidden" @click="emit('close')">
                <X class="h-5 w-5" />
            </button>
        </div>

        <!-- User card -->
        <div
            class="mx-5 mb-4 flex flex-col items-center rounded-2xl bg-sky-100/70 p-4 text-center"
        >
            <div
                class="h-16 w-16 overflow-hidden rounded-full bg-sky-100 ring-2 ring-sky-100"
            >
                <img
                    src="https://i.pravatar.cc/120?img=12"
                    alt="User"
                    class="h-full w-full object-cover"
                />
            </div>
            <p class="mt-3 text-sm font-semibold text-slate-800">{{ user.name }}</p>
            <p class="text-xs text-slate-500">{{ user.role }} {{ user.sub_role ? '| ' + user.sub_role : '' }}</p>

            <NButton
                type="error"
                class="w-full mt-3 text-white bg-destructive hover:bg-destructive/80"
                @click="emit('logout')"
            >
                <template #icon>
                    <LogOut />
                </template>
                Logout
            </NButton>
        </div>

        <!-- Menu -->
        <nav class="flex-1 space-y-1 overflow-y-auto px-4 pb-6">
            <template v-if="loading">
                <div
                    v-for="n in 6"
                    :key="n"
                    class="h-10 animate-pulse rounded-lg bg-slate-100"
                />
            </template>

            <div
                v-else-if="isEmpty"
                class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400"
            >
                <slot name="empty">Belum ada menu.</slot>
            </div>

            <template v-else>
                <NavGroup
                    v-for="item in menus"
                    :key="item.id"
                    :item="item"
                    :active-key="activeKey"
                />
            </template>

            <!-- Slot tambahan kalau nanti perlu blok menu custom -->
            <slot name="menu-extra" />
        </nav>
    </aside>
</template>
