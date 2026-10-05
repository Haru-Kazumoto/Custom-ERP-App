<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import CredentialForm from "@/Components/Feature/Login/CredentialForm.vue";
import LoginShowcase from "@/Components/Feature/Login/LoginShowcase.vue";
import {
    NModal,
    NButton,
    NConfigProvider,
    NDialogProvider,
    NMessageProvider,
    NNotificationProvider,
    NLoadingBarProvider,
} from "naive-ui";

const form = useForm({
    username: "",
    password: "",
    remember: false,
});

function submit() {
    form
        .transform((data) => ({
            ...data,
            remember: data.remember ? "on" : "",
        }))
        .post(route("login"), {
            onSuccess: () =>
                toast("Login berhasil, selamat datang kembali!", {
                    description: "Semangat kerja hari ini",
                }),
            onError: () =>
                toast("Login gagal!", {
                    description: "Periksa kembali username dan password kamu",
                }),
            onFinish: () => form.reset("password"),
        });
}

function onSocial(provider: string) {
    toast.info("Belum tersedia", {
        description: `Login dengan ${provider} belum diaktifkan.`,
    });
}
</script>

<template>

    <Head title="Sign In — Sales Application" />

    <!-- Mobile first: 1 kolom. Mulai lg (>=1024px): form + panel showcase -->
    <div
        class="min-h-dvh lg:h-dvh lg:overflow-hidden flex items-center justify-center bg-[#e3ebe7] p-2 sm:p-4 antialiased font-sans text-slate-800">
        <div
            class="card-enter w-full max-w-md lg:max-w-4xl bg-white rounded-3xl p-2 flex gap-2 shadow-sm lg:h-[min(36rem,calc(100dvh-2rem))]">
            <CredentialForm :form="form" @submit="submit" @social="onSocial" />
            <LoginShowcase class="hidden lg:flex" />
        </div>
    </div>

</template>

<style scoped>
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(14px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-enter {
    animation: slideUp 0.35s ease both;
}

@media (prefers-reduced-motion: reduce) {
    .card-enter {
        animation: none;
    }
}
</style>
