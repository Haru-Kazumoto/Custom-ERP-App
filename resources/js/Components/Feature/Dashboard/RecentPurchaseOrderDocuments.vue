<script setup lang="ts">
import DataTable from "@/Components/Common/DataTable.vue";
import { columns } from "@/lib/procurementColumnsTable";

interface RecentDocument {
    id: number;
    date: string; // ISO date dari DB
    supplier: string;
    shipping: "land" | "air" | "sea";
    total: number; // Rupiah
}

// DUMMY — nanti ganti dgn usePage().props.recentDocuments
const documents: RecentDocument[] = [
    {
        id: 1,
        date: "2026-06-18",
        supplier: "PT Maju Bersama",
        shipping: "land",
        total: 12_500_000,
    },
    {
        id: 2,
        date: "2026-06-17",
        supplier: "CV Sumber Rejeki",
        shipping: "sea",
        total: 48_900_000,
    },
    {
        id: 3,
        date: "2026-06-16",
        supplier: "Global Trade Co.",
        shipping: "air",
        total: 7_350_000,
    },
    {
        id: 4,
        date: "2026-06-15",
        supplier: "PT Anugerah Jaya",
        shipping: "land",
        total: 21_000_000,
    },
    {
        id: 5,
        date: "2026-06-13",
        supplier: "Nusantara Supply",
        shipping: "sea",
        total: 33_750_000,
    },
    {
        id: 6,
        date: "2026-06-12",
        supplier: "PT Berkah Mandiri",
        shipping: "air",
        total: 9_200_000,
    },
    {
        id: 7,
        date: "2026-06-11",
        supplier: "CV Karya Utama",
        shipping: "land",
        total: 15_800_000,
    },
];
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between px-5 pt-5">
            <h3 class="text-sm font-semibold text-slate-800">
                Recent Documents
            </h3>
            <button class="text-xs font-medium text-[#0284c7] hover:underline">
                View all
            </button>
        </div>
        <div class="px-5 pb-5 pt-3">
            <DataTable :columns="columns" :data="documents" :page-size="5" />
        </div>
    </div>
</template>
