<?php

namespace App\Modules\PurchaseOrder\Actions;

use App\Enum\TransactionType;
use App\Models\Transaction;
use App\Modules\Approval\DTOs\DecideApprovalDTO;
use App\Modules\PurchaseOrder\DTOs\RevisePurchaseOrderDTO;
use App\Modules\PurchaseOrder\Repositories\PurchaseOrderRepository;
use App\Modules\PurchaseOrder\Services\PurchaseOrderCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Menerapkan revisi pada Purchase Order yang sedang menunggu perbaikan, lalu
 * memulai ulang rantai approval-nya.
 *
 * Lima hal yang dijamin action ini, dan semuanya alasannya:
 *
 *  1. **Nomor PO tidak berubah.** `transaction_code` tidak pernah ditulis di
 *     sini. Nomor itu identitas dokumen; menggantinya saat revisi membuat
 *     approval lama dan jejak auditnya menggantung.
 *
 *  2. **Nominal dihitung ulang di server.** Sama seperti saat create, semua
 *     total berasal dari `PurchaseOrderCalculator`, tidak pernah dari kiriman
 *     browser. Kalau tidak, `transactions.grand_total` bisa tidak cocok dengan
 *     `Σ transaction_items.total_price` dan approve meloloskan angka palsu.
 *
 *  3. **Keputusan lama tidak hilang.** Baris `transaction_approvals` dipindah
 *     ke `transaction_approval_histories` sebelum dihapus. Kalau hanya
 *     `delete`, pertanyaan paling penting saat audit — "kenapa PO ini sudah
 *     direvisi tiga kali, dan siapa yang meminta?" — tidak punya sumber data.
 *
 *  4. **Approval dimulai dari nol.** Rantai baru dimulai lagi dari Finance
 *     (`order` 1), bukan dilanjutkan dari Marketing. PO yang berubah total
 *     belum pernah dilihat Finance dalam bentuk barunya.
 *
 *  5. **Cuma satu orang boleh revising.** Yaitu `created_by`. Approver tidak
 *     boleh memperbaiki dokumen di antrean approve-nya sendiri.
 *
 * Catatan yang disengaja belum dikerjakan: kuota `trade_promo` yang terpakai
 * tidak dikembalikan saat PO direvisi, sehingga kuota tetap terpakai oleh baris
 * lama yang sudah dihapus. Ini sudah disepakati ditunda; perbaikannya butuh
 * keputusan bisnis soal apakah kuota diikat pada PO atau pada transporter, jadi
 * tidak bisa asal diimplementasikan di sini.
 */
class RevisePurchaseOrderAction
{
    public function __construct(
        private readonly PurchaseOrderCalculator $calculator,
        private readonly PurchaseOrderRepository $purchase_orders,
    ) {}

    /**
     * @throws AuthorizationException kalau `$actorId` bukan pembuat PO.
     * @throws RuntimeException kalau dokumen bukan PO, sudah disetujui penuh,
     *                          atau sedang berjalan di rantai approval.
     */
    public function execute(int $transactionId, RevisePurchaseOrderDTO $dto, int $actorId): Transaction
    {
        return DB::transaction(function () use ($transactionId, $dto, $actorId) {
            $transaction = Transaction::query()
                ->where('id', $transactionId)
                ->lockForUpdate()
                ->first();

            if ($transaction === null || $transaction->transaction_type !== TransactionType::PurchaseOrder->value) {
                throw new RuntimeException('Purchase order tidak ditemukan.');
            }

            // Diperiksa sebelum cek status: permintaan dari user lain harus
            // tetap dibalas 403, bukan "dokumennya tidak bisa direvisi" yang
            // membocorkan kondisi dokumen target.
            if ((int) $transaction->created_by !== $actorId) {
                throw new AuthorizationException('Hanya pembuat purchase order yang boleh merevisinya.');
            }

            $approvals = $this->currentApprovals($transactionId);

            if ($approvals->isEmpty()) {
                throw new RuntimeException('Purchase order ini tidak memiliki alur approval.');
            }

            $this->guardRevisable($approvals);

            $this->archiveApprovals($transactionId, $approvals);
            DB::table('transaction_approvals')->where('transaction_id', $transactionId)->delete();

            $totals = $this->calculator->compute($dto);

            // `transaction_code` sengaja tidak ada di daftar ini.
            $transaction->update([
                'payment_term' => $dto->term_of_payment,
                'due_date' => $dto->due_date,
                'description' => $dto->description,
                'sub_total' => $totals['sub_total'],
                'total_discount' => $totals['total_discount'],
                'tax_amount' => $totals['tax_amount'],
                'grand_total' => $totals['grand_total'],
            ]);

            $this->replaceDetails($transactionId, $dto);
            $this->replaceItems($transactionId, $totals['lines']);

            $this->purchase_orders->generateApprovals($transactionId);

            return $transaction;
        });
    }

    /**
     * @return Collection<int, object>
     */
    private function currentApprovals(int $transactionId)
    {
        return DB::table('transaction_approvals')
            ->where('transaction_id', $transactionId)
            ->orderBy('order')
            ->get();
    }

    /**
     * Revisi hanya sah kalau dokumen sedang berhenti di `NEED_REVISION`.
     *
     * Kalau dokumen masih `PENDING`, approval-nya sedang berjalan dan belum
     * ada yang meminta perubahan — merevisi sekarang berarti mencabut keputusan
     * yang sedang diproses role lain. Kalau semua langkah sudah `APPROVED`, PO-nya
     * selesai dan tidak punya form revisi.
     */
    private function guardRevisable($approvals): void
    {
        if ($approvals->contains(fn ($approval) => DecideApprovalDTO::isTerminal($approval->status))) {
            return;
        }

        $pending = $approvals->firstWhere('status', 'PENDING');

        if ($pending !== null) {
            throw new RuntimeException(
                'Purchase order ini belum ditandai perlu revisi. Tunggu approval berjalan atau minta revisi dari approver.'
            );
        }

        if ($approvals->every(fn ($approval) => $approval->status === DecideApprovalDTO::STATUS_APPROVED)) {
            throw new RuntimeException('Purchase order ini sudah disetujui penuh dan tidak bisa direvisi.');
        }

        throw new RuntimeException('Status approval purchase order ini tidak dikenali.');
    }

    /**
     * Salin keputusan lama ke arsip sebelum dihapus.
     *
     * `source_approval_id` disimpan walau baris aslinya sudah hilang: sebagai
     * penanda bahwa baris ini berasal dari rantai sebelumnya, bukan dari
     * rantai yang sedang berjalan. Foreign key sengaja tidak dipasang ke
     * `transaction_approvals` supaya arsip tetap utuh walau baris aslinya
     * dihapus manual.
     */
    private function archiveApprovals(int $transactionId, $approvals): void
    {
        DB::table('transaction_approval_histories')->insert(
            $approvals->map(fn ($approval) => [
                'transaction_id' => $transactionId,
                'order' => (int) $approval->order,
                'role_id' => $approval->role_id,
                'sub_role_id' => $approval->sub_role_id,
                'status' => $approval->status,
                'description' => $approval->description,
                'proceed_by' => $approval->proceed_by,
                'proceed_at' => $approval->proceed_at,
                'source_approval_id' => $approval->id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );
    }

    private function replaceDetails(int $transactionId, RevisePurchaseOrderDTO $dto): void
    {
        DB::table('transaction_details')->where('transaction_id', $transactionId)->delete();

        DB::table('transaction_details')->insert(
            collect($dto->details)->map(fn ($detail) => [
                'transaction_id' => $transactionId,
                'name' => $detail->name,
                'type' => $detail->type,
                // Kolom `value` bertipe string; bool dan angka harus dibungkus
                // supaya tidak terpotong jadi "1".
                'value' => is_bool($detail->value)
                    ? ($detail->value ? 'true' : 'false')
                    : (string) $detail->value,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function replaceItems(int $transactionId, array $lines): void
    {
        DB::table('transaction_items')->where('transaction_id', $transactionId)->delete();

        if ($lines === []) {
            return;
        }

        DB::table('transaction_items')->insert(
            collect($lines)->map(fn ($line) => [
                'transaction_id' => $transactionId,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'base_price' => $line['base_price'],
                'total_price' => $line['total_price'],
                'trade_promo_id' => $line['trade_promo_id'],
                'promo_product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );
    }
}
