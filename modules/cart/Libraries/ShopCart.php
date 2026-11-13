<?php

namespace Modules\Cart\Libraries;

use Config\Shop;
use Modules\Cart\Models\OrderModel;
use Modules\Cart\Models\ShippingModel;
use Modules\Products\Models\ProductModel;
use Modules\Sitepanel\Models\CouponModel;

/**
 * The shopping cart (kept in the session).
 *
 * The session keeps only what the customer chose — product, colour, size,
 * quantity, coupon code — never prices: lines() reads the
 * products, stock rows and prices again on every use, so a changed price or
 * sold-out variant is seen at once and a session can't change what is paid.
 * Guests and signed-in customers share the session cart, as in the reference.
 *
 *   $cart->add($product_id, $color_id, $size_id, $qty)   NULL or an error message
 *   $cart->lines()                                         priced lines with any 'problem'
 *   $cart->summary($customer_id, $email)                   totals, coupon and delivery
 *
 * Delivery and GST come from Sitepanel > Settings > Online store
 * (SiteSettings::shop_amounts()): one shipping charge, free from a subtotal.
 */
class ShopCart
{
    public const SESSION_KEY = 'shop_cart';

    protected ProductModel $products;
    protected Shop $config;

    /**
     * lines() of this request.
     */
    protected ?array $lines = null;

    public function __construct(?ProductModel $products = null)
    {
        $this->products = $products ?? new ProductModel();
        $this->config   = config(Shop::class);
    }

    /**
     * ['items' => rowid => [product_id, color_id, size_id, qty], 'coupon' => code|null,
     *  'shipping' => chosen delivery method id|null]
     */
    protected function data(): array
    {
        $data = session(self::SESSION_KEY);

        return (is_array($data) ? $data : []) + ['items' => [], 'coupon' => null, 'shipping' => null];
    }

    protected function save(array $data): void
    {
        session()->set(self::SESSION_KEY, $data);
        $this->lines = null;
    }

    public static function rowid(int $product_id, int $color_id, int $size_id): string
    {
        return md5($product_id . '-' . $color_id . '-' . $size_id);
    }

    /**
     * Whether the visitor has a cart (without starting a session for those who don't).
     */
    public function is_empty(): bool
    {
        if (! isset($_COOKIE[config('Session')->cookieName]) && session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        return ! $this->data()['items'];
    }

    /**
     * Total units in the cart.
     */
    public function count(): int
    {
        return $this->is_empty() ? 0 : array_sum(array_column($this->data()['items'], 'qty'));
    }

    /**
     * Add units of a product (a colour and size when it has them). Units of a
     * line already in the cart are added to it.
     *
     * @return string|null NULL when added, else the message to show
     */
    public function add(int $product_id, int $color_id, int $size_id, int $qty): ?string
    {
        if ($qty < 1) {
            return 'Enter a quantity of at least 1.';
        }

        $product = $this->products->get_public_product($product_id);
        if ($product === false) {
            return 'The cart is not available right now. Please try again shortly.';
        }
        if ($product === null) {
            return 'This product is no longer available.';
        }

        $colors = $this->products->get_product_colors($product_id);
        $sizes  = $this->products->get_product_sizes($product_id);
        if ($colors === false || $sizes === false) {
            return 'The cart is not available right now. Please try again shortly.';
        }
        if ($colors && ! isset($colors[$color_id])) {
            return 'Please choose a colour.';
        }
        if ($sizes && ! isset($sizes[$size_id])) {
            return 'Please choose a size.';
        }
        $colors || $color_id = 0;
        $sizes || $size_id   = 0;

        $stock = $this->products->get_stock_row($product_id, $color_id, $size_id);
        if (! is_array($stock)) {
            return $stock === false ? 'The cart is not available right now. Please try again shortly.' : 'This option is not available.';
        }

        $data  = $this->data();
        $rowid = self::rowid($product_id, $color_id, $size_id);
        $total = $qty + (int) ($data['items'][$rowid]['qty'] ?? 0);
        $error = $this->quantity_error($product['product_name'], (int) $stock['product_quantity'], $total);
        if ($error !== null) {
            return $error;
        }

        $data['items'][$rowid] = ['product_id' => $product_id, 'color_id' => $color_id, 'size_id' => $size_id, 'qty' => $total];
        $this->save($data);

        return null;
    }

    /**
     * Change a line's quantity (0 removes it).
     *
     * @return string|null NULL when changed, else the message to show
     */
    public function update(string $rowid, int $qty): ?string
    {
        $data = $this->data();
        if (! isset($data['items'][$rowid])) {
            return 'That item is no longer in your cart.';
        }
        if ($qty <= 0) {
            $this->remove($rowid);

            return null;
        }

        $item  = $data['items'][$rowid];
        $stock = $this->products->get_stock_row($item['product_id'], $item['color_id'], $item['size_id']);
        if (is_array($stock)) {
            $line  = $this->find_line($rowid);
            $error = $this->quantity_error($line['name'] ?? 'This product', (int) $stock['product_quantity'], $qty);
            if ($error !== null) {
                return $error;
            }
        }

        $data['items'][$rowid]['qty'] = $qty;
        $this->save($data);

        return null;
    }

    public function remove(string $rowid): void
    {
        $data = $this->data();
        unset($data['items'][$rowid]);
        $this->save($data);
    }

    /**
     * Empty the cart (after an order is placed): items and coupon.
     */
    public function clear(): void
    {
        $this->save(['items' => [], 'coupon' => null]);
    }

    protected function quantity_error(string $name, int $available, int $qty): ?string
    {
        if ($available <= 0) {
            return 'Sorry, "' . $name . '" is out of stock.';
        }
        if ($qty > $available) {
            return 'Sorry, only ' . $available . ' of "' . $name . '" ' . ($available === 1 ? 'is' : 'are') . ' in stock.';
        }
        if ($qty > $this->config->maxQuantity) {
            return 'You can order at most ' . $this->config->maxQuantity . ' of one product.';
        }

        return null;
    }

    /**
     * The cart lines, priced from the database, in the order they were added.
     * Each: rowid, product_id, color_id, size_id, stock_id, qty, name, code,
     * url, image, category_name, brand_name, color_name, color_code, size_name,
     * price (unit, what is paid), regular (unit), line_total, available,
     * problem (NULL, or why the line can't be ordered as it is).
     *
     * @return list<array>|false
     */
    public function lines()
    {
        if ($this->lines !== null) {
            return $this->lines;
        }
        if ($this->is_empty()) {
            return $this->lines = [];
        }

        $items    = $this->data()['items'];
        $products = $this->products->get_public_by_ids(array_values(array_unique(array_map('intval', array_column($items, 'product_id')))));
        if ($products === false) {
            return false;
        }

        $lines = [];

        foreach ($items as $rowid => $item) {
            $product = $products[(int) $item['product_id']] ?? null;
            $line    = ['rowid' => $rowid, 'qty' => (int) $item['qty'], 'product_id' => (int) $item['product_id'],
                'color_id' => (int) $item['color_id'], 'size_id' => (int) $item['size_id'], 'stock_id' => null, 'problem' => null];

            if ($product === null) {
                $lines[] = $line + ['name' => 'Product no longer available', 'code' => '', 'url' => site_url('online-store'), 'image' => null,
                    'category_name' => '', 'brand_name' => null, 'color_name' => null, 'color_code' => null, 'size_name' => null,
                    'price' => 0.0, 'regular' => 0.0, 'line_total' => 0.0, 'available' => 0, 'problem' => 'This product is no longer available. Please remove it.'];

                continue;
            }

            $stock  = $this->products->get_stock_row($line['product_id'], $line['color_id'], $line['size_id']);
            $variant = $this->variant_names($line['product_id'], $line['color_id'], $line['size_id']);
            $prices = ProductModel::prices($product, is_array($stock) ? $stock : null);
            $line   = array_merge($line, $variant ?? ['color_name' => null, 'color_code' => null, 'size_name' => null], [
                'stock_id'      => is_array($stock) ? (int) $stock['stock_id'] : null,
                'name'          => $product['product_name'],
                'code'          => $product['product_code'],
                'url'           => site_url(ProductModel::URL_PREFIX . $product['friendly_url']),
                'image'         => $product['image'],
                'category_name' => $product['category_name'],
                'brand_name'    => $product['brand_name'],
                'price'         => $prices['sale'],
                'regular'       => $prices['regular'],
                'line_total'    => round($prices['sale'] * $line['qty'], 2),
                'available'     => is_array($stock) ? max(0, (int) $stock['product_quantity']) : 0,
            ]);

            if (! is_array($stock) || $variant === null) {
                $line['problem'] = 'This option is no longer available. Please remove it.';
            } elseif ($line['available'] <= 0) {
                $line['problem'] = 'Out of stock. Please remove it.';
            } elseif ($line['qty'] > $line['available']) {
                $line['problem'] = 'Only ' . $line['available'] . ' in stock. Please lower the quantity.';
            }
            $lines[] = $line;
        }

        return $this->lines = $lines;
    }

    /**
     * Colour and size names of a line, or NULL when its colour or size is no
     * longer offered.
     *
     * @return array|null color_name, color_code, size_name
     */
    protected function variant_names(int $product_id, int $color_id, int $size_id): ?array
    {
        $colors = $color_id > 0 ? $this->products->get_product_colors($product_id) : [];
        $sizes  = $size_id > 0 ? $this->products->get_product_sizes($product_id) : [];
        if (($color_id > 0 && ! isset($colors[$color_id])) || ($size_id > 0 && ! isset($sizes[$size_id]))) {
            return null;
        }

        return [
            'color_name' => $color_id > 0 ? $colors[$color_id]['color_name'] : null,
            'color_code' => $color_id > 0 ? $colors[$color_id]['color_code'] : null,
            'size_name'  => $size_id > 0 ? $sizes[$size_id]['size_name'] : null,
        ];
    }

    protected function find_line(string $rowid): ?array
    {
        foreach ((array) $this->lines() as $line) {
            if ($line['rowid'] === $rowid) {
                return $line;
            }
        }

        return null;
    }

    /* -----------------------------------------------------------------
     * Coupon and delivery
     * ----------------------------------------------------------------- */

    /**
     * Apply a coupon code (checked again by summary() on every page).
     *
     * @return string|null NULL when applied, else the message to show
     */
    public function apply_coupon(string $code, int $customer_id = 0, string $email = ''): ?string
    {
        $code = trim($code);
        if ($code === '') {
            return 'Please enter a coupon code.';
        }

        $error = $this->coupon_error($code, $this->subtotal(), $customer_id, $email);
        if ($error !== null) {
            return $error;
        }

        $data           = $this->data();
        $data['coupon'] = $code;
        $this->save($data);

        return null;
    }

    /**
     * Choose the delivery method (an active method of Sitepanel > Manage Shipping).
     *
     * @return string|null NULL when chosen, else the message to show
     */
    public function choose_shipping(int $shipping_id): ?string
    {
        $methods = (new ShippingModel())->get_active_methods();
        if (! is_array($methods) || ! isset($methods[$shipping_id])) {
            return 'Please choose an available delivery option.';
        }

        $data             = $this->data();
        $data['shipping'] = $shipping_id;
        $this->save($data);

        return null;
    }

    public function remove_coupon(): void
    {
        $data           = $this->data();
        $data['coupon'] = null;
        $this->save($data);
    }

    public function coupon_code(): ?string
    {
        return $this->is_empty() ? null : $this->data()['coupon'];
    }

    /**
     * Why a coupon can't be used on this cart, or NULL (the reference's
     * get_discount() + can_use_coupon(): active and in its dates, the minimum
     * order reached, the customer's usage limit not reached).
     */
    public function coupon_error(string $code, float $subtotal, int $customer_id, string $email): ?string
    {
        $coupons = new CouponModel();
        $coupon  = $coupons->get_valid_coupon($code);
        if ($coupon === false) {
            return 'Coupons can\'t be checked right now. Please try again shortly.';
        }
        if ($coupon === null) {
            return 'Invalid or expired coupon code.';
        }
        if ($subtotal < (float) $coupon['minimum_order_amount']) {
            return 'This coupon needs a minimum order of ' . html_entity_decode(display_price($coupon['minimum_order_amount']), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '.';
        }
        if (CouponModel::discount_for($coupon, $subtotal) <= 0) {
            return 'This coupon can\'t be used on this order.';
        }

        if ((string) ($coupon['coupon_for'] ?? '0') === '1' && $customer_id < 1) {
            return 'This coupon is for registered members. Please log in to use it.';
        }
        if ($customer_id > 0 || $email !== '') {
            $uses = (new OrderModel())->coupon_uses((int) $coupon['coupon_id'], $customer_id, $email);
            if ($uses === false) {
                return 'Coupons can\'t be checked right now. Please try again shortly.';
            }
            $limit = $coupon['coupon_usage'] === 'multiple' ? (int) $coupon['usage_count'] : 1;
            if ($uses >= $limit) {
                return 'You have already used this coupon the maximum number of times.';
            }
        }

        return null;
    }

    protected function subtotal(): float
    {
        $lines = $this->lines();

        return is_array($lines) ? round(array_sum(array_column($lines, 'line_total')), 2) : 0.0;
    }

    /**
     * Delivery charge of a cart: the shipping charge, or 0 once the subtotal
     * (before any coupon) reaches the free delivery amount; a free delivery
     * amount of 0 means delivery is never free.
     */
    public static function shipping_for(float $subtotal, float $charge, float $free_above): float
    {
        return $free_above > 0 && $subtotal >= $free_above ? 0.0 : round($charge, 2);
    }

    /**
     * Everything the cart, checkout and order need:
     *   lines, count (units), has_problems, subtotal,
     *   coupon (wl_coupons row or NULL), coupon_code, coupon_error (why a saved code was dropped), discount,
     *   shipping_methods (active methods: id => name, free_above, charge for this cart),
     *   shipping_id / shipping_method (the chosen method, else the default),
     *   shipping_charges and free_delivery_above (that method), shipping_amount (charged: 0 when free),
     *   free_delivery_gap (amount still to add for free delivery, 0 = none / reached),
     *   regular_total, product_discount, price_without_tax (discounted subtotal + delivery),
     *   gst_percent, gst_amount (VAT added on top), grand_total.
     * The customer (id / email) is used for coupon usage limits once known.
     *
     * @return array|false
     */
    public function summary(int $customer_id = 0, string $email = '')
    {
        $lines = $this->lines();
        if ($lines === false) {
            return false;
        }

        $subtotal = $this->subtotal();
        $summary  = [
            'lines'        => $lines,
            'count'        => array_sum(array_column($lines, 'qty')),
            'has_problems' => (bool) array_filter(array_column($lines, 'problem')),
            'subtotal'     => $subtotal,
            'coupon'       => null,
            'coupon_code'  => null,
            'coupon_error' => null,
            'discount'     => 0.0,
        ];

        // A saved coupon that no longer applies (cart changed, dates, limit) is dropped with a message.
        $code = $this->coupon_code();
        if ($code !== null) {
            $error = $this->coupon_error($code, $subtotal, $customer_id, $email);
            if ($error === null) {
                $summary['coupon']      = (new CouponModel())->get_valid_coupon($code);
                $summary['coupon_code'] = $code;
                $summary['discount']    = is_array($summary['coupon']) ? CouponModel::discount_for($summary['coupon'], $subtotal) : 0.0;
            } else {
                $summary['coupon_error'] = 'Coupon ' . $code . ' was removed: ' . $error;
                $lines && $this->remove_coupon();
            }
        }

        $amounts        = service('siteSettings')->shop_amounts();
        $after_discount = round($subtotal - $summary['discount'], 2);
        $gst            = $amounts['gst_percent'];

        // Delivery: the method the customer chose in the Order Summary, else the default
        // active method of Sitepanel > Manage Shipping (rate and "free above"); no active
        // method = free delivery. (The old Settings amounts are not used.)
        $methods = (new ShippingModel())->get_active_methods();
        $methods = is_array($methods) ? $methods : [];
        $chosen  = (int) ($this->data()['shipping'] ?? 0);
        $method  = $methods[$chosen] ?? ($methods ? reset($methods) : null);
        $amounts['shipping_charges']    = $method ? round((float) $method['shipment_rate'], 2) : 0.0;
        $amounts['free_delivery_above'] = $method ? round((float) $method['free_above'], 2) : 0.0;
        // Every active method with what it costs for this cart, for the Order Summary's choice.
        $summary['shipping_methods'] = array_map(static fn ($m) => [
            'name'       => (string) $m['shipping_type'],
            'free_above' => round((float) $m['free_above'], 2),
            'charge'     => self::shipping_for($subtotal, (float) $m['shipment_rate'], (float) $m['free_above']),
        ], $methods);
        $summary['shipping_id']     = $method ? (int) $method['shipping_id'] : null;
        $summary['delivery_min_days'] = $method ? (int) $method['delivery_min_days'] : null;
        $summary['delivery_max_days'] = $method ? max((int) $method['delivery_min_days'], (int) $method['delivery_max_days']) : null;
        $summary['shipping_method'] = $method ? (string) $method['shipping_type'] : null;

        $summary['shipping_charges']    = $amounts['shipping_charges'];
        $summary['free_delivery_above'] = $amounts['free_delivery_above'];
        $summary['shipping_amount']     = $lines ? self::shipping_for($subtotal, $amounts['shipping_charges'], $amounts['free_delivery_above']) : 0.0;
        $summary['free_delivery_gap']   = $summary['shipping_amount'] > 0 && $amounts['free_delivery_above'] > 0
            ? round($amounts['free_delivery_above'] - $subtotal, 2) : 0.0;
        // VAT (Settings > VAT percentage) is added on top of the discounted subtotal and delivery.
        $summary['regular_total']     = round(array_sum(array_map(static fn ($l) => $l['regular'] * $l['qty'], $lines)), 2);
        $summary['product_discount']  = round(max(0, $summary['regular_total'] - $subtotal), 2);
        $summary['price_without_tax'] = round($after_discount + $summary['shipping_amount'], 2);
        $summary['gst_percent']       = $gst;
        $summary['gst_amount']        = round($summary['price_without_tax'] * $gst / 100, 2);
        $summary['grand_total']       = round($summary['price_without_tax'] + $summary['gst_amount'], 2);

        return $summary;
    }
}
