<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Our Team (wl_team), shown on the website's /our-team page and
 * each member's /our-team/<slug> profile (module team; design:
 * html/our-team.htm, html/our-team-details.htm). The reference project has
 * no team module; the columns follow the design.
 *
 * Card: name, designation, photo. Profile: biography, the four facts (Role /
 * Position is the designation; Areas of Focus, Experience, Community
 * Involvement), "About <name>", Role & Responsibilities (one per line),
 * Community Contribution and the Areas of Focus tags (one per line). Every
 * profile section is optional and left out when empty. The rich-text fields
 * hold HTML from the basic CKEditor, cleaned by HtmlSanitizer. The member's
 * page address and SEO are its wl_meta_tags row (entity_type team/detail).
 * Photos in uploads/team/.
 */
class CreateTeamTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'team_id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'                   => ['type' => 'VARCHAR', 'constraint' => 150],
            'designation'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'friendly_url'           => ['type' => 'VARCHAR', 'constraint' => 150, 'comment' => 'Profile address: our-team/<friendly_url>'],
            'photo'                  => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'File name in uploads/team/'],
            'biography'              => ['type' => 'TEXT', 'null' => true, 'comment' => 'HTML (basic editor)'],
            'areas_of_focus'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'experience'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'community_involvement'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'about'                  => ['type' => 'TEXT', 'null' => true, 'comment' => 'HTML (basic editor)'],
            'responsibilities'       => ['type' => 'TEXT', 'null' => true, 'comment' => 'One per line'],
            'community_contribution' => ['type' => 'TEXT', 'null' => true, 'comment' => 'HTML (basic editor)'],
            'focus_tags'             => ['type' => 'TEXT', 'null' => true, 'comment' => 'One per line'],
            'sort_order'             => ['type' => 'INT', 'default' => 0],
            'status'                 => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'team_date_added'        => ['type' => 'DATETIME'],
            'team_date_updated'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('team_id');
        $this->forge->addUniqueKey('friendly_url', 'uq_team_friendly_url');
        $this->forge->addKey(['status', 'sort_order'], false, false, 'idx_team_status_order');
        $this->forge->createTable('wl_team', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_team', true);
    }
}
