<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Keeps the Product list's CI3 "Recently Added" bulk flag available in CI4.
 * Existing products are left unchanged; new products are marked by the
 * sitepanel Product controller when they are created.
 */
class AddProductRecentlyAdded extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('is_recently_added', 'wl_products')) {
            $this->forge->addColumn('wl_products', [
                'is_recently_added' => [
                    'type'       => 'ENUM',
                    'constraint' => ['0', '1'],
                    'default'    => '0',
                    'after'      => 'status',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('is_recently_added', 'wl_products')) {
            $this->forge->dropColumn('wl_products', 'is_recently_added');
        }
    }
}
