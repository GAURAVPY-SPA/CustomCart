<?php

/**
 * Sitepanel view helpers (CodeIgniter 3: modules/sitepanel/helpers/sitepanel_helper.php).
 */
if (! function_exists('sp_view')) {
    /**
     * Render a sitepanel view by its module-relative name, e.g.
     * sp_view('includes/header') — the CodeIgniter 3 HMVC call
     * $this->load->view('includes/header') inside the sitepanel module.
     * View data is shared between the views of one page, as in CodeIgniter 3.
     */
    function sp_view(string $view, array $data = []): string
    {
        return view('Modules\Sitepanel\Views\\' . $view, $data);
    }
}

if (! function_exists('sp_icon')) {
    /**
     * Inline SVG icon (24px grid, stroke style). Decorative: hidden from
     * screen readers, so the surrounding element must carry the label.
     */
    function sp_icon(string $name): string
    {
        static $paths = [
            'dashboard'  => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
            'globe'      => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
            'key'        => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3L21 2M17 6l3 3M15 8l2 2"/>',
            'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'menu'       => '<path d="M3 6h18M3 12h18M3 18h18"/>',
            'user'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'users'      => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 4a4 4 0 0 1 0 8M22 21a7 7 0 0 0-4-6.3"/>',
            'user-check' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 11l2 2 4-4"/>',
            'database'   => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
            'chevron'    => '<path d="M6 9l6 6 6-6"/>',
            'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
            'section'    => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 12h6"/>',
            'external'   => '<path d="M14 3h7v7M10 14L21 3M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/>',
            'image'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
            'video'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M10 9l5 3-5 3z"/>',
            'banner'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/>',
            'file'       => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
            'message'    => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'mail'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
            'inbox'      => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13L22 12v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/>',
            'folder'     => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
            'plus'       => '<path d="M12 5v14M5 12h14"/>',
            'sliders'    => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
            'box'        => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>',
            'cart'       => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a1 1 0 0 0 1 .8h9.6a1 1 0 0 0 1-.8L21 7H6"/>',
            'truck'      => '<path d="M1 4h14v12H1zM15 9h4l3 3v4h-7"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/>',
            'chart'      => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-7"/>',
            'alert'      => '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        ];

        if (! isset($paths[$name])) {
            return '';
        }

        return '<svg class="sp-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
    }
}

if (! function_exists('sp_datetime')) {
    /**
     * Format a MySQL DATETIME for display ("13 Oct 2026, 16:05"); empty string when unset.
     *
     * @param string|null $value
     */
    function sp_datetime($value): string
    {
        $time = $value ? strtotime($value) : false;

        return $time ? date('d M Y, H:i', $time) : '';
    }
}

if (! function_exists('sp_section_icon')) {
    /**
     * Sidebar icon for a tbl_admin_sections controller. Sections without a
     * dedicated icon get a neutral one; add entries here as modules are built.
     *
     * @return string sp_icon() name
     */
    function sp_section_icon(string $controller): string
    {
        static $icons = [
            'dashboard'            => 'dashboard',
            'settings'             => 'settings',
            'banners'              => 'banner',
            'staticpages'          => 'file',
            'testimonial'          => 'message',
            'newsletter'           => 'mail',
            'enquiry'              => 'inbox',
            'product_enquiry'      => 'inbox',
            'coupons'              => 'section',
            'header_images'        => 'image',
            'gallery'              => 'image',
            'video'                => 'video',
            'team'                 => 'users',
            'events'               => 'banner',
            'news'                 => 'file',
            'certificates'         => 'file',
            'marquee'              => 'banner',
            'referrals'            => 'mail',
            'returns'              => 'cart',
            'faq'                  => 'message',
            'subadmin'             => 'users',
            'location'             => 'globe',
            'meta'                 => 'file',
            'change_password'      => 'key',
            // Online store (Product Management, Members Management, Manage Orders, Manage Entities)
            'category'             => 'folder',
            'products'             => 'box',
            'color'                => 'sliders',
            'size'                 => 'sliders',
            'brand'                => 'section',
            'material'             => 'section',
            'reviews'              => 'message',
            'shipping'             => 'truck',
            'orders'               => 'cart',
            'members'              => 'users',
            'products_bulk'        => 'file',
            'order_products'       => 'box',
            'reasons'              => 'message',
        ];

        return $icons[$controller] ?? 'section';
    }
}

if (! function_exists('sp_theme_style')) {
    /**
     * <style> block with the sitepanel theme colours (CSS custom properties).
     * Printed in the <head> of every sitepanel page, after sitepanel.css.
     */
    function sp_theme_style(): string
    {
        return '<style id="sp-theme">' . service('sitepanelTheme')->css() . '</style>';
    }
}

if (! function_exists('sp_is_web_url')) {
    /**
     * Validation rule: empty, or an absolute http(s) URL with a host
     * (CodeIgniter's valid_url also accepts "example.com" and other schemes).
     *
     * @param string|null $value
     */
    function sp_is_web_url($value): bool
    {
        $value = (string) $value;
        if ($value === '') {
            return true;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host   = (string) parse_url($value, PHP_URL_HOST);

        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array($scheme, ['http', 'https'], true)
            && $host !== '';
    }
}

if (! function_exists('sp_slug')) {
    /**
     * URL slug: lower-case a-z, 0-9 and single hyphens ("About Us!" -> "about-us").
     */
    function sp_slug($text, int $max_length = 50): string
    {
        helper('text');

        $slug = strtolower(convert_accented_characters((string) $text));
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $slug), '-');

        return trim(substr($slug, 0, $max_length), '-');
    }
}

if (! function_exists('sp_page_slug')) {
    /**
     * Page URL segment made from a name (categories, products; the
     * reference's seo_url_title()): sp_slug() with apostrophes dropped
     * first, so "Men's Fashion" -> "mens-fashion", "Books & Media" -> "books-media".
     */
    function sp_page_slug($name, int $max_length = 100): string
    {
        return sp_slug(str_replace(["'", '’', '‘', '`'], '', (string) $name), $max_length);
    }
}

if (! function_exists('sp_valid_date')) {
    /**
     * Whether $value is a real calendar date written exactly as Y-m-d,
     * e.g. '2024-02-29' yes, '2023-02-29', '2024-2-9' and '' no. The value is
     * not trimmed.
     */
    function sp_valid_date(string $value): bool
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
