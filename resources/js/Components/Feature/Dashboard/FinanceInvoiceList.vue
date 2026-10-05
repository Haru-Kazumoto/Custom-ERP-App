<script setup lang="ts">
import DataTable from "@/Components/Common/DataTable.vue";
import FinanceSectionCard from "./FinanceSectionCard.vue";
import { invoiceColumns } from "@/lib/financeColumnsTable";
import type { InvoiceRow } from "@/types/finance";

defineProps<{ data: InvoiceRow[] }>();
</script>

<template>
    <FinanceSectionCard
        title="Faktur Terbit"
        subtitle="Dibaca saja — pembuatan faktur tetap milik modul invoicing"
        badge="Read only"
    >
        <DataTable
            :columns="invoiceColumns"
            :data="data"
            :page-size="5"
            empty-text="Belum ada faktur yang tercatat."
        />
    </FinanceSectionCard>
</template>