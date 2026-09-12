import { ref } from "vue";
import axios from "axios";
import type { TransactionApproval } from "@/types/purchase-order";

export function useApprovals(transactionId: number) {
    const approvals = ref<TransactionApproval[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchApprovals() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get<TransactionApproval[]>(
                `/purchase-orders/${transactionId}/approvals`,
            );
            // urutkan berdasarkan step
            approvals.value = [...data].sort((a, b) => a.order - b.order);
        } catch (e: any) {
            error.value = e?.message ?? "Gagal memuat approval";
        } finally {
            loading.value = false;
        }
    }

    /** Dipakai listener socket nanti: ganti seluruh daftar / patch satu step. */
    function applyRealtime(next: TransactionApproval[]) {
        approvals.value = [...next].sort((a, b) => a.order - b.order);
    }

    return { approvals, loading, error, fetchApprovals, applyRealtime };
}
