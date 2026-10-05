<?php

namespace App\Modules\Approval\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GetPurchaseOrderApprovalQueueQuery
{
    /**
     * Antrean approval Purchase Order untuk satu role.
     *
     * Sumber "langkah approval terakhir yang harus diproses" diambil dari view
     * `v_get_purchase_orders_with_approval`, yang sudah menghitungnya dengan
     * `ROW_NUMBER()`: langkah `PENDING` dengan `order` terkecil, atau langkah
     * decided terakhir kalau tidak ada yang menunggu. View ini tidak ditulis
     * ulang di sini supaya PO Index, dashboard Finance, dan halaman ini tidak
     * punya beberapa definisi "current step" yang bisa berbeda.
     *
     * Antrean ini murni berisi dokumen yang **gilirannya milik role pemohon**:
     * langkah yang sedang berjalan milik role ini dan masih `PENDING`. Begitu
     * sebuah langkah diputuskan, dokumen langsung hilang dari list role tersebut
     * dan otomatis tampil di list role pemilik langkah berikutnya — jadi tidak
     * ada duplikasi antrean dan tidak ada dokumen "sudah diproses" yang menggantung
     * di list orang yang sudah selesai menilai.
     *
     * Konsekuensinya tab "Telah Diproses" dihapus: tidak ada lagi isi yang bisa
     * ditampilkan di sana. Riwayat keputusan tetap bisa dibaca di halaman detail
     * PO lewat `GetApprovalDecisionContextQuery`.
     *
     * `due_date`, `description`, dan `total_discount` di-join balik dari
     * `transactions` karena view tidak mengexpos kolom itu, dan menambahkannya ke
     * view berarti membuat ulang definisi view lewat migration.
     */
    public function execute(string $roleName, array $filter = []): LengthAwarePaginator
    {
        $actionable = $this->actionableExpression($roleName);

        $query = DB::table('v_get_purchase_orders_with_approval as v')
            ->join('transactions as tx', 'tx.id', '=', 'v.id')
            ->where('v.transaction_type', 'PO')
            ->whereRaw($actionable[0], $actionable[1]);

        if (($search = trim((string) ($filter['search'] ?? ''))) !== '') {
            // `details` di view berupa JSON array dari JSON_ARRAYAGG, jadi
            // pencarian nama cukup mencocokkan isi JSON-nya. Kode dokumen tetap
            // lewat kolom biasa supaya bisa memakai indeks.
            $query->where(function ($inner) use ($search) {
                $inner->where('v.transaction_code', 'like', "%{$search}%")
                    ->orWhere('v.details', 'like', "%{$search}%");
            });
        }

        if (! empty($filter['date_from'])) {
            $query->whereDate('v.created_at', '>=', $filter['date_from']);
        }

        if (! empty($filter['date_to'])) {
            $query->whereDate('v.created_at', '<=', $filter['date_to']);
        }

        $paginator = $query
            ->select([
                'v.id',
                'v.transaction_code',
                'v.transaction_type',
                'v.payment_term',
                'v.created_at',
                'v.current_approval_order',
                'v.current_approval_status',
                'v.current_approval_role',
                'v.current_approval_sub_role',
                'v.current_approval_proceed_by',
                'v.current_approval_proceed_at',
                'v.current_approval_description',
                'v.details',
                'v.sub_total',
                'v.tax_amount',
                'v.grand_total',
                'tx.due_date',
                'tx.description',
                'tx.total_discount',
            ])
            ->selectRaw($actionable[0].' as can_decide', $actionable[1])
            ->orderByDesc('v.created_at')
            ->paginate(20)
            ->withQueryString();

        $paginator->getCollection()->transform(fn ($row) => $this->transform($row));

        return $paginator;
    }

    /**
     * Langkah yang sedang berjalan milik role ini dan belum diputuskan.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function actionableExpression(string $roleName): array
    {
        return [
            '(v.current_approval_role = ? and v.current_approval_status = ?)',
            [$roleName, 'PENDING'],
        ];
    }

    private function transform(object $row): object
    {
        $details = json_decode((string) $row->details, true);

        $row->supplier = $this->detailValue($details, 'SUPPLIER');
        $row->pic_name = $this->detailValue($details, 'PIC_NAME');
        $row->can_decide = (bool) $row->can_decide;
        $row->current_approval_order = (int) $row->current_approval_order;
        $row->sub_total = (float) $row->sub_total;
        $row->tax_amount = (float) $row->tax_amount;
        $row->grand_total = (float) $row->grand_total;
        $row->total_discount = (float) $row->total_discount;

        // `details` sudah dipakai untuk pencarian di SQL; frontend hanya butuh
        // pieces yang dipakai tabel.
        unset($row->details);

        return $row;
    }

    private function detailValue(mixed $details, string $type): ?string
    {
        foreach (is_array($details) ? $details : [] as $detail) {
            if (is_array($detail) && ($detail['type'] ?? null) === $type) {
                return $detail['value'] !== null ? (string) $detail['value'] : null;
            }
        }

        return null;
    }
}
