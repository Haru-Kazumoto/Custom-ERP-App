<script setup lang="ts">
import DataTable from "@/Components/Common/DataTable.vue";
import FinanceSectionCard from "./FinanceSectionCard.vue";
import { promoUsageColumns } from "@/lib/financeColumnsTable";
import type { PromoUsageRow } from "@/types/finance";

defineProps<{ data: PromoUsageRow[] }>();
</script>

<template>
    <FinanceSectionCard
        title="Promo Terpakai pada Delivery Order"
        subtitle="Sumber data pengajuan klaim promo ke pemasok barang"
        :badge="`${data.length} baris`"
    >
        <DataTable
            :columns="promoUsageColumns"
            :data="data"
            :page-size="5"
            empty-text="Modul Delivery Order belum tersedia, jadi belum ada promo yang tercatat di DO."
        />
    </FinanceSectionCard>
</template>