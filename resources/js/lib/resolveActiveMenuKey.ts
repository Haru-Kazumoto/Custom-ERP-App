import type { MenuItem } from '@/types/menu'

/** Buang query string, hash, dan slash trailing supaya URL stabil dibandingkan. */
const normalize = (url: string) =>
  url.split('?')[0].split('#')[0].replace(/\/+$/, '') || '/'

const isLeaf = (menu: MenuItem) => (menu.children?.length ?? 0) === 0

/**
 * Urutan preferensi kandidat: menu halaman (leaf) dulu, baru grup parent.
 * Grup parent tetap ikut dicocokkan supaya modul yang grupnya sekaligus
 * punya halaman sendiri tidak kehilangan menu aktifnya.
 */
function candidates(menus: MenuItem[]): MenuItem[] {
  const flat = menus.flatMap((menu) => [menu, ...(menu.children ?? [])])
  return [...flat.filter(isLeaf), ...flat.filter((menu) => !isLeaf(menu))]
}

/**
 * Tentukan key menu yang harus menyala untuk halaman saat ini.
 *
 * Prioritas nama route dulu karena URL halaman turunan (`/purchase-orders/create`)
 * adalah SAUDARA dari URL menu induknya (`/purchase-orders/documents`), bukan
 * anaknya — jadi prefiks URL tidak akan pernah memetakannya dengan benar.
 * `route_name` menangani route utama, `active_routes` menangani route turunan
 * seperti create/show.
 *
 * Mengembalikan `null` kalau memang tidak ada menu yang mewakili halaman ini,
 * supaya tidak ada menu lain yang ikut ter-highlight secara keliru.
 */
export function resolveActiveMenuKey(
  menus: MenuItem[],
  path: string,
  routeName: string | null,
): string | null {
  const list = candidates(menus)

  if (routeName) {
    const byRoute = list.find(
      (menu) =>
        menu.route_name === routeName ||
        (menu.active_routes ?? []).includes(routeName),
    )
    if (byRoute) return byRoute.key
  }

  const current = normalize(path)

  const exact = list.find((menu) => normalize(menu.url) === current)
  if (exact) return exact.key

  // Prefix terpanjang menang: /purchase-orders/123 -> /purchase-orders/documents
  const byPrefix = list
    .filter((menu) => current.startsWith(`${normalize(menu.url)}/`))
    .sort((a, b) => normalize(b.url).length - normalize(a.url).length)[0]

  return byPrefix?.key ?? null
}