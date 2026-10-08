<?php

namespace App\Modules\Approval\Actions;

use App\Enum\TransactionType;
use App\Modules\Approval\DTOs\DecideApprovalDTO;
use App\Modules\Approval\Exceptions\ApprovalForbiddenException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DecideApprovalAction
{
    /**
     * Menyimpan keputusan approval untuk langkah yang sedang berjalan.
     *
     * Semua pemeriksaan di dalam `DB::transaction` + `lockForUpdate()` supaya
     * dua orang yang membuka dokumen sama-sama dan menekan tombol di detik yang
     * sama tidak bisa sama-sama berhasil: yang kedua akan membaca baris yang
     * sudah `APPROVED`, bukan `PENDING`.
     *
     * Tidak ada "aktivasi" langkah berikutnya. `generateApprovals()` menyimpan
     * seluruh langkah dengan status `PENDING` sejak dokumen dibuat, jadi begitu
     * langkah ini disetujui, view otomatis menggeser current step ke langkah
     * berikutnya tanpa perlu update tambahan.
     *
     * @throws ApprovalForbiddenException kalau langkah yang sedang berjalan bukan
     *                                    milik role pemohon.
     * @throws RuntimeException kalau status tidak dikenali, langkah sudah
     *                          diproses orang lain, atau dokumen sedang berhenti
     *                          di langkah sebelumnya.
     *
     * `expectedType` membatasi endpoint ini ke satu tipe dokumen. Route
     * decision berada di namespace masing-masing (purchase-orders /
     * delivery-orders) tapi parameternya bebas angka apa saja, jadi tanpa cek
     * ini keputusan Delivery Order bisa disimpan lewat endpoint Purchase Order
     * dan sebaliknya.
     */
    public function execute(DecideApprovalDTO $dto, TransactionType $expectedType = TransactionType::PurchaseOrder): void
    {
        // Controller sudah memvalidasi `Rule::in(DecideApprovalDTO::STATUSES)`,
        // tapi action ini tidak selalu dipanggil dari HTTP — harness, queue, dan
        // integrasi lain memanggilnya langsung. Tanpa cek di sini, status yang
        // sudah dihapus (mis. `REJECTED`) akan tersimpan ke database dan tidak
        // lagi dikenali `isTerminal()`, sehingga rantai approval bisa berjalan
        // melewati keputusan yang seharusnya menghentikan dokumen.
        if (! in_array($dto->status, DecideApprovalDTO::STATUSES, true)) {
            throw new RuntimeException("Status approval {$dto->status} tidak dikenali.");
        }

        if ($dto->requiresDescription() && blank($dto->description)) {
            throw new RuntimeException('Alasan wajib diisi untuk meminta revisi.');
        }

        DB::transaction(function () use ($dto, $expectedType) {
            $type = DB::table('transactions')
                ->where('id', $dto->transaction_id)
                ->value('transaction_type');

            if ($type === null) {
                throw new RuntimeException("Dokumen {$expectedType->value} tidak ditemukan.");
            }

            if ($type !== $expectedType->value) {
                throw new RuntimeException("Dokumen ini bukan {$expectedType->value}.");
            }

            $approvals = DB::table('transaction_approvals')
                ->where('transaction_id', $dto->transaction_id)
                ->orderBy('order')
                ->lockForUpdate()
                ->get(['id', 'order', 'role_id', 'sub_role_id', 'status']);

            if ($approvals->isEmpty()) {
                throw new RuntimeException('Dokumen ini tidak memiliki alur approval.');
            }

            // Rantai berhenti begitu ada langkah yang perlu direvisi, meskipun
            // langkah berikutnya masih PENDING. Tanpa cek ini, marketing bisa
            // menyetujui dokumen yang sama sekali belum diperbaiki pemohon.
            $terminal = $approvals->first(fn ($approval) => DecideApprovalDTO::isTerminal($approval->status));

            if ($terminal !== null) {
                throw new RuntimeException(
                    "Rantai approval berhenti di langkah {$terminal->order} ({$terminal->status})."
                );
            }

            // `order` sudah urut naik, jadi PENDING pertama adalah langkah yang
            // sedang berjalan.
            $current = $approvals->firstWhere('status', 'PENDING');

            if ($current === null) {
                throw new RuntimeException('Seluruh langkah approval dokumen ini sudah diproses.');
            }

            // Langkah dengan sub-role (rantai sales, marketing DNP/DKU) hanya
            // boleh diputuskan pemilik sub-role itu persis — termasuk user
            // tanpa sub-role, yang memang tidak punya hak di langkah itu.
            // Langkah tanpa sub-role tetap terbuka untuk seluruh role-nya,
            // jadi aturan lama PO tidak berubah. Tanpa cek ini, salesman
            // bisa menyetujui langkah sales supervisor miliknya sendiri
            // karena role-nya sama.
            $subRoleMatches = $current->sub_role_id === null
                || ($dto->sub_role_id !== null
                    && (int) $current->sub_role_id === $dto->sub_role_id);

            if ((int) $current->role_id !== $dto->role_id || ! $subRoleMatches) {
                throw new ApprovalForbiddenException('Dokumen ini sedang menunggu persetujuan role lain.');
            }

            $updated = DB::table('transaction_approvals')
                ->where('id', $current->id)
                ->where('status', 'PENDING')
                ->update([
                    'status' => $dto->status,
                    'description' => $dto->description,
                    'proceed_by' => $dto->proceed_by,
                    'proceed_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new RuntimeException('Keputusan sudah tersimpan sebelumnya. Muat ulang halaman.');
            }
        });
    }
}
