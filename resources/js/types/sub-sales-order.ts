// Ringkasan PO untuk dropdown (list saat load halaman).
export interface PoOption {
    id: number;
    transaction_code: string; // "PO-2607-00001"
    alokasi: string; // "DNP" → dipakai di label "PO-2607-00001 - DNP"
}

// Item yang datang dari PO — untuk SSO hanya tanda terima,
// jadi harga tidak relevan, cukup identitas + jumlah.
export interface SsoItem {
    product_id: number;
    product_code: string;
    product_name: string;
    quantity: number;
    product_unit: string; // kemasan
}

// Response detail PO yang dipaste ke form.
export interface PoDetailForSso {
    id: number;
    transaction_code: string;
    tanggal_po: string;
    pemasok: string;
    jenis_pengiriman: string;
    alokasi: string;
    transportasi: string;
    items: SsoItem[];
}
