// Mirror tabel `products` di database, sudah dilabeli ulang oleh query daftar
// (`ProductRepository::listQuery`): `type`/`sub_type`/`vendor` berisi NAMA dari
// tabel referensi, bukan id.
export interface ProductRow {
  id: number
  name: string
  code: string
  unit: string
  category: string
  price: number | null          // decimal(18,2), nullable
  product_type_id: number | null
  product_sub_type_id: number | null
  vendor_id: number | null
  type: string | null
  sub_type: string | null
  vendor: string | null
  created_at: string | null
  updated_at: string | null
}

/** Opsi dropdown untuk NSelect: bentuk `{ label, value }`. */
export interface ProductOption {
  label: string
  value: number
}

/**
 * Bentuk paginator yang dikirim Laravel ke Inertia.
 */
export interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
  links?: Array<{ url: string | null; label: string; active: boolean }>
}