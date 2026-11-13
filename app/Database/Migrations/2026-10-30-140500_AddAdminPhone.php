<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Other Management > Manage Subadmins: the sub-admin's phone
 * number (required on the reference's sub-admin form). Nullable, so the
 * existing administrators are unchanged.
 */
class AddAdminPhone extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('phone', 'tbl_admin')) {
            $this->forge->addColumn('tbl_admin', [
                'phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'admin_email'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('phone', 'tbl_admin')) {
            $this->forge->dropColumn('tbl_admin', 'phone');
        }
    }
}
