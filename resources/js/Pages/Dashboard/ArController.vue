<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Feature/Dashboard/StatCard.vue";
import ArAgingSummary from "@/Components/Feature/Dashboard/ArAgingSummary.vue";
import ArInvoiceList from "@/Components/Feature/Dashboard/ArInvoiceList.vue";
import ArCustomerList from "@/Components/Feature/Dashboard/ArCustomerList.vue";
import { fmtRupiah } from "@/lib/utils";
import type {
    ArAgingBucket,
    ArCustomerRow,
    ArDashboardSummary,
    ArInvoiceRow,
} from "@/types/ar-controller";

const props = defineProps<{
    summary: ArDashboardSummary;
    aging: ArAgingBucket[];
    invoices: ArInvoiceRow[];
    customers: ArCustomerRow[];
}>();

const summary = props.summary;
</script>

<template>
    <AppLayout>
        <div class="space-y-4 lg:space-y-6">
            <div>
                <h1 class="text-xl font-bold text-slate-800">
                    Dashboard AR Controller
                </h1>
                <p class="text-sm text-slate-400">
                    Piutang perusahaan, umur faktur yang belum lunas, dan data
                    customer yang menjadi sumbernya.
                </p>
            </div>

            <!-- Stat row -->
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard
                    label="Total Piutang"
                    :value="fmtRupiah(summary.outstanding_total)"
                    icon="Wallet"
                />
                <StatCard
                    label="Faktur Belum Lunas"
                    :value="String(summary.unpaid_count)"
                    icon="Receipt"
                />
                <StatCard
                    label="Sudah Dibayar"
                    :value="fmtRupiah(summary.paid_total)"
                    icon="Banknote"
                />
                <StatCard
                    label="Piutang Terlambat"
                    :value="fmtRupiah(summary.overdue_value)"
                    icon="AlertTriangle"
                />
            </div>

            <!-- Umur invoice -->
            <ArAgingSummary :buckets="aging" />

            <!-- Rincian piutang + data customer -->
            <ArInvoiceList :data="invoices" />
            <ArCustomerList :data="customers" />
        </div>
    </AppLayout>
</template>
