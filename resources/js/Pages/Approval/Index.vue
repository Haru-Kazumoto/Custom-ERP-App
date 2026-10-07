<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { NIcon, NTag } from "naive-ui";
import { FileText, ChevronRight } from "lucide-vue-next";
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderPage from "@/Components/Common/HeaderPage.vue";

/**
 * Hub approval. Menu `approval_documents` di DB menunjuk ke `/approvals`,
 * jadi halaman ini perlu ada supaya induk menu tidak 404 walau baru
 * Purchase Order yang punya modul.
 */
const documents = [
    {
        label: "Purchase Order",
        description: "Persetujuan dokumen purchase order ke pemasok",
        available: true,
        route: "approvals.index.purchase-orders",
    },
    {
        label: "Delivery Order",
        description: "Persetujuan pengiriman barang ke pelanggan",
        available: true,
        route: "approvals.index.delivery-orders",
    },
    {
        label: "Faktur",
        description: "Persetujuan faktur pembelian dari pemasok",
        available: false,
        route: null,
    },
];
</script>

<template>
    <Head title="Approval" />

    <AppLayout>
        <div class="flex flex-col gap-5">
            <HeaderPage
                title="Approval"
                subTitle="Pilih jenis dokumen yang approvalnya ingin Anda tangani"
            />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    v-for="doc in documents"
                    :key="doc.label"
                    class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    :class="doc.available ? 'cursor-pointer hover:border-slate-300' : ''"
                    @click="doc.route && router.visit(route(doc.route))"
                >
                    <div class="flex items-center gap-3">
                        <NIcon
                            :component="FileText"
                            class="text-xl text-slate-400"
                        />
                        <h2 class="font-medium text-slate-900">
                            {{ doc.label }}
                        </h2>
                    </div>

                    <p class="text-sm text-slate-500">
                        {{ doc.description }}
                    </p>

                    <div class="mt-auto flex items-center justify-between">
                        <NTag
                            size="small"
                            :bordered="false"
                            :class="
                                doc.available
                                    ? 'bg-emerald-50 text-emerald-600'
                                    : 'bg-slate-100 text-slate-500'
                            "
                        >
                            {{
                                doc.available
                                    ? "Tersedia"
                                    : "Segera"
                            }}
                        </NTag>

                        <ChevronRight
                            v-if="doc.available"
                            class="h-4 w-4 text-slate-400"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>