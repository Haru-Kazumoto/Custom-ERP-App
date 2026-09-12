<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                {{ title }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ subTitle }}
            </p>
        </div>
        <slot name="action" />
        
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    subTitle: string;
}>();
</script>
