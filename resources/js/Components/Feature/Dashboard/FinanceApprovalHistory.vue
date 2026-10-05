<script setup lang="ts">
import DataTable from "@/Components/Common/DataTable.vue";
import FinanceSectionCard from "./FinanceSectionCard.vue";
import { approvalHistoryColumns } from "@/lib/financeColumnsTable";
import type { ApprovalHistory } from "@/types/finance";

defineProps<{ data: ApprovalHistory[] }>();
</script>

<template>
    <FinanceSectionCard
        title="Riwayat Persetujuan Finance"
        subtitle="Dokumen yang sudah disetujui atau perlu direvisi"
        :badge="`${data.length} riwayat`"
    >
        <DataTable
            :columns="approvalHistoryColumns"
            :data="data"
            :page-size="5"
            empty-text="Belum ada keputusan approval finance."
        />
    </FinanceSectionCard>
</template>