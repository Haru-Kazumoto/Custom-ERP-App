<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ActionSection from '@/Components/ActionSection.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});

// Hierarki sub-role dibaca dari `sub_roles.parent_id` lewat auth user —
// dinamis, jadi perubahan struktur organisasi tidak perlu ubah kode di sini.
const user = usePage().props.auth.user;
const hierarchy = computed(() => user?.sub_role_hierarchy ?? []);
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Profile
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <UpdateProfileInformationForm :user="$page.props.auth.user" />

                    <SectionBorder />
                </div>

                <!-- Hierarki organisasi sub-role: urutan dari sub-role user
                     ke puncak (mis. Salesman → Sales Supervisor → Sales
                     Manager). Tampil hanya kalau user punya sub-role. -->
                <ActionSection v-if="hierarchy.length" class="mt-10 sm:mt-0">
                    <template #title>Hierarki Sub-Role</template>
                    <template #description>
                        Posisi Anda dalam struktur organisasi
                        {{ user?.role ? `role ${user.role}` : '' }} — menentukan
                        siapa yang menyetujui dokumen yang Anda buat.
                    </template>

                    <template #content>
                        <ol class="flex flex-col gap-2">
                            <li
                                v-for="(item, index) in hierarchy"
                                :key="item.id"
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                                    :class="index === 0
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-600'"
                                >
                                    {{ index + 1 }}
                                </span>
                                <span
                                    class="text-sm"
                                    :class="index === 0
                                        ? 'font-semibold text-slate-800'
                                        : 'text-slate-600'"
                                >
                                    {{ item.name }}
                                </span>
                                <span
                                    v-if="index === 0"
                                    class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600"
                                >
                                    Anda
                                </span>
                            </li>
                        </ol>
                    </template>
                </ActionSection>

                <SectionBorder v-if="hierarchy.length" />

                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <UpdatePasswordForm class="mt-10 sm:mt-0" />

                    <SectionBorder />
                </div>

                <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                    <TwoFactorAuthenticationForm
                        :requires-confirmation="confirmsTwoFactorAuthentication"
                        class="mt-10 sm:mt-0"
                    />

                    <SectionBorder />
                </div>

                <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" />

                <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
                    <SectionBorder />

                    <DeleteUserForm class="mt-10 sm:mt-0" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
