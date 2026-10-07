<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * View approval untuk Delivery Order: salinan persis view PO dengan
     * `WHERE transaction_type = 'DO'`.
     *
     * `GetDeliveryOrdersQuery` dan `GetDeliveryOrderApprovalQueueQuery` membaca
     * view ini supaya definisi "langkah approval terakhir" (ROW_NUMBER + prioritas
     * NEED_REVISION) tetap satu sumber, sama seperti PO — hanya filter
     * dokumennya yang berbeda.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW v_get_delivery_orders_with_approval AS
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
            WHERE tx.transaction_type = 'DO'
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

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS v_get_delivery_orders_with_approval');
    }
};
