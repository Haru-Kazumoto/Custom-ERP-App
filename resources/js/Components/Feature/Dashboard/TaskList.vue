<script setup lang="ts">
import { ref, computed } from "vue";

interface Task {
    id: number;
    title: string;
    done: boolean;
}
const filter = ref<"all" | "pending" | "done">("pending");
const tasks = ref<Task[]>([
    { id: 1, title: "Welcome the new employee in department X", done: false },
    {
        id: 2,
        title: "Design the portfolio for prospect OI company",
        done: false,
    },
    { id: 3, title: "Fix the e-mail issue", done: true },
]);

const visible = computed(() => {
    if (filter.value === "pending") return tasks.value.filter((t) => !t.done);
    if (filter.value === "done") return tasks.value.filter((t) => t.done);
    return tasks.value;
});
const tabs = ["all", "pending", "done"] as const;
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between px-5 pt-5">
            <h3 class="text-sm font-semibold text-slate-800">My Tasks</h3>
            <div class="flex gap-1 rounded-lg bg-slate-100 p-1">
                <button
                    v-for="t in tabs"
                    :key="t"
                    class="rounded-md px-2.5 py-1 text-xs font-medium capitalize transition-colors"
                    :class="
                        filter === t
                            ? 'bg-white text-[#0284c7] shadow-sm'
                            : 'text-slate-500'
                    "
                    @click="filter = t"
                >
                    {{ t }}
                </button>
            </div>
        </div>

        <div class="space-y-1 px-5 pb-5 pt-3">
            <label
                v-for="task in visible"
                :key="task.id"
                class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2.5 hover:bg-slate-50"
            >
                <input
                    type="checkbox"
                    v-model="task.done"
                    class="h-4 w-4 rounded border-slate-300 text-[#0284c7] focus:ring-sky-400"
                />
                <span
                    class="text-sm"
                    :class="
                        task.done
                            ? 'text-slate-400 line-through'
                            : 'text-slate-700'
                    "
                >
                    {{ task.title }}
                </span>
            </label>
            <button
                class="mt-2 text-sm font-medium text-[#0284c7] hover:underline"
            >
                + New task
            </button>
        </div>
    </div>
</template>
