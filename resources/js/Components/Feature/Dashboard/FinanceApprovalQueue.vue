<script setup lang="ts">
import { computed, ref } from "vue";
import { NButton } from "naive-ui";
import DataTable from "@/Components/Common/DataTable.vue";
import FinanceSectionCard from "./FinanceSectionCard.vue";
import { approvalQueueColumns } from "@/lib/financeColumnsTable";
import { fmtRupiah } from "@/lib/utils";
import type { PendingApproval } from "@/types/finance";

const props = defineProps<{ data: PendingApproval[] }>();

const totalValue = computed(() =>
    props.data.reduce((sum, row) => sum + (row.grand_total ?? 0), 0),
);

const subtitle = computed(() =>
    props.data.length
        ? `Purchase Order, Delivery Order, dan faktur dalam satu antrean · ${fmtRupiah(totalValue.value)}`
        : "Purchase Order, Delivery Order, dan faktur dalam satu antrean",
);

const ALL = "__ALL__";
const activeType = ref<string>(ALL);

/**
 * Tab per tipe dokumen dibangun dari data yang benar-benar ada, bukan dari
 * daftar tetap. Modul DO belum ada, jadi tidak akan muncul chip yang tidak pernah
 * berisi apa pun — dan begitu modulnya hidup, chip-nya muncul tanpa perubahan
 * kode.
 */
const typeOptions = computed(() => {
    const counts = new Map<string, number>();
    for (const row of props.data) {
        const key = (row.transaction_type || "").toUpperCase();
        if (!key) continue;
        counts.set(key, (counts.get(key) ?? 0) + 1);
    }

    const options = [{ key: ALL, label: "Semua", count: props.data.length }];
    for (const [key, count] of counts) {
        options.push({ key, label: key, count });
    }
    return options;
});

const filtered = computed(() =>
    activeType.value === ALL
        ? props.data
        : props.data.filter(
              (row) =>
                  (row.transaction_type || "").toUpperCase() ===
                  activeType.value,
          ),
);
</script>

<template>
    <FinanceSectionCard
        title="Menunggu Persetujuan Finance"
        :subtitle="subtitle"
        :badge="`${data.length} dokumen`"
    >
        <div v-if="data.length" class="mb-3 flex flex-wrap gap-2">
            <NButton
                v-for="option in typeOptions"
                :key="option.key"
                size="tiny"
                :quaternary="activeType !== option.key"
                :type="activeType === option.key ? 'primary' : 'default'"
                class="!text-xs"
                @click="activeType = option.key"
            >
                {{ option.label }}
                <span class="ml-1 opacity-60">{{ option.count }}</span>
            </NButton>
        </div>

        <DataTable
            :columns="approvalQueueColumns"
            :data="filtered"
            :page-size="5"
            empty-text="Tidak ada dokumen yang menunggu persetujuan finance."
        />
    </FinanceSectionCard>
</template>