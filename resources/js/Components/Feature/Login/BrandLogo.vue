<script setup>
defineProps({
    // true = teks putih (untuk panel gelap)
    light: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex items-center gap-2.5">
        <img src="/images/logo.png" alt="Sales Application Logo" class="w-8 h-8" />
        <div>
            <p class="text-sm font-semibold leading-tight tracking-tight"
                :class="light ? 'text-white' : 'text-slate-900'">
                Sales Application
            </p>
            <p class="text-[11px] mt-0.5 leading-none" :class="light ? 'text-white/60' : 'text-slate-400'">
                Enterprise Sales Management
            </p>
        </div>
    </div>
</template>
