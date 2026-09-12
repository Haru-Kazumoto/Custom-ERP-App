import { ref } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";
import type { PoDetailForSso, SsoItem } from "@/types/sub-sales-order";

export function useSubSalesOrder() {
    

    // ringkasan PO (hasil dari tombol Proses) untuk ditampilkan read-only
    const poDetail = ref<PoDetailForSso | null>(null);
    const processing = ref(false);
    const error = ref<string | null>(null);

    // form yang akan disubmit
    const form = useForm({
        purchase_order_id: null as number | null,
        correlation_id: null as string | null,
        description: "",
        no_bukti: "",
        no_so: "",
        tanggal_kirim: null as string | null,
        items: [] as SsoItem[],
        details: [] as any[],
        ...poDetail.value
    });

    async function processPo(po_number: string) {
        processing.value = true;
        error.value = null;
        try {
            const { data } = await axios.get<PoDetailForSso>(
                `/purchase-orders/get-by-transaction-code?transaction_code=${po_number}`,
            );
            poDetail.value = {
                transaction_code: data.detail.transaction_code,
                tanggal_po: data.detail.tanggal_po,
                pemasok: data.detail.pemasok,
                jenis_pengiriman: data.detail.jenis_pengiriman,
                alokasi: data.detail.alokasi,
                transportasi: data.detail.transportasi
            };

            Object.assign(form, poDetail.value);

            // paste data PO → form
            form.purchase_order_id = data.id;
            form.correlation_id = data.correlation_id; 
            form.items = data.items ?? [];
            // ← [KAMU] kalau ada field lain dari PO yang mau ikut ke form, salin di sini
        } catch (e: any) {
            error.value =
                e?.response?.data?.message ?? "Gagal memuat detail PO";
            poDetail.value = null;
        } finally {
            processing.value = false;
        }
    }

    function reset() {
        poDetail.value = null;
        form.reset();
        form.items = [];
    }

    return { form, poDetail, processing, error, processPo, reset };
}
