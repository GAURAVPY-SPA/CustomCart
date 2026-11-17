<?php

/**
 * Online store view helpers (helper('shop')): prices, addresses and images
 * of the catalog, shared by the store pages, the cart, the customer account
 * and the sitepanel.
 */

use App\Libraries\ImageUpload;
use Config\Shop;
use Modules\Products\Models\ProductModel;
use Modules\Sitepanel\Models\MetaModel;

// Amounts: display_price() (app helper), in the wl_currencies default currency.

if (! function_exists('expected_delivery')) {
    /**
     * Expected delivery of an order: its placed date plus the delivery days of
     * the delivery method it was placed with (wl_order.delivery_min_days /
     * delivery_max_days, from Sitepanel > Manage Shipping); older orders
     * without them: 3-5 days. E.g. "15 – 17 Nov 2026", "30 Nov – 2 Dec 2026",
     * "30 Dec 2026 – 2 Jan 2027", or one date ("17 Nov 2026") when both are equal.
     * Plain text (escape when printed).
     */
    function expected_delivery(array $order): string
    {
        $placed = strtotime((string) $order['order_received_date']) ?: time();
        $min    = isset($order['delivery_min_days']) && $order['delivery_min_days'] !== null ? (int) $order['delivery_min_days'] : 3;
        $max    = isset($order['delivery_max_days']) && $order['delivery_max_days'] !== null ? (int) $order['delivery_max_days'] : 5;
        $max    = max($min, $max);
        $from   = strtotime('+' . $min . ' days', $placed);
        $to     = strtotime('+' . $max . ' days', $placed);

        if (date('Y-m-d', $from) === date('Y-m-d', $to)) {
            return date('j M Y', $to);
        }
        $start = date('Y', $from) !== date('Y', $to) ? 'j M Y' : (date('m', $from) !== date('m', $to) ? 'j M' : 'j');

        return date($start, $from) . ' – ' . date('j M Y', $to);
    }
}

if (! function_exists('order_url')) {
    /**
     * A customer-facing order page ('order-confirmation', 'acc-order-invoice',
     * 'invoice-print') for an order: ?order=<reference>, never the invoice number.
     */
    function order_url(string $page, array $order): string
    {
        return site_url($page) . '?order=' . \Modules\Cart\Models\OrderModel::reference((string) $order['invoice_number']);
    }
}

if (! function_exists('product_url')) {
    /**
     * Address of a product page from its friendly_url (at the site root: /<friendly_url>).
     */
    function product_url(string $friendly_url): string
    {
        return site_url(ProductModel::URL_PREFIX . $friendly_url);
    }
}

if (! function_exists('category_url')) {
    /**
     * Address of a category page from its friendly_url (already its full
     * path from the site root: <category>/<sub>/<sub-sub>).
     */
    function category_url(string $friendly_url): string
    {
        return site_url($friendly_url);
    }
}

if (! function_exists('store_url')) {
    /**
     * Address of the store's front page (Products::index), read from its
     * wl_meta_tags row (entity_type products/index), so it follows the URL
     * registry; the site root when the page is not registered.
     */
    function store_url(): string
    {
        static $url = null;

        if ($url === null) {
            $row = (new MetaModel())->get_route_of('products/index');
            $url = site_url(is_array($row) ? $row['page_url'] : '');
        }

        return $url;
    }
}

if (! function_exists('product_image_url')) {
    /**
     * URL of a product photo resized to fit width × height (get_image(), the
     * thumbnail cache; product photos are shown whole: object-fit contain),
     * or the store's placeholder when it is unset or missing. The default
     * suits a product card (258 × 187 slot, at twice the size for sharp
     * screens); $width NULL gives the original (zoom / full size).
     */
    function product_image_url(?string $file, ?int $width = 600, int $height = 440): string
    {
        static $images = null;
        $images ??= new ImageUpload();

        $url = $width === null
            ? (($path = $images->path('products', $file)) !== '' ? base_url($path) : '')
            : get_image('products', $file, $width, $height);

        return $url !== '' ? $url : theme_url() . 'images/placeholder.svg';
    }
}

if (! function_exists('upload_image_url')) {
    /**
     * URL of an image in another upload folder (category, brand), resized
     * with get_image() when a size is given, or '' when unset or missing.
     *
     * @param string $option get_image() option: 'R' fit, 'C' fill (cropped)
     */
    function upload_image_url(string $folder, ?string $file, ?int $width = null, int $height = 0, string $option = 'R'): string
    {
        if ($width !== null) {
            return get_image($folder, $file, $width, $height, $option);
        }

        static $images = null;
        $images ??= new ImageUpload();
        $path = $images->path($folder, $file);

        return $path !== '' ? base_url($path) : '';
    }
}

if (! function_exists('category_image_url')) {
    /**
     * Image of a category card: the category's image cropped to width ×
     * height (upload_image_url()), else the photo of its first product
     * (product_image_url(), $width × $photo_height, default $height), else
     * the store's placeholder.
     *
     * @param array $category Public category row (category_id, category_image)
     */
    function category_image_url(array $category, int $width, int $height, ?int $photo_height = null): string
    {
        $url = upload_image_url('category', $category['category_image'], $width, $height, 'C');
        if ($url !== '') {
            return $url;
        }

        $first = (new ProductModel())->search_public(['category_id' => (int) $category['category_id']], 'default', 0, 1);

        return product_image_url(is_array($first) && $first ? $first[0]['image'] : null, $width, $photo_height ?? $height);
    }
}

if (! function_exists('payment_method_label')) {
    /**
     * Title of a payment method key (Config\Shop::$paymentMethods), else the key.
     */
    function payment_method_label(string $key): string
    {
        return config(Shop::class)->paymentMethods[$key][0] ?? ucfirst($key);
    }
}

if (! function_exists('product_cards')) {
    /**
     * Product card data (Views/partials/product_card.php) for a list of
     * public product rows (ProductModel::search_public()): the row, its
     * colour swatches and sizes, whether a colour / size must be chosen on
     * the product page first, and whether it is in the visitor's wishlist.
     *
     * @param list<array> $rows
     *
     * @return list<array{product: array, options: bool, swatches: array, wish: bool}>
     */
    function product_cards(array $rows): array
    {
        if (! $rows) {
            return [];
        }
        $model    = new ProductModel();
        $ids      = array_map('intval', array_column($rows, 'products_id'));
        $variants = array_flip(array_map('intval', (array) $model->variant_product_ids($ids)));
        $options  = $model->card_options($ids);
        $wish     = [];
        $customer = (new \Modules\Users\Libraries\CustomerAuth())->current();
        if ($customer) {
            $saved = (new \Modules\Members\Models\WishlistModel())->product_ids((int) $customer['customers_id']);
            $wish  = array_flip(is_array($saved) ? $saved : []);
        }

        return array_map(static fn ($row) => [
            'product'  => $row,
            'options'  => isset($variants[(int) $row['products_id']]),
            'swatches' => $options[(int) $row['products_id']] ?? ['colors' => [], 'sizes' => []],
            'wish'     => isset($wish[(int) $row['products_id']]),
        ], $rows);
    }
}
