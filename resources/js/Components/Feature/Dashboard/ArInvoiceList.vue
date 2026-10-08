<script setup lang="ts">
import DataTable from "@/Components/Common/DataTable.vue";
import FinanceSectionCard from "./FinanceSectionCard.vue";
import { arInvoiceColumns } from "@/lib/arColumnsTable";
import type { ArInvoiceRow } from "@/types/ar-controller";

/**
 * Daftar faktur yang masih punya sisa tagihan.
 *
 * Pembayaran bisa bertahap, jadi kolom "Pembayaran" menunjukkan berapa kali
 * pembayaran tercatat dan "Sisa" adalah `grand_total` dikurangi total
 * `invoice_payments`.
 */
defineProps<{ data: ArInvoiceRow[] }>();
</script>

<template>
    <FinanceSectionCard
        title="Piutang Belum Lunas"
        subtitle="Faktur dengan sisa tagihan, dari yang paling lama jatuh tempo"
        badge="Read only"
    >
        <DataTable
            :columns="arInvoiceColumns"
            :data="data"
            :page-size="10"
            empty-text="Belum ada faktur yang tercatat."
        />
    </FinanceSectionCard>
</template>
