<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { NAlert, NButton, NEmpty, NSelect, NTag, useMessage } from "naive-ui";
import * as icons from "lucide-vue-next";
import {
    ArrowLeft,
    CornerUpLeft,
    FolderTree,
    Save,
    Unlink,
} from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import type { MenuItem } from "@/types/menu";

type MenuRow = MenuItem & { parent_name: string | null };

const props = defineProps<{
    menus_data: MenuRow[];
    parents: { label: string; value: number }[];
    /** { menu_id: [id anak, id cucu, ...] } dari MenusRepository::getDescendantMap() */
    descendantMap: Record<string, number[]>;
}>();

const message = useMessage();
const busyMenuId = ref<number | null>(null);

// Nilai parent yang belum disimpan, per menu.
//
// Diisi dari props saat setup supaya tiap baris punya nilai sendiri (dipakai
// langsung sebagai target v-model), dan `reset()` bisa mengembalikannya ke
// nilai server. `null` di sini berarti "parent dilepas".
const draftParent = reactive<Record<number, number | null>>(
    Object.fromEntries(props.menus_data.map((m) => [m.id, m.parent_id ?? null])),
);

function resolveIcon(name: string) {
    return (icons as Record<string, unknown>)[name] ?? icons.Circle;
}

function currentParent(menu: MenuRow): number | null {
    return menu.parent_id ?? null;
}

function isDirty(menu: MenuRow): boolean {
    return draftParent[menu.id] !== currentParent(menu);
}

/**
 * Opsi parent untuk satu menu, dengan anak turunannya dinonaktifkan.
 *
 * Menu tidak bisa jadi anak dari dirinya sendiri atau keturunannya — itu
 * membentuk siklus yang `buildMenuTree` tidak bisa selesaikan, sehingga menu
 * tersebut hilang dari sidebar. Guard yang sama ada di
 * AttachMenuToParentAction; ini cuma supaya opsi buruk tidak bisa diklik.
 */
function optionsFor(menu: MenuRow) {
    const blocked = new Set<number>([
        menu.id,
        ...(props.descendantMap[String(menu.id)] ?? []),
    ]);

    return props.parents.map((option) => ({
        ...option,
        disabled: blocked.has(option.value),
    }));
}

const dirtyCount = computed(
    () => props.menus_data.filter(isDirty).length,
);

function reset() {
    for (const menu of props.menus_data) {
        draftParent[menu.id] = menu.parent_id ?? null;
    }
}

function save(menu: MenuRow) {
    const parentId = draftParent[menu.id];

    if (parentId === null) {
        detach(menu);
        return;
    }

    busyMenuId.value = menu.id;

    router.post(
        route("menus.attach-parent"),
        { menu_id: menu.id, parent_id: parentId },
        {
            preserveScroll: true,
            onSuccess: () => {
                message.success(`Parent untuk "${menu.name}" diperbarui`);
                reset();
            },
            onError: (errors) => {
                const first = Object.values(errors)[0] as string | undefined;
                message.error(first ?? "Gagal menyimpan parent");
            },
            onFinish: () => (busyMenuId.value = null),
        },
    );
}

function detach(menu: MenuRow) {
    busyMenuId.value = menu.id;

    router.post(
        route("menus.detach-parent"),
        { menu_id: menu.id },
        {
            preserveScroll: true,
            onSuccess: () => {
                message.success(`"${menu.name}" sekarang level atas`);
                reset();
            },
            onError: () => message.error("Gagal melepas parent"),
            onFinish: () => (busyMenuId.value = null),
        },
    );
}
</script>

<template>
    <Head title="Kelola Parent Menu" />

    <AppLayout pageName="Kelola Parent Menu">
        <div class="space-y-4 lg:space-y-6">
            <HeaderPage
                title="Kelola Parent Menu"
                sub-title="Pasang setiap menu sebagai anak dari menu lain, atau lepas jadi menu level atas"
            >
                <template #action>
                    <Link :href="route('menus.index')">
                        <NButton secondary strong>
                            <template #icon><ArrowLeft :size="16" /></template>
                            Kembali
                        </NButton>
                    </Link>
                </template>
            </HeaderPage>

            <NAlert type="info" :show-icon="true">
                Menu tidak bisa dijadikan anak dari dirinya sendiri atau
                keturunannya — pilihan seperti itu dinonaktifkan, dan server juga
                akan menolaknya.
            </NAlert>

            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-2 border-b border-slate-100 p-4 md:flex-row md:items-center md:justify-between md:p-5"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-900">
                            Struktur Parent
                        </span>
                        <span
                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-xs font-medium text-slate-600"
                        >
                            {{ menus_data.length }}
                        </span>
                    </div>

                    <NTag
                        v-if="dirtyCount > 0"
                        type="warning"
                        :bordered="false"
                        size="small"
                    >
                        {{ dirtyCount }} belum disimpan
                    </NTag>
                </div>

                <div v-if="menus_data.length === 0" class="p-10">
                    <NEmpty description="Belum ada menu" />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500"
                            >
                                <th class="px-5 py-3 font-medium">Menu</th>
                                <th class="px-5 py-3 font-medium">
                                    Parent Saat Ini
                                </th>
                                <th class="px-5 py-3 font-medium">
                                    Parent Baru
                                </th>
                                <th class="px-5 py-3 font-medium text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="menu in menus_data"
                                :key="menu.id"
                                class="border-b border-slate-50 align-top last:border-0 hover:bg-slate-50/70"
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
                                                class="truncate font-mono text-xs text-slate-400"
                                            >
                                                {{ menu.key }}
                                            </p>
                                        </div>
                                    </div>
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
<NSelect
                                            v-model:value="
                                                draftParent[menu.id]
                                            "
                                            :options="optionsFor(menu)"
                                        placeholder="Pilih parent..."
                                        size="small"
                                        class="w-64"
                                        :disabled="busyMenuId === menu.id"
                                    />
                                </td>

                                <td class="px-5 py-3.5">
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <NButton
                                            size="small"
                                            type="primary"
                                            secondary
                                            strong
                                            :disabled="!isDirty(menu)"
                                            :loading="busyMenuId === menu.id"
                                            @click="save(menu)"
                                        >
                                            <template #icon>
                                                <Save :size="14" />
                                            </template>
                                            Simpan
                                        </NButton>

                                        <NButton
                                            v-if="menu.parent_id !== null"
                                            size="small"
                                            quaternary
                                            type="error"
                                            :disabled="busyMenuId === menu.id"
                                            @click="detach(menu)"
                                        >
                                            <template #icon>
                                                <Unlink :size="14" />
                                            </template>
                                            Lepas
                                        </NButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="flex items-center gap-2 border-t border-slate-100 p-4 text-xs text-slate-400 md:p-5"
                >
                    <CornerUpLeft :size="14" />
                    <span>
                        Perubahan baru berlaku setelah tekan Simpan pada
                        barisnya. Menu tanpa parent tampil sebagai "Level atas".
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>