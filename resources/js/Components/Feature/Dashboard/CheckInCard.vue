<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { Clock, LogOut } from "lucide-vue-next";

const time = ref("00:00:00");
let timer: number;
function tick() {
    time.value = new Intl.DateTimeFormat("en-GB", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false,
    }).format(new Date());
}
onMounted(() => {
    tick();
    timer = window.setInterval(tick, 1000);
});
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-[#0284c7] text-white shadow-sm"
    >
        <div class="relative p-5">
            <Clock class="absolute right-4 top-4 h-20 w-20 text-white/10" />
            <p class="text-3xl font-bold tracking-tight">{{ time }}</p>
            <p class="mt-1 text-sm text-sky-100">32 min left before checkout</p>
            <button
                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white/15 px-3 py-2 text-sm font-medium backdrop-blur hover:bg-white/25"
            >
                <LogOut class="h-4 w-4" /> Checkout early
            </button>
            <div
                class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-white/20"
            >
                <div class="h-full w-3/4 rounded-full bg-white" />
            </div>
        </div>
    </div>
</template>
