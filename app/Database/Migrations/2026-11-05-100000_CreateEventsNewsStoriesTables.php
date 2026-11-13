<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The Events & Updates menu's new sections, each a listing page and one
 * page per record (address <listing>/<friendly_url>, the record's own
 * wl_meta_tags row with its SEO data, as Our Team's profiles):
 *
 *   wl_events    Sitepanel > Manage Events & Activities   /events-activities   (html/events.htm, events-details.htm)
 *   wl_news      Sitepanel > Manage News & Announcements  /news-announcements  (html/news.htm, news-details.htm)
 *   wl_stories   Sitepanel > Manage Success Stories       /success-stories     (html/success-stories.htm, success-storie-details.htm)
 *
 * Columns follow the designs' editable parts. Rich text (description,
 * story) is HTML from the full CKEditor, cleaned by HtmlSanitizer. Lists are
 * in display order (sort_order ascending; 0 = by date) then by date.
 * Images in uploads/events/, uploads/news/, uploads/stories/.
 *
 * Events: the tab (Upcoming / Current / Past) follows the dates; the
 * registration status (open / waitlist / closed / completed) is set by hand.
 */
class CreateEventsNewsStoriesTables extends Migration
{
    public function up(): void
    {
        $common = static fn (string $p) => [
            'sort_order'          => ['type' => 'INT', 'default' => 0],
            'status'              => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            $p . '_date_added'   => ['type' => 'DATETIME'],
            $p . '_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ];

        $this->forge->addField([
            'event_id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_title'       => ['type' => 'VARCHAR', 'constraint' => 200],
            'friendly_url'      => ['type' => 'VARCHAR', 'constraint' => 150, 'comment' => 'Page address: <events page>/<friendly_url>'],
            'event_category'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'e.g. Course, Volunteer, Youth, Talk, Community'],
            'event_status'      => ['type' => 'ENUM', 'constraint' => ['open', 'waitlist', 'closed', 'completed'], 'default' => 'open', 'comment' => 'Registration status'],
            'start_date'        => ['type' => 'DATE'],
            'end_date'          => ['type' => 'DATE', 'null' => true, 'comment' => 'NULL = one day'],
            'date_note'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'e.g. 2-day class'],
            'event_time'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'e.g. 9:30am – 5:00pm'],
            'time_note'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'e.g. Both days'],
            'venue'             => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'venue_note'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'short_description' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Plain text (listing card, detail lead)'],
            'description'       => ['type' => 'MEDIUMTEXT', 'null' => true, 'comment' => 'HTML (About This Event)'],
            'event_image'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'File name in uploads/events/'],
            'registration_url'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'comment' => 'Register Now / Join Waitlist link (empty = contact page)'],
        ] + $common('event'));
        $this->forge->addPrimaryKey('event_id');
        $this->forge->addUniqueKey('friendly_url', 'uq_event_friendly_url');
        $this->forge->addKey(['status', 'start_date'], false, false, 'idx_events_status_date');
        $this->forge->createTable('wl_events', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'news_id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'news_title'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'friendly_url'      => ['type' => 'VARCHAR', 'constraint' => 150, 'comment' => 'Page address: <news page>/<friendly_url>'],
            'news_category'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'e.g. Announcement, Community, Course Update'],
            'publish_date'      => ['type' => 'DATE'],
            'short_description' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Plain text (card excerpt)'],
            'description'       => ['type' => 'MEDIUMTEXT', 'null' => true, 'comment' => 'HTML (the article)'],
            'news_image'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'File name in uploads/news/'],
        ] + $common('news'));
        $this->forge->addPrimaryKey('news_id');
        $this->forge->addUniqueKey('friendly_url', 'uq_news_friendly_url');
        $this->forge->addKey(['status', 'publish_date'], false, false, 'idx_news_status_date');
        $this->forge->createTable('wl_news', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'story_id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'story_title'       => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'Card title'],
            'friendly_url'      => ['type' => 'VARCHAR', 'constraint' => 150, 'comment' => 'Page address: <stories page>/<friendly_url>'],
            'story_label'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'Programme label, e.g. Community Care'],
            'story_quote'       => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true, 'comment' => 'Heading of the story page and the featured card (empty = the title)'],
            'publish_date'      => ['type' => 'DATE'],
            'short_description' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Plain text (summary)'],
            'description'       => ['type' => 'MEDIUMTEXT', 'null' => true, 'comment' => 'HTML (The Story)'],
            'highlight_quote'   => ['type' => 'TEXT', 'null' => true, 'comment' => 'Plain text (.story-highlight)'],
            'highlight_cite'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'author_name'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'e.g. Volunteer, Bedok'],
            'author_role'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'author_photo'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'File name in uploads/stories/'],
            'story_image'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'File name in uploads/stories/'],
        ] + $common('story'));
        $this->forge->addPrimaryKey('story_id');
        $this->forge->addUniqueKey('friendly_url', 'uq_story_friendly_url');
        $this->forge->addKey(['status', 'sort_order'], false, false, 'idx_stories_status_order');
        $this->forge->createTable('wl_stories', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_stories', true);
        $this->forge->dropTable('wl_news', true);
        $this->forge->dropTable('wl_events', true);
    }
}
