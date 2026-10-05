<script setup lang="ts">
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { NButton, NEmpty, NInput, NTag } from "naive-ui";
import * as icons from "lucide-vue-next";
import {
    ArrowLeft,
    FolderTree,
    Plus,
    Search,
    ShieldCheck,
} from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import type { MenuItem } from "@/types/menu";

/**
 * `parent_name` tidak ada di tipe MenuItem karena bukan kolom tabel `menus`,
 * melainkan hasil LEFT JOIN di GetMenusQuery.
 */
type MenuRow = MenuItem & { parent_name: string | null };

const props = defineProps<{
    menus_data: MenuRow[];
    roles: { label: string; value: number }[];
}>();

const search = ref("");

// Samakan dengan NavItem.vue — nama icon datang dari DB sebagai string.
function resolveIcon(name: string) {
    return (icons as Record<string, unknown>)[name] ?? icons.Circle;
}

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.menus_data;

    return props.menus_data.filter((menu) =>
        [menu.name, menu.key, menu.url, menu.route_name, menu.parent_name]
            .filter((v): v is string => !!v)
            .some((v) => v.toLowerCase().includes(q)),
    );
});

const activeCount = computed(
    () => props.menus_data.filter((menu) => menu.is_active).length,
);
const childCount = computed(
    () => props.menus_data.filter((menu) => menu.parent_id !== null).length,
);

function go(url: string) {
    router.visit(url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Manajemen Menu" />

    <AppLayout pageName="Manajemen Menu">
        <div class="space-y-4 lg:space-y-6">
            <HeaderPage
                title="Manajemen Menu"
                sub-title="Atur struktur parent/child menu dan icon yang tampil di sidebar"
            >
                <template #action>
                    <div class="flex flex-wrap gap-2">
                        <NButton
                            secondary
                            strong
                            @click="go(route('menus.manage-menu'))"
                        >
                            <template #icon><FolderTree :size="16" /></template>
                            Kelola Parent
                        </NButton>
                        <NButton
                            secondary
                            strong
                            @click="go(route('menus.manage-role'))"
                        >
                            <template #icon>
                                <ShieldCheck :size="16" />
                            </template>
                            Kelola Role
                        </NButton>
                        <Link :href="route('menus.create')">
                            <NButton type="primary" strong>
                                <template #icon><Plus :size="16" /></template>
                                Menu Baru
                            </NButton>
                        </Link>
                    </div>
                </template>
            </HeaderPage>

            <!-- Ringkasan -->
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p class="text-2xl font-bold text-slate-800">
                        {{ menus_data.length }}
                    </p>
                    <p class="text-sm text-slate-400">Total Menu</p>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p class="text-2xl font-bold text-slate-800">
                        {{ activeCount }}
                    </p>
                    <p class="text-sm text-slate-400">Aktif</p>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p class="text-2xl font-bold text-slate-800">
                        {{ childCount }}
                    </p>
                    <p class="text-sm text-slate-400">Punya Parent</p>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p class="text-2xl font-bold text-slate-800">
                        {{ roles.length }}
                    </p>
                    <p class="text-sm text-slate-400">Role Terdaftar</p>
                </div>
            </div>

            <!-- Tabel -->
            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-4 md:flex-row md:items-center md:justify-between md:p-5"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-900">
                            Daftar Menu
                        </span>
                        <span
                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-xs font-medium text-slate-600"
                        >
                            {{ filtered.length }}
                        </span>
                    </div>

                    <NInput
                        v-model:value="search"
                        placeholder="Cari nama, key, url, atau parent..."
                        clearable
                        class="md:w-80"
                    >
                        <template #prefix>
                            <Search :size="16" class="text-slate-400" />
                        </template>
                    </NInput>
                </div>

                <div v-if="filtered.length === 0" class="p-10">
                    <NEmpty description="Belum ada menu yang cocok" />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500"
                            >
                                <th class="px-5 py-3 font-medium">Menu</th>
                                <th class="px-5 py-3 font-medium">Key</th>
                                <th class="px-5 py-3 font-medium">URL</th>
                                <th class="px-5 py-3 font-medium">Parent</th>
                                <th class="px-5 py-3 font-medium">Route</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="menu in filtered"
                                :key="menu.id"
                                class="border-b border-slate-50 last:border-0 hover:bg-slate-50/70"
                            >
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100"
                                        >
                                            <component
                                                :is="resolveIcon(menu.icon)"
                                                class="h-4 w-4 text-slate-500"
                                            />
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate font-medium text-slate-900"
                                            >
                                                {{ menu.name }}
                                            </p>
                                            <p
                                                v-if="menu.description"
                                                class="truncate text-xs text-slate-400"
                                            >
                                                {{ menu.description }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-600"
                                    >
                                        {{ menu.key }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="font-mono text-xs text-slate-500"
                                    >
                                        {{ menu.url }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        v-if="menu.parent_name"
                                        class="inline-flex items-center gap-1.5 text-slate-700"
                                    >
                                        <FolderTree
                                            :size="14"
                                            class="text-slate-400"
                                        />
                                        {{ menu.parent_name }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400">
                                        Level atas
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        v-if="menu.route_name"
                                        class="font-mono text-xs text-slate-600"
                                    >
                                        {{ menu.route_name }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400">
                                        —
                                    </span>
                                    <div
                                        v-if="
                                            menu.active_routes &&
                                            menu.active_routes.length
                                        "
                                        class="mt-1 flex flex-wrap gap-1"
                                    >
                                        <NTag
                                            v-for="route in menu.active_routes"
                                            :key="route"
                                            size="tiny"
                                            :bordered="false"
                                        >
                                            {{ route }}
                                        </NTag>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <NTag
                                        :type="
                                            menu.is_active ? 'success' : 'default'
                                        "
                                        size="small"
                                        :bordered="false"
                                    >
                                        {{
                                            menu.is_active
                                                ? "Aktif"
                                                : "Nonaktif"
                                        }}
                                    </NTag>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="flex items-center justify-between border-t border-slate-100 p-4 text-sm text-slate-500 md:p-5"
                >
                    <span
                        >Menampilkan {{ filtered.length }} dari
                        {{ menus_data.length }} menu</span
                    >
                    <Link
                        :href="route('menus.index')"
                        class="inline-flex items-center gap-1.5 text-slate-600 hover:text-slate-800"
                    >
                        <ArrowLeft :size="14" />
                        Muat ulang
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
