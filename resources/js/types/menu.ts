// Mirror tabel `menus` di database.
export interface MenuItem {
  id: number
  name: string
  key: string
  icon: string            // nama icon lucide-vue-next
  url: string
  description: string | null
  is_active: boolean      // dari TINYINT(1)
  parent_id: number | null
  created_at?: string
  updated_at?: string
  children?: MenuItem[]    // dibangun dari parent_id
}