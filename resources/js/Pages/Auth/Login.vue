<template>
    <Head title="Sign In — Sales Application" />

    <!--
    ====================================================================
    ROOT
    ====================================================================
    -->
    <div
        class="bg-pattern min-h-screen flex flex-col antialiased font-sans text-slate-800"
    >
        <!-- Accent orbs -->
        <div
            aria-hidden="true"
            class="pointer-events-none fixed top-0 left-0 w-80 h-80 rounded-full bg-primary/5 -translate-x-1/2 -translate-y-1/2"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none fixed bottom-0 right-0 w-[26rem] h-[26rem] rounded-full bg-primary/5 translate-x-1/3 translate-y-1/3"
        />

        <!-- ============================================================
        CENTER CONTENT
        ============================================================ -->
        <div class="flex-1 flex items-center justify-center px-4 py-12">
            <!--
            OUTER CARD
            Max-width wide enough for the two-column layout.
            -->
            <div
                class="card-enter w-full max-w-3xl bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex"
            >
                <!-- ====================================================
                LEFT — Form panel
                ==================================================== -->
                <CredentialForm
                    :form="form"
                    :show-password="showPassword"
                    @submit="submit"
                    @update:show-password="showPassword = $event"
                />

                <!-- ====================================================
                RIGHT — Illustration / promo panel
                ==================================================== -->
                <LoginIllustration />
            </div>
        </div>

        <!-- Footer -->
        <footer class="py-5 text-center">
            <p class="text-xs text-slate-300">
                © {{ new Date().getFullYear() }} Sales Application · Enterprise
                Sales Management
            </p>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import LoginIllustration from "@/Components/Feature/Login/LoginIllustration.vue";
import CredentialForm from "@/Components/Feature/Login/CredentialForm.vue";
import {toast} from "vue-sonner";

// -----------------------------------------------------------------------
// State
// -----------------------------------------------------------------------
const showPassword = ref(false);

const form = useForm({
    username: "",
    password: "",
    remember: false,
});

// -----------------------------------------------------------------------
// Submit
// -----------------------------------------------------------------------
function submit() {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? "on" : "",
    })).post(route("login"), {
        onSuccess: () => toast('Login success, Welcome back!', {
            description: "Let's get work today"
        }),
        onError: () => toast('Login failed!', {
            description: "Check the log of error"
        }),
        onFinish: () => form.reset("password"),
    });
};
</script>

<style scoped>
/* -----------------------------------------------------------------------
   Background grid pattern
----------------------------------------------------------------------- */
.bg-pattern {
    background-color: #f8fafc;
    background-image:
        linear-gradient(rgba(14, 165, 233, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(14, 165, 233, 0.04) 1px, transparent 1px);
    background-size: 32px 32px;
}

/* -----------------------------------------------------------------------
   Card entrance animation
----------------------------------------------------------------------- */
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
</style>
