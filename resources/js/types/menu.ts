// Mirror tabel `menus` di database.
export interface MenuItem {
  id: number
  name: string
  key: string
  icon: string            // nama icon lucide-vue-next
  url: string
  route_name: string | null   // route utama yang diwakili menu ini
  active_routes: string[] | null // route turunan yang tetap menyalakan menu ini
  description: string | null
  is_active: boolean      // dari TINYINT(1)
  parent_id: number | null
  created_at?: string
  updated_at?: string
  children?: MenuItem[]    // dibangun dari parent_id
}