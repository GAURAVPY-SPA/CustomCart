<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Manage Services (wl_services): the content of the website's
 * /experience-services and /community-activities pages (module services;
 * designs: html/experience-services.htm, html/community-activities.htm,
 * the same CMS layout). service_type picks the page:
 *
 *   our-service           "Our Service"            /experience-services
 *   experiences-courses   "Experiences & Courses"  /community-activities
 *
 * A record is one run of the design's CMS blocks: the intro (title,
 * description, image) and five boxes. Each box has an optional heading
 * (the design's h2.cms-heading; the basic editor has no headings) and rich
 * text from the basic editor (HTML cleaned by HtmlSanitizer). Box 1 is the
 * framed .cms-content-box, box 2 shows its lists as cards, box 4 is the
 * .cms-highlight-box and box 5 follows it (Services::PAGES). Images in
 * uploads/services/.
 */
class CreateServicesTable extends Migration
{
    public function up(): void
    {
        $fields = [
            'service_id'    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'service_type'  => ['type' => 'VARCHAR', 'constraint' => 30, 'comment' => 'ServicesModel::TYPES key'],
            'service_title' => ['type' => 'VARCHAR', 'constraint' => 200],
            'description'   => ['type' => 'TEXT', 'null' => true, 'comment' => 'HTML (basic editor)'],
            'service_image' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'File name in uploads/services/'],
        ];
        for ($box = 1; $box <= 5; $box++) {
            $fields['box' . $box . '_heading'] = ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true];
            $fields['box' . $box]              = ['type' => 'TEXT', 'null' => true, 'comment' => 'HTML (basic editor)'];
        }
        $fields += [
            'sort_order'           => ['type' => 'INT', 'default' => 0],
            'status'               => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'service_date_added'   => ['type' => 'DATETIME'],
            'service_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('service_id');
        $this->forge->addKey(['service_type', 'status', 'sort_order'], false, false, 'idx_services_type_status_order');
        $this->forge->createTable('wl_services', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_services', true);
    }
}
