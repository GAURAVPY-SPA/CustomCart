<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Blog posts' wl_meta_tags rows: entity_type 'blog' => 'blog/detail'.
 *
 * Every row is now routed by one rule (MetaModel::route_for()):
 * '<module>/<method>' + entity_id => Modules\<Module>\Controllers\<Module>::<method>/<entity_id>.
 * A post's row becomes 'blog/detail' + blog_id => Blog::detail/<blog_id>, so
 * the router needs no blog-specific entry.
 *
 * Only entity_type changes, in place: meta_id, page_url (the post's address,
 * blogs/<slug>), entity_id and the SEO data are kept, so no URL changes and
 * no row is created or deleted. down() restores 'blog'.
 */
class BlogMetaEntityType extends Migration
{
    public function up(): void
    {
        $this->db->table('wl_meta_tags')
            ->where('entity_type', 'blog')
            ->where('entity_id >', 0)
            ->update(['entity_type' => 'blog/detail']);
    }

    public function down(): void
    {
        $this->db->table('wl_meta_tags')
            ->where('entity_type', 'blog/detail')
            ->where('entity_id >', 0)
            ->update(['entity_type' => 'blog']);
    }
}
