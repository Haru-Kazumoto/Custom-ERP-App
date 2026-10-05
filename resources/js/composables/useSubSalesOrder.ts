import { ref } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";
import type { PoDetailForSso, SsoItem } from "@/types/sub-sales-order";

type PurchaseOrderResponse = {
    id: number;
    transaction_code: string;
    transaction_type: string;
    detail: {
        tanggal_po?: string;
        pemasok?: string;
        jenis_pengiriman?: string;
        alokasi?: string;
        transportasi?: string;
    };
    items: SsoItem[];
};

export function useSubSalesOrder() {
    const poDetail = ref<PoDetailForSso | null>(null);
    const processing = ref(false);
    const error = ref<string | null>(null);

    const form = useForm({
        purchase_order_id: null as number | null,
        no_bukti: "",
        no_so: "",
        tanggal_kirim: null as string | null,
        description: "",
        items: [] as SsoItem[],
    });

    function clearProcessedPo() {
        poDetail.value = null;
        form.purchase_order_id = null;
        form.items = [];
    }

    async function processPo(poNumber: string) {
        processing.value = true;
        error.value = null;
        clearProcessedPo();

        try {
            const { data } = await axios.get<PurchaseOrderResponse>(
                "/purchase-orders/get-by-transaction-code",
                { params: { transaction_code: poNumber } },
            );

            if (data.transaction_type !== "PO") {
                throw new Error("Dokumen yang dipilih bukan Purchase Order");
            }

            const detail: PoDetailForSso = {
                id: data.id,
                transaction_code: data.transaction_code,
                tanggal_po: data.detail.tanggal_po ?? "",
                pemasok: data.detail.pemasok ?? "",
                jenis_pengiriman: data.detail.jenis_pengiriman ?? "",
                alokasi: data.detail.alokasi ?? "",
                transportasi: data.detail.transportasi ?? "",
                items: data.items ?? [],
            };

            poDetail.value = detail;
            form.purchase_order_id = detail.id;
            form.items = detail.items;
        } catch (e: unknown) {
            error.value = axios.isAxiosError(e)
                ? e.response?.data?.message ?? "Gagal memuat detail PO"
                : e instanceof Error
                  ? e.message
                  : "Gagal memuat detail PO";
        } finally {
            processing.value = false;
        }
    }

    function reset() {
        clearProcessedPo();
        error.value = null;
        form.reset();
    }

    return {
        form,
        poDetail,
        processing,
        error,
        processPo,
        clearProcessedPo,
        reset,
    };
}
