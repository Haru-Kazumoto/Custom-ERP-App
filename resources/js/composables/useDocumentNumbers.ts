import { ref } from "vue";
import axios from "axios";

export function useDocumentNumbers() {
    const options = ref<{ label: string; value: string }[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchOptions() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get<
                { id: number; transaction_code: string }[]
            >(
                "/purchase-orders/transaction-codes",
            );
            options.value = data.map((po) => ({
                label: po.transaction_code,
                value: po.transaction_code,
            }));
        } catch (e: unknown) {
            error.value = axios.isAxiosError(e)
                ? e.response?.data?.message ?? "Gagal memuat daftar PO"
                : "Gagal memuat daftar PO";
        } finally {
            loading.value = false;
        }
    }

    return { options, loading, error, fetchOptions };
}
