import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { ArrowUpDown, Building2, FileText, Receipt } from 'lucide-vue-next'
import { NButton } from 'naive-ui'
import { fmtDate, fmtRupiah } from './utils'
import type { ArCustomerRow, ArInvoiceRow } from '@/types/ar-controller'

/**
 * Header sortable yang dipakai berulang, sama pola dengan
 * `financeColumnsTable.ts` dan `procurementColumnsTable.ts` — helper memang
 * dibuat privat per file karena ketiganya tidak berbagi modul.
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

function codeCell(code: string) {
  return h('div', { class: 'flex items-center gap-2.5' }, [
    h(
      'div',
      { class: 'flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600' },
      h(Receipt, { class: 'h-4 w-4' }),
    ),
    h('span', { class: 'font-medium text-slate-700' }, code),
  ])
}

function rupiahCell(value: number, className = 'text-slate-800') {
  return h('div', { class: `text-right font-semibold ${className}` }, fmtRupiah(value))
}

function countCell(value: number) {
  return h('div', { class: 'text-right font-semibold text-slate-700' }, String(value))
}

/**
 * Umur invoice dari `transactions.aging_days`.
 *
 * Nol berarti belum jatuh tempo (dan untuk sekarang juga berarti belum ada
 * proses terjadwal yang mengisinya), jadi tidak ditampilkan sebagai "0 hari"
 * supaya tidak terbaca sebagai faktur telat sehari.
 */
function agingCell(days: number) {
  const tone =
    days <= 0
      ? 'bg-slate-100 text-slate-500'
      : days <= 30
        ? 'bg-amber-50 text-amber-700'
        : days <= 60
          ? 'bg-orange-50 text-orange-700'
          : days <= 90
            ? 'bg-rose-50 text-rose-700'
            : 'bg-red-100 text-red-700'

  const label = days <= 0 ? 'Belum tempo' : `${days} hari`

  return h(
    'span',
    { class: `inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${tone}` },
    label,
  )
}

function customerCell(name: string | null) {
  if (!name) {
    return h('span', { class: 'text-slate-400' }, '—')
  }

  return h('div', { class: 'flex items-center gap-2.5' }, [
    h(
      'div',
      { class: 'flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600' },
      h(Building2, { class: 'h-4 w-4' }),
    ),
    h('span', { class: 'font-medium text-slate-700' }, name),
  ])
}

function textCell(value: string | null, fallback = '—') {
  return h('span', { class: 'text-slate-500' }, value || fallback)
}

function badgeCell(label: string, className: string) {
  return h(
    'span',
    { class: `inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${className}` },
    label,
  )
}

/** Faktur yang masih punya sisa tagihan, diurutkan dari yang paling lama jatuh tempo. */
export const arInvoiceColumns: ColumnDef<ArInvoiceRow>[] = [
  {
    accessorKey: 'customer',
    header: sortableHeader('Pelanggan'),
    cell: ({ row }) => customerCell(row.getValue('customer')),
  },
  {
    accessorKey: 'transaction_code',
    header: sortableHeader('No. Faktur'),
    cell: ({ row }) => codeCell(row.getValue('transaction_code')),
  },
  {
    accessorKey: 'created_at',
    header: sortableHeader('Terbit'),
    cell: ({ row }) => textCell(row.getValue('created_at') ? fmtDate(row.getValue('created_at')) : null),
  },
  {
    accessorKey: 'due_date',
    header: sortableHeader('Jatuh Tempo'),
    cell: ({ row }) => {
      const due = row.getValue('due_date')
      return h('span', { class: 'text-slate-500' }, due ? fmtDate(due as string) : '—')
    },
  },
  {
    accessorKey: 'aging_days',
    header: sortableHeader('Umur'),
    cell: ({ row }) => agingCell(row.getValue('aging_days')),
  },
  {
    accessorKey: 'payment_count',
    header: sortableHeader('Pembayaran', 'right'),
    cell: ({ row }) => {
      const count: number = row.getValue('payment_count')
      return h(
        'span',
        { class: 'text-right text-slate-500' },
        count > 0 ? `${count}x` : '—',
      )
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

/** Data customer beserta piutang yang melekat padanya. */
export const arCustomerColumns: ColumnDef<ArCustomerRow>[] = [
  {
    accessorKey: 'name',
    header: sortableHeader('Pelanggan'),
    cell: ({ row }) => customerCell(row.getValue('name')),
  },
  {
    accessorKey: 'segment',
    header: sortableHeader('Segment'),
    cell: ({ row }) => {
      const segment = row.getValue('segment')
      return segment
        ? badgeCell(segment as string, 'bg-slate-100 text-slate-600')
        : h('span', { class: 'text-slate-400' }, '—')
    },
  },
  {
    accessorKey: 'term_payment',
    header: sortableHeader('Termin'),
    cell: ({ row }) => {
      const days: number = row.getValue('term_payment')
      return h(
        'span',
        { class: 'text-slate-500' },
        days > 0 ? `${days} hari` : 'Tunai',
      )
    },
  },
  {
    accessorKey: 'invoice_count',
    header: sortableHeader('Faktur', 'right'),
    cell: ({ row }) => countCell(row.getValue('invoice_count')),
  },
  {
    accessorKey: 'total',
    header: sortableHeader('Total', 'right'),
    cell: ({ row }) => rupiahCell(row.getValue('total')),
  },
  {
    accessorKey: 'paid',
    header: sortableHeader('Terbayar', 'right'),
    cell: ({ row }) => rupiahCell(row.getValue('paid'), 'text-emerald-600'),
  },
  {
    accessorKey: 'outstanding',
    header: sortableHeader('Piutang', 'right'),
    cell: ({ row }) => {
      const value: number = row.getValue('outstanding')
      return rupiahCell(value, value > 0 ? 'text-rose-600' : 'text-slate-400')
    },
  },
  {
    accessorKey: 'overdue_count',
    header: sortableHeader('Terlambat', 'right'),
    cell: ({ row }) => {
      const count: number = row.getValue('overdue_count')
      return count > 0
        ? badgeCell(`${count} faktur`, 'bg-rose-50 text-rose-600')
        : h('span', { class: 'text-slate-400' }, '—')
    },
  },
]
