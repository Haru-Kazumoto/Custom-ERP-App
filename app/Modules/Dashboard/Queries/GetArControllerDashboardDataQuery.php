<?php

namespace App\Modules\Dashboard\Queries;

use App\Enum\TransactionType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class GetArControllerDashboardDataQuery
{
    /**
     * Data dashboard AR Controller: piutang, umur invoice, dan customer.
     *
     * Sumber piutang adalah dokumen faktur (`transactions.transaction_type`
     * = `INV`) yang pembayarannya dicatat bertahap di `invoice_payments` —
     * satu faktur boleh punya banyak baris pembayaran. Faktur dianggap lunas
     * bila total `paid_amount` sudah menutup `grand_total`, jadi sisa tagihan
     * selalu dihitung per baris, bukan dari status yang disimpan di mana pun
     * (kolom lunas tidak ada di skema).
     *
     * Umur invoice dibaca dari `transactions.aging_days`. Kolom itu belum
     * terisi otomatis (masih nol semua) karena pengisiannya dijadwalkan lewat
     * cron job terpisah; tugas query ini hanya mengelompokkan angka yang ada
     * supaya tampilan aging tidak perlu diubah lagi saat cron-nya menyala.
     *
     * Pelanggan tidak punya foreign key di `transactions` — identitasnya hanya
     * ada di `transaction_details` sebagai nama (`type = 'CUSTOMER'`), jadi
     * dipakai subquery MAX(value) per transaksi, sama seperti yang dilakukan
     * daftar dokumen dan antrean approval.
     */
    public function execute(): array
    {
        return [
            'summary' => $this->summary(),
            'aging' => $this->aging(),
            'invoices' => $this->invoices(),
            'customers' => $this->customers(),
        ];
    }

    private function summary(): array
    {
        $row = (clone $this->base())
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('COALESCE(SUM(tx.grand_total), 0) as invoice_total')
            ->selectRaw('COALESCE(SUM(ip.paid_amount), 0) as paid_total')
            ->selectRaw('COALESCE(SUM(GREATEST(tx.grand_total - COALESCE(ip.paid_amount, 0), 0)), 0) as outstanding_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN tx.grand_total > COALESCE(ip.paid_amount, 0) THEN 1 ELSE 0 END), 0) as unpaid_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN tx.grand_total > COALESCE(ip.paid_amount, 0) AND COALESCE(tx.aging_days, 0) > 0 THEN GREATEST(tx.grand_total - COALESCE(ip.paid_amount, 0), 0) ELSE 0 END), 0) as overdue_value')
            ->selectRaw('COUNT(DISTINCT cust.customer_name) as customer_count')
            ->first();

        return [
            'invoice_count' => (int) ($row->invoice_count ?? 0),
            'invoice_total' => (float) ($row->invoice_total ?? 0),
            'paid_total' => (float) ($row->paid_total ?? 0),
            'outstanding_total' => (float) ($row->outstanding_total ?? 0),
            'unpaid_count' => (int) ($row->unpaid_count ?? 0),
            'overdue_value' => (float) ($row->overdue_value ?? 0),
            'customer_count' => (int) ($row->customer_count ?? 0),
        ];
    }

    /**
     * Bucket umur invoice selalu dikirim lima, meski belum ada isinya, supaya
     * kartu aging di halaman tidak berubah bentuk saat data mulai masuk.
     *
     * Hanya faktur yang masih punya sisa tagihan yang masuk hitungan — faktur
     * lunas tidak boleh tetap duduk di bucket terlambat.
     */
    private function aging(): array
    {
        $rows = (clone $this->base())
            ->whereRaw('tx.grand_total > COALESCE(ip.paid_amount, 0)')
            ->selectRaw($this->bucketCase() . ' as bucket')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM(tx.grand_total - COALESCE(ip.paid_amount, 0)), 0) as value')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        return array_map(function (array $bucket) use ($rows) {
            $found = $rows->get($bucket['key']);

            return [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'count' => (int) ($found->total ?? 0),
                'value' => (float) ($found->value ?? 0),
            ];
        }, $this->bucketDefinitions());
    }

    private function invoices(): array
    {
        return (clone $this->base())
            ->whereRaw('tx.grand_total > COALESCE(ip.paid_amount, 0)')
            ->orderByRaw('tx.due_date IS NULL')
            ->orderBy('tx.due_date')
            ->orderBy('tx.created_at')
            ->limit(25)
            ->get([
                'tx.id as transaction_id',
                'tx.transaction_code',
                'tx.created_at',
                'tx.due_date',
                'tx.aging_days',
                'tx.grand_total',
                'ip.paid_amount',
                'ip.payment_count',
                'cust.customer_name',
            ])
            ->map(function ($row) {
                $grand_total = (float) $row->grand_total;
                $paid_amount = (float) ($row->paid_amount ?? 0);

                return [
                    'transaction_id' => (int) $row->transaction_id,
                    'transaction_code' => $row->transaction_code,
                    'created_at' => $row->created_at,
                    'due_date' => $row->due_date,
                    'aging_days' => (int) ($row->aging_days ?? 0),
                    'grand_total' => $grand_total,
                    'paid_amount' => $paid_amount,
                    'outstanding_amount' => max(0, $grand_total - $paid_amount),
                    'payment_count' => (int) ($row->payment_count ?? 0),
                    'customer' => $row->customer_name,
                ];
            })
            ->all();
    }

    /**
     * Daftar customer beserta piutangnya.
     *
     * Semua baris `customers` ikut tampil walaupun belum punya faktur (dengan
     * angka nol), karena halaman ini juga dipakai untuk memeriksa data customer
     * — bukan hanya yang sedang berutang.
     */
    private function customers(): array
    {
        $byCustomer = (clone $this->base())
            ->groupBy('cust.customer_name')
            ->selectRaw('cust.customer_name as customer_name')
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('COALESCE(SUM(tx.grand_total), 0) as total')
            ->selectRaw('COALESCE(SUM(ip.paid_amount), 0) as paid')
            ->selectRaw('COALESCE(SUM(GREATEST(tx.grand_total - COALESCE(ip.paid_amount, 0), 0)), 0) as outstanding')
            ->selectRaw('COALESCE(SUM(CASE WHEN tx.grand_total > COALESCE(ip.paid_amount, 0) AND COALESCE(tx.aging_days, 0) > 0 THEN 1 ELSE 0 END), 0) as overdue_count');

        return DB::table('customers as c')
            ->leftJoinSub($byCustomer, 'agg', 'agg.customer_name', '=', 'c.name')
            ->orderByRaw('COALESCE(agg.outstanding, 0) DESC')
            ->orderBy('c.name')
            ->limit(50)
            ->get([
                'c.id',
                'c.name',
                'c.segment',
                'c.term_payment',
                'agg.invoice_count',
                'agg.total',
                'agg.paid',
                'agg.outstanding',
                'agg.overdue_count',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'segment' => $row->segment,
                'term_payment' => (int) ($row->term_payment ?? 0),
                'invoice_count' => (int) ($row->invoice_count ?? 0),
                'total' => (float) ($row->total ?? 0),
                'paid' => (float) ($row->paid ?? 0),
                'outstanding' => (float) ($row->outstanding ?? 0),
                'overdue_count' => (int) ($row->overdue_count ?? 0),
            ])
            ->all();
    }

    /**
     * Faktur ⨝ total pembayaran ⨝ nama customer.
     *
     * Keduanya subquery ber-agregasi, bukan join langsung ke tabel baris,
     * supaya satu faktur tetap satu baris dan query aman di mode
     * `ONLY_FULL_GROUP_BY` — pola yang sama dipakai `GetInvoiceOverviewQuery`.
     */
    private function base(): Builder
    {
        return DB::table('transactions as tx')
            ->leftJoinSub($this->paymentsSub(), 'ip', 'ip.transaction_id', '=', 'tx.id')
            ->leftJoinSub($this->customerSub(), 'cust', 'cust.transaction_id', '=', 'tx.id')
            ->where('tx.transaction_type', TransactionType::Invoice->value);
    }

    private function paymentsSub(): Builder
    {
        return DB::table('invoice_payments')
            ->select('transaction_id')
            ->selectRaw('SUM(paid_amount) as paid_amount')
            ->selectRaw('COUNT(*) as payment_count')
            ->groupBy('transaction_id');
    }

    private function customerSub(): Builder
    {
        return DB::table('transaction_details')
            ->where('type', 'CUSTOMER')
            ->select('transaction_id')
            ->selectRaw('MAX(value) as customer_name')
            ->groupBy('transaction_id');
    }

    private function bucketCase(): string
    {
        return "CASE
            WHEN COALESCE(tx.aging_days, 0) <= 0 THEN 'not_due'
            WHEN tx.aging_days <= 30 THEN 'd1_30'
            WHEN tx.aging_days <= 60 THEN 'd31_60'
            WHEN tx.aging_days <= 90 THEN 'd61_90'
            ELSE 'd90_plus'
        END";
    }

    private function bucketDefinitions(): array
    {
        return [
            ['key' => 'not_due', 'label' => 'Belum jatuh tempo'],
            ['key' => 'd1_30', 'label' => '1 - 30 hari'],
            ['key' => 'd31_60', 'label' => '31 - 60 hari'],
            ['key' => 'd61_90', 'label' => '61 - 90 hari'],
            ['key' => 'd90_plus', 'label' => 'Lebih dari 90 hari'],
        ];
    }
}
