<?php

namespace App\Modules\Approval\Queries;

use App\Modules\Approval\DTOs\DecideApprovalDTO;
use Illuminate\Support\Facades\DB;

class GetApprovalDecisionContextQuery
{
    /**
     * Konteks keputusan approval untuk satu dokumen dari sisi role pemanggil.
     *
     * `can_decide` dihitung ulang dari `transaction_approvals` (bukan dari view)
     * supaya frontend tidak perlu menebak, dan supaya halaman detail memakai
     * sumber kebenaran yang sama dengan `DecideApprovalAction`.
     *
     * `sub_role_id` opsional: langkah ber-sub-role (rantai sales, marketing
     * DNP/DKU) hanya bisa diputuskan pemilik sub-role persis; langkah tanpa
     * sub-role terbuka untuk seluruh role-nya. PO tidak pernah mengirim
     * nilai ini dan perilakunya tidak berubah.
     */
    public function execute(int $transactionId, int $roleId, string $expectedType = 'PO', ?int $subRoleId = null): ?array
    {
        $transaction = DB::table('transactions as tx')
            ->select('tx.id', 'tx.transaction_code', 'tx.transaction_type')
            ->where('tx.id', $transactionId)
            ->first();

        if ($transaction === null || $transaction->transaction_type !== $expectedType) {
            return null;
        }

        $approvals = DB::table('transaction_approvals as ta')
            ->leftJoin('roles as r', 'r.id', '=', 'ta.role_id')
            ->leftJoin('sub_roles as sr', 'sr.id', '=', 'ta.sub_role_id')
            ->leftJoin('users as u', 'u.id', '=', 'ta.proceed_by')
            ->where('ta.transaction_id', $transactionId)
            ->orderBy('ta.order')
            ->get([
                'ta.id',
                'ta.order',
                'ta.role_id',
                'ta.sub_role_id',
                'ta.status',
                'ta.description',
                'ta.proceed_by',
                'ta.proceed_at',
                'r.name as role_name',
                'sr.name as sub_role_name',
                'u.name as proceed_by_name',
            ]);

        if ($approvals->isEmpty()) {
            return null;
        }

        // Keputusan terminal (perlu revisi) menutup rantai: langkah sesudahnya
        // tetap PENDING di database tapi tidak pernah boleh dijalankan, jadi
        // current step adalah langkah terminal itu, bukan PENDING berikutnya.
        $terminal = $approvals->first(fn ($approval) => DecideApprovalDTO::isTerminal($approval->status));

        $current = $terminal ?? $approvals->firstWhere('status', 'PENDING');

        $myDecision = $approvals->first(
            fn ($approval) => (int) $approval->role_id === $roleId && $approval->status !== 'PENDING'
        );

        $canDecide = $terminal === null
            && $current !== null
            && (int) $current->role_id === $roleId
            && ($current->sub_role_id === null
                || ($subRoleId !== null && (int) $current->sub_role_id === $subRoleId));

        return [
            'transaction_id' => (int) $transaction->id,
            'transaction_code' => $transaction->transaction_code,
            'steps' => $approvals->map(fn ($approval) => [
                'id' => (int) $approval->id,
                'order' => (int) $approval->order,
                'role_id' => (int) $approval->role_id,
                'role' => $approval->role_name ?? "",
                'sub_role' => $approval->sub_role_name,
                'status' => $approval->status,
                'description' => $approval->description,
                'proceed_by' => $approval->proceed_by_name,
                'proceed_at' => $approval->proceed_by !== null ? $approval->proceed_at : null,
            ])->all(),
            'current' => $current === null ? null : [
                'order' => (int) $current->order,
                'role' => $current->role_name,
                'sub_role' => $current->sub_role_name,
                'status' => $current->status,
            ],
            'can_decide' => $canDecide,
            'my_decision' => $myDecision === null ? null : [
                'status' => $myDecision->status,
                'description' => $myDecision->description,
                'proceed_at' => $myDecision->proceed_at,
            ],
            'revision_history' => $this->revisionHistory($transactionId),
        ];
    }

    /**
     * Riwayat approval yang sudah diarsipkan karena PO direvisi.
     *
     * `RevisePurchaseOrderAction` memindahkan baris `transaction_approvals` ke
     * `transaction_approval_histories` sebelum menggenerate ulang rantai dari
     * nol. Tanpa ini, keputusan "siapa yang meminta revisi dan alasannya" hilang
     * begitu PO diperbaiki — padahal itu jawaban dari pertanyaan "kenapa PO ini
     * direvisi?".
     */
    private function revisionHistory(int $transactionId): array
    {
        return DB::table('transaction_approval_histories as h')
            ->leftJoin('roles as r', 'r.id', '=', 'h.role_id')
            ->leftJoin('users as u', 'u.id', '=', 'h.proceed_by')
            ->where('h.transaction_id', $transactionId)
            ->orderBy('h.created_at')
            ->orderBy('h.order')
            ->get()
            ->map(fn ($row) => [
                'order' => (int) $row->order,
                'role' => $row->role_name ?? "",
                'status' => $row->status,
                'description' => $row->description,
                'proceed_by' => $row->proceed_by_name ?? "",
                'proceed_at' => $row->proceed_at,
                'archived_at' => $row->created_at,
            ])->all();
    }
}
