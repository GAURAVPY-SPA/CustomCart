<?php

namespace Modules\Products\Controllers;

use App\Controllers\PublicController;
use App\Exceptions\HaltException;
use App\Libraries\Captcha;
use App\Libraries\FormValidation;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Shop;
use Modules\Members\Models\WishlistModel;
use Modules\Brand\Models\BrandModel;
use Modules\Category\Models\CategoryModel;
use Modules\Color\Models\ColorModel;
use Modules\Material\Models\MaterialModel;
use Modules\Products\Models\ProductModel;
use Modules\Review\Models\ReviewModel;
use Modules\Size\Models\SizeModel;
use Modules\Sitepanel\Controllers\Banners;
use Modules\Sitepanel\Models\BannerModel;
use Modules\Sitepanel\Models\FaqModel;
use Modules\Sitepanel\Models\EnquiryModel;
use Modules\Users\Libraries\CustomerAuth;

/**
 * Online store catalog (design: html/category.htm, sub-category.htm, listing.htm,
 * details.htm). Every address is a wl_meta_tags row:
 *
 *   /online-store              index     top-level categories (+ featured products)
 *   /products                  listing   every public product, with filters
 *   /<cat>[/<sub>[/<sub-sub>]] category pages: Modules\Category\Controllers\Category::index
 *                                        (entity_type category/index, entity_id = category_id),
 *                                        rendered by category_page(): its sub-categories, or its
 *                                        products when it has none
 *   /<slug>                    detail    (entity_type products/detail, entity_id = products_id);
 *                                        ?variant=1&color=&size= answers JSON (price and stock of
 *                                        one colour × size); POST action=review | enquiry
 *
 * Listing filters are GET parameters, so a filtered page can be bookmarked:
 * q (name / code), cat (a sub-category), price[] (PRICE_RANGES keys),
 * availability[] (in-stock / out-of-stock), colors[], sizes[], brands[],
 * sort (ProductModel::SORTS), page.
 *
 * Reviews (wl_review) are saved pending approval in Sitepanel > Product
 * Reviews, as in the reference; product enquiries go to Sitepanel > Manage
 * Product Enquiry (wl_enquiry type '3'). Both use the contact form's spam
 * controls: CSRF, a hidden honeypot field and a per-IP hourly limit.
 */
class Products extends PublicController
{
    public const CATEGORY_TYPE = CategoryModel::META_TYPE;
    public const DETAIL_TYPE   = 'products/detail';

    /**
     * Price filter (design: listing.htm) => [from, below (NULL = no limit)];
     * labels from price_range_label().
     */
    public const PRICE_RANGES = [
        '0-100'   => [0, 100],
        '100-200' => [100, 200],
        '200-300' => [200, 300],
        '300-'    => [300, null],
    ];

    public const SORT_LABELS = [
        'default'    => 'Default',
        'newest'     => 'Latest',
        'popular'    => 'Popularity',
        'price-asc'  => 'Price: Low to High',
        'price-desc' => 'Price: High to Low',
        'name'       => 'Name: A to Z',
    ];

    public const REVIEWS_PER_HOUR   = 5;
    public const ENQUIRIES_PER_HOUR = 5;

    /**
     * Verification code of the General Inquiry form (Config\Captcha::$forms).
     */
    public const ENQUIRY_CAPTCHA = 'product_enquiry';

    /**
     * Design suffix of the store page titles.
     */
    public const TITLE_SUFFIX = ' | CustomCart';

    protected $helpers = ['shop'];

    protected CategoryModel $category_model;
    protected ProductModel $product_model;

    /**
     * HTML-safe label of a PRICE_RANGES entry in the store currency, whole
     * amounts as in the design: "Under SAR 100", "SAR 100 – 200", "Above SAR 300".
     *
     * @param array{0: int, 1: int|null} $range
     */
    public static function price_range_label(array $range): string
    {
        [$from, $below] = $range;

        if ($below === null) {
            return 'Above ' . display_price($from, 0);
        }

        return $from > 0 ? display_price($from, 0) . ' &ndash; ' . number_format($below) : 'Under ' . display_price($below, 0);
    }

    protected function init(): void
    {
        $this->category_model = new CategoryModel();
        $this->product_model  = new ProductModel();

    }

    /**
     * /online-store: the top-level categories (design: category.htm) and featured products.
     */
    public function index(): string
    {
        $categories = $this->category_model->get_public_children(0);
        if ($categories === false) {
            $this->unavailable('Store::index could not load categories.');
        }

        $featured = $this->product_model->search_public(['flag' => 'is_featured'], 'newest', 0, 4);
        if (is_array($featured) && ! $featured) {
            $featured = $this->product_model->search_public([], 'newest', 0, 4);
        }

        return $this->render('Modules\Products\Views\store_categories', [
            'page_meta'  => ['title' => 'Online Store' . self::TITLE_SUFFIX],
            'hero'       => ['title' => 'Online Store', 'crumbs' => ['Online Store' => null]],
            'category'   => null,
            'categories' => $this->with_images($categories),
            'featured'   => is_array($featured) ? $featured : [],
            'variant_ids' => is_array($featured) ? (array) $this->product_model->variant_product_ids(array_map('intval', array_column($featured, 'products_id'))) : [],
        ]);
    }

    /**
     * Session key of the listings' search, filters and sort (all sent by POST,
     * kept out of the address): [listing key => state], see listing_state().
     */
    public const LISTING_KEY = 'shop_listing';

    /**
     * /products: every public product (design: listing.htm), or those matching
     * the header search.
     *
     * The header search is POSTed (q; blank = all products): it starts a new
     * search of /products (filters and sort cleared) and the visitor is sent to
     * /products, without ?q= in the address. An old /products?q= link is
     * converted the same way. Filters and sort: render_listing().
     */
    public function listing(): string
    {
        $legacy = $this->request->getGet('q');
        $posted = $this->request->getMethod() === 'POST' && $this->request->getPost('listing') === null;

        if ($posted || is_string($legacy)) {
            $q = $posted ? $this->request->getPost('q') : $legacy;
            $this->save_listing_state('products', ['q' => mb_substr(trim((string) (is_string($q) ? $q : '')), 0, 80)]);
            redirect_now('products');
        }

        return $this->render_listing(null);
    }

    /**
     * Search, filters and sort of one listing ('products' or 'category-<id>'),
     * from the session.
     */
    protected function listing_state(string $key): array
    {
        $all   = session(self::LISTING_KEY);
        $state = is_array($all) && isset($all[$key]) && is_array($all[$key]) ? $all[$key] : [];

        return $state + ['q' => '', 'cat' => 0, 'price' => [], 'availability' => [], 'colors' => [], 'sizes' => [], 'brands' => [], 'fabrics' => [], 'sort' => 'default'];
    }

    /**
     * Replace one listing's state (missing parts: empty).
     */
    protected function save_listing_state(string $key, array $state): void
    {
        $all       = session(self::LISTING_KEY);
        $all       = is_array($all) ? $all : [];
        $all[$key] = $state;
        session()->set(self::LISTING_KEY, $all);
    }

    /**
     * A category page by id (rows of the former entity_type products/category).
     * Category pages are now Modules\Category\Controllers\Category::index,
     * which also checks the requested address; both render category_page().
     *
     * @param int|string $category_id The entity_id of the category's wl_meta_tags row
     */
    public function category($category_id = 0): string
    {
        if (preg_match('/^[1-9][0-9]*$/D', (string) $category_id) !== 1) {
            $this->not_found_page();
        }

        $category = $this->category_model->get_public_category((int) $category_id);
        if ($category === false) {
            $this->unavailable('Store::category could not load category ' . $category_id);
        }
        if ($category === null) {
            $this->not_found_page();
        }

        return $this->category_page($category);
    }

    /**
     * A public category's page (Category::index and category() above): its
     * sub-categories (design: sub-category.htm), or its products when it has
     * none (listing.htm).
     */
    protected function category_page(array $category): string
    {
        $category_id = (int) $category['category_id'];
        $children    = $this->category_model->get_public_children($category_id);
        if ($children === false) {
            $this->unavailable('Store::category could not load the sub-categories of ' . $category_id);
        }
        if (! $children) {
            return $this->render_listing($category);
        }

        return $this->render('Modules\Products\Views\store_categories', [
            'page_meta'   => $this->category_meta($category),
            'hero'        => ['title' => $category['category_name'], 'crumbs' => $this->crumbs($category), 'image' => $this->banner_image($category)],
            'category'    => $category,
            'categories'  => $this->with_images($children),
            'featured'    => [],
            'variant_ids' => [],
        ]);
    }

    /**
     * /products/<slug>: one product (design: details.htm).
     *
     * @param int|string $product_id The entity_id of the product's wl_meta_tags row
     *
     * @return ResponseInterface|string
     */
    public function detail($product_id = 0)
    {
        if (preg_match('/^[1-9][0-9]*$/D', (string) $product_id) !== 1) {
            $this->not_found_page();
        }

        $product = $this->product_model->get_public_product((int) $product_id);
        if ($product === false) {
            $this->unavailable('Store::detail could not load product ' . $product_id);
        }
        if ($product === null) {
            $this->not_found_page();
        }

        $id     = (int) $product['products_id'];
        $colors = $this->product_model->get_product_colors($id);
        $sizes  = $this->product_model->get_product_sizes($id);
        $stock  = $this->product_model->get_stock_rows($id);
        if ($colors === false || $sizes === false || $stock === false) {
            $this->unavailable('Store::detail could not load the options of product ' . $id);
        }
        $variants = $this->variants($product, $colors, $sizes, $stock);

        if ($this->request->getGet('variant') === '1') {
            return $this->variant_json($product, $colors, $sizes);
        }

        $forms = [
            'review'  => ['values' => ['poster_name' => '', 'email' => '', 'rate' => '', 'comment' => ''], 'errors' => [], 'sent' => session()->getFlashdata('review_sent') === '1'],
            'enquiry' => ['values' => ['full_name' => '', 'email' => '', 'mobile' => '', 'subject' => '', 'message' => ''], 'errors' => [], 'sent' => session()->getFlashdata('enquiry_sent') === '1'],
        ];
        $customer = (new CustomerAuth())->current();
        if ($customer) {
            $name                                       = trim($customer['first_name'] . ' ' . $customer['last_name']);
            $forms['review']['values']['poster_name']   = $name;
            $forms['review']['values']['email']         = $customer['user_name'];
            $forms['enquiry']['values']['full_name']    = $name;
            $forms['enquiry']['values']['email']        = $customer['user_name'];
            $forms['enquiry']['values']['mobile']       = (string) $customer['mobile_number'];
        }

        $shop = config(Shop::class);
        if ($this->request->getMethod() === 'POST') {
            $action = (string) $this->request->getPost('action');
            if ($action === 'review' && $shop->feature('reviews')) {
                $forms['review'] = $this->submit_review($product, $forms['review'], $customer);
            } elseif ($action === 'enquiry') {
                $forms['enquiry'] = $this->submit_enquiry($product, $forms['enquiry']);
            }
        } else {
            $this->product_model->increment_viewed($id);
        }

        $reviews_model = new ReviewModel();
        $reviews       = $shop->feature('reviews') ? $reviews_model->get_public_reviews($id) : [];
        $rating        = $shop->feature('reviews') ? $reviews_model->rating_summary($id) : ['average' => 0, 'count' => 0];
        $media         = $this->product_model->get_media($id);
        $related       = $this->product_model->get_public_related($product, 4);
        $categories    = $this->public_categories($product);
        foreach (['reviews' => $reviews, 'rating' => $rating, 'media' => $media, 'related' => $related, 'categories' => $categories] as $what => $value) {
            $value === false && log_message('error', 'Store::detail could not load the ' . $what . ' of product ' . $id);
        }
        $categories = is_array($categories) ? $categories : ['crumbs' => [], 'links' => []];

        $in_wishlist = false;
        if ($customer) {
            $wishlist    = (new WishlistModel())->product_ids((int) $customer['customers_id']);
            $in_wishlist = is_array($wishlist) && in_array($id, $wishlist, true);
        }

        $related = is_array($related) ? $related : [];
        $crumbs  = ['Online Store' => store_url()];
        foreach ($categories['crumbs'] as $row) {
            $crumbs[$row['category_name']] = category_url($row['friendly_url']);
        }
        $crumbs[$product['product_name']] = null;

        return $this->render('Modules\Products\Views\product_detail', [
            'page_meta'   => [
                'entity'      => [self::DETAIL_TYPE, $id],
                'title'       => $product['product_name'] . self::TITLE_SUFFIX,
                'description' => mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $product['products_description']))), 0, 160),
                'canonical'   => product_url($product['friendly_url']),
            ],
            'hero'        => ['title' => $product['product_name'], 'crumbs' => $crumbs],
            'product'     => $product,
            'path'        => $categories['links'],
            'features'    => $shop->productFeatures,
            'colors'      => $colors,
            'sizes'       => $sizes,
            'variants'    => $variants,
            'media'       => is_array($media) ? $media : [],
            'reviews'     => is_array($reviews) ? $reviews : [],
            'rating'      => is_array($rating) ? $rating : ['average' => 0, 'count' => 0],
            'related'     => $related,
            'variant_ids' => (array) $this->product_model->variant_product_ids(array_map('intval', array_column($related, 'products_id'))),
            'in_wishlist' => $in_wishlist,
            'forms'       => $forms,
            'faqs'        => (new FaqModel())->get_product_faqs($id) ?: [],
            'material'    => $product['material_id'] ? (new MaterialModel())->get_options(true)[(int) $product['material_id']] ?? null : null,
            'free_above'  => (float) ((new \Modules\Cart\Models\ShippingModel())->get_store_method()['free_above'] ?? 0),
            'page_path'   => ProductModel::URL_PREFIX . $product['friendly_url'],
            'page_scripts' => [['src' => asset_url('assets/designer/resources/js/product.js'), 'defer' => true]],
        ]);
    }

    /* -----------------------------------------------------------------
     * Listing
     * ----------------------------------------------------------------- */

    /**
     * Products of a category (and below it), or of the whole store.
     */
    protected function render_listing(?array $category): string
    {
        $base_id  = $category ? (int) $category['category_id'] : 0;
        $children = $this->category_model->get_public_children($base_id);
        if ($children === false) {
            $this->unavailable('Store::listing could not load categories.');
        }
        $category_options = [];
        foreach ($children as $row) {
            $category_options[(int) $row['category_id']] = $row;
        }

        // Filters and sort are POSTed by the listing's form (listing=1; clear / clear_search buttons),
        // kept in the session for this listing and the visitor sent back to its address (page 1).
        $key = $category ? 'category-' . (int) $category['category_id'] : 'products';
        $url = $category ? category_url($category['friendly_url']) : site_url('products');
        if ($this->request->getMethod() === 'POST' && $this->request->getPost('listing') === '1') {
            $state = $this->listing_state($key);
            if ($this->request->getPost('clear_search') === '1') {
                $state['q'] = '';
            } elseif ($this->request->getPost('clear') === '1') {
                $state = ['q' => $state['q']];
            } else {
                $post  = fn (string $field) => $this->request->getPost($field);
                $state = ['q' => $state['q'], 'cat' => (int) $post('cat'), 'price' => $post('price'), 'availability' => $post('availability'),
                    'colors' => $post('colors'), 'sizes' => $post('sizes'), 'brands' => $post('brands'), 'fabrics' => $post('fabrics'),
                    'sort' => is_string($post('sort')) && $post('sort') !== '' ? $post('sort') : 'default'];
            }
            $this->save_listing_state($key, $state);
            redirect_now($url);
        }

        $state   = $this->listing_state($key);
        $get     = static fn (string $field) => $state[$field] ?? null;
        $list    = static fn ($value) => is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
        $ints    = static fn ($value) => array_values(array_unique(array_filter(array_map('intval', is_array($value) ? $value : []), static fn ($n) => $n > 0)));
        // The header search keyword (listing(), session): /products only, not category pages.
        $keyword = $category ? '' : mb_substr(trim((string) $state['q']), 0, 80);
        $cat     = (int) $get('cat');
        $cat     = isset($category_options[$cat]) ? $cat : 0;
        $prices  = array_values(array_intersect(array_keys(self::PRICE_RANGES), $list($get('price'))));
        $avail   = array_values(array_intersect(['in-stock', 'out-of-stock'], $list($get('availability'))));
        $sort    = is_string($get('sort')) && isset(self::SORT_LABELS[$get('sort')]) ? $get('sort') : 'default';

        // Colour / size / brand filters only when those features are on (Config\Shop::$productFeatures).
        $shop          = config(Shop::class);
        $color_options = $shop->feature('colors') ? (new ColorModel())->get_options(true) : [];
        $size_options  = $shop->feature('sizes') ? (new SizeModel())->get_options(true) : [];
        $brand_options = $shop->feature('brand') ? (new BrandModel())->get_options(true) : [];
        $fabric_options = $shop->feature('material') ? (new MaterialModel())->get_options(true) : [];
        $selected      = [
            'colors'  => array_values(array_intersect($ints($get('colors')), array_keys((array) $color_options))),
            'sizes'   => array_values(array_intersect($ints($get('sizes')), array_keys((array) $size_options))),
            'brands'  => array_values(array_intersect($ints($get('brands')), array_keys((array) $brand_options))),
            'fabrics' => array_values(array_intersect($ints($get('fabrics')), array_keys((array) $fabric_options))),
        ];

        $filters = [
            'category_id'  => $cat ?: $base_id,
            'keyword'      => $keyword,
            'brand_ids'    => $selected['brands'],
            'material_ids' => $selected['fabrics'],
            'color_ids'    => $selected['colors'],
            'size_ids'     => $selected['sizes'],
            'prices'       => array_map(static fn ($key) => self::PRICE_RANGES[$key], $prices),
            'availability' => count($avail) === 1 ? ($avail[0] === 'in-stock' ? 'in' : 'out') : '',
        ];

        $per_page = config(Shop::class)->perPage;
        $page     = max(1, (int) $this->request->getGet('page')); // the only part kept in the address (?page=)
        $products = $this->product_model->search_public($filters, $sort, ($page - 1) * $per_page, $per_page);
        if ($products === false) {
            $this->unavailable('Store::listing could not load products.');
        }
        $total = $this->product_model->total_rec_found;
        if (! $products && $total > 0) {
            $this->not_found_page(); // a page past the last one
        }

        $query = []; // filters and sort are in the session, not the page links
        $filtered = $keyword !== '' || $cat || $prices || $avail || $selected['colors'] || $selected['sizes'] || $selected['brands'] || $selected['fabrics'];

        $page_meta = $category ? $this->category_meta($category) : ['title' => 'All Products' . self::TITLE_SUFFIX];
        $page_meta['canonical'] = $url;

        return $this->render('Modules\Products\Views\product_listing', [
            'page_meta'        => $page_meta,
            'hero'             => [
                'title'  => $category ? $category['category_name'] : 'All Products',
                'crumbs' => $category ? $this->crumbs($category) : ['Online Store' => store_url(), 'All Products' => null],
                'image'  => $category ? $this->banner_image($category) : '',
            ],
            'tree'             => $this->sidebar_tree(),
            'category'         => $category,
            'category_options' => $category_options,
            'color_options'    => is_array($color_options) ? $color_options : [],
            'size_options'     => is_array($size_options) ? $size_options : [],
            'brand_options'    => is_array($brand_options) ? $brand_options : [],
            'fabric_options'   => is_array($fabric_options) ? $fabric_options : [],
            'keyword'          => $keyword,
            'cat'              => $cat,
            'prices'           => $prices,
            'availability'     => $avail,
            'selected'         => $selected,
            'sort'             => $sort,
            'filtered'         => $filtered,
            'products'         => $products,
            'variant_ids'      => (array) $this->product_model->variant_product_ids(array_map('intval', array_column($products, 'products_id'))),
            'total'            => $total,
            'pager'            => ['page' => $page, 'total' => $total, 'per_page' => $per_page, 'url' => $url, 'query' => $query],
            'form_url'         => $url,
        ]);
    }

    /* -----------------------------------------------------------------
     * Product page helpers
     * ----------------------------------------------------------------- */

    /**
     * The public categories a product is filed in: 'crumbs' = the path of its
     * main category (or its first public one when the main one is hidden), for
     * the breadcrumbs; 'links' = every category on the paths of all of them,
     * each once, main path first (design: "Category: Gift Sets, Starter Bundles").
     *
     * @return array{crumbs: list<array>, links: list<array>}|false
     */
    protected function public_categories(array $product)
    {
        $ids = $this->product_model->get_category_ids((int) $product['products_id']);
        if ($ids === false) {
            return false;
        }

        $result = ['crumbs' => [], 'links' => []];
        $seen   = [];

        foreach ($ids as $category_id) {
            if (! is_array($this->category_model->get_public_category($category_id))) {
                continue;
            }
            $path = (array) $this->category_model->path($category_id);
            $result['crumbs'] = $result['crumbs'] ?: $path;
            foreach ($path as $row) {
                if (! isset($seen[(int) $row['category_id']])) {
                    $seen[(int) $row['category_id']] = true;
                    $result['links'][]               = $row;
                }
            }
        }

        return $result;
    }

    /**
     * Every orderable colour × size of a product, keyed "color-size": stock_id,
     * available, low (at or under the low-stock threshold), prices; plus the
     * preselected combination (first in stock) under 'selected'.
     */
    protected function variants(array $product, array $colors, array $sizes, array $stock): array
    {
        $variants = ['list' => [], 'selected' => null];

        foreach ($stock as $row) {
            $color = (int) $row['product_color'];
            $size  = (int) $row['product_size'];
            // Only combinations of offered (active) colours and sizes.
            if (($colors ? ! isset($colors[$color]) : $color !== 0) || ($sizes ? ! isset($sizes[$size]) : $size !== 0)) {
                continue;
            }
            $key                     = $color . '-' . $size;
            $variants['list'][$key]  = $this->variant_data($product, $row);
            if ($variants['selected'] === null && $variants['list'][$key]['available'] > 0) {
                $variants['selected'] = $key;
            }
        }
        if ($variants['selected'] === null && $variants['list']) {
            $variants['selected'] = array_key_first($variants['list']);
        }

        return $variants;
    }

    /**
     * Price and stock of one stock row, as sent to the page script.
     */
    protected function variant_data(array $product, array $row): array
    {
        $prices    = ProductModel::prices($product, $row);
        $available = max(0, (int) $row['product_quantity']);
        $limit     = (int) $row['inventory'] > 0 ? (int) $row['inventory'] : $this->site_settings->low_stock_threshold();
        $low       = $available > 0 && $available <= $limit;

        return [
            'ok'           => true,
            'stock_id'     => (int) $row['stock_id'],
            'color'        => (int) $row['product_color'],
            'size'         => (int) $row['product_size'],
            'available'    => $available,
            'low'          => $low,
            'price'        => $prices['sale'],
            'regular'      => $prices['regular'],
            'saving'       => $prices['saving'],
            'price_text'   => display_price($prices['sale']),
            'regular_text' => display_price($prices['regular']),
            'saving_text'  => 'Save ' . display_price($prices['saving']),
            'status_text'  => $available <= 0 ? 'Out of Stock' : ($low ? 'Only ' . $available . ' left' : 'In Stock'),
            'max'          => max(1, min($available, config(Shop::class)->maxQuantity)),
        ];
    }

    /**
     * ?variant=1&color=<id>&size=<id>: price and stock of one combination (JSON).
     */
    protected function variant_json(array $product, array $colors, array $sizes): ResponseInterface
    {
        $color = $colors ? (int) $this->request->getGet('color') : 0;
        $size  = $sizes ? (int) $this->request->getGet('size') : 0;
        $row   = ($colors && ! isset($colors[$color])) || ($sizes && ! isset($sizes[$size]))
            ? null
            : $this->product_model->get_stock_row((int) $product['products_id'], $color, $size);

        $this->response->setHeader('Cache-Control', 'no-store');
        if (! is_array($row)) {
            return $this->response->setStatusCode($row === false ? 503 : 200)->setJSON([
                'ok' => false, 'stock_id' => null, 'available' => 0, 'max' => 1,
                'status_text' => $row === false ? 'Availability can\'t be checked right now.' : 'This option is not available.',
            ]);
        }

        return $this->response->setJSON($this->variant_data($product, $row));
    }

    /**
     * "Write a Review" (reference: products/write_review): saved pending approval.
     */
    protected function submit_review(array $product, array $form, ?array $customer): array
    {
        $form['sent'] = false;
        // After sending: the thank-you message once (flashdata), not a ?review=sent address that keeps showing it.
        $done         = ProductModel::URL_PREFIX . $product['friendly_url'] . '#reviews';

        foreach (array_keys($form['values']) as $field) {
            $form['values'][$field] = trim((string) $this->request->getPost($field));
        }
        if (trim((string) $this->request->getPost('fax_number')) !== '') {
            session()->setFlashdata('review_sent', '1');
            redirect_now($done);
        }

        $v = new FormValidation($this->request, $this);
        $v->set_data($form['values']);
        $v->set_rules('poster_name', 'Name', "required|max_length[60]|regex_match[/^[\\p{L}][\\p{L} .'\\-]*$/u]", ['regex_match' => 'Your name may contain only letters, spaces and . \' -']);
        $v->set_rules('email', 'Email', 'required|max_length[120]|valid_email');
        $v->set_rules('rate', 'Rating', 'required|in_list[1,2,3,4,5]', ['required' => 'Please choose a rating.', 'in_list' => 'Please choose a rating from 1 to 5 stars.']);
        $v->set_rules('comment', 'Review', 'required|min_length[3]|max_length[2000]');
        if (! $v->run()) {
            $form['errors'] = array_values($v->error_array());

            return $form;
        }

        $reviews = new ReviewModel();
        $ip      = $this->request->getIPAddress();
        $recent  = $reviews->count_recent_from_ip($ip, 60);
        if ($recent !== false && $recent >= self::REVIEWS_PER_HOUR) {
            $form['errors'] = ['We have already received several reviews from your connection. Please try again later.'];

            return $form;
        }

        $saved = $recent !== false && $reviews->safe_insert(ReviewModel::TABLE, [
            'rev_type'    => 'P',
            'prod_id'     => (int) $product['products_id'],
            'mem_id'      => $customer ? (int) $customer['customers_id'] : null,
            'poster_name' => $form['values']['poster_name'],
            'email'       => strtolower($form['values']['email']),
            'rate'        => (int) $form['values']['rate'],
            'comment'     => $form['values']['comment'],
            'ip_address'  => $ip,
            'posted_date' => date('Y-m-d H:i:s'),
            'status'      => '0',
        ]);
        if (! $saved) {
            log_message('error', 'Store::submit_review could not save a review of product ' . $product['products_id']);
            $form['errors'] = ['Your review could not be sent right now. Please try again in a few minutes.'];

            return $form;
        }

        session()->setFlashdata('review_sent', '1');
        redirect_now($done);
    }

    /**
     * "General Inquiry" (reference: products/send_enquiry): Sitepanel > Manage Product Enquiry.
     */
    protected function submit_enquiry(array $product, array $form): array
    {
        $form['sent'] = false;
        // After sending: the thank-you message once (flashdata), not a ?enquiry=sent address that keeps showing it.
        $done         = ProductModel::URL_PREFIX . $product['friendly_url'] . '#general-inquiry';

        foreach (array_keys($form['values']) as $field) {
            $form['values'][$field] = trim((string) $this->request->getPost($field));
        }
        if (trim((string) $this->request->getPost('fax_number')) !== '') {
            session()->setFlashdata('enquiry_sent', '1');
            redirect_now($done);
        }

        $captcha = new Captcha();
        $v       = new FormValidation($this->request, $this);
        $v->set_data($form['values'] + [$captcha->field() => (string) $this->request->getPost($captcha->field())]);
        $v->set_rules('full_name', 'Full name', "required|max_length[60]|regex_match[/^[\\p{L}][\\p{L} .'\\-]*$/u]", ['regex_match' => 'Your name may contain only letters, spaces and . \' -']);
        $v->set_rules('email', 'Email address', 'required|max_length[80]|valid_email');
        $v->set_rules('mobile', 'Mobile number', 'required|max_length[20]|regex_match[/^\+?[0-9][0-9 ()\-]{6,18}$/]', ['regex_match' => 'Please enter a valid mobile number, e.g. +65 9123 4567.']);
        $v->set_rules('subject', 'Subject', 'max_length[120]');
        $v->set_rules('message', 'Message', 'required|min_length[10]|max_length[2000]', ['min_length' => 'Please describe your question in a little more detail.']);
        $captcha->add_rule($v, self::ENQUIRY_CAPTCHA);
        if (! $v->run()) {
            $form['errors'] = array_values($v->error_array());

            return $form;
        }

        $enquiry = new EnquiryModel();
        $ip      = $this->request->getIPAddress();
        $recent  = $enquiry->count_recent_from_ip($ip, 60);
        if ($recent !== false && $recent >= self::ENQUIRIES_PER_HOUR) {
            $form['errors'] = ['We have already received several enquiries from your connection. Please try again later or contact us directly.'];

            return $form;
        }

        $names = preg_split('/\s+/', $form['values']['full_name'], 2);
        $saved = $recent !== false && $enquiry->safe_insert('wl_enquiry', [
            'type'            => '3',
            'product_service' => mb_substr($product['product_name'] . ' (' . $product['product_code'] . ')', 0, 255),
            'first_name'      => mb_substr($names[0], 0, 30),
            'last_name'       => mb_substr($names[1] ?? '', 0, 30),
            'email'           => $form['values']['email'],
            'mobile_number'   => $form['values']['mobile'],
            'message'         => ($form['values']['subject'] !== '' ? 'Subject: ' . $form['values']['subject'] . "\n\n" : '') . $form['values']['message'],
            'ip_address'      => $ip,
            'reply_status'    => 'N',
            'receive_date'    => date('Y-m-d H:i:s'),
        ]);
        if (! $saved) {
            log_message('error', 'Store::submit_enquiry could not save an enquiry about product ' . $product['products_id']);
            $form['errors'] = ['Your inquiry could not be sent right now. Please try again in a few minutes.'];

            return $form;
        }

        session()->setFlashdata('enquiry_sent', '1');
        redirect_now($done);
    }

    /* -----------------------------------------------------------------
     * Shared
     * ----------------------------------------------------------------- */

    /**
     * Category cards: the category's image, else a photo of one of its products.
     *
     * @param list<array> $categories
     */
    protected function with_images(array $categories): array
    {
        foreach ($categories as &$row) {
            $row['image_url'] = upload_image_url('category', $row['category_image'], 600, 436, 'C');
            if ($row['image_url'] === '') {
                $first            = $this->product_model->search_public(['category_id' => (int) $row['category_id']], 'default', 0, 1);
                $row['image_url'] = product_image_url(is_array($first) && $first ? $first[0]['image'] : null);
            }
        }

        return $categories;
    }

    /**
     * Breadcrumbs of a category page: Online Store › ancestors › the category.
     */
    protected function crumbs(array $category): array
    {
        $crumbs = ['Online Store' => store_url()];
        $path   = $this->category_model->path((int) $category['category_id']);

        foreach (is_array($path) ? $path : [$category] as $row) {
            $crumbs[$row['category_name']] = (int) $row['category_id'] === (int) $category['category_id'] ? null : category_url($row['friendly_url']);
        }

        return $crumbs;
    }

    /**
     * Hero image of a category page: its banner (Manage Banners > Category
     * Pages), else its nearest parent's, else '' (the inner banner).
     */
    protected function banner_image(array $category): string
    {
        $id  = (int) $category['category_id'];
        $ids = $this->category_model->ancestor_ids($id);
        $row = (new BannerModel())->get_category_banner(array_merge([$id], is_array($ids) ? array_reverse($ids) : []));

        return is_array($row) ? get_image(Banners::IMAGE_FOLDER, $row['banner_image'], 1920, 1920) : '';
    }

    /**
     * The listing sidebar's categories: top-level public categories with
     * their public sub-categories and product counts.
     *
     * @return list<array>
     */
    protected function sidebar_tree(): array
    {
        $top = $this->category_model->get_public_children(0);
        if (! is_array($top)) {
            return [];
        }
        $ids = array_map('intval', array_column($top, 'category_id'));
        foreach ($top as &$row) {
            $children        = $this->category_model->get_public_children((int) $row['category_id']);
            $row['children'] = is_array($children) ? $children : [];
            $ids             = array_merge($ids, array_map('intval', array_column($row['children'], 'category_id')));
        }
        unset($row);
        $counts = $this->category_model->list_counts($ids);
        foreach ($top as &$row) {
            $row['count'] = $counts[(int) $row['category_id']]['products'] ?? 0;
            foreach ($row['children'] as $child) {
                $row['count'] += $counts[(int) $child['category_id']]['products'] ?? 0;
            }
        }

        return $top;
    }

    protected function category_meta(array $category): array
    {
        return [
            'entity'      => [self::CATEGORY_TYPE, (int) $category['category_id']],
            'title'       => $category['category_name'] . self::TITLE_SUFFIX,
            'description' => mb_substr(trim((string) $category['category_description']), 0, 160),
            'canonical'   => category_url($category['friendly_url']),
        ];
    }

    protected function unavailable(string $log): never
    {
        log_message('error', $log);

        throw HaltException::error('The online store is temporarily unavailable. Please try again shortly.', 503);
    }
}
