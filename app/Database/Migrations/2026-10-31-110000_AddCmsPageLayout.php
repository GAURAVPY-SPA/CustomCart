<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Static pages (wl_cms_pages): the layout a page is shown in moves to its
 * own column, and each page's wl_meta_tags row is named after the page, as
 * in the reference: entity_type 'pages/index/<friendly_url>', entity_id =
 * page_id.
 *
 *   layout NULL     the page text (its Description)
 *   layout <view>   a designed view of modules/pages (Pages::DESIGNED_VIEWS)
 *
 * Existing rows: a page whose row was 'pages/index/<view>' gets that layout;
 * 'pages/cms' rows are text pages. Their entity_type becomes
 * 'pages/index/<friendly_url>'.
 */
class AddCmsPageLayout extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('layout', 'wl_cms_pages')) {
            $this->forge->addColumn('wl_cms_pages', [
                'layout' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'friendly_url', 'comment' => 'Designed view (Pages::DESIGNED_VIEWS); NULL = the page text'],
            ]);
        }

        $pages = $this->db->prefixTable('wl_cms_pages');
        $meta  = $this->db->prefixTable('wl_meta_tags');

        // Designed pages: until now every 'pages/index/<view>' row named the page's view.
        $this->db->query("UPDATE {$pages} p JOIN {$meta} m ON m.entity_id = p.page_id AND m.entity_type LIKE 'pages/index/%'
            SET p.layout = SUBSTRING(m.entity_type, 13)");
        $this->db->query("UPDATE {$meta} m JOIN {$pages} p ON p.page_id = m.entity_id
            SET m.entity_type = CONCAT('pages/index/', p.friendly_url)
            WHERE m.entity_type = 'pages/cms' OR m.entity_type LIKE 'pages/index/%'");
    }

    public function down(): void
    {
        $pages = $this->db->prefixTable('wl_cms_pages');
        $meta  = $this->db->prefixTable('wl_meta_tags');

        $this->db->query("UPDATE {$meta} m JOIN {$pages} p ON p.page_id = m.entity_id
            SET m.entity_type = IF(p.layout IS NULL OR p.layout = '', 'pages/cms', CONCAT('pages/index/', p.layout))
            WHERE m.entity_type LIKE 'pages/index/%'");
        if ($this->db->fieldExists('layout', 'wl_cms_pages')) {
            $this->forge->dropColumn('wl_cms_pages', 'layout');
        }
    }
}
