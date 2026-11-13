<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Other Management > Manage FAQs (reference: wl_faq).
 *
 * Kept from the reference: faq_question, faq_answer, sort_order, status,
 * faq_date_added. Left out: faq_parent_id (never used). Status is '0'/'1'
 * (the reference enum('1','2','3') could not store "inactive").
 */
class CreateFaqTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'faq_id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'faq_question'   => ['type' => 'VARCHAR', 'constraint' => 225],
            'faq_answer'     => ['type' => 'TEXT'],
            'sort_order'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status'         => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'faq_date_added' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('faq_id');
        $this->forge->addKey(['status', 'sort_order'], false, false, 'idx_faq_status_order');
        $this->forge->createTable('wl_faq', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_faq', true);
    }
}
