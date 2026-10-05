<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    NAlert,
    NButton,
    NCheckbox,
    NEmpty,
    NSelect,
    NSpin,
    NTag,
    useMessage,
} from "naive-ui";
import * as icons from "lucide-vue-next";
import {
    ArrowLeft,
    CheckCheck,
    Save,
    ShieldCheck,
    XCircle,
} from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";
import type { MenuItem } from "@/types/menu";

type MenuRow = MenuItem & { parent_name: string | null };

const props = defineProps<{
    menus_data: MenuRow[];
    roles: { label: string; value: number }[];
    selectedRoleId: number;
    attachedMenuIds: number[];
}>();

const message = useMessage();
const submitting = ref(false);

// Checkbox tercentang = menu yang akan ter-attach ke role terpilih.
const selected = ref<number[]>([...props.attachedMenuIds]);

// Ganti role lewat select -> pindah halaman, server yang kirim daftar baru.
const roleId = ref<number>(props.selectedRoleId);

watch(
    () => props.attachedMenuIds,
    (ids) => {
        selected.value = [...ids];
    },
);

const selectedRole = computed(
    () => props.roles.find((r) => r.value === roleId.value)?.label ?? "-",
);

const selectedSet = computed(() => new Set(selected.value));

function resolveIcon(name: string) {
    return (icons as Record<string, unknown>)[name] ?? icons.Circle;
}

function changeRole(value: number) {
    // Guard: NSelect sudah mengubah `roleId`, tapi ambil dari argumen supaya
    // tidak bergantung pada urutan handler v-model.
    roleId.value = value;

    router.get(
        route("menus.manage-role"),
        { role: value },
        { preserveScroll: true, preserveState: true },
    );
}

function toggle(id: number, checked: boolean) {
    selected.value = checked
        ? [...selected.value, id]
        : selected.value.filter((v) => v !== id);
}

function checkAll() {
    selected.value = props.menus_data.map((m) => m.id);
}

function clearAll() {
    selected.value = [];
}

/**
 * Jumlah menu yang berubah dibanding kondisi server.
 *
 * `attach` = ada di sini tapi belum ter-attach, `detach` = sebaliknya.
 */
const changes = computed(() => {
    const server = new Set(props.attachedMenuIds);
    const picked = selectedSet.value;

    const toAttach = [...picked].filter((id) => !server.has(id));
    const toDetach = [...server].filter((id) => !picked.has(id));

    return { toAttach, toDetach };
});

const dirty = computed(
    () => changes.value.toAttach.length > 0 || changes.value.toDetach.length > 0,
);

function save() {
    submitting.value = true;

    router.post(
        route("menus.sync-role-menus"),
        {
            role_id: roleId.value,
            // Checkbox yang tidak tercentang tidak terkirim sama sekali; nilai
            // kosong berarti server melepas semua menu dari role ini.
            menu_ids: selected.value,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                message.success(
                    `Hak akses menu untuk "${selectedRole.value}" diperbarui`,
                ),
            onError: (errors) => {
                const first = Object.values(errors)[0] as string | undefined;
                message.error(first ?? "Gagal menyimpan hak akses");
            },
            onFinish: () => (submitting.value = false),
        },
    );
}
</script>

<template>
    <Head title="Kelola Menu per Role" />

    <AppLayout pageName="Kelola Menu per Role">
        <div class="space-y-4 lg:space-y-6">
            <HeaderPage
                title="Kelola Menu per Role"
                sub-title="Centang menu yang boleh diakses oleh role terpilih, lalu simpan"
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

            <NSpin :show="submitting">
                <div class="grid gap-4 lg:grid-cols-3 lg:gap-6">
                    <!-- Panel role -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-1"
                    >
                        <h2
                            class="mb-4 text-sm font-semibold text-slate-900"
                        >
                            Pilih Role
                        </h2>

                        <NSelect
                            v-model:value="roleId"
                            :options="roles"
                            class="w-full"
                            @update:value="changeRole"
                        />

                        <div
                            class="mt-4 space-y-2 rounded-xl bg-slate-50 p-4"
                        >
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-slate-500">Role aktif</span>
                                <span
                                    class="font-semibold text-slate-800"
                                    >{{ selectedRole }}</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-slate-500">Menu tercentang</span>
                                <span
                                    class="font-semibold text-slate-800"
                                >
                                    {{ selected.length }} / {{ menus_data.length }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <NButton
                                size="small"
                                secondary
                                strong
                                class="flex-1"
                                @click="checkAll"
                            >
                                <template #icon>
                                    <CheckCheck :size="14" />
                                </template>
                                Beri Semua
                            </NButton>
                            <NButton
                                size="small"
                                secondary
                                strong
                                class="flex-1"
                                @click="clearAll"
                            >
                                <template #icon>
                                    <XCircle :size="14" />
                                </template>
                                Kosongkan
                            </NButton>
                        </div>

                        <NButton
                            type="primary"
                            strong
                            block
                            class="mt-4"
                            :loading="submitting"
                            :disabled="!dirty"
                            @click="save"
                        >
                            <template #icon><Save :size="16" /></template>
                            Simpan Hak Akses
                        </NButton>

                        <p
                            v-if="!dirty"
                            class="mt-2 text-center text-xs text-slate-400"
                        >
                            Belum ada perubahan untuk disimpan
                        </p>
                    </div>

                    <!-- Panel menu -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2"
                    >
                        <div
                            class="flex flex-col gap-2 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between md:p-5"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    Menu
                                </span>
                                <span
                                    class="flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-xs font-medium text-slate-600"
                                >
                                    {{ menus_data.length }}
                                </span>
                            </div>

                            <div class="flex gap-2">
                                <NTag
                                    v-if="changes.toAttach.length"
                                    type="success"
                                    size="small"
                                    :bordered="false"
                                >
                                    +{{ changes.toAttach.length }} ditambah
                                </NTag>
                                <NTag
                                    v-if="changes.toDetach.length"
                                    type="error"
                                    size="small"
                                    :bordered="false"
                                >
                                    -{{ changes.toDetach.length }} dilepas
                                </NTag>
                            </div>
                        </div>

                        <NAlert
                            v-if="menus_data.length === 0"
                            type="info"
                            class="m-4"
                        >
                            <NEmpty description="Belum ada menu" />
                        </NAlert>

                        <div v-else class="divide-y divide-slate-50">
                            <label
                                v-for="menu in menus_data"
                                :key="menu.id"
                                class="flex cursor-pointer items-start gap-3 px-5 py-3.5 transition hover:bg-slate-50/70"
                            >
                                <NCheckbox
                                    :checked="
                                        selectedSet.has(menu.id)
                                    "
                                    class="mt-0.5"
                                    @update:checked="
                                        (v: boolean) => toggle(menu.id, v)
                                    "
                                />

                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100"
                                >
                                    <component
                                        :is="resolveIcon(menu.icon)"
                                        class="h-4 w-4 text-slate-500"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="truncate text-sm font-medium text-slate-900"
                                        >
                                            {{ menu.name }}
                                        </p>
                                        <span
                                            v-if="!menu.is_active"
                                            class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500"
                                        >
                                            Nonaktif
                                        </span>
                                    </div>
                                    <p
                                        class="truncate font-mono text-xs text-slate-400"
                                    >
                                        {{ menu.key }}
                                        <span v-if="menu.parent_name">
                                            · anak dari
                                            {{ menu.parent_name }}
                                        </span>
                                    </p>
                                </div>

                                <NTag
                                    v-if="menu.parent_name === null"
                                    size="tiny"
                                    :bordered="false"
                                    class="shrink-0"
                                >
                                    Level atas
                                </NTag>
                            </label>
                        </div>

                        <div
                            class="flex items-center gap-2 border-t border-slate-100 p-4 text-xs text-slate-400 md:p-5"
                        >
                            <ShieldCheck :size="14" />
                            <span>
                                Menu yang dicentang akan tersimpan di
                                <span class="font-mono">role_menus</span
                                >. Menu nonaktif tetap bisa dicentang, tapi tidak
                                muncul di sidebar sampai diaktifkan.
                            </span>
                        </div>
                    </div>
                </div>
            </NSpin>
        </div>
    </AppLayout>
</template>