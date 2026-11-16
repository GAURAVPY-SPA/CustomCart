<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel "Forgot password?": one row per emailed reset link.
 *
 * Only the sha256 of the link's random token is stored (token_hash), never
 * the token itself. A link is valid while used_at is NULL and expires_at is in
 * the future; using it sets used_at, and requesting a new link ends the
 * previous ones (expires_at = now). tbl_admin is not changed.
 *
 * Same table as docs/admin_password_resets.sql (manual install).
 */
class CreateAdminPasswordResetsTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tbl_admin_password_resets')) {
            return;
        }

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'admin_id'     => ['type' => 'INT', 'unsigned' => true],
            'token_hash'   => ['type' => 'CHAR', 'constraint' => 64, 'comment' => 'sha256 of the emailed token; the token itself is never stored'],
            'expires_at'   => ['type' => 'DATETIME'],
            'used_at'      => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Set when the link was used (single use)'],
            'request_ip'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash', 'uq_admin_reset_token');
        $this->forge->addKey(['admin_id', 'created_at'], false, false, 'idx_admin_reset_admin');
        $this->forge->addForeignKey('admin_id', 'tbl_admin', 'admin_id', 'CASCADE', 'CASCADE', 'fk_admin_reset_admin');
        $this->forge->createTable('tbl_admin_password_resets', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('tbl_admin_password_resets', true);
    }
}
