<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Preserve the CI3 product purchase-button mode in the CI4 catalog. */
class AddProductButtonType extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('is_show_button', 'wl_products')) {
            $this->forge->addColumn('wl_products', ['is_show_button' => [
                'type' => 'INT', 'constraint' => 1, 'default' => 4, 'after' => 'is_popular',
            ]]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('is_show_button', 'wl_products')) {
            $this->forge->dropColumn('is_show_button');
        }
    }
}
