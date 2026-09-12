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

export async function searchProducts(q: string, signal?: AbortSignal): Promise<ApiProduct[]> {
  const { data } = await axios.get<ApiProduct[]>('/api/product/', {
    params: { search: q || undefined },
    signal,
  })
  return data
}