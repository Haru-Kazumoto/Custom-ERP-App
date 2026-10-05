import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowUpDown, BadgeCheck, BadgePercent, Building2, FileText, Receipt, Wallet } from 'lucide-vue-next'
import { NButton } from 'naive-ui'
import { fmtDate, fmtRupiah } from './utils'
import { normalizeApproval } from '@/utils/approval'
import type {
  ApprovalHistory,
  InvoiceRow,
  PendingApproval,
  PromoClaimRow,
  PromoUsageRow,
} from '@/types/finance'

/**
 * Label + warna per tipe dokumen.
 *
 * Kode diambil apa adanya dari kolom `transactions.transaction_type`. Kode yang
 * belum pernah dipakai sistem (misalnya DO yang modulnya belum ada) tetap
 * ditampilkan lewat `raw`, bukan dibuang — user perlu tahu dokumen apa yang
 * menunggu, bukan melihat tabel kosong tanpa penjelasan.
 */
export const transactionTypeMeta: Record<string, { label: string; className: string }> = {
  PO: { label: 'Purchase Order', className: 'bg-sky-50 text-sky-700' },
  DO: { label: 'Delivery Order', className: 'bg-violet-50 text-violet-700' },
  DELIVERY_ORDER: { label: 'Delivery Order', className: 'bg-violet-50 text-violet-700' },
  INV: { label: 'Faktur', className: 'bg-amber-50 text-amber-700' },
  CO: { label: 'Sales Order', className: 'bg-emerald-50 text-emerald-700' },
  SSO: { label: 'Sub Sales Order', className: 'bg-teal-50 text-teal-700' },
}

export function transactionTypeBadge(code?: string | null) {
  const key = (code ?? '').toUpperCase()
  const meta = transactionTypeMeta[key]
  return {
    label: meta?.label ?? (key || '—'),
    className: meta?.className ?? 'bg-slate-100 text-slate-600',
  }
}

/** Peta status approval ke kelas Tailwind (label diambil dari utils/approval). */
const approvalTone: Record<string, string> = {
  emerald: 'bg-emerald-50 text-emerald-700',
  amber: 'bg-amber-50 text-amber-700',
  red: 'bg-rose-50 text-rose-600',
  violet: 'bg-violet-50 text-violet-700',
  slate: 'bg-slate-100 text-slate-500',
}

function approvalBadge(status?: string | null) {
  const meta = normalizeApproval(status)
  return { label: meta.label, className: approvalTone[meta.tone] ?? approvalTone.slate }
}

/**
 * Header sortable yang dipakai berulang, sama pola `procurementColumnsTable.ts`
 * supaya tabel dashboard lain dan ini tidak terlihat berbeda.
 */
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

function plainHeader(label: string, align: 'left' | 'right' = 'left') {
  return () =>
    h(
      'span',
      { class: `text-xs font-medium text-slate-400 ${align === 'right' ? 'float-right' : ''}` },
      label,
    )
}

function pill(label: string, className: string, icon?: any) {
  return h(
    'span',
    { class: `inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ${className}` },
    icon ? [h(icon, { class: 'h-3.5 w-3.5' }), label] : [label],
  )
}

function codeCell(code: string) {
  return h(
    'div',
    { class: 'flex items-center gap-2.5' },
    [
      h(
        'div',
        { class: 'flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-400' },
        h(FileText, { class: 'h-4 w-4' }),
      ),
      h('span', { class: 'font-medium text-slate-700' }, code),
    ],
  )
}

function rupiahCell(value: number, className = 'text-slate-800') {
  return h('div', { class: `text-right font-semibold ${className}` }, fmtRupiah(value))
}

/**
 * Antrean approval finance — satu tabel untuk Purchase Order, Delivery Order,
 * dan faktur karena ketiganya satu antrean persetujuan yang sama. Tipe dokumen
 * dibedakan lewat badge, bukan lewat tabel terpisah.
 */
export const approvalQueueColumns: ColumnDef<PendingApproval>[] = [
  {
    accessorKey: 'transaction_code',
    header: sortableHeader('Dokumen'),
    cell: ({ row }) => codeCell(row.getValue('transaction_code')),
  },
  {
    accessorKey: 'transaction_type',
    header: sortableHeader('Tipe'),
    cell: ({ row }) => {
      const meta = transactionTypeBadge(row.getValue('transaction_type'))
      return pill(meta.label, meta.className)
    },
  },
  {
    accessorKey: 'created_at',
    header: sortableHeader('Tanggal'),
    cell: ({ row }) =>
      h('span', { class: 'text-slate-500' }, fmtDate(row.getValue('created_at'))),
  },
  {
    accessorKey: 'requested_by',
    header: sortableHeader('Diajukan'),
    cell: ({ row }) =>
      h('span', { class: 'text-slate-500' }, row.getValue('requested_by') ?? '—'),
  },
  {
    accessorKey: 'grand_total',
    header: sortableHeader('Nilai', 'right'),
    cell: ({ row }) => rupiahCell(row.getValue('grand_total')),
  },
  {
    accessorKey: 'approval_order',
    header: plainHeader('Langkah'),
    cell: ({ row }) =>
      h('span', { class: 'text-xs text-slate-400' }, `Ke-${row.getValue('approval_order')}`),
  },
]

export const approvalHistoryColumns: ColumnDef<ApprovalHistory>[] = [
  {
    accessorKey: 'transaction_code',
    header: sortableHeader('Dokumen'),
    cell: ({ row }) => codeCell(row.getValue('transaction_code')),
  },
  {
    accessorKey: 'transaction_type',
    header: sortableHeader('Tipe'),
    cell: ({ row }) => {
      const meta = transactionTypeBadge(row.getValue('transaction_type'))
      return pill(meta.label, meta.className)
    },
  },
  {
    accessorKey: 'status',
    header: sortableHeader('Status'),
    cell: ({ row }) => {
      const meta = approvalBadge(row.getValue('status'))
      return pill(meta.label, meta.className, BadgeCheck)
    },
  },
  {
    accessorKey: 'proceed_by',
    header: sortableHeader('Diproses'),
    cell: ({ row }) =>
      h('span', { class: 'text-slate-500' }, row.getValue('proceed_by') ?? '—'),
  },
  {
    accessorKey: 'proceed_at',
    header: sortableHeader('Waktu'),
    cell: ({ row }) => {
      const at = row.getValue('proceed_at')
      return h('span', { class: 'text-slate-500' }, at ? fmtDate(at) : '—')
    },
  },
  {
    accessorKey: 'grand_total',
    header: sortableHeader('Nilai', 'right'),
    cell: ({ row }) => rupiahCell(row.getValue('grand_total')),
  },
]

export const invoiceColumns: ColumnDef<InvoiceRow>[] = [
  {
    accessorKey: 'transaction_code',
    header: sortableHeader('No. Faktur'),
    cell: ({ row }) => {
      const code = row.getValue('transaction_code')
      return h(
        'div',
        { class: 'flex items-center gap-2.5' },
        [
          h(
            'div',
            { class: 'flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600' },
            h(Receipt, { class: 'h-4 w-4' }),
          ),
          h('span', { class: 'font-medium text-slate-700' }, code),
        ],
      )
    },
  },
  {
    accessorKey: 'created_at',
    header: sortableHeader('Diterbitkan'),
    cell: ({ row }) =>
      h('span', { class: 'text-slate-500' }, fmtDate(row.getValue('created_at'))),
  },
  {
    accessorKey: 'due_date',
    header: sortableHeader('Jatuh Tempo'),
    cell: ({ row }) => {
      const due = row.getValue('due_date')
      return h('span', { class: 'text-slate-500' }, due ? fmtDate(due) : '—')
    },
  },
  {
    accessorKey: 'grand_total',
    header: sortableHeader('Total', 'right'),
    cell: ({ row }) => rupiahCell(row.getValue('grand_total')),
  },
  {
    accessorKey: 'paid_amount',
    header: sortableHeader('Terbayar', 'right'),
    cell: ({ row }) => rupiahCell(row.getValue('paid_amount'), 'text-emerald-600'),
  },
  {
    accessorKey: 'outstanding_amount',
    header: sortableHeader('Sisa', 'right'),
    cell: ({ row }) => {
      const value: number = row.getValue('outstanding_amount')
      return rupiahCell(value, value > 0 ? 'text-rose-600' : 'text-slate-400')
    },
  },
]

export const promoUsageColumns: ColumnDef<PromoUsageRow>[] = [
  {
    accessorKey: 'transaction_code',
    header: sortableHeader('Dokumen'),
    cell: ({ row }) => codeCell(row.getValue('transaction_code')),
  },
  {
    accessorKey: 'received_at',
    header: sortableHeader('Diterima'),
    cell: ({ row }) =>
      h('span', { class: 'text-slate-500' }, fmtDate(row.getValue('received_at'))),
  },
  {
    accessorKey: 'vendor_name',
    header: sortableHeader('Pemasok'),
    cell: ({ row }) => {
      const vendor = row.getValue('vendor_name')
      return h(
        'div',
        { class: 'flex items-center gap-2.5' },
        [
          h(
            'div',
            { class: 'flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-400' },
            h(Building2, { class: 'h-4 w-4' }),
          ),
          h('span', { class: 'font-medium text-slate-700' }, vendor ?? '—'),
        ],
      )
    },
  },
  {
    accessorKey: 'trade_promo_name',
    header: sortableHeader('Promo'),
    cell: ({ row }) => pill(row.getValue('trade_promo_name'), 'bg-violet-50 text-violet-700', BadgePercent),
  },
  {
    accessorKey: 'product_name',
    header: sortableHeader('Produk'),
    cell: ({ row }) =>
      h(
        'div',
        null,
        [
          h('div', { class: 'font-medium text-slate-700' }, row.getValue('product_name')),
          h('div', { class: 'text-xs text-slate-400' }, row.getValue('product_code')),
        ],
      ),
  },
  {
    accessorKey: 'received_qty',
    header: sortableHeader('Qty', 'right'),
    cell: ({ row }) => {
      const received: number = row.getValue('received_qty')
      const ordered: number = row.original.ordered_qty
      const short = received < ordered
      return h(
        'div',
        { class: 'text-right' },
        [
          h('div', { class: `font-semibold ${short ? 'text-amber-600' : 'text-slate-800'}` }, `${received} / ${ordered}`),
          h('div', { class: 'text-xs text-slate-400' }, 'terima / pesan'),
        ],
      )
    },
  },
  {
    accessorKey: 'line_total',
    header: sortableHeader('Nilai', 'right'),
    cell: ({ row }) => rupiahCell(row.getValue('line_total')),
  },
]

export const promoClaimColumns: ColumnDef<PromoClaimRow>[] = [
  {
    accessorKey: 'claim_number',
    header: sortableHeader('No. Klaim'),
    cell: ({ row }) => {
      const code = row.getValue('claim_number')
      return h(
        'div',
        { class: 'flex items-center gap-2.5' },
        [
          h(
            'div',
            { class: 'flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-400' },
            h(Wallet, { class: 'h-4 w-4' }),
          ),
          h('span', { class: 'font-medium text-slate-700' }, code),
        ],
      )
    },
  },
  {
    accessorKey: 'month',
    header: sortableHeader('Periode'),
    cell: ({ row }) => h('span', { class: 'text-slate-500' }, row.getValue('month') ?? '—'),
  },
  {
    accessorKey: 'program',
    header: sortableHeader('Program'),
    cell: ({ row }) => {
      const program = row.getValue('program')
      return program
        ? pill(program, 'bg-sky-50 text-sky-700')
        : h('span', { class: 'text-slate-400' }, '—')
    },
  },
  {
    accessorKey: 'distributor_name',
    header: sortableHeader('Distributor'),
    cell: ({ row }) =>
      h('span', { class: 'text-slate-500' }, row.getValue('distributor_name') ?? '—'),
  },
  {
    accessorKey: 'area',
    header: sortableHeader('Wilayah'),
    cell: ({ row }) => h('span', { class: 'text-slate-500' }, row.getValue('area') ?? '—'),
  },
  {
    accessorKey: 'grand_total',
    header: sortableHeader('Total', 'right'),
    cell: ({ row }) => rupiahCell(row.getValue('grand_total')),
  },
]