<?php

/**
 * Application-wide helpers (autoloaded, see Config\Autoload::$helpers).
 *
 * asset_url() ... trace() are the CodeIgniter 3 app helper functions.
 * html_escape(), form_error() and redirect_now() keep the CodeIgniter 3
 * behaviour the migrated views and controllers rely on.
 */

use CodeIgniter\HTTP\Exceptions\RedirectException;

if (! function_exists('asset_url')) {
    /**
     * URL of a file under the web root (public/), with its modification time
     * appended so browsers pick up edited CSS/JS without a hard refresh.
     *
     * @param string $path Path relative to the web root, e.g. "assets/sitepanel/css/sitepanel.css"
     */
    function asset_url($path): string
    {
        $path = ltrim((string) $path, '/');
        $file = FCPATH . $path;

        return base_url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
    }
}

if (! function_exists('website_url')) {
    /**
     * URL of a page of the public website (Config\App::$websiteURL, else the
     * base URL), e.g. website_url('blogs/my-post'). In CodeIgniter 3 the
     * sitepanel and the website shared one base URL, so these links used
     * base_url() / site_url(); as there, a leading slash is dropped and a
     * trailing one kept ('blogs/' -> .../blogs/).
     */
    function website_url(string $path = ''): string
    {
        $url  = trim(config('App')->websiteURL);
        $root = $url !== '' ? rtrim($url, '/') . '/' : base_url();

        return $root . ltrim($path, '/');
    }
}

if (! function_exists('seo_head')) {
    /**
     * SEO tags for a website page's <head> (title, description, keywords,
     * canonical, Open Graph) from its wl_meta_tags row; see
     * App\Libraries\PageMeta::head() for $page (page defaults, record entity).
     */
    function seo_head(array $page = []): string
    {
        return view('includes/seo_head', ['seo' => service('pageMeta')->head($page)], ['saveData' => false]);
    }
}

if (! function_exists('theme_url')) {
    /**
     * Base URL for the active designer theme assets.
     */
    function theme_url(): string
    {
        return base_url('assets/designer/themes/default/');
    }
}

if (! function_exists('map_embed_src')) {
    /**
     * The Google Maps embed URL in a stored map code (an <iframe ...> snippet
     * or the bare URL), or '' when it is anything else. Pages print their own
     * <iframe> with this src, never the stored markup.
     *
     * @param string|null $code
     */
    function map_embed_src($code): string
    {
        $code = trim((string) $code);
        if ($code === '') {
            return '';
        }
        if (str_contains($code, '<')) {
            // Exactly one iframe and nothing executable around it.
            if (preg_match_all('/<\s*iframe\b/i', $code) !== 1 || preg_match('/<\s*(script|object|embed|link|style|meta)\b|\son[a-z]+\s*=/i', $code) === 1
                || preg_match('/<\s*iframe\b[^>]*\ssrc\s*=\s*(["\'])(.*?)\1/is', $code, $m) !== 1) {
                return '';
            }
            $code = $m[2];
        }

        $url    = html_entity_decode($code, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host   = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path   = (string) parse_url($url, PHP_URL_PATH);

        $allowed = ['www.google.com', 'google.com', 'maps.google.com'];

        return ($scheme === 'https' && in_array($host, $allowed, true) && str_starts_with($path, '/maps')
            && filter_var($url, FILTER_VALIDATE_URL) !== false && preg_match('/[\s"\'<>]/', $url) !== 1)
            ? $url : '';
    }
}

if (! function_exists('phone_digits')) {
    /**
     * A phone number reduced to digits with an optional leading "+", for
     * tel: (keep_plus) and wa.me links.
     *
     * @param string|null $number
     */
    function phone_digits($number, $keep_plus = true): string
    {
        $number = trim((string) $number);
        $digits = preg_replace('/\D+/', '', $number);

        return ($keep_plus && $digits !== '' && str_starts_with($number, '+')) ? '+' . $digits : $digits;
    }
}

if (! function_exists('split_name')) {
    /**
     * A full name as [first, last]: the first word and the rest ('' when
     * there is only one word), e.g. "Jane van Doe" gives ['Jane', 'van Doe'].
     *
     * @return array{0: string, 1: string}
     */
    function split_name(string $full): array
    {
        return array_pad(preg_split('/\s+/u', trim($full), 2), 2, '');
    }
}

if (! function_exists('trace')) {
    /**
     * Display arguments and the calling source location for debugging.
     */
    function trace(): void
    {
        $trace           = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller          = $trace[1] ?? [];
        $file            = $caller['file'] ?? '[unknown file]';
        $line            = isset($caller['line']) ? (int) $caller['line'] : 0;
        $arguments       = func_get_args();
        $total_arguments = count($arguments);

        echo '<fieldset style="background:#fefefe !important;border:3px red solid;padding:5px;font-family:Courier New,Courier,monospace;font-size:12px">';
        echo '<legend style="background:lightgrey;padding:6px">'
            . html_escape($file) . ' @ line: ' . $line . '</legend><pre>';

        foreach ($arguments as $index => $argument) {
            echo '<br><strong>Debug #' . ($index + 1) . ' of ' . $total_arguments . '</strong>: ';
            ob_start();
            if ((is_array($argument) || is_object($argument)) && count((array) $argument)) {
                print_r($argument);
            } else {
                var_dump($argument);
            }
            echo html_escape(ob_get_clean());
        }

        echo '</pre></fieldset>' . PHP_EOL;
    }
}

if (! function_exists('html_escape')) {
    /**
     * HTML-escape a value (or each value of an array) exactly as CodeIgniter 3's
     * html_escape() did, so the migrated views print byte-identical markup.
     *
     * @param mixed $var
     * @param bool  $double_encode
     *
     * @return mixed
     */
    function html_escape($var, $double_encode = true)
    {
        if (empty($var)) {
            return $var;
        }

        if (is_array($var)) {
            foreach (array_keys($var) as $key) {
                $var[$key] = html_escape($var[$key], $double_encode);
            }

            return $var;
        }

        return htmlspecialchars((string) $var, ENT_QUOTES, 'UTF-8', $double_encode);
    }
}

if (! function_exists('character_limiter')) {
    /**
     * CodeIgniter 3's character_limiter(), defined before CodeIgniter 4's text
     * helper (whose version is then skipped). CodeIgniter 4 compares the
     * length before trimming, so a text cut exactly at its last word gets an
     * extra "…"; the sitepanel lists keep the CodeIgniter 3 output.
     *
     * @param string|null $str
     *
     * @return string|null
     */
    function character_limiter($str, $n = 500, $end_char = '&#8230;')
    {
        $str = (string) $str;
        if (mb_strlen($str) < $n) {
            return $str;
        }

        // a bit complicated, but faster than preg_replace with \s+
        $str = preg_replace('/ {2,}/', ' ', str_replace(["\r", "\n", "\t", "\v", "\f"], ' ', $str));

        if (mb_strlen($str) <= $n) {
            return $str;
        }

        $out = '';

        foreach (explode(' ', trim($str)) as $val) {
            $out .= $val . ' ';

            if (mb_strlen($out) >= $n) {
                $out = trim($out);

                return (mb_strlen($out) === mb_strlen($str)) ? $out : $out . $end_char;
            }
        }

        return null;
    }
}

if (! function_exists('set_checkbox')) {
    /**
     * CodeIgniter 3's set_checkbox(), defined before CodeIgniter 4's form
     * helper (whose version is then skipped). After a POST, a box is checked
     * only when it was submitted: browsers don't send unchecked boxes, so a
     * box the admin unticked stays unticked when the form is shown again with
     * errors. (CodeIgniter 4 falls back to $default when the field is absent.)
     */
    function set_checkbox(string $field, string $value = '', bool $default = false): string
    {
        $request = service('request');
        $input   = $request->getPost($field);

        if (is_array($input)) {
            return in_array($value, array_map('strval', $input), true) ? ' checked="checked"' : '';
        }

        if ($request->getMethod() === 'POST') {
            return ($input === $value) ? ' checked="checked"' : '';
        }

        return $default ? ' checked="checked"' : '';
    }
}

if (! function_exists('set_radio')) {
    /**
     * CodeIgniter 3's set_radio(): same rule as set_checkbox() above.
     */
    function set_radio(string $field, string $value = '', bool $default = false): string
    {
        return set_checkbox($field, $value, $default);
    }
}

if (! function_exists('form_error')) {
    /**
     * The validation error of one field wrapped in $prefix/$suffix, or ''
     * (CodeIgniter 3 form_error(); reads the shared validation service).
     */
    function form_error(string $field, string $prefix = '<p>', string $suffix = '</p>'): string
    {
        $error = service('validation')->getError($field);

        return $error !== '' ? $prefix . $error . $suffix : '';
    }
}

if (! function_exists('redirect_now')) {
    /**
     * Redirect and end the request immediately, like CodeIgniter 3's redirect():
     * code that follows the call never runs. Relative URIs go through
     * site_url() (a query string is kept). Status code as in CodeIgniter 3:
     * 303 after a non-GET request, 307 after GET (HTTP/1.1).
     *
     * @throws RedirectException
     */
    function redirect_now(string $uri): never
    {
        if (preg_match('#^(\w+:)?//#i', $uri) !== 1) {
            $query = '';
            if (($pos = strpos($uri, '?')) !== false) {
                $query = substr($uri, $pos);
                $uri   = substr($uri, 0, $pos);
            }
            $uri = site_url($uri) . $query;
        }

        $method = strtoupper((string) service('superglobals')->server('REQUEST_METHOD'));
        $code   = service('request')->getProtocolVersion() >= 1.1 ? ($method !== 'GET' ? 303 : 307) : 302;

        throw new RedirectException(service('response')->redirect($uri, 'auto', $code));
    }
}

if (! function_exists('site_menu_url')) {
    /**
     * URL of a header/footer link (Config\SiteMenu), or NULL while its page
     * doesn't exist: no 'uri', or no GET route for that URI ('' = home) —
     * a fixed route, or a page routed through wl_meta_tags (built-in pages,
     * published static pages; MetaModel::routed_urls()).
     *
     * @param array $link ['uri' => string|null, 'fragment' => string (optional)]
     */
    function site_menu_url(array $link): ?string
    {
        static $routes = null;

        if (! isset($link['uri'])) {
            return null;
        }
        $routes ??= array_flip(array_merge(array_keys(service('routes')->getRoutes('GET')), (new \Modules\Sitepanel\Models\MetaModel())->routed_urls()));

        $uri = trim((string) $link['uri'], '/');
        if (! isset($routes[$uri === '' ? '/' : $uri]) && ! ($uri === '' && isset($routes[\Modules\Sitepanel\Models\MetaModel::HOME_URL]))) {
            return null;
        }

        return site_url($uri) . (empty($link['fragment']) ? '' : '#' . $link['fragment']);
    }
}

if (! function_exists('inner_banner')) {
    /**
     * Hero image of an inner page (app/Views/inner_hero.php) from Sitepanel >
     * Manage Banners: the newest active banner of the first of $positions
     * that has one whose file exists, else of "Inner Pages Top", else the
     * design's inner banner. Positions: Config\Sitepanel::$bannerPositions.
     *
     * @param list<string>|string $positions Most specific first
     *
     * @return array{src: string, text: string} Image URL and the banner text ('' = none)
     */
    function inner_banner($positions): array
    {
        $positions = array_values(array_unique(array_merge((array) $positions, [\Modules\Sitepanel\Models\BannerModel::INNER_PAGES_POSITION])));
        $rows      = (new \Modules\Sitepanel\Models\BannerModel())->get_active_banners($positions);
        $known     = config(\Modules\Sitepanel\Config\Sitepanel::class)->bannerPositions;
        $images    = new \App\Libraries\ImageUpload();

        if ($rows === false) {
            log_message('error', 'inner_banner() could not load the banners of: ' . implode(', ', $positions) . '.');
        }
        foreach ($positions as $position) {
            isset($known[$position]) || log_message('warning', 'inner_banner(): "' . $position . '" is not a banner position of Config\Sitepanel::$bannerPositions.');

            foreach (is_array($rows) ? $rows : [] as $row) {
                $path = $row['banner_position'] === $position ? $images->path(\Modules\Sitepanel\Controllers\Banners::IMAGE_FOLDER, $row['banner_image']) : '';
                if ($path !== '') {
                    // Full-width 300 px hero: capped at 1920 px, proportions kept (get_image()).
                    return ['src' => get_image(\Modules\Sitepanel\Controllers\Banners::IMAGE_FOLDER, $row['banner_image'], 1920, 1920), 'text' => trim((string) $row['banner_title'])];
                }
            }
        }

        return ['src' => theme_url() . 'images/customcart-inner-banner.jpg', 'text' => ''];
    }
}

if (! function_exists('site_menu_links')) {
    /**
     * The links of a Config\SiteMenu list that can be shown, each with its
     * 'url' and shown 'children' (recursive). A parent without a page of its
     * own takes its first shown child's URL; one with neither is left out.
     *
     * @param list<array> $links
     *
     * @return list<array>
     */
    function site_menu_links(array $links): array
    {
        $shown = [];

        foreach ($links as $link) {
            $link['children'] = site_menu_links($link['children'] ?? []);
            $link['url']      = site_menu_url($link) ?? ($link['children'][0]['url'] ?? null);

            if ($link['url'] !== null) {
                $shown[] = $link;
            }
        }

        return $shown;
    }
}

if (! function_exists('cart_count')) {
    /**
     * Units in the visitor's cart, for the header cart badge (read from the
     * session only; no session is started for a visitor without one).
     */
    function cart_count(): int
    {
        return (new \Modules\Cart\Libraries\ShopCart())->count();
    }
}

if (! function_exists('account_nav')) {
    /**
     * The account links of the header Register menu, the footer and the
     * Sitemap for this visitor (one rule for the three, CustomerAuth::NAV_LINKS):
     * 'state' guest | user, 'links' key => link with its 'url'.
     * Pages check the account type themselves (CustomerAuth::require_login()).
     *
     * @return array{state: string, links: array<string, array>}
     */
    function account_nav(): array
    {
        // The sign-in is checked once per request (CustomerAuth::signed_in()).
        $state = (new \Modules\Users\Libraries\CustomerAuth())->nav_state();
        $links = [];
        foreach (\Modules\Users\Libraries\CustomerAuth::NAV_LINKS[$state] as $key => $link) {
            $links[$key] = $link + ['url' => site_url($link['uri']) . (empty($link['fragment']) ? '' : '#' . $link['fragment']), 'post' => false];
        }

        return ['state' => $state, 'links' => $links];
    }
}

if (! function_exists('site_currency')) {
    /**
     * The store currency: the active default row of wl_currencies
     * (is_default 'Y', status 1), as display_price() reads it. Symbols are
     * stored as HTML (e.g. '&#x20b9;'), so they are decoded here and escaped where printed.
     *
     * Read once per request and kept in the application cache for an hour
     * (Sitepanel > Clear Cache empties it after wl_currencies is changed).
     * When no row can be read nothing is assumed: code and symbols are '' (the
     * amount is shown without a symbol), it is logged and not cached.
     *
     * @return array{currency_id: int, code: string, symbol_left: string, symbol_right: string, decimal_place: int}
     */
    function site_currency(): array
    {
        static $currency = null;

        if ($currency !== null) {
            return $currency;
        }

        $cached = cache('site_currency');
        if (is_array($cached)) {
            return $currency = $cached;
        }

        $row = null;

        try {
            $query = db_connect()->table('wl_currencies')
                ->select('currency_id, code, symbol_left, symbol_right, decimal_place')
                ->where(['is_default' => 'Y', 'status' => 1])
                ->orderBy('currency_id')
                ->get(1);
            $row = $query ? $query->getRowArray() : null;
        } catch (\Throwable $e) {
            log_message('error', 'site_currency(): wl_currencies could not be read: ' . $e->getMessage());
        }

        if (! is_array($row)) {
            log_message('error', 'site_currency(): no active default currency in wl_currencies; amounts are shown without a symbol.');

            return $currency = ['currency_id' => 0, 'code' => '', 'symbol_left' => '', 'symbol_right' => '', 'decimal_place' => 2];
        }

        $decode   = static fn ($value): string => trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $decimals = trim((string) $row['decimal_place']);

        $currency = [
            'currency_id'   => (int) $row['currency_id'],
            'code'          => $decode($row['code']),
            'symbol_left'   => $decode($row['symbol_left']),
            'symbol_right'  => $decode($row['symbol_right']),
            'decimal_place' => ctype_digit($decimals) ? (int) $decimals : 2,
        ];

        cache()->save('site_currency', $currency, HOUR);

        return $currency;
    }
}

if (! function_exists('display_price')) {
    /**
     * An amount in the store currency (site_currency()), HTML-safe, e.g.
     * "S$1,250.00". As in the reference: the left symbol, else the right one,
     * around number_format() with the currency's decimal places. A symbol made
     * of letters ("SAR", "AED") is set apart by a non-breaking space
     * ("SAR 18.00", "18.00 AED"). Display only: the amount is not converted.
     * NULL, '' or a non-numeric value shows as zero.
     *
     * @param float|int|string|null $price
     * @param int|null              $decimals Override the currency's decimal places
     *                                       (0 for whole-amount labels: "Under S$25")
     */
    function display_price($price, ?int $decimals = null): string
    {
        $currency = site_currency();
        $number   = number_format(is_numeric($price) ? (float) $price : 0.0, $decimals ?? $currency['decimal_place']);
        $left     = $currency['symbol_left'];
        $right    = $currency['symbol_right'];

        if ($left !== '') {
            return esc($left) . (preg_match('/\p{L}$/u', $left) ? "\u{00A0}" : '') . $number;
        }

        if ($right !== '') {
            return $number . (preg_match('/^\p{L}/u', $right) ? "\u{00A0}" : '') . esc($right);
        }

        return $number;
    }
}

if (! function_exists('display_symbol')) {
    /**
     * The store currency's symbol alone, HTML-safe, for labels such as
     * "Shipping Charges (S$)" (the reference's display_symbol()); its code
     * when it has no symbol.
     */
    function display_symbol(): string
    {
        $currency = site_currency();

        return esc($currency['symbol_left'] !== '' ? $currency['symbol_left'] : ($currency['symbol_right'] !== '' ? $currency['symbol_right'] : $currency['code']));
    }
}

if (! function_exists('get_image')) {
    /**
     * URL of an uploaded image resized for display, from the thumbnail cache:
     *
     *   <img src="<?php echo get_image('products', $row['media'], 285, 250); ?>">
     *
     * The copy is made on first request in writable/thumb_cache/<folder>/
     * (Config\Uploads), reused afterwards (the use is recorded for expiry) and
     * made again when missing (after Sitepanel > Clear Cache or the daily
     * `php spark cache:cleanup-thumbnails`), or under a new name when the
     * original changes (ImageUpload::thumbnail(); docs/THUMBNAIL_CACHE.md).
     * Each size is its own file. Use the
     * original's URL (ImageUpload::path()) where the full image is wanted:
     * zoom, lightbox, downloads.
     *
     * @param string          $folder A Config\Uploads::$folders key ('products', 'blog', ...)
     * @param string|null     $image  The stored file name
     * @param int|string      $width  Box to fit in, in pixels
     * @param int|string      $height
     * @param string          $option 'R' fit within the box (default), 'C' fill it, cropped from the centre
     *
     * @return string '' when the image is unset or missing (the caller shows its own placeholder)
     */
    function get_image(string $folder, ?string $image, $width = 50, $height = 50, string $option = 'R'): string
    {
        static $images = null;
        $images ??= new \App\Libraries\ImageUpload();
        $path = $images->thumbnail($folder, $image, (int) $width, (int) $height, $option);

        return $path !== '' ? base_url($path) : '';
    }
}

if (! function_exists('site_mega_menu')) {
    /**
     * Product menu of the header (design: header.lbi mega menu) and the
     * footer's product links: the public top-level categories (those flagged
     * "Menu" in Manage Categories, else all, in display order) with their
     * public sub-categories. Read once per request.
     *
     * @return list<array> category rows + 'url', 'image_url', 'children' (rows + 'url')
     */
    function site_mega_menu(): array
    {
        static $menu = null;
        if ($menu !== null) {
            return $menu;
        }

        $model = new \Modules\Category\Models\CategoryModel();
        $top   = $model->get_public_children(0);
        if (! is_array($top)) {
            log_message('error', 'site_mega_menu() could not load the categories.');

            return $menu = [];
        }
        $flagged = array_values(array_filter($top, static fn ($row) => ($row['is_menu'] ?? '0') === '1'));
        $menu    = [];
        foreach ($flagged ?: $top as $row) {
            $children = $model->get_public_children((int) $row['category_id']);
            $menu[]   = $row + [
                'url'       => site_url($row['friendly_url']),
                'image_url' => get_image('category', $row['category_image'], 400, 400, 'C'),
                'children'  => array_map(static fn ($child) => $child + ['url' => site_url($child['friendly_url'])], is_array($children) ? $children : []),
            ];
        }

        return $menu;
    }
}

if (! function_exists('wishlist_count')) {
    /**
     * Products in the signed-in customer's wishlist (0 for visitors), for the header badge.
     */
    function wishlist_count(): int
    {
        static $count = null;
        if ($count !== null) {
            return $count;
        }
        $customer = (new \Modules\Users\Libraries\CustomerAuth())->current();
        if (! $customer) {
            return $count = 0;
        }
        $n = (new \Modules\Members\Models\WishlistModel())->count_items((int) $customer['customers_id']);

        return $count = is_int($n) ? $n : 0;
    }
}

if (! function_exists('site_marquee')) {
    /**
     * Active Running Marquee items (Sitepanel > Manage Running Marquee), in display order.
     *
     * @return list<array{text: string, url: string}>
     */
    function site_marquee(): array
    {
        static $items = null;
        if ($items !== null) {
            return $items;
        }
        $rows = (new \Modules\Sitepanel\Models\MarqueeModel())->get_active();

        return $items = array_map(static fn ($row) => ['text' => (string) $row['marquee_text'], 'url' => trim((string) $row['marquee_url'])], is_array($rows) ? $rows : []);
    }
}
