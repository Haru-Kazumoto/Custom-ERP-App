<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaikan langkah "current step" pada view approval PO.
     *
     * `generateApprovals()` menyimpan SELURUH langkah approval dengan status
     * `PENDING` sejak dokumen dibuat, lalu setiap langkah berubah status saat
     * diproses. View lama menghitung current step dengan urutan:
     *
     *   1. `PENDING` (order terkecil)
     *   2. kalau tidak ada, langkah decided dengan order terbesar
     *
     * Karena `PENDING` selalu menang, begitu finance menolak di `order` 1,
     * langkah marketing di `order` 2 yang masih `PENDING` tetap terambil
     * sebagai current step. Akibatnya PO yang sudah ditolak muncul lagi di
     * antrean marketing seolah belum pernah diputuskan.
     *
     * Urutan baru memberi prioritas lebih tinggi pada keputusan terminal
     * (`REJECTED` / `NEED_REVISION`) karena status itu menghentikan rantai:
     * langkah setelahnya tidak pernah boleh dijalankan.
     *
     * Kolom hasil view sengaja tidak ditambah/diubah supaya semua konsumen yang
     * sudah ada (PO Index, dashboard Finance, halaman approval) tetap jalan.
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

    /**
     * Mengembalikan definisi lama. `CREATE OR REPLACE` jadi tidak perlu drop.
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
                                CASE WHEN ta.status = 'PENDING' THEN 0 ELSE 1 END,
                                CASE WHEN ta.status = 'PENDING' THEN ta.`order` END,
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
