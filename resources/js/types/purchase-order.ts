export interface TradePromo {
    id: number;
    grosir_account: string;
    discount_price: number;
    quota: number;
    is_active: boolean;
}

export interface ProductOption {
    id: number;
    label: string;
    value: string;
    code: string;
    unit: string;
    redemp_price: number;
    trade_promos?: TradePromo[];
}

export interface TransactionItem {
    unit: string;
    quantity: number;
    product_id: number;
    /** Harga satuan bersih (excl PPN) → dikirim sebagai `transaction_items.base_price`. */
    amount: number;
    /** Harga satuan bruto (incl PPN). Satu-satunya angka yang dikirim ke server untuk dihitung ulang. */
    unit_price: number;
    /** Harga katalog sebelum promo; dipakai untuk menghitung `total_discount`. */
    original_price: number;
    tax_id: number | null;
    use_tax: boolean;
    trade_promo_id: number | null;
    /** Total bruto baris (incl PPN). Penjumlahannya = `grand_total`. */
    total_price: number;
    product: { code: string; unit: string; name: string };
}

export interface TradePromoOption {
    label: string;
    value: number;
    price: number;
    quota: number;
    is_active: boolean;
}

export interface PoItem {
    id: number;
    product_id: number;
    base_price: string;
    total_price: string;
    /**
     * Harga satuan BRUTO turunan `total_price / quantity`.
     *
     * Tidak disimpan di `transaction_items` (kalkulator menyimpan `base_price`
     * dan `total_price`), tapi form revisi butuh harga yang akan dikirim ulang
     * ke server — yang bukan `base_price`, karena `unit_price` di kalkulator
     * dipotong PPN sekali lagi.
     */
    unit_price: number;
    quantity: number;
    trade_promo_id: number | null;
    product_name: string;
    product_unit: string;
    product_category: string;
    product_code?: string;
    /** Harga katalog produk (sebelum promo). */
    product_price?: string;
    trade_promo: string | null;
    trade_promo_price: string | null;
}

export interface PoDetails {
    pemasok: string;
    alokasi: string;
    tanggal_po: string;
    tanggal_kirim: string;
    transportasi: string;
    jenis_pengiriman: string;
    harga_angkutan: string;
    ppn: string; // "true" | "false"
    nomor_polisi: string;
}

export interface PoHead {
    id: number;
    transaction_code: string;
    transaction_type: string;
    correlation_id: string;
    due_date: string;
    /** Dicerminkan ke form revisi sebagai "Term of Payment" (hari). */
    payment_term: number;
    /** Pemilik dokumen. Hanya dia yang boleh merevisi PO ini. */
    created_by: number | null;
    updated_at: string | null;
    file_attachment: string | null;
    description: string | null;
    sub_total: string;
    total_discount: string;
    tax_amount: string;
    grand_total: string;
    details: PoDetails;
    items: PoItem[];
}

export interface TransactionApproval {
    order: number;
    status: string; // "APPROVED" | "PENDING" | "NEED_REVISION" | dst
    role: string;
    sub_role?: string | null; // opsional, bisa null
    proceed_at?: string | null;
    proceed_by?: string | null; // nama orang yang memproses
    description?: string | null; // deskripsi tambahan dari approval
}

/**
 * Satu baris di daftar dokumen (`/purchase-orders/documents` dan
 * `/purchase-orders/revisions`).
 *
 * Berasal dari `GetPurchaseOrdersQuery`, jadi penamaannya mengikuti kolom
 * database — bukan alias frontend. Satu-satunya transformasi yang sudah
 * dilakukan query adalah `details` (JSON) menjadi map `detail` dengan kunci
 * `name` yang sudah lowercase.
 */
export interface PurchaseOrderSummary {
    id: number;
    transaction_code: string;
    transaction_type: string;
    payment_term: number;
    sub_total: number | string;
    tax_amount: number | string;
    grand_total: number | string;
    total_discount: number | string;
    created_at: string;
    last_updated_at: string | null;
    document_description: string | null;
    /** Id user pembuat. Satu-satunya dasar hak revisi di frontend. */
    created_by: number | null;
    detail: Partial<PoDetails> & { supplier?: string | null };
    /** Langkah approval yang sedang berjalan menurut view. */
    current_approval_order: number | null;
    current_approval_status: string;
    current_approval_description: string | null;
    current_approval_role: string | null;
    current_approval_proceed_by: string | null;
    current_approval_proceed_at: string | null;
}
