<script setup>
import { NButton, NIcon } from "naive-ui";
import { ChevronsLeft } from "lucide-vue-next";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    label: {
        type: String,
        default: "Kembali",
    },
    to: {
        type: String,
        default: null,
    },
    // opsi tambahan kalau mau pakai preserveState/preserveScroll dsb
    options: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["click"]);

function handleBack() {
    emit("click");
    if (props.to) {
        router.visit(props.to, props.options);
    } else {
        window.history.back();
    }
}
</script>

<template>
    <n-button
        quaternary
        @click="handleBack"
        class="w-fit"
    >
        <template #icon>
            <n-icon>
                <ChevronsLeft class="h-5 w-5" />
            </n-icon>
        </template>
        <span class="hidden md:inline ml-1 text-sm font-medium">
            {{ label }}
        </span>
    </n-button>
</template>
