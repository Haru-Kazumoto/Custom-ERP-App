// Baris daftar semua barang — agregat PER PRODUK.
export interface StockItem {
    product_id: number;
    code: string;
    name: string;
    unit: string;
    stock: number;
    batch_count: number;
}

// Baris daftar per kode barang (batch hasil pecahan).
export interface StockBatch {
    product_id: number;
    code: string;
    name: string;
    unit: string;
    batch_code: string;
    stock: number;
    expiry_date: string | null;
    stagnation_limit_date: string | null;
    last_received_at: string | null;
}

// Baris daftar barang tertunda (discrepancy GRADUALLY OPEN).
export interface GradualItem {
    id: number;
    transaction_items_id: number;
    product_code: string;
    product_name: string;
    product_unit: string;
    sso_code: string;
    remaining_qty: number;
    received_at: string;
    description: string | null;
}

export interface CompanyRow {
    id: number;
    name: string;
    code: string;
}

export interface StockFilters {
    search: string;
    company_id: number | null;
}
