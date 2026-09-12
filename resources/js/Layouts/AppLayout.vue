<script setup lang="ts">
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { buildMenuTree } from "@/lib/buildMenuTree";
import { dummyMenus } from "@/data/dummyMenu";
import AppSidebar from "@/Components/Common/Sidebar/AppSidebar.vue";
import AppHeader from "@/Components/Common/AppHeader.vue";
import { NModal, NButton, NConfigProvider } from "naive-ui";
import { MenuItem } from "@/types/menu";
import { NNotificationProvider } from "naive-ui";
import { Account } from "@/types/account";

const sidebarOpen = ref(false);
const menus = computed(() =>
    buildMenuTree(usePage().props.menus as MenuItem[]),
);

const page = usePage();
const logoutDialogOpen = ref(false);
const activeKey = computed(() => {
    const path = page.url.split("?")[0];
    const match = menus.value
        .flatMap((m) => [m, ...(m.children ?? [])])
        .find((m) => m.url === path);
    return match?.key ?? "dashboard";
});

function logout() {
    router.post(
        route("logout"),
        {},
        {
            onSuccess: () => {},
        },
    );
}

const themeOverrides = {
    Select: {
        peers: {
            InternalSelection: {
                boxShadowFocus: "inset 0 0 0 1px var(--n-border-color)",
            },
        },
    },
};
</script>

<style>
.n-base-selection-input:focus,
.n-base-selection-input:focus-visible {
    outline: none;
    box-shadow: none;
}
</style>

<template>
    <NConfigProvider :theme-overrides="themeOverrides">
        <!-- Kunci tinggi layar, matikan scroll global -->
        <div class="flex h-screen overflow-hidden bg-slate-50 text-slate-800">
            <AppSidebar
                :menus="menus"
                :active-key="activeKey"
                :open="sidebarOpen"
                @close="sidebarOpen = false"
                @logout="logoutDialogOpen = true"
                :user="(page.props.auth as any).user as Account"
            />

            <NModal
                v-model:show="logoutDialogOpen"
                preset="card"
                title="Keluar Aplikasi?"
                class="sm:max-w-[425px]"
                :bordered="false"
            >
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
                <AppHeader
                    class="shrink-0"
                    @toggle-sidebar="sidebarOpen = !sidebarOpen"
                />

                <n-notification-provider>
                    <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                        <slot />
                    </main>
                </n-notification-provider>
            </div>
        </div>
    </NConfigProvider>
</template>
