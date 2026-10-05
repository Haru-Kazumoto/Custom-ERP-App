<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Feature/Dashboard/StatCard.vue";
import FinanceApprovalQueue from "@/Components/Feature/Dashboard/FinanceApprovalQueue.vue";
import FinanceApprovalHistory from "@/Components/Feature/Dashboard/FinanceApprovalHistory.vue";
import FinanceInvoiceList from "@/Components/Feature/Dashboard/FinanceInvoiceList.vue";
import FinancePromoUsageList from "@/Components/Feature/Dashboard/FinancePromoUsageList.vue";
import FinancePromoClaimList from "@/Components/Feature/Dashboard/FinancePromoClaimList.vue";
import { fmtRupiah } from "@/lib/utils";
import type {
    ApprovalHistory,
    FinanceSummary,
    InvoiceRow,
    PendingApproval,
    PromoClaimRow,
    PromoUsageRow,
} from "@/types/finance";

const props = defineProps<{
    summary: FinanceSummary;
    pendingApprovals: PendingApproval[];
    approvalHistory: ApprovalHistory[];
    invoices: InvoiceRow[];
    promoUsage: PromoUsageRow[];
    promoClaims: PromoClaimRow[];
}>();

const summary = props.summary;
</script>

<template>
    <AppLayout>
        <div class="space-y-4 lg:space-y-6">
            <div>
                <h1 class="text-xl font-bold text-slate-800">
                    Dashboard Finance
                </h1>
                <p class="text-sm text-slate-400">
                    Persetujuan dokumen, faktur yang sudah terbit, dan klaim promo
                    ke pemasok barang.
                </p>
            </div>

            <!-- Stat row -->
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard label="Menunggu Approval" :value="String(summary.pending_approval.count)" icon="FileCheck" />
                <StatCard label="Total Faktur Terbit" :value="fmtRupiah(summary.invoice.total)" icon="Receipt" />
                <StatCard label="Sisa Tagihan" :value="fmtRupiah(summary.invoice.outstanding)" icon="Wallet" />
                <StatCard label="Klaim Promo Tercatat" :value="String(summary.promo_claim.count)" icon="BadgePercent" />
            </div>

            <!-- Konten utama + kolom samping -->

            <FinanceApprovalQueue :data="pendingApprovals" />
        </div>
    </AppLayout>
</template>
