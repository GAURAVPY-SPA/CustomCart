<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Other Management > Manage Images Gallery and Manage Video
 * Gallery, shown on the website's /images-gallery and /videos-gallery pages
 * (modules gallery and video; design: html/images-gallery.htm,
 * html/videos-gallery.htm). Reference: wl_gallery / wl_video.
 *
 * Kept from the reference: the title, image, link (videos), display order,
 * status and dates. Left out: albums (album_id, parent_id, *_album_name),
 * friendly_url, *_alt, *_project_option and post_date — the designs show one
 * flat list without detail pages, and the title is each photo's alt text.
 * Added for the video cards' "Basics · 08:24" line: video_category and
 * video_duration. Images in uploads/gallery/ and uploads/video/.
 */
class CreateGalleryTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'gallery_id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'gallery_title'        => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'Description of the photo, used as its alt text'],
            'gallery_image'        => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'File name in uploads/gallery/'],
            'sort_order'           => ['type' => 'INT', 'default' => 0],
            'status'               => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'gallery_date_added'   => ['type' => 'DATETIME'],
            'gallery_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('gallery_id');
        $this->forge->addKey(['status', 'sort_order'], false, false, 'idx_gallery_status_order');
        $this->forge->createTable('wl_gallery', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'video_id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'video_title'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'video_link'         => ['type' => 'VARCHAR', 'constraint' => 255, 'comment' => 'YouTube or Vimeo address as entered'],
            'video_category'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'e.g. Basics, Technique'],
            'video_duration'     => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true, 'comment' => 'mm:ss or h:mm:ss'],
            'video_image'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'Thumbnail in uploads/video/ (empty = the YouTube thumbnail)'],
            'sort_order'         => ['type' => 'INT', 'default' => 0],
            'status'             => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'video_date_added'   => ['type' => 'DATETIME'],
            'video_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('video_id');
        $this->forge->addKey(['status', 'sort_order'], false, false, 'idx_video_status_order');
        $this->forge->createTable('wl_video', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_video', true);
        $this->forge->dropTable('wl_gallery', true);
    }
}
