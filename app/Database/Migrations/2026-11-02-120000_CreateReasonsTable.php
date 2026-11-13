<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Manage Orders > Manage Reasons (reference:
 * wl_reasons): the reasons a customer picks when asking to cancel
 * (reason_type 1) or return (2) an order item. The reference's unused
 * "replace" type (3) is left out. Starts with the reasons of
 * Config\Shop::$cancelReasons / $returnReasons.
 */
class CreateReasonsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'reason_id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reason_name'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'reason_type'         => ['type' => 'ENUM', 'constraint' => ['1', '2'], 'default' => '1', 'comment' => '1=Cancel,2=Return'],
            'sort_order'          => ['type' => 'INT', 'default' => 0],
            'status'              => ['type' => 'ENUM', 'constraint' => ['0', '1', '2'], 'default' => '1', 'comment' => '1=Active,0=Inactive,2=Deleted'],
            'reason_date_added'   => ['type' => 'DATETIME'],
            'reason_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('reason_id');
        $this->forge->addKey(['reason_type', 'status', 'sort_order'], false, false, 'idx_reason_type');
        $this->forge->createTable('wl_reasons', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        if ($this->db->table('wl_reasons')->countAllResults() === 0) {
            $shop = config('Shop');
            $now  = date('Y-m-d H:i:s');
            $rows = [];
            foreach (['1' => $shop->cancelReasons, '2' => $shop->returnReasons] as $type => $names) {
                foreach ($names as $i => $name) {
                    $rows[] = ['reason_name' => $name, 'reason_type' => $type, 'sort_order' => $i + 1, 'status' => '1', 'reason_date_added' => $now];
                }
            }
            $rows && $this->db->table('wl_reasons')->insertBatch($rows);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_reasons', true);
    }
}
