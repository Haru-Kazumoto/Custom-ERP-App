<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import StatCard from "@/Components/Feature/Dashboard/StatCard.vue";
import CheckInCard from "@/Components/Feature/Dashboard/CheckInCard.vue";
import RevenueChart from "@/Components/Feature/Dashboard/RevenueChart.vue";
import CalendarWidget from "@/Components/Feature/Dashboard/CalendarWidget.vue";
import TaskList from "@/Components/Feature/Dashboard/TaskList.vue";

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
                    icon="Inbox"
                    :delta="-3.0"
                />
                <StatCard
                    label="Total Revenue"
                    value="$48.2k"
                    icon="DollarSign"
                    :delta="12.5"
                />
                <StatCard
                    label="Active Projects"
                    value="24"
                    icon="FolderKanban"
                    :delta="4.1"
                />
                <StatCard
                    label="Attendance"
                    value="88%"
                    icon="UserCheck"
                    :delta="2.3"
                />
            </div>

            <!-- Konten + kalender -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
                <div class="space-y-4 lg:col-span-2 lg:space-y-6">
                    <CheckInCard />
                    <RevenueChart />
                </div>
                <div class="space-y-4 lg:space-y-6">
                    <CalendarWidget @select="onDate" />
                    <TaskList />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
