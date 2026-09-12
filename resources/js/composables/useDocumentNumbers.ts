import { ref } from "vue";
import axios from "axios";
import type { PoOption } from "@/types/sub-sales-order";

export function useDocumentNumbers() {
    const options = ref<{ label: string; value: number }[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchOptions() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get<PoOption[]>(
                "/purchase-orders/transaction-codes",
            );
            // format response → bentuk yang NSelect mau
            options.value = data.map((po) => ({
                label: po.transaction_code, // nanti kalo udah ada alokasi, bisa diganti jadi `${po.transaction_code} - ${po.alokasi}`
                value: po.transaction_code, // pakai transaction_code sebagai value, biar bisa langsung dipakai di query string
            }));
        } catch (e: any) {
            error.value =
                e?.response?.data?.message ?? "Gagal memuat daftar PO";
        } finally {
            loading.value = false;
        }
    }

    return { options, loading, error, fetchOptions };
}
