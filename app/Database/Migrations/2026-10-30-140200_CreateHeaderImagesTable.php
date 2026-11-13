<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Other Management > Manage Header Images, the home page slider
 * (reference: wl_header_images). Images in uploads/header_images/.
 *
 * Kept from the reference: header_image, header_url, line_one..line_three,
 * status, added_date. Left out: line_four / line_five (no field in the
 * reference forms). Status is '0'/'1'.
 */
class CreateHeaderImagesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'header_image' => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'File name in uploads/header_images/'],
            'header_url'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'line_one'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'line_two'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'line_three'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'added_date'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status', false, false, 'idx_header_images_status');
        $this->forge->createTable('wl_header_images', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_header_images', true);
    }
}
