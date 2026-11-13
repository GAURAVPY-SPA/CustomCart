<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Other Management > Manage Location (reference:
 * wl_countries and wl_state; the reference shows wl_state rows as "City").
 *
 * A city name is unique within its country; a country's cities are removed
 * with it (foreign key cascade), as the reference's country delete did.
 * Status is '0'/'1'.
 */
class CreateLocationTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'country_id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 128],
            'iso_code_2' => ['type' => 'CHAR', 'constraint' => 2, 'default' => ''],
            'iso_code_3' => ['type' => 'CHAR', 'constraint' => 3, 'default' => ''],
            'status'     => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
        ]);
        $this->forge->addPrimaryKey('country_id');
        $this->forge->addUniqueKey('name', 'uq_country_name');
        $this->forge->addKey('status', false, false, 'idx_country_status');
        $this->forge->createTable('wl_countries', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'country_id'  => ['type' => 'INT', 'unsigned' => true],
            'region_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'comment' => 'City name'],
            'status'      => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['country_id', 'region_name'], 'uq_state_country_name');
        $this->forge->addForeignKey('country_id', 'wl_countries', 'country_id', 'CASCADE', 'CASCADE', 'fk_state_country');
        $this->forge->createTable('wl_state', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_state', true);
        $this->forge->dropTable('wl_countries', true);
    }
}
