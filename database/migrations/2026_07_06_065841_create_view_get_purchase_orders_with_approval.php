<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $view_name = 'v_get_purchase_orders_with_approval';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS $this->view_name");

        DB::statement("
            CREATE VIEW $this->view_name AS
            SELECT 
                tx.id,
                tx.transaction_code,
                tx.transaction_type,
                tx.payment_term,
                tx.sub_total,
                tx.tax_amount,
                tx.grand_total,
                tx.created_at,
                ca.`order`       AS current_approval_order,
                ca.status        AS current_approval_status,
                ca.description   AS current_approval_description,
                ca.role       	AS current_approval_role,
                ca.sub_role   	AS current_approval_sub_role,
                ca.pic_proceed   AS current_approval_proceed_by,
                ca.proceed_at    AS current_approval_proceed_at,
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'name', td.name,
                        'value', td.value,
                        'type', td.type
                    )
                ) AS details
            FROM transactions tx
            LEFT JOIN transaction_details td 
                ON td.transaction_id = tx.id
            LEFT JOIN (
                SELECT *
                FROM (
                    SELECT 
                        ta.*,
                        r.name AS `role`,
                        sr.name AS sub_role,
                        u.name AS pic_proceed,
                        ROW_NUMBER() OVER (
                            PARTITION BY ta.transaction_id 
                            ORDER BY 
                                CASE WHEN ta.status = 'PENDING' THEN 0 ELSE 1 END ASC,
                                CASE WHEN ta.status = 'PENDING' THEN ta.`order` END ASC,
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
            GROUP BY tx.id, ca.id, ca.`order`, ca.status, ca.description, 
                    ca.role, ca.sub_role, ca.pic_proceed, ca.proceed_at
            ORDER BY tx.created_at DESC;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS $this->view_name");
    }
};
