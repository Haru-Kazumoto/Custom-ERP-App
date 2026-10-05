<script setup lang="ts">
import DataTable from "@/Components/Common/DataTable.vue";
import FinanceSectionCard from "./FinanceSectionCard.vue";
import { promoClaimColumns } from "@/lib/financeColumnsTable";
import type { PromoClaimRow } from "@/types/finance";

defineProps<{ data: PromoClaimRow[] }>();
</script>

<template>
    <FinanceSectionCard
        title="Klaim Promo Tercatat"
        subtitle="Tabel promo_claim yang sudah ada — belum orientasi klaim ke pemasok"
        :badge="`${data.length} klaim`"
    >
        <DataTable
            :columns="promoClaimColumns"
            :data="data"
            :page-size="5"
            empty-text="Belum ada klaim promo yang tercatat."
        />
    </FinanceSectionCard>
</template>