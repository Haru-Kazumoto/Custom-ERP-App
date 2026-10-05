import axios from 'axios'

export interface ApiTradePromo {
  id: number
  name: string
  price: number | string
  quota: number
  is_active: number | boolean
}

export interface ApiProduct {
  product_id: number
  name: string
  code: string
  unit: string
  category: string
  price: string
  vendor: string
  type: string
  sub_type: string
  vendor_id: number
  product_type_id: number
  product_sub_type_id: number
  trade_promos: ApiTradePromo[]
}

/** Filter opsional untuk mempersempit katalog. */
export interface ProductFilters {
  /** Batasi satu kategori produk, mis. "TEPUNG". */
  category?: string | null
  /** Batasi barang milik satu principal (vendor). */
  vendorId?: number | null
}

const EMPTY: ApiProduct[] = []

/**
 * Katalog produk untuk form pemesanan.
 *
 * `q` boleh kosong: backend mengembalikan halaman pertama katalog supaya
 * dropdown tidak terasa kosong sebelum user sempat mengetik.
 */
export async function searchProducts(
  q: string,
  options: ProductFilters & { signal?: AbortSignal; limit?: number } = {},
): Promise<ApiProduct[]> {
  const { signal, limit, category, vendorId } = options

  try {
    const { data } = await axios.get<ApiProduct[]>('/api/product/', {
      params: {
        search: q || undefined,
        limit: limit ?? 20,
        category: category || undefined,
        vendor_id: vendorId || undefined,
      },
      signal,
    })
    return Array.isArray(data) ? data : EMPTY
  } catch (e: any) {
    // Request yang dibatalkan karena user masih mengetik bukan error — biar
    // pemanggil melanjutkan dengan hasil sebelumnya.
    if (e?.name === 'CanceledError' || e?.code === 'ERR_CANCELED') throw e
    console.error('Gagal memuat katalog produk:', e?.message ?? e)
    return EMPTY
  }
}