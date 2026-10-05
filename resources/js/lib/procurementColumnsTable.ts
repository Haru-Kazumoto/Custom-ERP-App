import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowUpDown, FileText, Truck, Plane, Ship } from 'lucide-vue-next'
import { NButton } from 'naive-ui'
import { fmtDate, fmtRupiah } from './utils'

interface RecentDocument {
  id: number
  date: string // ISO date dari DB
  supplier: string
  shipping: 'land' | 'air' | 'sea'
  total: number // Rupiah
}

// Hanya dipakai tabel dashboard Procurement, jadi warna brand hijau
// (default Naive UI) ditaruh langsung di sini. Mode pengiriman tetap
// dibedakan oleh ikon + label, bukan hanya warna.
const shippingMeta = {
  land: { label: 'Darat', icon: Truck, class: 'bg-[#18a058] text-white' },
  air: { label: 'Udara', icon: Plane, class: 'bg-violet-50 text-violet-600' },
  sea: { label: 'Laut', icon: Ship, class: 'bg-amber-50 text-amber-600' },
} as const

// Header tombol sortable yang dipakai berulang.
function sortableHeader(label: string, align: 'left' | 'right' = 'left') {
  return ({ column }: any) =>
    h(
      NButton,
      {
        ghost: true,
        class: `-ml-3 h-8 text-xs font-medium text-slate-400 hover:text-slate-600 ${align === 'right' ? 'ml-auto flex' : ''}`,
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      },
      () => [label, h(ArrowUpDown, { class: 'ml-1.5 h-3.5 w-3.5' })],
    )
}

export const columns: ColumnDef<RecentDocument>[] = [
  {
    accessorKey: 'date',
    header: sortableHeader('Tanggal'),
    cell: ({ row }) =>
      h('span', { class: 'text-slate-500' }, fmtDate(row.getValue('date'))),
  },
  {
    accessorKey: 'supplier',
    header: sortableHeader('Supplier'),
    cell: ({ row }) =>
      h('div', { class: 'flex items-center gap-2.5' }, [
        h(
          'div',
          { class: 'flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-400' },
          h(FileText, { class: 'h-4 w-4' }),
        ),
        h('span', { class: 'font-medium text-slate-700' }, row.getValue('supplier')),
      ]),
  },
  {
    accessorKey: 'shipping',
    header: () => h('span', { class: 'text-xs font-medium text-slate-400' }, 'Pengiriman'),
    cell: ({ row }) => {
      const meta = shippingMeta[row.getValue('shipping') as keyof typeof shippingMeta]
      return h(
        'span',
        { class: `inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ${meta.class}` },
        [h(meta.icon, { class: 'h-3.5 w-3.5' }), meta.label],
      )
    },
  },
  {
    accessorKey: 'total',
    header: sortableHeader('Total', 'right'),
    cell: ({ row }) =>
      h(
        'div',
        { class: 'text-right font-semibold text-slate-800' },
        fmtRupiah(row.getValue('total')),
      ),
  },
]