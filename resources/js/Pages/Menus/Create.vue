<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    NAlert,
    NButton,
    NCheckbox,
    NDynamicInput,
    NForm,
    NFormItem,
    NInput,
    NSelect,
    NSpin,
    useMessage,
} from "naive-ui";
import * as icons from "lucide-vue-next";
import { ArrowLeft, Save } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";

const props = defineProps<{
    parents: { label: string; value: number }[];
}>();

const message = useMessage();
const submitting = ref(false);

const form = reactive({
    name: "",
    key: "",
    icon: "",
    url: "",
    route_name: "",
    description: "",
    is_active: true,
    parent_id: null as number | null,
    active_routes: [] as string[],
});

/**
 * Semua nama export lucide jadi opsi select.
 *
 * `import * as icons` sudah dipakai NavItem/NavGroup/StatCard, jadi daftar ini
 * tidak menambah ukuran bundle. `virtual-scroll` wajib karena jumlahnya ribuan.
 */
const iconOptions = computed(() =>
    Object.keys(icons)
        .filter((name) => /^[A-Z]/.test(name))
        .sort()
        .map((name) => ({ label: name, value: name })),
);

// Samakan dengan NavItem.vue — icon yang dipilih bisa belum dikenal DB.
const previewIcon = computed(
    () => (icons as Record<string, unknown>)[form.icon] ?? icons.Circle,
);

const parentOptions = computed(() => [
    { label: "Level atas (tanpa parent)", value: null },
    ...props.parents,
]);

function submit() {
    submitting.value = true;

    router.post(
        route("menus.store"),
        {
            name: form.name,
            key: form.key,
            icon: form.icon,
            url: form.url,
            route_name: form.route_name || null,
            description: form.description || null,
            is_active: form.is_active ? 1 : 0,
            parent_id: form.parent_id,
            active_routes: form.active_routes,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                message.success(`Menu "${form.name}" berhasil dibuat`),
            onError: (errors) => {
                const first = Object.values(errors)[0] as string | undefined;
                message.error(first ?? "Gagal membuat menu");
            },
            onFinish: () => (submitting.value = false),
        },
    );
}
</script>

<template>
    <Head title="Buat Menu" />

    <AppLayout pageName="Buat Menu">
        <div class="space-y-4 lg:space-y-6">
            <HeaderPage
                title="Buat Menu"
                sub-title="Tambahkan menu baru ke tabel menus, lengkap dengan parent dan route-nya"
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
                <NForm label-placement="top">
                    <div class="grid gap-4 lg:grid-cols-3 lg:gap-6">
                        <div class="space-y-4 lg:col-span-2 lg:space-y-6">
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <h2
                                    class="mb-4 text-sm font-semibold text-slate-900"
                                >
                                    Informasi Menu
                                </h2>

                                <NFormItem
                                    label="Nama"
                                    required
                                    :validation-status="
                                        form.name.length > 255 ? 'error' : undefined
                                    "
                                >
                                    <NInput
                                        v-model:value="form.name"
                                        placeholder="Contoh: Daftar Dokumen"
                                        maxlength="255"
                                        show-count
                                    />
                                </NFormItem>

                                <NFormItem
                                    label="Key"
                                    required
                                    :feedback="
                                        'Hanya huruf kecil, angka, underscore, atau strip'
                                    "
                                >
                                    <NInput
                                        v-model:value="form.key"
                                        placeholder="contoh: list_po_documents"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem
                                    label="URL"
                                    required
                                    :feedback="
                                        'Dipakai sidebar untuk navigasi'
                                    "
                                >
                                    <NInput
                                        v-model:value="form.url"
                                        placeholder="Contoh: /purchase-orders/documents"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem label="Deskripsi">
                                    <NInput
                                        v-model:value="form.description"
                                        type="textarea"
                                        placeholder="Keterangan singkat, opsional"
                                        :autosize="{ minRows: 2, maxRows: 4 }"
                                        maxlength="1000"
                                        show-count
                                    />
                                </NFormItem>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <h2
                                    class="mb-1 text-sm font-semibold text-slate-900"
                                >
                                    Route
                                </h2>
                                <p class="mb-4 text-xs text-slate-400">
                                    Sidebar menyalakan menu berdasarkan
                                    <span class="font-mono">route_name</span>, bukan
                                    kesamaan URL — jadi halaman turunan perlu
                                    dicatat di
                                    <span class="font-mono">active_routes</span>.
                                </p>

                                <NFormItem label="Route Utama">
                                    <NInput
                                        v-model:value="form.route_name"
                                        placeholder="Contoh: purchase-order.index"
                                        maxlength="255"
                                    />
                                </NFormItem>

                                <NFormItem label="Route Turunan">
                                    <NDynamicInput
                                        v-model:value="form.active_routes"
                                        placeholder="Contoh: purchase-order.create"
                                    />
                                </NFormItem>
                            </div>
                        </div>

                        <div class="space-y-4 lg:space-y-6">
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <h2
                                    class="mb-4 text-sm font-semibold text-slate-900"
                                >
                                    Icon
                                </h2>

                                <div
                                    class="mb-4 flex items-center gap-3 rounded-xl bg-slate-50 p-3"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm"
                                    >
                                        <component
                                            :is="previewIcon"
                                            class="h-5 w-5 text-slate-600"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-medium text-slate-800"
                                        >
                                            {{
                                                form.icon || "Belum dipilih"
                                            }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            Preview di sidebar
                                        </p>
                                    </div>
                                </div>

                                <NFormItem label="Nama Icon" required>
                                    <NSelect
                                        v-model:value="form.icon"
                                        filterable
                                        virtual-scroll
                                        :options="iconOptions"
                                        placeholder="Cari nama icon lucide..."
                                    />
                                </NFormItem>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <h2
                                    class="mb-4 text-sm font-semibold text-slate-900"
                                >
                                    Penempatan
                                </h2>

                                <NFormItem label="Parent">
                                    <NSelect
                                        v-model:value="form.parent_id"
                                        :options="parentOptions"
                                        placeholder="Level atas"
                                    />
                                </NFormItem>

                                <NFormItem>
                                    <NCheckbox v-model:checked="form.is_active">
                                        Aktifkan menu ini
                                    </NCheckbox>
                                </NFormItem>

                                <NAlert
                                    v-if="!form.is_active"
                                    type="warning"
                                    :show-icon="true"
                                    class="mt-4"
                                >
                                    Menu nonaktif tidak muncul di sidebar.
                                </NAlert>
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <NButton
                                    type="primary"
                                    strong
                                    block
                                    :loading="submitting"
                                    :disabled="!form.name || !form.key || !form.icon || !form.url"
                                    @click="submit"
                                >
                                    <template #icon><Save :size="16" /></template>
                                    Simpan Menu
                                </NButton>
                            </div>
                        </div>
                    </div>
                </NForm>
            </NSpin>
        </div>
    </AppLayout>
</template>