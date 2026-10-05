<script setup lang="ts">
/**
 * Kerangka kartu untuk setiap section dashboard finance.
 *
 * Kelima section memakai header, badge, dan jarak yang sama; memisahkannya ke
 * sini supaya `Finance.vue` hanya mengatur tata letak dan bukan mengulang
 * markup kartu lima kali.
 */
withDefaults(
  defineProps<{
    title: string;
    subtitle?: string;
    badge?: string;
  }>(),
  { subtitle: "", badge: "" },
);
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-start justify-between gap-3 px-5 pt-5">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">
                    {{ title }}
                </h3>
                <p v-if="subtitle" class="mt-0.5 text-xs text-slate-400">
                    {{ subtitle }}
                </p>
            </div>
            <span
                v-if="badge"
                class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-500"
            >
                {{ badge }}
            </span>
        </div>
        <div class="px-5 pb-5 pt-3">
            <slot />
        </div>
    </div>
</template>