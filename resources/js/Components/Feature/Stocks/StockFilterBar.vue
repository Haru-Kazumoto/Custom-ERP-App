<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { NButton, NIcon, NInput, NSelect } from "naive-ui";
import { Search } from "lucide-vue-next";
import type { CompanyRow, StockFilters } from "@/types/stock";

const props = defineProps<{
    companies: CompanyRow[];
    filters: StockFilters;
    routeName: string;
}>();

const search = ref(props.filters.search ?? "");
const companyId = ref<number | null>(props.filters.company_id ?? null);

// Sinkronkan dari query string setiap kali Inertia reload halaman.
watch(
    () => props.filters,
    (value) => {
        search.value = value.search ?? "";
        companyId.value = value.company_id ?? null;
    },
);

const companyOptions = [
    { label: "Semua Company", value: 0 as number | null },
    ...props.companies.map((company) => ({
        label: company.name,
        value: company.id,
    })),
];

function apply(page?: number) {
    router.get(
        route(props.routeName),
        {
            search: search.value.trim() || undefined,
            company_id: companyId.value || undefined,
            page: page && page > 1 ? page : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// Company adalah pilihan instan seperti filter status di halaman lain.
// Dijaga supaya reload dari query string tidak memicu request berantai.
watch(companyId, (value) => {
    if ((value ?? null) !== (props.filters.company_id ?? null)) {
        apply();
    }
});

function clearSearch() {
    search.value = "";
    apply();
}

defineExpose({ apply });
</script>

<template>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <NSelect
            v-model:value="companyId"
            :options="companyOptions"
            filterable
            clearable
            placeholder="Semua Company"
            class="w-full sm:w-48"
        />

        <div class="flex flex-1 items-center gap-2">
            <NInput
                v-model:value="search"
                placeholder="Cari kode / nama barang…"
                class="w-full sm:w-64"
                clearable
                @keyup.enter="apply()"
                @clear="clearSearch"
            >
                <template #prefix>
                    <NIcon :component="Search" />
                </template>
            </NInput>

            <NButton @click="apply()">Cari</NButton>

            <NButton v-if="filters.search" quaternary @click="clearSearch">
                Reset
            </NButton>
        </div>
    </div>
</template>
