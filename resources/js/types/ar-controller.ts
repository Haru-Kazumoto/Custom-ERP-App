/**
 * Tipe data dashboard AR Controller.
 *
 * Field mengikuti hasil `GetArControllerDashboardDataQuery` — angka sudah
 * di-cast di backend, jadi frontend tinggal menampilkan.
 */

export interface ArDashboardSummary {
  invoice_count: number;
  invoice_total: number;
  paid_total: number;
  outstanding_total: number;
  unpaid_count: number;
  overdue_value: number;
  customer_count: number;
}

export type ArAgingKey = "not_due" | "d1_30" | "d31_60" | "d61_90" | "d90_plus";

export interface ArAgingBucket {
  key: ArAgingKey;
  label: string;
  count: number;
  value: number;
}

export interface ArInvoiceRow {
  transaction_id: number;
  transaction_code: string;
  created_at: string;
  due_date: string | null;
  aging_days: number;
  grand_total: number;
  paid_amount: number;
  outstanding_amount: number;
  payment_count: number;
  customer: string | null;
}

export interface ArCustomerRow {
  id: number;
  name: string;
  segment: string | null;
  term_payment: number;
  invoice_count: number;
  total: number;
  paid: number;
  outstanding: number;
  overdue_count: number;
}
