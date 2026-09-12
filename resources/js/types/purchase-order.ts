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
    amount: number; // sudah di-exclude PPN: round(harga / 1.11)
    tax_id: number | null;
    use_tax: boolean;
    trade_promo_id: number | null;
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
    base_price: string;
    total_price: string;
    quantity: number;
    product_name: string;
    product_unit: string;
    product_category: string;
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
    status: string; // "APPROVED" | "PENDING" | "REJECTED" | "NEED_REVISION" | dst
    role: string;
    sub_role?: string | null; // opsional, bisa null
    proceed_at?: string | null;
    proceed_by?: string | null; // nama orang yang memproses
    description?: string | null; // deskripsi tambahan dari approval
}
