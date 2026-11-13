<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Post details shown on the website's blog pages (design: html/blog.htm,
 * html/blog-details.htm), set in Sitepanel > Manage Blogs:
 *   added_by        "By: <name>" on the cards and the article (empty = Admin)
 *   contents_types  label above the article title, e.g. Community (empty = none)
 *   posted_on       "Posted On" date (empty = the publication date)
 * All optional, so existing posts are shown as before.
 */
class AddBlogPostDetails extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('wl_blogs', [
            'added_by'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'blog_url', 'comment' => 'Author shown as "By: ..." (empty = Admin)'],
            'contents_types' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'added_by', 'comment' => 'Label above the article title, e.g. Community'],
            'posted_on'      => ['type' => 'DATE', 'null' => true, 'after' => 'contents_types', 'comment' => 'Date shown as "Posted On" (empty = published_at)'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('wl_blogs', ['added_by', 'contents_types', 'posted_on']);
    }
}
