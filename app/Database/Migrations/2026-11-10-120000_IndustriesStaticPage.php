<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Industries becomes a static page (Sitepanel > Manage Static Pages):
 *
 *  - a published CMS page "Industries" at /industries (text layout,
 *    Pages::render_cms()), its text made from the page as it was: the
 *    introduction, each active industry of the former Manage Industries
 *    (heading, description, highlights) and the closing contact line;
 *  - the address row industries/index becomes the page's pages/index/industries;
 *  - the Manage Industries Sitepanel section and its sub-admin permissions are removed.
 *
 * The wl_industries table and its rows are kept (no table is dropped without
 * approval); nothing reads them any more.
 *
 * down() removes the page and its address row only; the former Manage
 * Industries code is no longer part of the project.
 */
class IndustriesStaticPage extends Migration
{
    protected const URL  = 'industries';
    protected const NAME = 'Industries';

    /**
     * The design's industries (html/industries.htm), used when wl_industries has none (a new installation).
     */
    protected const DEFAULTS = [
        ['oil-and-gas', 'Oil & Gas', 'Flame-resistant coveralls and two-piece uniforms for upstream, midstream and downstream operations, designed to protect against flash fire and electric arc while staying breathable in high heat.',
            "FR treated and inherent FR fabrics
Reflective tape for low-light and night shifts
Company logos, name tags and colour coding on request"],
        ['petrochemical', 'Petrochemical', 'Protective clothing for refineries and chemical plants, where workers need resistance to heat, sparks and splashes without losing freedom of movement.',
            "Anti-static and chemical-splash resistant options
Heavy-duty stitching and reinforced stress points
Sizes and fits for large crews"],
        ['industrial-and-manufacturing', 'Industrial & Manufacturing', 'Durable everyday uniforms, work jackets and trousers for factories, workshops, warehouses and maintenance teams, made to last through daily wear and industrial laundering.',
            "Cotton drill and poly-cotton blends
Hi-vis workwear for logistics and site safety
Bulk orders with consistent quality and on-time delivery"],
    ];

    public function up(): void
    {
        $now  = date('Y-m-d H:i:s');
        $page = $this->db->table('wl_cms_pages')->where('friendly_url', self::URL)->where('status <>', '2')->get()->getRowArray();

        if ($page) {
            $id = (int) $page['page_id']; // already a static page: its text is kept
        } else {
            $this->db->table('wl_cms_pages')->insert([
                'parent_id' => 0, 'page_name' => self::NAME, 'friendly_url' => self::URL, 'layout' => null,
                'page_description' => $this->content(), 'page_short_description' => null, 'image' => '',
                'status' => '1', 'page_updated_date' => $now,
            ]);
            $id = (int) $this->db->insertID();
        }

        // The address: one row, now the static page's (keeps an SEO title set for the old page).
        $old = $this->db->table('wl_meta_tags')->where('page_url', self::URL)->get()->getRowArray();
        $this->db->table('wl_meta_tags')->where('page_url', self::URL)->delete();
        $this->db->table('wl_meta_tags')->where('entity_id', $id)->where('entity_type', 'pages/index/' . self::URL)->delete();
        $this->db->table('wl_meta_tags')->insert([
            'is_fixed' => 'Y', 'entity_type' => 'pages/index/' . self::URL, 'entity_id' => $id, 'page_url' => self::URL,
            'meta_title' => (string) ($old['meta_title'] ?? '') !== '' ? $old['meta_title'] : self::NAME . ' | CustomCart',
            'meta_description' => (string) ($old['meta_description'] ?? ''), 'meta_keyword' => (string) ($old['meta_keyword'] ?? ''), 'status' => '1',
        ]);

        // Sitepanel: the Manage Industries section and the sub-admin permissions on it.
        $section = $this->db->table('tbl_admin_sections')->where('section_controller', 'industries')->get()->getRowArray();
        if ($section) {
            $this->db->table('tbl_admin_allowed_sections')->where('sec_id', (int) $section['id'])->delete();
            $this->db->table('tbl_admin_sections')->where('id', (int) $section['id'])->delete();
        }
    }

    public function down(): void
    {
        $page = $this->db->table('wl_cms_pages')->where('friendly_url', self::URL)->get()->getRowArray();
        if ($page) {
            $this->db->table('wl_meta_tags')->where('entity_type', 'pages/index/' . self::URL)->where('entity_id', (int) $page['page_id'])->delete();
            $this->db->table('wl_cms_pages')->where('page_id', (int) $page['page_id'])->delete();
        }
    }

    /**
     * The page's text: the former /industries page with its active industries.
     */
    protected function content(): string
    {
        $e    = static fn ($text) => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        $rows = $this->db->tableExists('wl_industries')
            ? $this->db->table('wl_industries')->where('status', '1')->orderBy('sort_order ASC, industry_id ASC')->get()->getResultArray()
            : [];
        if (! $rows) {
            foreach (self::DEFAULTS as [$url, $name, $text, $points]) {
                $rows[] = ['friendly_url' => $url, 'industry_name' => $name, 'short_description' => $text, 'highlights' => $points];
            }
        }

        $html = '<h2>Industries We Serve</h2>'
            . '<p>Since 1980, CustomCart has manufactured industrial workwear and FR uniforms for teams that work in some of the most demanding conditions in the Kingdom. '
            . 'Every garment is built around the hazards, climate and compliance needs of the industry it serves.</p>';

        foreach ($rows as $row) {
            $html .= '<h3 id="' . $e($row['friendly_url']) . '">' . $e($row['industry_name']) . '</h3>';
            if (trim((string) $row['short_description']) !== '') {
                $html .= '<p>' . nl2br($e(trim($row['short_description'])), false) . '</p>';
            }
            $points = array_filter(array_map('trim', preg_split('/\R/', (string) $row['highlights'])), 'strlen');
            if ($points) {
                $html .= '<ul>' . implode('', array_map(static fn ($p) => '<li>' . $e($p) . '</li>', $points)) . '</ul>';
            }
        }

        return $html . '<p>Need workwear for a different industry? <a href="contactus">Contact our team</a> and we will recommend the right fabric, design and protection level for your workforce.</p>';
    }
}
