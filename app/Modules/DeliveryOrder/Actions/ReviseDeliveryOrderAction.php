<?php

namespace App\Modules\DeliveryOrder\Actions;

use App\Enum\TransactionType;
use App\Models\Transaction;
use App\Modules\Approval\DTOs\DecideApprovalDTO;
use App\Modules\DeliveryOrder\DTOs\CreateDeliveryOrderDTO;
use App\Modules\DeliveryOrder\Repositories\DeliveryOrderRepository;
use App\Modules\DeliveryOrder\Services\StockAllocator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Menerapkan revisi pada Delivery Order yang sedang menunggu perbaikan,
 * mengalokasikan ulang stok, lalu memulai rantai approval dari nol.
 *
 * Enam hal yang dijamin action ini, dan semuanya alasannya:
 *
 *  1. **Nomor DO tidak berubah.** `transaction_code` tidak pernah ditulis di
 *     sini. Nomor itu identitas dokumen; menggantinya saat revisi membuat
 *     approval lama dan jejak auditnya menggantung.
 *
 *  2. **Nominal dihitung ulang di server.** Sama seperti saat create, semua
 *     total berasal dari `DeliveryOrderCalculator`, tidak pernah dari
 *     kiriman browser. Kalau tidak, `transactions.grand_total` bisa tidak
 *     cocok dengan `Σ transaction_items.total_price` dan approve meloloskan
 *     angka palsu.
 *
 *  3. **Keputusan lama tidak hilang.** Baris `transaction_approvals` dipindah
 *     ke `transaction_approval_histories` sebelum dihapus. Kalau hanya
 *     `delete`, pertanyaan paling penting saat audit — "kenapa DO ini sudah
 *     direvisi, dan siapa yang meminta?" — tidak punya sumber data.
 *
 *  4. **Stok dialokasikan ulang untuk isi yang baru.** Mengganti
 *     `transaction_items` menghapus baris OUT lama lewat cascade foreign key
 *     (stok kembali), dan `StockAllocator` lalu mengeluarkan stok untuk
 *     barang revisi. Keduanya berada di satu transaksi database: kalau stok
 *     tidak mencukupi, dokumen lama beserta approval-nya ikut dipulihkan —
 *     tidak ada DO yang isinya sudah diganti tapi stoknya belum.
 *
 *  5. **Approval dimulai dari nol.** Rantai baru dibentuk ulang dari
 *     langkah pertama (rantai sales milik pembuat, bila ada), bukan
 *     dilanjutkan dari langkah yang tersisa. Approver belum pernah melihat
 *     isi revisi.
 *
 *  6. **Cuma satu orang boleh merevisi**, yaitu `created_by`. Approver tidak
 *     boleh memperbaiki dokumen di antrean approve-nya sendiri.
 */
class ReviseDeliveryOrderAction
{
    public function __construct(
        private readonly DeliveryOrderRepository $delivery_orders,
        private readonly StockAllocator $stock_allocator,
    ) {}

    /**
     * @param  bool  $needs_bd_approval  Ada baris harga/diskon manual di
     *                                   kiriman revisi — menyalakan langkah
     *                                   Business Development, sama seperti
     *                                   saat create.
     *
     * @throws AuthorizationException kalau `$actorId` bukan pembuat DO.
     * @throws RuntimeException kalau dokumen bukan DO, tidak punya alur
     *                          approval, sudah disetujui penuh, masih
     *                          berjalan, atau revisinya ditolak (harga/promo/
     *                          stok).
     */
    public function execute(
        int $transactionId,
        CreateDeliveryOrderDTO $dto,
        int $actorId,
        bool $needs_bd_approval,
    ): Transaction {
        return DB::transaction(function () use ($transactionId, $dto, $actorId, $needs_bd_approval) {
            $transaction = Transaction::query()
                ->where('id', $transactionId)
                ->lockForUpdate()
                ->first();

            if ($transaction === null || $transaction->transaction_type !== TransactionType::DeliveryOrder->value) {
                throw new RuntimeException('Delivery order tidak ditemukan.');
            }

            // Diperiksa sebelum cek status: permintaan dari user lain harus
            // tetap dibalas 403, bukan "dokumennya tidak bisa direvisi" yang
            // membocorkan kondisi dokumen target.
            if ((int) $transaction->created_by !== $actorId) {
                throw new AuthorizationException('Hanya pembuat delivery order yang boleh merevisinya.');
            }

            $approvals = $this->currentApprovals($transactionId);

            if ($approvals->isEmpty()) {
                throw new RuntimeException('Delivery order ini tidak memiliki alur approval.');
            }

            $this->guardRevisable($approvals);

            $this->archiveApprovals($transactionId, $approvals);
            DB::table('transaction_approvals')->where('transaction_id', $transactionId)->delete();

            // Ganti isi + nominal; item lama terhapus beserta baris OUT-nya,
            // jadi stok sudah kembali ke gudang sebelum alokasi di bawah.
            $revised = $this->delivery_orders->revise($transactionId, $dto);

            $this->stock_allocator->allocate(
                $transactionId,
                $dto->company_id,
                $revised['lines'],
            );

            $this->delivery_orders->generateApprovals(
                $transactionId,
                (int) $transaction->created_by,
                $needs_bd_approval,
            );

            return $revised['transaction'];
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
     * ada yang meminta perubahan — merevisi sekarang berarti mencabut
     * keputusan yang sedang diproses role lain. Kalau semua langkah sudah
     * `APPROVED`, DO-nya selesai dan tidak punya form revisi.
     *
     * @param  Collection<int, object>  $approvals
     */
    private function guardRevisable(Collection $approvals): void
    {
        if ($approvals->contains(fn ($approval) => DecideApprovalDTO::isTerminal($approval->status))) {
            return;
        }

        $pending = $approvals->firstWhere('status', 'PENDING');

        if ($pending !== null) {
            throw new RuntimeException(
                'Delivery order ini belum ditandai perlu revisi. Tunggu approval berjalan atau minta revisi dari approver.'
            );
        }

        if ($approvals->every(fn ($approval) => $approval->status === DecideApprovalDTO::STATUS_APPROVED)) {
            throw new RuntimeException('Delivery order ini sudah disetujui penuh dan tidak bisa direvisi.');
        }

        throw new RuntimeException('Status approval delivery order ini tidak dikenali.');
    }

    /**
     * Salin keputusan lama ke arsip sebelum dihapus.
     *
     * `source_approval_id` disimpan walau baris aslinya sudah hilang: sebagai
     * penanda bahwa baris ini berasal dari rantai sebelumnya, bukan dari
     * rantai yang sedang berjalan. Foreign key sengaja tidak dipasang ke
     * `transaction_approvals` supaya arsip tetap utuh walau baris aslinya
     * dihapus manual.
     *
     * @param  Collection<int, object>  $approvals
     */
    private function archiveApprovals(int $transactionId, Collection $approvals): void
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
}
