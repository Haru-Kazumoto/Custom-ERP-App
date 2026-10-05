<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Feature/Dashboard/StatCard.vue";
import CheckInCard from "@/Components/Feature/Dashboard/CheckInCard.vue";
import RevenueChart from "@/Components/Feature/Dashboard/RevenueChart.vue";
import CalendarWidget from "@/Components/Feature/Dashboard/CalendarWidget.vue";
import TaskList from "@/Components/Feature/Dashboard/TaskList.vue";
import RecentPurchaseOrderDocuments from "@/Components/Feature/Dashboard/RecentPurchaseOrderDocuments.vue";

function onDate(d: Date) {
    // hook untuk filter data dashboard berdasarkan tanggal,
    // mis. router.reload({ data: { date: d.toISOString().slice(0,10) } })
    console.log("filter date:", d);
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4 lg:space-y-6">
            <!-- Stat row -->
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard
                    label="Open Requests"
                    value="7"
                    icon="ScrollText"
                    :delta="-3.0"
                    tone="green"
                />
                <StatCard
                    label="Total Revenue"
                    value="$48.2k"
                    icon="FileText"
                    :delta="12.5"
                    tone="green"
                />
                <StatCard
                    label="Active Projects"
                    value="24"
                    icon="FileText"
                    :delta="4.1"
                    tone="green"
                />
                <StatCard
                    label="Attendance"
                    value="88%"
                    icon="UserCheck"
                    :delta="2.3"
                    tone="green"
                />
            </div>

            <!-- Konten + kalender -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
                <div class="space-y-4 lg:col-span-2 lg:space-y-6">
<!-- <CheckInCard tone="green" /> -->
            <!-- <RevenueChart tone="green" /> -->
                    <RecentPurchaseOrderDocuments />
                </div>
                <div class="space-y-4 lg:space-y-6">
                    <CalendarWidget tone="green" @select="onDate" />
                    <!-- <TaskList tone="green" /> -->
                    
                </div>
            </div>
        </div>
    </AppLayout>
</template>
