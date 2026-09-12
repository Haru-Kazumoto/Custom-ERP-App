<template>
    <div
        class="w-full md:w-[46%] flex-shrink-0 flex flex-col justify-center px-8 py-10 border-r border-slate-100"
    >
        <!-- Brand -->
        <BrandLogo />

        <!-- Heading -->
        <div class="mb-6">
            <h1 class="text-lg font-semibold text-slate-900 leading-tight">
                Sign in to your account
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Enter your credentials to continue.
            </p>
        </div>

        <!-- ------------------------------------------------
        Form
        ------------------------------------------------ -->
        <form @submit.prevent="emit('submit')" class="space-y-4">
            <!-- Username -->
            <div class="space-y-1.5">
                <label
                    for="username"
                    class="text-xs font-medium text-slate-600 uppercase tracking-wide block"
                >
                    Username
                </label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 z-10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </span>
                    <NInput
                        id="username"
                        v-model:value="form.username"
                        type="text"
                        input-props="{ autocomplete: 'username' }"
                        placeholder="Enter username"
                        class="pl-9 text-sm"
                        :status="form.errors.username ? 'error' : undefined"
                        autofocus
                    />
                </div>
                <p
                    v-if="form.errors.username"
                    class="text-xs text-destructive mt-1"
                >
                    {{ form.errors.username }}
                </p>
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <label
                    for="password"
                    class="text-xs font-medium text-slate-600 uppercase tracking-wide block"
                >
                    Password
                </label>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 z-10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect
                                x="3"
                                y="11"
                                width="18"
                                height="11"
                                rx="2"
                                ry="2"
                            />
                            <path d="M7 11V7a5 5 0 0110 0v4" />
                        </svg>
                    </span>
                    <NInput
                        id="password"
                        v-model:value="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        :input-props="{ autocomplete: 'current-password' }"
                        placeholder="Enter password"
                        class="pl-9 pr-10 text-sm"
                        :status="form.errors.password ? 'error' : undefined"
                    />
                    <!-- Toggle -->
                    <button
                        type="button"
                        @click="toggleShowPassword"
                        class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors z-10"
                        :aria-label="
                            showPassword ? 'Hide password' : 'Show password'
                        "
                    >
                        <!-- Eye open -->
                        <svg
                            v-if="!showPassword"
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                        <!-- Eye off -->
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                            />
                        </svg>
                    </button>
                </div>
                <p
                    v-if="form.errors.password"
                    class="text-xs text-destructive mt-1"
                >
                    {{ form.errors.password }}
                </p>
            </div>

            <!-- Submit -->
            <NButton
                attr-type="submit"
                class="w-full mt-1 bg-[#0284c7] hover:bg-[#0369a1] active:bg-[#075985] text-white border-0 shadow-none"
                :disabled="form.processing"
                :loading="form.processing"
            >
                {{ form.processing ? "Verifying…" : "Sign in" }}
            </NButton>
        </form>

        <!-- Footer note -->
        <p class="mt-6 text-[11px] text-slate-400 text-center leading-relaxed">
            Contact your administrator if you can't sign in.
        </p>
    </div>
</template>

<script setup>
import { NButton, NInput } from "naive-ui";
import BrandLogo from "./BrandLogo.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    showPassword: {
        type: Boolean,
        required: true,
    },
    onSubmit: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits([
    "update:showPassword", // dynamic state
    "submit", // form submission
]);

function toggleShowPassword() {
    emit("update:showPassword", !props.showPassword);
}
</script>

<style lang="scss" scoped></style>
