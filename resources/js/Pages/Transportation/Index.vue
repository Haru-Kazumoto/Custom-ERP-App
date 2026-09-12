<script setup>
import { ref, computed, h } from "vue";
import { Head, router } from "@inertiajs/vue3";
import {
    NInput,
    NButton,
    NDropdown,
    NEmpty,
    useDialog,
    useMessage,
} from "naive-ui";
import {
    Search,
    Plus,
    ArrowLeftRight,
    MoreVertical,
    Eye,
    UserCircle,
    Trash2,
    Truck,
} from "lucide-vue-next";

/**
 * Props kontrak minimal sesuai permintaan: setiap item hanya
 * membawa `name` dan `code`. Sesuaikan nama prop / route di bawah
 * dengan controller Laravel kamu.
 */
const props = defineProps({
    transportations: {
        type: Array,
        default: () => [],
        // contoh item: { name: 'Flatbed Truck', code: 'TRK-001' }
    },
});

const dialog = useDialog();
const message = useMessage();
const search = ref("");

const filtered = computed(() => {
    if (!search.value.trim()) return props.transportations;
    const q = search.value.toLowerCase();
    return props.transportations.filter(
        (t) =>
            t.name.toLowerCase().includes(q) ||
            t.code.toLowerCase().includes(q),
    );
});

function renderIcon(icon) {
    return () => h(icon, { size: 16 });
}

const actionOptions = [
    { label: "Lihat Detail", key: "detail", icon: renderIcon(Eye) },
    {
        label: "Informasi PIC",
        key: "pic",
        icon: renderIcon(UserCircle),
    },
    { type: "divider", key: "d1" },
    { label: "Hapus", key: "delete", icon: renderIcon(Trash2) },
];

function handleAction(key, item) {
    switch (key) {
        case "detail":
            // sesuaikan dengan named route kamu, mis. route('transportation.show', item.code)
            router.visit(`/transportations/${item.code}`);
            break;
        case "pic":
            router.visit(`/transportations/${item.code}/pic`);
            break;
        case "delete":
            confirmDelete(item);
            break;
    }
}

function confirmDelete(item) {
    dialog.warning({
        title: "Hapus Data Transportasi",
        content: `Yakin ingin menghapus "${item.name}"? Tindakan ini tidak bisa dibatalkan.`,
        positiveText: "Hapus",
        negativeText: "Batal",
        onPositiveClick: () => {
            router.delete(`/transportations/${item.code}`, {
                preserveScroll: true,
                onSuccess: () =>
                    message.success(`${item.name} berhasil dihapus`),
                onError: () => message.error("Gagal menghapus data"),
            });
        },
    });
}
</script>

<template>
    <Head title="Data Transportasi" />

    <div class="min-h-screen bg-slate-50 px-4 py-6 md:px-8 md:py-8">
        <!-- Page header -->
        <div
            class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
        >
            <div>
                <h1 class="text-xl font-bold text-slate-900 md:text-2xl">
                    Data Transportasi
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Pantau seluruh moda transportasi pengiriman beserta kodenya
                </p>
            </div>

            <div class="flex gap-2">
                <NButton class="flex-1 md:flex-none" secondary strong>
                    <template #icon>
                        <ArrowLeftRight :size="16" />
                    </template>
                    Kelola Transportasi
                </NButton>
                <NButton class="flex-1 md:flex-none" type="primary" strong>
                    <template #icon>
                        <Plus :size="16" />
                    </template>
                    Transportasi Baru
                </NButton>
            </div>
        </div>

        <!-- Content card -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <!-- Toolbar -->
            <div
                class="flex flex-col gap-3 border-b border-slate-100 p-4 md:flex-row md:items-center md:justify-between md:p-5"
            >
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-900">
                        Daftar Transportasi
                    </span>
                    <span
                        class="flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-xs font-medium text-slate-600"
                    >
                        {{ filtered.length }}
                    </span>
                </div>

                <NInput
                    v-model:value="search"
                    placeholder="Cari nama atau kode transportasi..."
                    clearable
                    class="md:w-72"
                >
                    <template #prefix>
                        <Search :size="16" class="text-slate-400" />
                    </template>
                </NInput>
            </div>

            <!-- Empty state -->
            <div v-if="filtered.length === 0" class="p-10">
                <NEmpty description="Belum ada data transportasi yang cocok" />
            </div>

            <template v-else>
                <!-- Mobile: card list (default, mobile-first) -->
                <div class="flex flex-col gap-3 p-4 md:hidden">
                    <div
                        v-for="item in filtered"
                        :key="item.code"
                        class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-3 active:bg-slate-50"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100"
                            >
                                <Truck :size="18" class="text-slate-500" />
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-slate-900"
                                >
                                    {{ item.name }}
                                </p>
                                <p
                                    class="mt-0.5 truncate font-mono text-xs text-slate-500"
                                >
                                    {{ item.code }}
                                </p>
                            </div>
                        </div>

                        <NDropdown
                            trigger="click"
                            :options="actionOptions"
                            @select="(key) => handleAction(key, item)"
                        >
                            <button
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                            >
                                <MoreVertical :size="18" />
                            </button>
                        </NDropdown>
                    </div>
                </div>

                <!-- Desktop / tablet: table -->
                <div class="hidden md:block">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500"
                            >
                                <th class="px-5 py-3 font-medium">
                                    Nama Transportasi
                                </th>
                                <th class="px-5 py-3 font-medium">Kode</th>
                                <th class="px-5 py-3 font-medium text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in filtered"
                                :key="item.code"
                                class="border-b border-slate-50 last:border-0 hover:bg-slate-50/70"
                            >
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100"
                                        >
                                            <Truck
                                                :size="16"
                                                class="text-slate-500"
                                            />
                                        </div>
                                        <span
                                            class="font-medium text-slate-900"
                                            >{{ item.name }}</span
                                        >
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-600"
                                    >
                                        {{ item.code }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <NDropdown
                                        trigger="click"
                                        placement="bottom-end"
                                        :options="actionOptions"
                                        @select="
                                            (key) => handleAction(key, item)
                                        "
                                    >
                                        <button
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                        >
                                            <MoreVertical :size="18" />
                                        </button>
                                    </NDropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- Footer / pagination -->
            <div
                class="flex flex-col gap-3 border-t border-slate-100 p-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between md:p-5"
            >
                <span
                    >Menampilkan {{ filtered.length }} dari
                    {{ transportasi.length }} data</span
                >
                <div class="flex gap-2 self-end md:self-auto">
                    <NButton size="small" disabled>« Previous</NButton>
                    <NButton size="small" disabled>Next »</NButton>
                </div>
            </div>
        </div>
    </div>
</template>
