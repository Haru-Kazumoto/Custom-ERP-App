export type ApprovalDecisionStatus = "APPROVED" | "NEED_REVISION";

/**
 * Satu baris antrean approval untuk role pemohon.
 *
 * Antrean hanya berisi dokumen yang gilirannya milik role pemohon, jadi
 * `can_decide` selalu true. Flag ini tetap dikirim server karena frontend tidak
 * boleh menentukan sendiri dokumen mana yang boleh diputuskan.
 */
export interface PurchaseOrderApprovalQueueItem {
    id: number;
    transaction_code: string;
    transaction_type: string;
    payment_term: number;
    created_at: string;
    due_date: string | null;
    description: string | null;
    total_discount: number;
    sub_total: number;
    tax_amount: number;
    grand_total: number;
    current_approval_order: number;
    current_approval_status: string | null;
    current_approval_role: string | null;
    current_approval_sub_role: string | null;
    current_approval_proceed_by: string | null;
    current_approval_proceed_at: string | null;
    current_approval_description: string | null;
    supplier: string | null;
    pic_name: string | null;
    /** Giliran step ini milik role pemohon dan belum diputuskan. */
    can_decide: boolean;
}

export interface PurchaseOrderApprovalQueue {
    data: PurchaseOrderApprovalQueueItem[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface ApprovalQueueFilters {
    search: string;
    date_from: string;
    date_to: string;
}

/**
 * Konteks keputusan untuk satu dokumen, dikirim sebagai prop halaman detail.
 *
 * `can_decide` dihitung ulang di server memakai aturan yang sama dengan
 * `DecideApprovalAction`, termasuk penghentian rantai saat ada langkah
 * NEED_REVISION.
 */
export interface ApprovalDecisionContext {
    transaction_id: number;
    transaction_code: string;
    steps: {
        id: number;
        order: number;
        role_id: number;
        role: string | null;
        status: string;
        description: string | null;
        proceed_by: string | null;
        proceed_at: string | null;
    }[];
    current: {
        order: number;
        role: string | null;
        status: string;
    } | null;
    can_decide: boolean;
    my_decision: {
        status: string;
        description: string | null;
        proceed_at: string | null;
    } | null;
    /**
     * Keputusan approval dari rantai sebelumnya, yang diarsipkan `RevisePurchaseOrderAction`
     * sebelum PO dikirim ulang ke Finance.
     *
     * Tidak pernah `undefined`: dokumen yang belum pernah direvisi mengirim array
     * kosong, supaya frontend tidak perlu membedakan "belum ada" dari "halaman
     * detail tidak mengirim datanya".
     */
    revision_history: {
        order: number;
        role: string | null;
        status: string;
        description: string | null;
        proceed_by: string | null;
        proceed_at: string | null;
        archived_at: string;
    }[];
}