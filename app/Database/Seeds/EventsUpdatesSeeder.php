<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * News & Events sections (modules events, news; migration
 * CreateEventsNewsStoriesTables):
 *
 *  1. the listing pages' addresses in wl_meta_tags, the URL registry
 *     (entity_type <module>/index, entity_id 0); each record's page is its
 *     own row, saved with the record in the Sitepanel;
 *  2. the Sitepanel menu / permission rows, under Other Management (shown in
 *     the sidebar's News & Events group; 1=View, 2=Add, 3=Edit,
 *     4=Activate, 5=Deactivate, 6=Delete).
 *
 * Safe to run again: an address or section that exists is left as it is.
 */
class EventsUpdatesSeeder extends Seeder
{
    /**
     * entity_type => [address, SEO title, SEO description].
     */
    protected array $pages = [
        'news/index'    => ['news-announcements', 'News & Announcements | CustomCart', 'News, events and product updates from CustomCart.'],
    ];

    /**
     * controller => [title, display order, privileges (default all)].
     */
    protected array $sections = [
        'events'  => ['Manage Events & Activities', 19],
        'news'    => ['Manage News & Announcements', 20],
    ];

    public function run(): void
    {
        foreach ($this->pages as $type => [$url, $title, $description]) {
            $at = $this->db->table('wl_meta_tags')->where('page_url', $url)->get()->getRowArray();
            if ($at) {
                $at['entity_type'] !== $type && $this->report('Address "' . $url . '" is used by ' . ($at['entity_type'] ?: 'an SEO-only row') . '; ' . $type . ' was not registered.');

                continue;
            }
            $this->db->table('wl_meta_tags')->insert([
                'is_fixed' => 'Y', 'entity_type' => $type, 'entity_id' => 0, 'page_url' => $url,
                'meta_title' => $title, 'meta_description' => $description, 'meta_keyword' => '', 'status' => '1',
            ]);
        }

        $heading = $this->db->table('tbl_admin_sections')->select('id')->where(['parent_id' => 0, 'section_title' => OtherManagementSectionsSeeder::HEADING])->get()->getRowArray();
        if (! $heading) {
            $this->report('Sitepanel heading "' . OtherManagementSectionsSeeder::HEADING . '" is missing (run OtherManagementSectionsSeeder first); the sections were not added.');

            return;
        }
        foreach ($this->sections as $controller => $section) {
            [$title, $order] = $section;
            if ($this->db->table('tbl_admin_sections')->where('section_controller', $controller)->countAllResults() > 0) {
                continue;
            }
            $this->db->table('tbl_admin_sections')->insert([
                'parent_id'          => (int) $heading['id'],
                'section_title'      => $title,
                'section_controller' => $controller,
                'disp_order'         => $order,
                'actual_privilege'   => $section[2] ?? '1,2,3,4,5,6',
                'status'             => '1',
            ]);
        }
    }

    protected function report(string $message): void
    {
        is_cli() ? CLI::write($message, 'yellow') : log_message('warning', 'EventsUpdatesSeeder: ' . $message);
    }
}
