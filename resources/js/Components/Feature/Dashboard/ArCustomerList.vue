<script setup lang="ts">
import DataTable from "@/Components/Common/DataTable.vue";
import FinanceSectionCard from "./FinanceSectionCard.vue";
import { arCustomerColumns } from "@/lib/arColumnsTable";
import type { ArCustomerRow } from "@/types/ar-controller";

/**
 * Data customer beserta piutangnya.
 *
 * Pelanggan tidak punya foreign key di dokumen — pengelompokan dilakukan di
 * backend lewat nama pada `transaction_details`; customer yang belum punya
 * faktur tetap tampil dengan angka nol karena halaman ini juga untuk memeriksa
 * data customer.
 */
defineProps<{ data: ArCustomerRow[] }>();
</script>

<template>
    <FinanceSectionCard
        title="Data Customer & Piutang"
        subtitle="Total tagihan dan sisa piutang per customer"
        badge="Read only"
    >
        <DataTable
            :columns="arCustomerColumns"
            :data="data"
            :page-size="10"
            empty-text="Belum ada data customer."
        />
    </FinanceSectionCard>
</template>
