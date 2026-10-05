<script setup lang="ts">
import { ref, computed } from "vue";
import { router, usePage, Head } from "@inertiajs/vue3";
import { buildMenuTree } from "@/lib/buildMenuTree";
import { resolveActiveMenuKey } from "@/lib/resolveActiveMenuKey";
import AppSidebar from "@/Components/Common/Sidebar/AppSidebar.vue";
import AppHeader from "@/Components/Common/AppHeader.vue";
import {
    NModal,
    NButton,
    NConfigProvider,
    NDialogProvider,
    NMessageProvider,
    NNotificationProvider,
    NLoadingBarProvider,
} from "naive-ui";
import { MenuItem } from "@/types/menu";
import { Account } from "@/types/account";
import { naiveThemeOverrides } from "@/lib/naiveTheme";

const props = defineProps({
    pageName: {
        type: String,
        default: "Page",
        nullable: true
    }
});

const sidebarOpen = ref(false);
const menus = computed(() =>
    buildMenuTree(usePage().props.menus as MenuItem[]),
);

const page = usePage();
const logoutDialogOpen = ref(false);

// Menu aktif ditentukan dari nama route (shared prop `currentRoute`) supaya
// halaman turunan seperti create/show tetap menyalakan menu induknya. Kalau
// halamannya memang tidak punya menu, `null` — jangan fallback ke menu lain.
const activeKey = computed(() =>
    resolveActiveMenuKey(
        menus.value,
        page.url,
        (page.props.currentRoute as string | null) ?? null,
    ),
);

function logout() {
    router.post(
        route("logout"),
        {},
        {
            onSuccess: () => { },
        },
    );
}

// Global SaaS theme — blue sky (#0284c7) + radius 10/12px
// Lihat `resources/js/lib/naiveTheme.js` untuk detail token.
// Sebelumnya ada override Select.InternalSelection.boxShadowFocus custom (inset hack),
// sekarang diganti glow blue `0 0 0 2px rgba(14,165,233,0.2)` via naiveThemeOverrides.common.primaryColor.
const themeOverrides = naiveThemeOverrides;
</script>

<template>

    <Head :title="pageName"></Head>

    <!-- Kunci tinggi layar, matikan scroll global -->
    <div class="flex h-screen overflow-hidden bg-slate-50 text-slate-800">
        <AppSidebar :menus="menus" :active-key="activeKey" :open="sidebarOpen" @close="sidebarOpen = false"
            @logout="logoutDialogOpen = true" :user="(page.props.auth as any).user as Account" />

        <NModal v-model:show="logoutDialogOpen" preset="card" title="Keluar Aplikasi?" class="sm:max-w-[425px]"
            :bordered="false">
            <p class="text-sm text-slate-500">
                Sesi akan dihapus. Jika ingin masuk kembali, Anda harus
                login lagi.
            </p>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <NButton @click="logoutDialogOpen = false">
                        Batal
                    </NButton>

                    <NButton type="error" @click="logout"> Keluar </NButton>
                </div>
            </template>
        </NModal>

        <!-- Kolom kanan: header sticky + hanya main yang scroll -->
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <AppHeader class="shrink-0" @toggle-sidebar="sidebarOpen = !sidebarOpen" />

            <!-- Provider stack agar semua hooks (useDialog, useMessage, useNotification) mewarisi theme blue sky -->
            <NLoadingBarProvider>
                <NDialogProvider>
                    <NMessageProvider>
                        <NNotificationProvider>
                            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                                <slot />
                            </main>
                        </NNotificationProvider>
                    </NMessageProvider>
                </NDialogProvider>
            </NLoadingBarProvider>
        </div>
    </div>
</template>
