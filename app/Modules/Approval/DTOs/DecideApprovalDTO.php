<?php

namespace App\Modules\Approval\DTOs;

class DecideApprovalDTO
{
    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_NEED_REVISION = 'NEED_REVISION';

    /**
     * Status yang bisa dipilih approver.
     *
     * Tidak ada `REJECTED`: nomor PO tidak pernah perlu dibuat ulang. PO yang
     * tidak disetujui dikirim ke halaman Revisi PO dan diperbaiki memakai nomor
     * yang sama, jadi "tolak" dan "perlu revisi" sebenarnya hal yang sama.
     * Menolak tanpa memberi jalan keluar hanya akan membuat dokumen itu buntu.
     */
    public const STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_NEED_REVISION,
    ];

    /**
     * Status yang menghentikan rantai approval. Selama `NEED_REVISION` ada di
     * langkah mana pun, langkah setelahnya tidak boleh dijalankan — walau
     * barisnya masih `PENDING` karena `generateApprovals()` membuat seluruh
     * langkah sekaligus.
     */
    public const TERMINAL_STATUSES = [
        self::STATUS_NEED_REVISION,
    ];

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL_STATUSES, true);
    }

    public function __construct(
        public readonly int $transaction_id,
        public readonly int $role_id,
        public readonly int $proceed_by,
        public readonly string $status,
        public readonly ?string $description,
    ) {}

    /**
     * `NEED_REVISION` tanpa alasan tidak bisa ditindaklanjuti: pemohon tidak
     * akan tahu apa yang harus diperbaiki. `APPROVED` boleh tanpa catatan.
     */
    public function requiresDescription(): bool
    {
        return $this->status === self::STATUS_NEED_REVISION;
    }
}
