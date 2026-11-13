<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use Modules\Blog\Controllers\Blog;
use Modules\Pages\Models\PagesModel;
use Modules\Sitepanel\Models\MetaModel;

/**
 * The website's pages and their addresses: Sitepanel > Manage Static Pages
 * (wl_cms_pages) and wl_meta_tags, the URL registry through which every
 * page and module address is routed (as the reference's
 * create_listing_page_meta() made its rows):
 *
 *  1. a static page per designed page of the website (Contact Us, Sitemap,
 *     Privacy, Terms), layout = its designed view;
 *  2. the module pages' rows (home, blogs, blog categories, testimonials);
 *  3. each blog post's row (blog/detail + blog_id, page_url blogs/<slug>);
 *  4. each static page's row, named after the page as in the reference:
 *     entity_type pages/index/<friendly_url>, entity_id = page_id.
 *
 * Safe to run again: a page or row that already exists is reused, never
 * doubled (page_url is unique). A static page already at a designed page's
 * address becomes that page — shown as its text when it is Privacy or
 * Terms with text of its own (as it was shown before), else in
 * the designed view; a row already at an address keeps its SEO data. An
 * address held by something else is reported and left alone.
 */
class PageMetaTagsSeeder extends Seeder
{
    /**
     * Designed pages: view => [address, page title, SEO title, SEO description].
     */
    protected array $pages = [
        'contact_us'     => ['contactus', 'Contact Us', 'Contact Us | CustomCart', 'Contact CustomCart by phone, email or the enquiry form.'],
        'privacy_policy' => ['privacy-policy', 'Privacy Policy', 'Privacy Policy | CustomCart', 'Privacy Policy of CustomCart.'],
        'terms'          => ['terms-conditions', 'Terms & Conditions', 'Terms & Conditions | CustomCart', 'Terms & Conditions of CustomCart.'],
        'refund_policy'  => ['refund-policy', 'Refund Policy', 'Refund Policy | CustomCart', 'Returns, refunds and exchanges for orders placed through the CustomCart online store.'],
    ];

    /**
     * Designed pages a static page with text of its own at their address
     * keeps showing as its text.
     */
    protected array $text_wins = ['privacy_policy', 'terms'];

    /**
     * Module pages: entity_type => [address, SEO title, SEO description].
     */
    protected array $modules = [
        'home/index'         => [MetaModel::HOME_URL, 'Home', ''],
        'blog/index'         => ['blogs', 'Blog | CustomCart', 'News, articles and updates from CustomCart.'],
        'blog/categories'    => ['blogs/categories', 'Blog Categories | CustomCart', 'Browse the CustomCart blog by topic.'],
        'pages/sitemap'      => ['sitemap', 'Sitemap | CustomCart', 'Sitemap of the CustomCart website.'],
        'testimonials/index' => ['testimonials', 'Testimonials | CustomCart', 'Read what our customers say about CustomCart, and share your own testimonial.'],
    ];

    public function run(): void
    {
        // 1. Designed pages.
        foreach ($this->pages as $view => [$url, $name, $title, $description]) {
            if ($this->db->table('wl_cms_pages')->where(['layout' => $view, 'status <>' => '2'])->countAllResults() > 0) {
                continue; // a page is already shown in this design
            }
            $row  = $this->row(['page_url' => $url]);
            $page = $this->db->table('wl_cms_pages')->where(['friendly_url' => $url, 'status <>' => '2'])->get()->getRowArray();
            $free = $row === null || (string) $row['entity_type'] === ''
                || ($page && str_starts_with((string) $row['entity_type'], MetaModel::PAGE_TYPE_PREFIX) && (int) $row['entity_id'] === (int) $page['page_id']);

            if (! $free) {
                $this->report('Address "' . $url . '" is used by ' . $row['entity_type'] . '; the ' . $name . ' page was not added.');

                continue;
            }

            if (! $page) {
                $this->db->table('wl_cms_pages')->insert([
                    'parent_id' => 0, 'page_name' => $name, 'friendly_url' => $url, 'layout' => $view, 'page_description' => '',
                    'page_short_description' => null, 'image' => '', 'status' => '1', 'page_updated_date' => date('Y-m-d H:i:s'),
                ]);
                $page = ['page_id' => $this->db->insertID(), 'page_name' => $name, 'page_description' => '', 'layout' => $view];
            } elseif ((string) ($page['layout'] ?? '') === '' && ! (in_array($view, $this->text_wins, true) && trim(strip_tags((string) $page['page_description'])) !== '')) {
                $this->db->table('wl_cms_pages')->where('page_id', $page['page_id'])->update(['layout' => $view]);
            }

            $link = ['entity_type' => MetaModel::page_type($url), 'entity_id' => (int) $page['page_id'], 'is_fixed' => 'Y'];

            if ($row) {
                $this->db->table('wl_meta_tags')->where('meta_id', $row['meta_id'])->update($link);
            } else {
                $this->db->table('wl_meta_tags')->insert($link + [
                    'page_url' => $url, 'meta_title' => $title, 'meta_description' => $description, 'meta_keyword' => '', 'status' => '1',
                ]);
            }
        }

        // 2. Module pages.
        foreach ($this->modules as $type => [$url, $title, $description]) {
            $row = $this->row(['page_url' => $url]);
            if ($row === null) {
                $this->db->table('wl_meta_tags')->insert([
                    'is_fixed' => 'Y', 'entity_type' => $type, 'entity_id' => 0, 'page_url' => $url,
                    'meta_title' => $title, 'meta_description' => $description, 'meta_keyword' => '', 'status' => '1',
                ]);
            } elseif ((string) $row['entity_type'] === '') {
                $this->db->table('wl_meta_tags')->where('meta_id', $row['meta_id'])->update(['is_fixed' => 'Y', 'entity_type' => $type, 'entity_id' => 0]);
            } elseif ($row['entity_type'] !== $type) {
                $this->report('Address "' . $url . '" is used by ' . $row['entity_type'] . '; the module page was not registered.');
            }
        }

        // 3. Blog posts.
        foreach ($this->db->table('wl_blogs')->get()->getResultArray() as $post) {
            $url = 'blogs/' . $post['friendly_url'];
            $own = $this->row(['entity_type' => Blog::META_TYPE, 'entity_id' => (int) $post['blog_id']]);
            $at  = $this->row(['page_url' => $url]);

            if ($own) {
                $own['page_url'] !== $url && $at === null && $this->db->table('wl_meta_tags')->where('meta_id', $own['meta_id'])->update(['page_url' => $url]);

                continue;
            }
            if ($at) {
                (string) $at['entity_type'] === ''
                    ? $this->db->table('wl_meta_tags')->where('meta_id', $at['meta_id'])->update(['entity_type' => Blog::META_TYPE, 'entity_id' => (int) $post['blog_id']])
                    : $this->report('Blog post #' . $post['blog_id'] . ': the address "' . $url . '" is used by ' . $at['entity_type'] . '.');

                continue;
            }
            $this->db->table('wl_meta_tags')->insert([
                'is_fixed' => 'N', 'entity_type' => Blog::META_TYPE, 'entity_id' => (int) $post['blog_id'], 'page_url' => $url, 'status' => '1',
                'meta_title' => $post['title'], 'meta_keyword' => $post['title'], 'meta_description' => mb_substr(trim(strip_tags((string) $post['excerpt'])), 0, 160),
            ]);
        }

        // 4. Other static pages (shown as their text).
        foreach ($this->db->table('wl_cms_pages')->where('status <>', '2')->get()->getResultArray() as $page) {
            $url = (string) $page['friendly_url'];
            $own = $this->db->table('wl_meta_tags')->where('entity_id', (int) $page['page_id'])->like('entity_type', MetaModel::PAGE_TYPE_PREFIX, 'after')->get()->getRowArray();
            $at  = $this->row(['page_url' => $url]);

            if ($own) {
                if (($own['page_url'] !== $url && $at === null) || $own['entity_type'] !== MetaModel::page_type($url)) {
                    $this->db->table('wl_meta_tags')->where('meta_id', $own['meta_id'])->update(['page_url' => $at === null ? $url : $own['page_url'], 'entity_type' => MetaModel::page_type($at === null ? $url : $own['page_url'])]);
                }

                continue;
            }
            if ($at) {
                $this->report('Static page #' . $page['page_id'] . ' ("' . $url . '"): the address is used by ' . ($at['entity_type'] ?: 'a Meta Tags row') . '; change the page address in Manage Static Pages.');

                continue;
            }
            $this->db->table('wl_meta_tags')->insert(PagesModel::meta_for((string) $page['page_name'], (string) $page['page_description']) + [
                'is_fixed' => 'N', 'entity_type' => MetaModel::page_type($url), 'entity_id' => (int) $page['page_id'], 'page_url' => $url, 'status' => '1',
            ]);
        }
    }

    protected function row(array $where): ?array
    {
        return $this->db->table('wl_meta_tags')->where($where)->get()->getRowArray();
    }

    protected function report(string $message): void
    {
        is_cli() ? CLI::write('  ' . $message, 'yellow') : log_message('warning', 'PageMetaTagsSeeder: ' . $message);
    }
}
