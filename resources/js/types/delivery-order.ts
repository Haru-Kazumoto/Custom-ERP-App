/**
 * Tipe frontend modul Delivery Order.
 *
 * Kontrak datanya berasal dari backend:
 *   - form options  : `GetDeliveryOrderFormOptionsQuery`
 *   - pencarian barang: `GetDeliveryOrderProductsQuery`
 *   - daftar dokumen  : `GetDeliveryOrdersQuery` (view `v_get_delivery_orders_with_approval`)
 *   - detail dokumen  : `FindDeliveryOrderQuery` + `GetDeliveryOrderItemsQuery`
 *   - antrean approval : `GetDeliveryOrderApprovalQueueQuery`
 */

/** Program promo yang eligible untuk produk + pelanggan terpilih. */
export interface DeliveryPromo {
    promo_product_id: number;
    name: string;
    code: string;
    /** NORMAL | FLUSH_OUT — FLUSH_OUT melewati validasi range min/max. */
    type: string;
    min_qty: number | null;
    max_qty: number | null;
    base_quota: number | null;
    percentage_1: number | null;
    percentage_2: number | null;
    percentage_3: number | null;
    manual_type: string | null;
    manual_percentage: number | null;
    manual_value: number | null;
}

/** Satu baris hasil pencarian barang (`GET /delivery-order/products`). */
export interface DeliveryProduct {
    id: number;
    code: string;
    name: string;
    unit: string;
    category: string;
    stock: number;
    /** false → tombol tambah dinonaktifkan (baris `product_prices` tidak ada). */
    has_price: boolean;
    price: number | null;
    promos: DeliveryPromo[];
}

/**
 * Satu baris barang di form DO.
 *
 * `unit_price` selalu harga BRUTO awal (A) sebelum promo — server memakai
 * `product_id + quantity (+ unit_price bila harga manual)` lalu menerapkan
 * cascading promo sendiri. Field display-only (`catalog_price`, `stock`,
 * `promo`) hanya untuk preview dan tidak divalidasi server.
 */
export interface DeliveryOrderItem {
    product_id: number;
    quantity: number;
    /** Harga satuan bruto awal (sebelum promo). */
    unit_price: number;
    /**
     * Mode harga baris ini — dipilih per produk di section penginputan.
     * false = harga daftar (`product_prices`), true = harga manual yang
     * dibatasi tidak boleh melebihi harga daftar.
     */
    use_manual_price: boolean;
    /** Snapshot konfigurasi promo untuk preview diskon cascading. */
    promo: DeliveryPromo | null;
    product: { code: string; unit: string; name: string };
    /** Harga katalog (sebelum promo) — dasar kembali ke harga normal. */
    catalog_price: number;
    /** Snapshot stok saat dipilih; dipakai peringatan DEPO. */
    stock: number | null;
}

/** Payload `transaction_items` yang dikirim ke `delivery-order.store`. */
export interface DeliveryOrderDetailRow {
    name: string;
    type: string;
    value: string;
    data_type: "string" | "float" | "boolean" | "datetime";
}

/**
 * Map detail dokumen hasil `FindDeliveryOrderQuery` / daftar — kunci sudah
 * snake_case dari nama detail (`Delivery Date` → `delivery_date`).
 */
export interface DeliveryOrderDetails {
    delivery?: string | null;
    sub_delivery?: string | null;
    customer?: string | null;
    segment?: string | null;
    company?: string | null;
    warehouse?: string | null;
    delivery_date?: string | null;
    /** "true" | "false" */
    ppn?: string | null;
    /** "true" | "false" — true bila minimal satu baris memakai harga manual. */
    manual_price?: string | null;
    /** Informasi opsional (hanya diisi bila user mengisinya). */
    nomor_po_pelanggan?: string | null;
    /** String angka — `formatRupiah(Number(...))` untuk tampilan. */
    cashback_pph_4?: string | null;
    biaya_bongkar?: string | null;
    /** Label syarat pembayaran digabung ", ". */
    syarat_pembayaran?: string | null;
    [key: string]: string | null | undefined;
}

/** Satu tahap diskon tersimpan di `transacton_item_discounts`. */
export interface DeliveryDiscountStage {
    sequence: number;
    discount_type: "PERCENTAGE" | "VALUE" | string;
    discount_value: string;
    source: string;
}

export interface DeliveryOrderItemRow {
    id: number;
    product_id: number;
    quantity: number | string;
    base_price: string | number;
    total_price: string | number;
    /** Harga satuan bruto hasil promo (turunan `total_price / quantity`). */
    unit_price: number;
    promo_product_id: number | null;
    product_name: string | null;
    product_unit: string | null;
    product_category: string | null;
    product_code: string | null;
    promo_name: string | null;
    promo_code: string | null;
    /** Rincian per tahap; kosong untuk baris tanpa promo. */
    discounts: DeliveryDiscountStage[];
    /** Harga satuan sebelum promo (stage pertama); null bila tanpa promo. */
    unit_price_before: number | null;
    /** Harga satuan sesudah promo (stage terakhir); null bila tanpa promo. */
    unit_price_after: number | null;
}

export interface DeliveryOrderHead {
    id: number;
    transaction_code: string;
    transaction_type: string;
    correlation_id: string;
    due_date: string | null;
    payment_term: number | string;
    created_by: number | null;
    updated_at: string | null;
    file_attachment: string | null;
    description: string | null;
    sub_total: string | number;
    total_discount: string | number;
    tax_amount: string | number;
    grand_total: string | number;
    details: DeliveryOrderDetails;
    items: DeliveryOrderItemRow[];
}

/**
 * Satu baris di halaman daftar (`/delivery-order/documents`).
 * Penamaan mengikuti kolom view — `detail` adalah map snake_case hasil
 * transformasi `GetDeliveryOrdersQuery`.
 */
export interface DeliveryOrderSummary {
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
    created_by: number | null;
    detail: DeliveryOrderDetails;
    current_approval_order: number | null;
    current_approval_status: string;
    current_approval_description: string | null;
    current_approval_role: string | null;
    current_approval_proceed_by: string | null;
    current_approval_proceed_at: string | null;
}

export interface CompanyOption {
    id: number;
    code: string;
    name: string;
}

export interface SubShippingOption {
    id: number;
    code: string;
    name: string;
    shipping_id: number;
}

export interface ShippingOption {
    id: number;
    code: string;
    name: string;
    subs: SubShippingOption[];
}

export interface CustomerOption {
    id: number;
    name: string;
    segment: string | null;
    term_payment: number | string;
    salesman_id: number | null;
}

export interface DeliveryOrderFormOptions {
    companies: CompanyOption[];
    shippings: ShippingOption[];
    customers: CustomerOption[];
}
