<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sinkronkan view approval dengan penghapusan status `REJECTED`.
     *
     * Status `REJECTED` dihapus dari alur karena nomor PO tidak perlu dibuat
     * ulang: PO yang tidak disetujui bisa langsung direvisi di halaman
     * `/purchase-orders/revisions` memakai nomor yang sama. Satu-satunya jalan
     * keluar dari approval sekarang adalah `NEED_REVISION`.
     *
     * View sebelumnya masih mengenali `REJECTED` sebagai status terminal. itu
     * tidak merusak apa pun sekarang (tidak ada lagi baris berstatus itu), tapi
     * definisi "apa itu terminal" jadi ada di dua tempat — `DecideApprovalDTO`
     * dan SQL view — dan keduanya harus berhenti berbeda. Migration ini
     * menyamakan keduanya ke `NEED_REVISION`.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW v_get_purchase_orders_with_approval AS
            SELECT
                tx.id,
                tx.transaction_code,
                tx.transaction_type,
                tx.payment_term,
                tx.sub_total,
                tx.tax_amount,
                tx.grand_total,
                tx.created_at,
                ca.`order` AS current_approval_order,
                ca.status AS current_approval_status,
                ca.description AS current_approval_description,
                ca.role AS current_approval_role,
                ca.sub_role AS current_approval_sub_role,
                ca.pic_proceed AS current_approval_proceed_by,
                ca.proceed_at AS current_approval_proceed_at,
                JSON_ARRAYAGG(JSON_OBJECT('name', td.name, 'value', td.value, 'type', td.type)) AS details
            FROM transactions tx
            LEFT JOIN transaction_details td ON td.transaction_id = tx.id
            LEFT JOIN (
                SELECT t.* FROM (
                    SELECT
                        ta.id,
                        ta.`order`,
                        ta.status,
                        ta.description,
                        ta.proceed_at,
                        ta.transaction_id,
                        ta.role_id,
                        ta.sub_role_id,
                        ta.proceed_by,
                        r.name AS role,
                        sr.name AS sub_role,
                        u.name AS pic_proceed,
                        ROW_NUMBER() OVER (
                            PARTITION BY ta.transaction_id
                            ORDER BY
                                CASE
                                    WHEN ta.status = 'NEED_REVISION' THEN 0
                                    WHEN ta.status = 'PENDING' THEN 1
                                    ELSE 2
                                END,
                                CASE WHEN ta.status = 'APPROVED' THEN NULL ELSE ta.`order` END,
                                ta.`order` DESC
                        ) AS rn
                    FROM transaction_approvals ta
                    LEFT JOIN roles r ON r.id = ta.role_id
                    LEFT JOIN sub_roles sr ON sr.id = ta.sub_role_id
                    LEFT JOIN users u ON u.id = ta.proceed_by
                ) t
                WHERE t.rn = 1
            ) ca ON ca.transaction_id = tx.id
            WHERE tx.transaction_type = 'PO'
            GROUP BY
                tx.id,
                ca.id,
                ca.`order`,
                ca.status,
                ca.description,
                ca.role,
                ca.sub_role,
                ca.pic_proceed,
                ca.proceed_at
        SQL);
    }

    /**
     * Definisi sebelum migration ini masih mengenali `REJECTED` sebagai
     * terminal. Dikembalikan supaya rollback tidak mengubah bentuk view.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW v_get_purchase_orders_with_approval AS
            SELECT
                tx.id,
                tx.transaction_code,
                tx.transaction_type,
                tx.payment_term,
                tx.sub_total,
                tx.tax_amount,
                tx.grand_total,
                tx.created_at,
                ca.`order` AS current_approval_order,
                ca.status AS current_approval_status,
                ca.description AS current_approval_description,
                ca.role AS current_approval_role,
                ca.sub_role AS current_approval_sub_role,
                ca.pic_proceed AS current_approval_proceed_by,
                ca.proceed_at AS current_approval_proceed_at,
                JSON_ARRAYAGG(JSON_OBJECT('name', td.name, 'value', td.value, 'type', td.type)) AS details
            FROM transactions tx
            LEFT JOIN transaction_details td ON td.transaction_id = tx.id
            LEFT JOIN (
                SELECT t.* FROM (
                    SELECT
                        ta.id,
                        ta.`order`,
                        ta.status,
                        ta.description,
                        ta.proceed_at,
                        ta.transaction_id,
                        ta.role_id,
                        ta.sub_role_id,
                        ta.proceed_by,
                        r.name AS role,
                        sr.name AS sub_role,
                        u.name AS pic_proceed,
                        ROW_NUMBER() OVER (
                            PARTITION BY ta.transaction_id
                            ORDER BY
                                CASE
                                    WHEN ta.status IN ('REJECTED', 'NEED_REVISION') THEN 0
                                    WHEN ta.status = 'PENDING' THEN 1
                                    ELSE 2
                                END,
                                CASE WHEN ta.status = 'APPROVED' THEN NULL ELSE ta.`order` END,
                                ta.`order` DESC
                        ) AS rn
                    FROM transaction_approvals ta
                    LEFT JOIN roles r ON r.id = ta.role_id
                    LEFT JOIN sub_roles sr ON sr.id = ta.sub_role_id
                    LEFT JOIN users u ON u.id = ta.proceed_by
                ) t
                WHERE t.rn = 1
            ) ca ON ca.transaction_id = tx.id
            WHERE tx.transaction_type = 'PO'
            GROUP BY
                tx.id,
                ca.id,
                ca.`order`,
                ca.status,
                ca.description,
                ca.role,
                ca.sub_role,
                ca.pic_proceed,
                ca.proceed_at
        SQL);
    }
};
