import { ref } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";
import type {
    ReceiptItemForm,
    ReceiptSsoDetail,
    ReceiptSplit,
    ReceiptDiscrepancy,
} from "@/types/goods-receipt";

export function useGoodsReceipt(defaultCompanyId: number) {
    const ssoDetail = ref<ReceiptSsoDetail | null>(null);
    const processing = ref(false);
    const error = ref<string | null>(null);

    const form = useForm({
        transaction_id: null as number | null,
        company_id: defaultCompanyId,
        noted: "",
        items: [] as ReceiptItemForm[],
    });

    function makeSplit(quantity: number): ReceiptSplit {
        return {
            batch_code: "",
            quantity,
            expiry_date: null,
            stagnation_limit_date: null,
        };
    }

    function clearProcessedSso() {
        ssoDetail.value = null;
        form.transaction_id = null;
        form.items = [];
    }

    async function processSso(transactionCode: string) {
        processing.value = true;
        error.value = null;
        clearProcessedSso();

        try {
            const { data } = await axios.get<ReceiptSsoDetail>(
                "/goods-receipt/get-by-transaction-code",
                { params: { transaction_code: transactionCode } },
            );

            ssoDetail.value = data;
            form.transaction_id = data.id;
            // Default: semua item diterima penuh dengan satu baris pecahan
            // yang qty-nya sama dengan qty SSO — user tinggal menyesuaikan.
            form.items = data.items.map((item) => ({
                transaction_items_id: item.id,
                product_id: item.product_id,
                product_code: item.product_code,
                product_name: item.product_name,
                product_unit: item.product_unit,
                ordered_qty: item.quantity,
                received_qty: item.quantity,
                splits: [makeSplit(item.quantity)],
                discrepancies: [],
            }));
        } catch (e: unknown) {
            error.value = axios.isAxiosError(e)
                ? e.response?.data?.message ?? "Gagal memuat detail SSO"
                : e instanceof Error
                  ? e.message
                  : "Gagal memuat detail SSO";
        } finally {
            processing.value = false;
        }
    }

    function addSplit(item: ReceiptItemForm) {
        const remaining =
            (item.received_qty ?? 0) -
            item.splits.reduce((sum, split) => sum + (split.quantity ?? 0), 0);

        item.splits.push(makeSplit(remaining > 0 ? remaining : 1));
    }

    function removeSplit(item: ReceiptItemForm, index: number) {
        item.splits.splice(index, 1);
    }

    function addDiscrepancy(item: ReceiptItemForm) {
        item.discrepancies.push({
            type: "DAMAGED",
            remaining_qty: 1,
            description: "",
        });
    }

    function removeDiscrepancy(item: ReceiptItemForm, index: number) {
        item.discrepancies.splice(index, 1);
    }

    /**
     * Validasi sebelum submit — meniru aturan server supaya error muncul
     * di client tanpa request bolak-balik.
     */
    function validate(): string | null {
        if (!form.transaction_id || !form.items.length) {
            return "Proses SSO dulu sebelum submit.";
        }

        for (let i = 0; i < form.items.length; i++) {
            const item = form.items[i];
            const label = item.product_code;

            if (item.received_qty === null || item.received_qty < 0) {
                return `Qty diterima ${label} belum diisi.`;
            }

            if (item.received_qty > item.ordered_qty) {
                return `Qty diterima ${label} melebihi qty SSO (${item.ordered_qty}).`;
            }

            // Qty bertahap (Bertahap/GRADUALLY) tidak boleh melebihi qty
            // yang belum datang.
            const gradualPending = item.discrepancies
                .filter((discrepancy) => discrepancy.type === "GRADUALLY")
                .reduce(
                    (sum, discrepancy) => sum + (discrepancy.remaining_qty ?? 0),
                    0,
                );

            if (gradualPending > item.ordered_qty - item.received_qty) {
                return `Qty bertahap ${label} (${gradualPending}) melebihi qty yang belum diterima (${item.ordered_qty - item.received_qty}).`;
            }

            // Qty diterima 0: belum ada yang datang — splits diabaikan.
            if (item.received_qty === 0) {
                continue;
            }

            if (!item.splits.length) {
                return `${label} harus dipecah menjadi minimal satu kode barang.`;
            }

            const totalSplit = item.splits.reduce(
                (sum, split) => sum + (split.quantity ?? 0),
                0,
            );

            if (totalSplit !== item.received_qty) {
                return `Total qty pecahan ${label} (${totalSplit}) harus sama dengan qty diterima (${item.received_qty}).`;
            }

            for (const split of item.splits) {
                if (!split.batch_code.trim()) {
                    return `Kode barang hasil pecahan ${label} wajib diisi.`;
                }
                if (!split.quantity || split.quantity < 1) {
                    return `Qty pecahan ${label} minimal 1.`;
                }
            }

            for (const discrepancy of item.discrepancies) {
                if (!discrepancy.remaining_qty || discrepancy.remaining_qty < 1) {
                    return `Qty kekurangan ${label} minimal 1.`;
                }
            }
        }

        return null;
    }

    function reset() {
        clearProcessedSso();
        error.value = null;
        form.reset();
        form.company_id = defaultCompanyId;
    }

    return {
        form,
        ssoDetail,
        processing,
        error,
        processSso,
        clearProcessedSso,
        addSplit,
        removeSplit,
        addDiscrepancy,
        removeDiscrepancy,
        validate,
        reset,
    };
}
