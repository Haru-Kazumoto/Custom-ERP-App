// Item SSO yang diretrieve untuk halaman entry goods receipt.
export interface ReceiptSsoItem {
    id: number;
    product_id: number;
    product_code: string;
    product_name: string;
    quantity: number;
    product_unit: string;
}

export interface ReceiptSsoDetail {
    id: number;
    transaction_code: string;
    description: string | null;
    created_at: string;
    details: Record<string, string | null>;
    items: ReceiptSsoItem[];
}

// Satu baris pemecahan kode barang (batch) milik satu item.
export interface ReceiptSplit {
    batch_code: string;
    quantity: number | null;
    expiry_date: string | null;
    stagnation_limit_date: string | null;
}

// Catatan kondisi/kekurangan manual per item.
export interface ReceiptDiscrepancy {
    type: string;
    remaining_qty: number | null;
    description: string;
}

// Bentuk item yang dikirim ke server.
export interface ReceiptItemForm {
    transaction_items_id: number;
    product_id: number;
    product_code: string;
    product_name: string;
    product_unit: string;
    ordered_qty: number;
    received_qty: number | null;
    splits: ReceiptSplit[];
    discrepancies: ReceiptDiscrepancy[];
}
