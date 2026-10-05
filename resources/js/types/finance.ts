/**
 * Tipe data untuk dashboard finance.
 *
 * Field mengikuti hasil query di `app/Modules/Finance` — backend sudah mengubah
 * nilai numerik dari string DB menjadi number, jadi tidak adaparsing di frontend.
 */

export type TransactionTypeCode = string;

export interface FinanceSummary {
  pending_approval: {
    count: number;
    value: number;
    by_type: Record<TransactionTypeCode, number>;
  };
  invoice: {
    count: number;
    total: number;
    paid: number;
    outstanding: number;
  };
  promo_claim: {
    count: number;
    total: number;
  };
}

export interface PendingApproval {
  approval_id: number;
  approval_order: number;
  transaction_id: number;
  transaction_code: string;
  transaction_type: TransactionTypeCode;
  grand_total: number;
  created_at: string;
  requested_by: string | null;
}

export interface ApprovalHistory {
  approval_id: number;
  approval_order: number;
  status: string;
  description: string | null;
  proceed_at: string | null;
  transaction_id: number;
  transaction_code: string;
  transaction_type: TransactionTypeCode;
  grand_total: number;
  proceed_by: string | null;
}

export interface InvoiceRow {
  transaction_id: number;
  transaction_code: string;
  created_at: string;
  due_date: string | null;
  sub_total: number;
  tax_amount: number;
  grand_total: number;
  paid_amount: number;
  outstanding_amount: number;
}

export interface PromoUsageRow {
  receiving_item_id: number;
  receiving_id: number;
  received_at: string;
  receiving_status: string;
  transaction_id: number;
  transaction_code: string;
  vendor_name: string | null;
  product_name: string;
  product_code: string;
  trade_promo_id: number;
  trade_promo_name: string;
  ordered_qty: number;
  received_qty: number;
  line_total: number;
}

export interface PromoClaimRow {
  id: number;
  claim_number: string;
  month: string;
  distributor_name: string;
  area: string | null;
  program: string | null;
  sub_total: number;
  grand_total: number;
}