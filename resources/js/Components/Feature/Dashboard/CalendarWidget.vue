<script setup lang="ts">
import { ref, computed } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

const emit = defineEmits<{ (e: "select", date: Date): void }>();

const today = new Date();
const cursor = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const selected = ref<Date>(new Date(today));

const WEEKDAYS = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];

const monthLabel = computed(() =>
    new Intl.DateTimeFormat("en-US", { month: "long", year: "numeric" }).format(
        cursor.value,
    ),
);

const cells = computed(() => {
    const y = cursor.value.getFullYear(),
        m = cursor.value.getMonth();
    const firstDay = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const arr: (Date | null)[] = [];
    for (let i = 0; i < firstDay; i++) arr.push(null);
    for (let d = 1; d <= daysInMonth; d++) arr.push(new Date(y, m, d));
    return arr;
});

function isSameDay(a: Date | null, b: Date) {
    return !!a && a.toDateString() === b.toDateString();
}
function shift(delta: number) {
    cursor.value = new Date(
        cursor.value.getFullYear(),
        cursor.value.getMonth() + delta,
        1,
    );
}
function pick(d: Date | null) {
    if (!d) return;
    selected.value = d;
    emit("select", d);
}

const selectedLabel = computed(() =>
    new Intl.DateTimeFormat("en-US", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(selected.value),
);
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="px-5 pb-5 pt-5">
            <div class="mb-4 rounded-xl bg-[#0284c7] px-4 py-3 text-white">
                <p class="text-xs text-sky-100">Selected date</p>
                <p class="text-sm font-semibold">{{ selectedLabel }}</p>
            </div>

            <div class="mb-3 flex items-center justify-between">
                <button
                    class="rounded-lg p-1.5 text-slate-500 hover:bg-sky-50"
                    @click="shift(-1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <span class="text-sm font-semibold text-slate-800">{{
                    monthLabel
                }}</span>
                <button
                    class="rounded-lg p-1.5 text-slate-500 hover:bg-sky-50"
                    @click="shift(1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-xs">
                <span
                    v-for="w in WEEKDAYS"
                    :key="w"
                    class="py-1 font-medium text-slate-400"
                    >{{ w }}</span
                >
                <button
                    v-for="(c, i) in cells"
                    :key="i"
                    :disabled="!c"
                    class="flex aspect-square items-center justify-center rounded-lg transition-colors"
                    :class="[
                        !c && 'invisible',
                        isSameDay(c, selected) &&
                            'bg-[#0284c7] font-semibold text-white',
                        !isSameDay(c, selected) &&
                            isSameDay(c, today) &&
                            'bg-sky-50 font-semibold text-[#0284c7]',
                        !isSameDay(c, selected) &&
                            !isSameDay(c, today) &&
                            'text-slate-600 hover:bg-sky-50',
                    ]"
                    @click="pick(c)"
                >
                    {{ c?.getDate() }}
                </button>
            </div>
        </div>
    </div>
</template>
