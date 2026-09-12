import type { MenuItem } from '@/types/menu'

/** Susun parent/child dari list flat, buang yang inactive. */
export function buildMenuTree(items: MenuItem[]): MenuItem[] {
  const active = items.filter((i) => i.is_active)
  const byId = new Map<number, MenuItem>()
  active.forEach((i) => byId.set(i.id, { ...i, children: [] }))

  const roots: MenuItem[] = []
  byId.forEach((node) => {
    if (node.parent_id != null && byId.has(node.parent_id)) {
      byId.get(node.parent_id)!.children!.push(node)
    } else {
      roots.push(node)
    }
  })
  return roots
}