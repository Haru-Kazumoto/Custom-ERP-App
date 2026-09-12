<script setup lang="ts">
import { ref, computed } from "vue";
import { Line } from "vue-chartjs";
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    type ChartData,
    type ChartOptions,
} from "chart.js";

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
);

type Period = "7d" | "30d" | "12m";
const period = ref<Period>("30d");

const datasets: Record<Period, { labels: string[]; values: number[] }> = {
    "7d": {
        labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        values: [32, 41, 38, 55, 48, 61, 70],
    },
    "30d": { labels: ["W1", "W2", "W3", "W4"], values: [120, 160, 140, 210] },
    "12m": {
        labels: [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec",
        ],
        values: [40, 55, 48, 70, 65, 90, 85, 100, 95, 120, 110, 140],
    },
};

const periods: { key: Period; label: string }[] = [
    { key: "7d", label: "7 Days" },
    { key: "30d", label: "30 Days" },
    { key: "12m", label: "12 Months" },
];

const BRAND = "#0284c7";

const chartData = computed<ChartData<"line">>(() => {
    const d = datasets[period.value];
    return {
        labels: d.labels,
        datasets: [
            {
                label: "Revenue",
                data: d.values,
                borderColor: BRAND,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: "#fff",
                pointBorderColor: BRAND,
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                // gradient fill di bawah garis
                backgroundColor: (ctx) => {
                    const { chart } = ctx;
                    const { ctx: c, chartArea } = chart;
                    if (!chartArea) return "rgba(2,132,199,0.15)";
                    const g = c.createLinearGradient(
                        0,
                        chartArea.top,
                        0,
                        chartArea.bottom,
                    );
                    g.addColorStop(0, "rgba(2,132,199,0.25)");
                    g.addColorStop(1, "rgba(2,132,199,0)");
                    return g;
                },
            },
        ],
    };
});

const chartOptions: ChartOptions<"line"> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: "#0f172a",
            padding: 10,
            cornerRadius: 8,
            displayColors: false,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: { color: "#94a3b8", font: { size: 11 } },
        },
        y: {
            grid: { color: "#e2e8f0" },
            border: { display: false },
            ticks: { color: "#94a3b8", font: { size: 11 } },
        },
    },
};
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between px-5 pt-5">
            <h3 class="text-sm font-semibold text-slate-800">
                Revenue Overview
            </h3>
            <div class="flex gap-1 rounded-lg bg-slate-100 p-1">
                <button
                    v-for="p in periods"
                    :key="p.key"
                    class="rounded-md px-2.5 py-1 text-xs font-medium transition-colors"
                    :class="
                        period === p.key
                            ? 'bg-white text-[#0284c7] shadow-sm'
                            : 'text-slate-500'
                    "
                    @click="period = p.key"
                >
                    {{ p.label }}
                </button>
            </div>
        </div>

        <div class="px-5 pb-5 pt-3">
            <div class="h-56">
                <Line :data="chartData" :options="chartOptions" />
            </div>
        </div>
    </div>
</template>
