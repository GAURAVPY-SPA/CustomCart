<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optional Blog URL of a post (Sitepanel > Manage Blogs): when set, the
 * website's blog cards, related posts and recent posts link to it instead of
 * the post's own page /blogs/<friendly_url> (blog_post_url()). NULL / empty =
 * the post's own page, so existing posts are unchanged.
 */
class AddBlogUrl extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('wl_blogs', [
            'blog_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'friendly_url',
                'comment' => 'Optional link target (http/https); empty = the post page'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('wl_blogs', 'blog_url');
    }
}
