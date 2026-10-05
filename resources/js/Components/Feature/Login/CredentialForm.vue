<script setup>
import { ref, watch } from "vue";
import { NAlert, NButton, NForm, NFormItem, NInput } from "naive-ui";
import BrandLogo from "./BrandLogo.vue";

const props = defineProps({
    form: { type: Object, required: true },
    // Set false kalau belum butuh login via akun sosial
    showSocial: { type: Boolean, default: true },
});

const emit = defineEmits(["submit", "social"]);

const activeTab = ref("signin");
const formRef = ref(null);

const rules = {
    username: [
        {
            required: true,
            whitespace: true, // spasi saja dianggap kosong
            message: "Username wajib diisi",
            trigger: ["input", "blur"],
        },
    ],
    password: [
        {
            required: true,
            message: "Password wajib diisi",
            trigger: ["input", "blur"],
        },
    ],
};

function handleSubmit() {
    formRef.value?.validate((errors) => {
        // Hanya lanjut ke server kalau semua rule lolos
        if (!errors) emit("submit");
    });
}

// Error dari server (Inertia) hilang begitu user mengetik ulang
watch(
    () => props.form.username,
    () => props.form.clearErrors("username"),
);
watch(
    () => props.form.password,
    () => props.form.clearErrors("password"),
);

const socials = [
    { name: "google", label: "Google" },
    { name: "microsoft", label: "Microsoft" },
];
</script>

<template>
    <section
        class="flex-1 min-w-0 lg:flex-none lg:w-1/2 flex flex-col rounded-2xl border border-slate-200 px-4 pt-4 pb-3 sm:px-8 lg:px-7 lg:overflow-y-auto">
        <BrandLogo />

        <!-- Konten utama, dipusatkan secara vertikal -->
        <div class="flex-1 flex flex-col justify-center w-full max-w-sm mx-auto py-4">
            <header class="text-center mb-4">
                <h1 class="text-lg sm:text-xl font-semibold text-slate-900 leading-tight">
                    Selamat datang kembali
                </h1>
                <p class="text-[13px] text-slate-500 mt-1">
                    Masuk untuk melanjutkan ke Sales Application.
                </p>
            </header>

            <!-- Sign In -->
            <NForm ref="formRef" :model="form" :rules="rules" size="large" label-placement="top"
                require-mark-placement="right-hanging" @submit.prevent="handleSubmit">
                <!-- Username -->
                <NFormItem label="Username" path="username"
                    :validation-status="form.errors.username ? 'error' : undefined"
                    :feedback="form.errors.username || undefined">
                    <NInput v-model:value="form.username" placeholder="Masukkan username"
                        :input-props="{ autocomplete: 'username' }">
                        <template #prefix>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </template>
                    </NInput>
                </NFormItem>

                <!-- Password -->
                <NFormItem label="Password" path="password"
                    :validation-status="form.errors.password ? 'error' : undefined"
                    :feedback="form.errors.password || undefined">
                    <NInput v-model:value="form.password" type="password" show-password-on="click"
                        placeholder="Masukkan password" :input-props="{ autocomplete: 'current-password' }">
                        <template #prefix>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0110 0v4" />
                            </svg>
                        </template>
                    </NInput>
                </NFormItem>

                <NButton size="large" type="primary" attr-type="submit" block :disabled="form.processing"
                    :loading="form.processing">
                    {{ form.processing ? "Memverifikasi…" : "Sign In" }}
                </NButton>
            </NForm>
        </div>

        <!-- Footer -->
        <p class="text-[11px] text-slate-400 text-center leading-relaxed">
            © {{ new Date().getFullYear() }} Sales Application. Hubungi
            administrator jika tidak bisa masuk.
        </p>
    </section>
</template>

<style scoped>
.tab-btn:focus-visible {
    outline: 2px solid #18a058;
    outline-offset: 1px;
}

/* Rapatkan ruang feedback bawaan NFormItem (default 24px) supaya tetap compact */
:deep(.n-form-item .n-form-item-feedback-wrapper) {
    min-height: 18px;
}

:deep(.n-form-item .n-form-item-label) {
    font-size: 13px;
    font-weight: 500;
}
</style>
