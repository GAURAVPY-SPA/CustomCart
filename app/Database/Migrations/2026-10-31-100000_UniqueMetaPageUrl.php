<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * wl_meta_tags is the website's URL registry (as in the reference, whose
 * router resolved a request through wl_meta_tags.page_url): one row per
 * page address. A unique page_url makes duplicate SEO records impossible;
 * (entity_type, entity_id) finds the row a record owns (a static page,
 * a blog post).
 *
 * Stops, without changing anything, if the table already holds duplicate
 * page URLs: those have to be merged in Sitepanel > Meta Tags first.
 */
class UniqueMetaPageUrl extends Migration
{
    public function up(): void
    {
        $duplicates = $this->db->query('SELECT page_url FROM ' . $this->db->prefixTable('wl_meta_tags') . ' GROUP BY page_url HAVING COUNT(*) > 1')->getResultArray();
        if ($duplicates) {
            throw new RuntimeException('wl_meta_tags has duplicate page_url values (' . implode(', ', array_column($duplicates, 'page_url')) . '); merge them before migrating.');
        }

        $table = $this->db->prefixTable('wl_meta_tags');
        $this->db->query('ALTER TABLE ' . $table . ' DROP INDEX page_url, ADD UNIQUE KEY uq_meta_page_url (page_url), ADD KEY idx_meta_entity (entity_type, entity_id)');
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('wl_meta_tags');
        $this->db->query('ALTER TABLE ' . $table . ' DROP INDEX uq_meta_page_url, DROP INDEX idx_meta_entity, ADD KEY page_url (page_url)');
    }
}
