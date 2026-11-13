<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Other Management > Manage Discount Coupons (reference:
 * wl_coupons, wl_coupon_customers).
 *
 * wl_coupons keeps the reference columns. coupon_code is unique and
 * case-sensitive (utf8mb4_bin), as the reference's binary collation was.
 * coupon_type: p = percentage, a = amount (the unused 'f' is left out).
 *
 * wl_coupon_customers (coupons given to individual members) is kept for the
 * members coupon flow; its rows go with their coupon (foreign key cascade).
 */
class CreateCouponTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'coupon_id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'coupon_title'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'coupon_code'             => ['type' => 'VARCHAR', 'constraint' => 10],
            'coupon_type'             => ['type' => 'ENUM', 'constraint' => ['p', 'a'], 'default' => 'p', 'comment' => 'p=Percentage,a=Amount'],
            'coupon_usage'            => ['type' => 'ENUM', 'constraint' => ['single', 'multiple'], 'default' => 'single'],
            'usage_count'             => ['type' => 'INT', 'unsigned' => true, 'default' => 1, 'comment' => 'Uses per customer when coupon_usage=multiple'],
            'coupon_for'              => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '0', 'comment' => '0=All,1=Members only'],
            'coupon_discount'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'maximum_coupon_discount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00', 'comment' => 'Cap for percentage coupons, 0 = none'],
            'minimum_order_amount'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'start_date'              => ['type' => 'DATE'],
            'end_date'                => ['type' => 'DATE'],
            'status'                  => ['type' => 'ENUM', 'constraint' => ['0', '1', '2'], 'default' => '1', 'comment' => '1=Active,0=Inactive,2=Deleted'],
            'date_added'              => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('coupon_id');
        $this->forge->addUniqueKey('coupon_code', 'uq_coupon_code');
        $this->forge->addKey(['status', 'start_date', 'end_date'], false, false, 'idx_coupon_validity');
        $this->forge->createTable('wl_coupons', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
        $this->db->query('ALTER TABLE ' . $this->db->prefixTable('wl_coupons') . ' MODIFY coupon_code VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'coupon_id'   => ['type' => 'INT', 'unsigned' => true],
            'used_count'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status'      => ['type' => 'ENUM', 'constraint' => ['0', '1', '2'], 'default' => '0', 'comment' => '0=Unused,1=Used,2=Multiple use'],
            'receive_on'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['coupon_id', 'customer_id'], false, false, 'idx_coupon_customer');
        $this->forge->addForeignKey('coupon_id', 'wl_coupons', 'coupon_id', 'CASCADE', 'CASCADE', 'fk_coupon_customers_coupon');
        $this->forge->createTable('wl_coupon_customers', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_coupon_customers', true);
        $this->forge->dropTable('wl_coupons', true);
    }
}
