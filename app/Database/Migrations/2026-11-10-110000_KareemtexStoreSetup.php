<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CustomCart Phase 2 configuration and content (approved clean-up of the
 * previous project's records):
 *
 *  - SAR the default currency (amounts are not converted; the store has no
 *    priced records yet);
 *  - settings: VAT 15% added on top of prices (gst_percent), low-stock
 *    threshold, CustomCart SEO / contact values; the previous project's
 *    homepage figures removed;
 *  - static pages: the previous project's pages removed, About Us, Contact,
 *    Privacy, Terms, Returns and Refunds as CustomCart pages;
 *  - wl_meta_tags: titles rebranded, Blog / delivery rows removed, the new
 *    pages registered (URL registry);
 *  - Sitepanel sections: removed modules' rows deleted, new sections added;
 *  - Saudi Arabia cities (Manage Location) for checkout.
 *
 * down() restores only what can be restored safely (currency, sections).
 */
class KareemtexStoreSetup extends Migration
{
    /**
     * New pages: entity_type => [address, SEO title].
     */
    protected array $pages = [
        'team/index'              => ['our-team', 'Our Team'],
        'team/partners'           => ['partners', 'Our Partners'],
        'industries/index'        => ['industries', 'Industries'],
        'certificates/index'      => ['certificates', 'Certificates'],
        'certificates/brochure'   => ['brochure', 'Brochures'],
        'news/index'              => ['news-and-events', 'News & Events'],
        'pages/faq'               => ['faq', 'Frequently Asked Questions'],
        'pages/send_inquiry'      => ['send-inquiry', 'Send Inquiry'],
        'pages/refer_friend'      => ['refer-a-friend', 'Refer a Friend'],
        'cart/guest'              => ['guest-checkout', 'Guest Checkout'],
        'members/address'         => ['acc-address', 'My Address'],
        'members/coupons'         => ['acc-coupons', 'My Coupons'],
        'members/track_order'     => ['track-order', 'Track Order'],
        'members/item_request'    => ['item-request', 'Cancel / Return Item'],
    ];

    /**
     * Sitepanel sections: controller => [title, heading, display order, privileges].
     */
    protected array $sections = [
        'certificates'  => ['Manage Certificates & Brochures', 'Other Management', 16, '1,2,3,4,5,6'],
        'industries'    => ['Manage Industries', 'Other Management', 17, '1,2,3,4,5,6'],
        'marquee'       => ['Manage Running Marquee', 'Other Management', 18, '1,2,3,4,5,6'],
        'referrals'     => ['Manage Refer a Friend', 'Other Management', 19, '1,6'],
        'returns'       => ['Manage Returns & Refunds', 'Manage Orders', 5, '1,3,6'],
    ];

    /**
     * Previous project sections (no controller in this project).
     */
    protected array $removed_sections = ['courses', 'course_enquiries', 'volunteers', 'services', 'stories', 'events',
        'event_registrations', 'donation_orders', 'blogs', 'blog_categories', 'blog_comments'];

    protected array $saudi_cities = ['Riyadh', 'Jeddah', 'Makkah', 'Madinah', 'Dammam', 'Khobar', 'Dhahran', 'Jubail', 'Qatif', 'Al Ahsa',
        'Hafr Al Batin', 'Taif', 'Tabuk', 'Buraidah', 'Unaizah', 'Abha', 'Khamis Mushait', 'Hail', 'Jazan', 'Najran',
        'Al Bahah', 'Arar', 'Sakaka', 'Yanbu', 'Al Kharj', 'Ras Tanura', 'Abqaiq', 'Rabigh'];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        // Currency: SAR.
        $currencies = $this->db->table('wl_currencies');
        if ($currencies->where('code', 'SAR')->countAllResults() === 0) {
            $currencies->insert(['title' => 'Saudi Riyal', 'code' => 'SAR', 'symbol_left' => 'SAR', 'symbol_right' => '', 'curr_image' => '',
                'decimal_place' => '2', 'value' => 1, 'is_default' => 'N', 'status' => 1, 'date_modified' => $now]);
        }
        $this->db->table('wl_currencies')->where('code !=', 'SAR')->update(['is_default' => 'N', 'date_modified' => $now]);
        $this->db->table('wl_currencies')->where('code', 'SGD')->update(['status' => 0]);
        $this->db->table('wl_currencies')->where('code', 'SAR')->update(['title' => 'Saudi Riyal', 'symbol_left' => 'SAR', 'is_default' => 'Y', 'status' => 1, 'date_modified' => $now]);

        // Settings.
        $settings = [
            'gst_percent'         => '15.00',
            'low_stock_threshold' => '5',
            'meta_title'          => 'CustomCart | Industrial Workwear & FR Uniforms',
            'meta_description'    => 'CustomCart manufactures industrial workwear and FR uniforms in Dammam, Saudi Arabia, since 1980.',
            'site_tagline'        => 'Industrial workwear and FR uniform manufacturer. Established 1980, Dammam, Saudi Arabia.',
            'footer_description'  => 'Industrial workwear and FR uniform manufacturer. Established 1980, Dammam, Saudi Arabia.',
            'contact_phone'       => '0138220000',
            'whatsapp_no'         => '+966138220000',
            'whatsapp_text'       => 'CustomCart',
            'footer_copyright'    => '© {year} CustomCart. All Rights Reserved',
            'office_hours'        => 'Sun – Thu: 8:00 AM – 6:00 PM, Fri – Sat: Closed',
        ];
        foreach ($settings as $key => $value) {
            $exists = $this->db->table('tbl_site_settings')->where('setting_key', $key)->countAllResults() > 0;
            $exists
                ? $this->db->table('tbl_site_settings')->where('setting_key', $key)->update(['setting_value' => $value, 'updated_at' => $now])
                : $this->db->table('tbl_site_settings')->insert(['setting_key' => $key, 'setting_value' => $value, 'updated_at' => $now]);
        }
        $this->db->table('tbl_site_settings')->whereIn('setting_key', ['programs_conducted', 'active_volunteers', 'community_activities', 'people_reached'])->delete();

        // Static pages.
        $pages = $this->db->table('wl_cms_pages');
        $old   = array_column($pages->whereIn('friendly_url', ['mission-and-story', 'cch-foundation', 'yuan-shi-dian-dr-zhang',
            'southeast-asia-original-point-promotion-office', 'singapore-zhang-zhaohan-promotion-center', 'donations'])->get()->getResultArray(), 'page_id');
        if ($old) {
            $this->db->table('wl_meta_tags')->whereIn('entity_id', $old)->like('entity_type', 'pages/index/', 'after')->delete();
            $this->db->table('wl_cms_pages')->whereIn('page_id', $old)->delete();
        }
        $text = static fn (string $title, string $intro) => '<h2>' . $title . '</h2><p>' . $intro . '</p>';
        $this->save_page('contactus', 'Contact Us', 'contact_us', '<p>Questions about our workwear, bulk orders or an online order? Send us a message and our Dammam team will reply within one working day.</p>');
        $this->save_page('aboutus', 'About CustomCart', 'about_us', '<p>Since 1980, CustomCart has manufactured industrial workwear and FR uniforms in Dammam, Saudi Arabia, for oil &amp; gas, petrochemical and industrial teams across the Kingdom.</p>');
        $this->save_page('privacy-policy', 'Privacy Policy', null, $text('Privacy Policy', 'This Privacy Policy explains how CustomCart collects, uses and protects the personal information you give us when you use this website and place orders.'));
        $this->save_page('terms-conditions', 'Terms & Conditions', null, $text('Terms &amp; Conditions', 'These Terms &amp; Conditions apply to the use of the CustomCart website and to orders placed through our online store.'));
        $this->save_page('returns', 'Returns', null, $text('Returns', 'You can ask to return a delivered item from My Account &gt; Orders History. Our team reviews every request and contacts you with the next steps.'), 'refund-policy');
        $this->save_page('refunds', 'Refunds', null, $text('Refunds', 'Approved returns and cancelled paid items are refunded to the original payment method. You can follow the refund status of each item in My Account.'));

        // URL registry: rebrand, drop removed pages, register the new ones.
        $meta = $this->db->table('wl_meta_tags');
        $meta->whereIn('entity_type', ['blog/index', 'blog/categories', 'blog', 'cart/delivery'])->delete();
        $this->db->table('wl_meta_tags')->where('entity_type', 'home/index')->update(['meta_title' => 'CustomCart | Industrial Workwear & FR Uniforms', 'meta_description' => $settings['meta_description']]);
        foreach ($this->db->table('wl_meta_tags')->select('meta_id, meta_title, meta_description')->get()->getResultArray() as $row) {
            $title = preg_replace('/\s*[|\-]\s*(Singapore YSD Association|YSD Online Store|Yuan Shi Dian \(Singapore\) Limited)$/', ' | CustomCart', (string) $row['meta_title']);
            $desc  = preg_match('/YSD|Yuan|Singapore|volunteer/i', (string) $row['meta_description']) ? '' : (string) $row['meta_description'];
            $this->db->table('wl_meta_tags')->where('meta_id', $row['meta_id'])->update(['meta_title' => str_replace(['Gallery | CustomCart', 'Video | CustomCart'], ['Images Gallery | CustomCart', 'Video Gallery | CustomCart'], $title), 'meta_description' => $desc]);
        }
        foreach ($this->pages as $type => [$url, $title]) {
            $at = $this->db->table('wl_meta_tags')->where('page_url', $url)->get()->getRowArray();
            if ($at) {
                $at['entity_type'] === $type || $this->db->table('wl_meta_tags')->where('meta_id', $at['meta_id'])->update(['entity_type' => $type, 'entity_id' => 0]);

                continue;
            }
            if ($this->db->table('wl_meta_tags')->where('entity_type', $type)->countAllResults() > 0) {
                $this->db->table('wl_meta_tags')->where('entity_type', $type)->update(['page_url' => $url]);

                continue;
            }
            $this->db->table('wl_meta_tags')->insert(['is_fixed' => 'Y', 'entity_type' => $type, 'entity_id' => 0, 'page_url' => $url,
                'meta_title' => $title . ' | CustomCart', 'meta_description' => '', 'meta_keyword' => '', 'status' => '1']);
        }

        // Sitepanel sections.
        $ids = array_column($this->db->table('tbl_admin_sections')->select('id')->whereIn('section_controller', $this->removed_sections)->get()->getResultArray(), 'id');
        if ($ids) {
            $this->db->table('tbl_admin_allowed_sections')->whereIn('sec_id', $ids)->delete();
            $this->db->table('tbl_admin_sections')->whereIn('id', $ids)->delete();
        }
        $this->db->table('tbl_admin_sections')->where('section_controller', 'team')->update(['section_title' => 'Manage Team & Partners']);
        $this->db->table('tbl_admin_sections')->where('section_controller', 'news')->update(['section_title' => 'Manage News & Events']);
        $this->db->table('tbl_admin_sections')->where('section_controller', 'material')->update(['section_title' => 'Manage Fabrics', 'status' => '1']);
        $this->db->table('tbl_admin_sections')->where('section_controller', 'reviews')->countAllResults() === 0
            && $this->sections['reviews'] = ['Manage Product Testimonials', 'Product Management', 4, '1,4,5,6'];
        foreach ($this->sections as $controller => [$title, $heading, $order, $privileges]) {
            if ($this->db->table('tbl_admin_sections')->where('section_controller', $controller)->countAllResults() > 0) {
                continue;
            }
            $parent = $this->db->table('tbl_admin_sections')->where(['parent_id' => 0, 'section_title' => $heading])->get()->getRowArray();
            $parent && $this->db->table('tbl_admin_sections')->insert(['parent_id' => (int) $parent['id'], 'section_title' => $title,
                'section_controller' => $controller, 'disp_order' => $order, 'actual_privilege' => $privileges, 'status' => '1']);
        }

        // Saudi Arabia cities (Manage Location).
        $sa = $this->db->table('wl_countries')->where('iso_code_2', 'SA')->get()->getRowArray();
        if ($sa) {
            $this->db->table('wl_countries')->where('country_id', $sa['country_id'])->update(['status' => '1']);
            foreach ($this->saudi_cities as $city) {
                $this->db->table('wl_state')->where(['country_id' => $sa['country_id'], 'region_name' => $city])->countAllResults() === 0
                    && $this->db->table('wl_state')->insert(['country_id' => $sa['country_id'], 'region_name' => $city, 'status' => '1']);
            }
        }
    }

    /**
     * A static page by address (created when missing), with its route row.
     */
    protected function save_page(string $url, string $name, ?string $layout, string $html, ?string $old_url = null): void
    {
        $now  = date('Y-m-d H:i:s');
        $page = $old_url !== null ? $this->db->table('wl_cms_pages')->where('friendly_url', $old_url)->get()->getRowArray() : null;
        $page ??= $this->db->table('wl_cms_pages')->where('friendly_url', $url)->get()->getRowArray();
        $data = ['page_name' => $name, 'friendly_url' => $url, 'layout' => $layout, 'page_description' => $html, 'status' => '1', 'parent_id' => 0];

        if ($page) {
            $this->db->table('wl_cms_pages')->where('page_id', $page['page_id'])->update($data);
            $id = (int) $page['page_id'];
        } else {
            $fields = $this->db->getFieldNames('wl_cms_pages');
            in_array('page_date_added', $fields, true) && $data['page_date_added'] = $now;
            in_array('page_added_date', $fields, true) && $data['page_added_date'] = $now;
            $this->db->table('wl_cms_pages')->insert($data);
            $id = (int) $this->db->insertID();
        }

        $this->db->table('wl_meta_tags')->where('entity_id', $id)->like('entity_type', 'pages/index/', 'after')->delete();
        $this->db->table('wl_meta_tags')->where('page_url', $url)->delete();
        $this->db->table('wl_meta_tags')->insert(['is_fixed' => 'Y', 'entity_type' => 'pages/index/' . $url, 'entity_id' => $id, 'page_url' => $url,
            'meta_title' => $name . ' | CustomCart', 'meta_description' => '', 'meta_keyword' => '', 'status' => '1']);
    }

    public function down(): void
    {
        $this->db->table('wl_currencies')->where('code', 'SAR')->update(['is_default' => 'N']);
        $this->db->table('wl_currencies')->where('code', 'SGD')->update(['is_default' => 'Y', 'status' => 1]);
        $this->db->table('tbl_admin_sections')->whereIn('section_controller', array_keys($this->sections))->delete();
        $this->db->table('wl_meta_tags')->whereIn('entity_type', array_keys($this->pages))->delete();
    }
}
