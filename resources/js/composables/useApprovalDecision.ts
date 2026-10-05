import { ref } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import type { ApprovalDecisionStatus } from "@/types/approval";

/**
 * Mengirim keputusan approval untuk satu dokumen.
 *
 * Setelah sukses halaman dimuat ulang (`reload`) supaya `approvalContext`,
 * timeline, dan status antrean ikut ter-refresh dari server.Memuat ulang lebih
 * aman daripada patch state lokal: keputusan bisa jadi sudah tidak berlaku karena
 * ada yang memutuskan duluan, dan server sudah mengembalikan pesan konflik itu.
 */
export function useApprovalDecision(transactionId: number) {
    const submitting = ref<ApprovalDecisionStatus | null>(null);
    const error = ref<string | null>(null);

    async function decide(
        status: ApprovalDecisionStatus,
        description?: string | null,
    ) {
        submitting.value = status;
        error.value = null;

        try {
            await axios.post(`/approvals/purchase-orders/${transactionId}/decision`, {
                status,
                description: description?.trim() || null,
            });

            router.reload({ only: ["purchaseOrder", "approvalContext"] });
        } catch (e: any) {
            error.value = readError(e);
            return false;
        } finally {
            submitting.value = null;
        }

        return true;
    }

    return { submitting, error, decide };
}

/**
 * Pesan error dari axios.
 *
 * Endpoint ini membalas 422 dengan dua bentuk: `{ message }` untuk konflik
 * business (mis. sudah diputuskan orang lain), dan `{ message, errors }` dari
 * validasi Laravel. Yang kedua harus diekstrak supaya alasan yang salah tidak
 * hanya muncul sebagai "gagal menyimpan".
 */
function readError(e: any): string {
    const data = e?.response?.data;

    if (data?.errors && typeof data.errors === "object") {
        const first = Object.values(data.errors as Record<string, string[]>)[0];

        if (Array.isArray(first) && first.length) return first[0];
    }

    return data?.message ?? e?.message ?? "Gagal menyimpan keputusan approval.";
}