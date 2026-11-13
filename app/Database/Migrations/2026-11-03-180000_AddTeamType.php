<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * wl_team.team_type: the member's group on the website's /our-team page,
 * one of the fixed keys of Modules\Team\Models\TeamModel::TYPES (patrons,
 * board_of_directors, management_staff). The types are fixed in code: no
 * table and no Sitepanel management.
 */
class AddTeamType extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('wl_team', [
            'team_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'after' => 'designation', 'comment' => 'Key of TeamModel::TYPES'],
        ]);
        $this->db->query('ALTER TABLE wl_team ADD KEY idx_team_type (team_type)');
    }

    public function down(): void
    {
        $this->forge->dropColumn('wl_team', 'team_type');
    }
}
