<?php

namespace Modules\Cart\Controllers;

use App\Controllers\PublicController;
use App\Libraries\FormValidation;
use Config\Shop;
use Modules\Cart\Libraries\DeliveryAddress;
use Modules\Cart\Libraries\ShopCart;
use Modules\Cart\Models\OrderModel;
use Modules\Members\Models\AddressModel;
use Modules\Users\Libraries\CustomerAuth;
use Modules\Users\Models\CustomerModel;

/**
 * Cart, checkout and order placement (the reference's cart module and
 * cart/process; designs html/cart.htm, checkout.htm, guest-checkout.htm,
 * payment.htm, order-confirmation.htm). Pages are module pages of wl_meta_tags:
 *
 *   /cart                  index         the cart; POST action=update|remove|coupon|remove_coupon
 *   /cart/add              add           POST product_id, color_id, size_id, qty, buy_now (JSON for AJAX)
 *   /cart/shipping         shipping      POST shipping_id: the delivery option of the Order Summary (JSON for AJAX)
 *   /checkout              checkout      step 2 (signed in): choose / add a delivery address
 *   /guest-checkout        guest         step 2 (guest): contact details and delivery address
 *   /payment               payment       step 3: payment method, terms, Complete My Order
 *   /order-confirmation    confirmation  step 4: ?order=<order reference> (OrderModel::reference(), not the invoice number)
 *   /order-invoice         invoice       invoice of the same order (?order=<order reference>), guests too; &print=1: printable
 *
 * Guests can check out, as in the reference: their order is filed under a
 * guest customer row of their email (CustomerModel::guest_for_checkout()).
 * The steps keep their answers in the session ('shop_checkout'); every step
 * re-prices the cart from the database (ShopCart), and the order is placed
 * by OrderModel::place_order(), which takes the stock all or nothing.
 * Payment: Config\Shop::$paymentMethods (the gateway is not connected yet: the
 * order is Unpaid until Sitepanel > Manage Orders marks it paid).
 */
class Cart extends PublicController
{
    /**
     * Session key of the checkout answers: ['customer' => [...], 'delivery' => [...], 'token' => one-time Place Order token].
     */
    public const CHECKOUT_KEY = 'shop_checkout';

    /**
     * Session key of the invoice numbers this visitor placed (their confirmation pages).
     */
    public const ORDERS_KEY = 'shop_orders';

    protected $helpers = ['shop'];

    protected ShopCart $cart;
    protected CustomerAuth $auth;
    protected Shop $shop;

    protected function init(): void
    {
        $this->cart = new ShopCart();
        $this->auth = new CustomerAuth();
        $this->shop = config(Shop::class);

        // Personal, changing pages: never cached.
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->view_data['body_class'] = 'page-inner';

    }

    /* -----------------------------------------------------------------
     * Cart
     * ----------------------------------------------------------------- */

    public function index(): string
    {
        if ($this->request->getMethod() === 'POST') {
            $this->cart_action();
        }

        [$customer_id, $email] = $this->identity();
        $summary = $this->summary_or_fail($customer_id, $email);

        return $this->render('Modules\Cart\Views\cart', [
            'page_meta'    => ['title' => 'Your Cart'],
            'summary'      => $summary,
            'customer'     => $this->auth->current(),
        ]);
    }

    /**
     * POST actions of the cart page; always redirects back (PRG).
     */
    protected function cart_action(): never
    {
        $action = (string) $this->request->getPost('action');
        // A line's remove button (inside the quantities form) sends its rowid as "remove".
        if (is_string($this->request->getPost('remove'))) {
            $action = 'remove';
            $_POST['rowid'] = $this->request->getPost('remove');
            $this->request->setGlobal('post', $_POST);
        }

        switch ($action) {
            case 'update':
                $quantities = $this->request->getPost('qty');
                $errors     = [];
                foreach (is_array($quantities) ? $quantities : [] as $rowid => $qty) {
                    if (is_string($rowid) && preg_match('/^[0-9a-f]{32}$/D', $rowid) && is_scalar($qty)) {
                        $error = $this->cart->update($rowid, max(0, (int) $qty));
                        $error !== null && $errors[] = $error;
                    }
                }
                $errors ? $this->flash('shop_error', implode(' ', array_unique($errors))) : $this->flash('shop_success', 'Your cart was updated.');
                break;

            case 'remove':
                $rowid = (string) $this->request->getPost('rowid');
                if (preg_match('/^[0-9a-f]{32}$/D', $rowid)) {
                    $this->cart->remove($rowid);
                    $this->flash('shop_success', 'The item was removed from your cart.');
                }
                break;

            case 'coupon':
                [$customer_id, $email] = $this->identity();
                $code  = mb_substr(trim((string) $this->request->getPost('coupon_code')), 0, 20);
                $error = $this->cart->apply_coupon($code, $customer_id, $email);
                $error === null ? $this->flash('shop_success', 'Coupon ' . $code . ' applied.') : $this->flash('shop_error', $error);
                break;

            case 'remove_coupon':
                $this->cart->remove_coupon();
                $this->flash('shop_success', 'The coupon was removed.');
                break;
        }

        redirect_now('cart');
    }

    /**
     * POST: add a product to the cart (product cards, product pages).
     *
     * @return \CodeIgniter\HTTP\ResponseInterface|null
     */
    public function add()
    {
        if ($this->request->getMethod() !== 'POST') {
            redirect_now('cart');
        }

        $product_id = (int) $this->request->getPost('product_id');
        $buy_now    = (string) $this->request->getPost('buy_now') === '1';
        $qty        = $this->request->getPost('qty');
        $qty        = is_scalar($qty) && (string) $qty !== '' ? (int) $qty : 1;
        $error      = $product_id > 0
            ? $this->cart->add($product_id, max(0, (int) $this->request->getPost('color_id')), max(0, (int) $this->request->getPost('size_id')), $qty)
            : 'Choose a product to add.';
        $message = $error ?? ($buy_now ? 'Added to your cart. Please complete your order.' : 'Added to your cart.');

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'       => $error === null,
                'message'  => $message,
                'count'    => $this->cart->count(),
                'redirect' => $error === null && $buy_now ? site_url($this->auth->current() ? 'checkout' : 'cart') : null,
            ]);
        }

        if ($error !== null) {
            $this->flash('shop_error', $error);
            redirect_now($this->same_site_referer() ?? 'cart');
        }
        $this->flash('shop_success', $message);
        redirect_now($buy_now && $this->auth->current() ? 'checkout' : 'cart');
    }

    /**
     * POST shipping_id (and items = 1 for the checkout steps' Order Summary):
     * choose the delivery option. AJAX gets the refreshed summary card
     * (html) and the new total; otherwise back to the page.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface|null
     */
    public function shipping()
    {
        if ($this->request->getMethod() !== 'POST') {
            redirect_now('cart');
        }

        $error = $this->cart->is_empty()
            ? 'Your cart is empty.'
            : $this->cart->choose_shipping((int) $this->request->getPost('shipping_id'));

        if ($this->request->isAJAX()) {
            [$customer_id, $email] = $this->identity();
            $summary = $this->cart->summary($customer_id, $email);
            if ($summary === false) {
                return $this->response->setStatusCode(503)->setJSON(['ok' => false, 'message' => 'Please try again in a moment.']);
            }

            return $this->response->setJSON([
                'ok'          => $error === null,
                'message'     => $error ?? 'Delivery option updated.',
                'html'        => view('Modules\Cart\Views\partials\summary', ['summary' => $summary, 'items' => $this->request->getPost('items') === '1', 'choose_shipping' => true]),
                'grand_total' => display_price($summary['grand_total']),
            ]);
        }

        $error === null ? $this->flash('shop_success', 'Delivery option updated.') : $this->flash('shop_error', $error);
        redirect_now($this->same_site_referer() ?? 'cart');
    }

    /* -----------------------------------------------------------------
     * Checkout steps
     * ----------------------------------------------------------------- */

    /**
     * Step 2 for a signed-in customer (design: checkout.htm): choose a saved
     * address or add one (the Add New Address popup). Visitors are sent to
     * sign in first; the login page offers Guest Checkout.
     */
    public function checkout(): string
    {
        $customer = $this->auth->require_login('checkout');
        $summary  = $this->checkout_summary();
        $book     = new AddressModel();
        $id       = (int) $customer['customers_id'];
        $values   = DeliveryAddress::blank();
        $errors   = [];
        $open     = false;

        if ($this->request->getMethod() === 'POST') {
            $action = (string) $this->request->getPost('action');
            if ($action === 'add') {
                $values = DeliveryAddress::posted($this->request);
                $v      = new FormValidation($this->request, $this);
                $v->set_data($values);
                DeliveryAddress::rules($v, $values);
                if (! $v->run()) {
                    $errors = array_values($v->error_array());
                    $open   = true;
                } else {
                    $count = count((array) $book->get_addresses($id));
                    $saved = $count < AddressModel::MAX_ADDRESSES ? $book->save_address($id, DeliveryAddress::to_book($values)) : false;
                    if ($saved === false) {
                        $errors = [$count >= AddressModel::MAX_ADDRESSES ? 'You can keep up to ' . AddressModel::MAX_ADDRESSES . ' addresses. Please delete one in My Address first.' : 'The address could not be saved. Please try again.'];
                        $open   = true;
                    } else {
                        $this->use_address($customer, $values);
                        redirect_now('payment');
                    }
                }
            } elseif ($action === 'deliver') {
                $address = $book->get_address($id, (int) $this->request->getPost('address_id'));
                if (is_array($address)) {
                    $this->use_address($customer, DeliveryAddress::from_book($address));
                    redirect_now('payment');
                }
                $errors = ['Please choose a delivery address.'];
            }
        }

        $addresses = $book->get_addresses($id);
        if ($addresses === false) {
            throw \App\Exceptions\HaltException::error('Your addresses are temporarily unavailable. Please try again shortly.', 503);
        }
        $chosen = $this->checkout_data()['delivery'] ?? null;

        return $this->render('Modules\Cart\Views\checkout', [
            'page_meta' => ['title' => 'Checkout'],
            'summary'   => $summary,
            'addresses' => $addresses,
            'chosen'    => $chosen,
            'values'    => $values,
            'errors'    => $errors,
            'open_form' => $open || ! $addresses,
            'locations' => DeliveryAddress::locations(),
            'shop'      => $this->shop,
        ]);
    }

    /**
     * Step 2 for a visitor (design: guest-checkout.htm): contact email and
     * mobile, and the delivery address. Signed-in customers use checkout().
     */
    public function guest(): string
    {
        if ($this->auth->current()) {
            redirect_now('checkout');
        }
        $summary = $this->checkout_summary();
        $data    = $this->checkout_data();
        $values  = ($data['delivery'] ?? null) ?: DeliveryAddress::blank();
        $values += ['email' => (string) ($data['customer']['email'] ?? ''), 'contact_mobile' => (string) ($data['customer']['phone'] ?? ''), 'newsletter' => ''];
        $errors  = [];

        if ($this->request->getMethod() === 'POST') {
            $values = DeliveryAddress::posted($this->request) + [
                'email'          => trim((string) $this->request->getPost('email')),
                'contact_mobile' => trim((string) $this->request->getPost('contact_mobile')),
                'newsletter'     => $this->request->getPost('newsletter') === '1' ? '1' : '',
            ];
            $v = new FormValidation($this->request, $this);
            $v->set_data($values);
            $v->set_rules('email', 'Email Address', 'required|max_length[120]|valid_email');
            $v->set_rules('contact_mobile', 'Mobile Number', ['required', ['valid_mobile', static fn ($value) => DeliveryAddress::valid_mobile((string) $value, $values['country'])]],
                ['valid_mobile' => DeliveryAddress::mobile_message($values['country'], 'for your contact details')]);
            DeliveryAddress::rules($v, $values);

            if ($v->run()) {
                [$first, $last] = array_pad(preg_split('/\s+/u', $values['full_name'], 2), 2, '');
                $this->save_checkout('customer', ['email' => strtolower($values['email']), 'phone' => DeliveryAddress::digits($values['contact_mobile']),
                    'first_name' => mb_substr($first, 0, 50), 'last_name' => mb_substr($last, 0, 50), 'newsletter' => $values['newsletter']]);
                $this->save_checkout('delivery', array_intersect_key($values, array_flip(DeliveryAddress::FIELDS)));
                redirect_now('payment');
            }
            $errors = array_values($v->error_array());
        }

        return $this->render('Modules\Cart\Views\guest_checkout', [
            'page_meta' => ['title' => 'Guest Checkout'],
            'summary'   => $summary,
            'values'    => $values,
            'errors'    => $errors,
            'locations' => DeliveryAddress::locations(),
            'shop'      => $this->shop,
        ]);
    }

    /**
     * Use an address for this checkout (a signed-in customer's details come from the account).
     */
    protected function use_address(array $customer, array $values): void
    {
        $this->save_checkout('customer', ['email' => (string) $customer['user_name'], 'phone' => (string) ($customer['mobile_number'] ?: DeliveryAddress::digits($values['mobile'])),
            'first_name' => (string) $customer['first_name'], 'last_name' => (string) $customer['last_name'], 'newsletter' => '']);
        $this->save_checkout('delivery', array_intersect_key($values, array_flip(DeliveryAddress::FIELDS)));
    }

    /**
     * Step 3 (design: payment.htm): the delivery address, payment method,
     * terms and Complete My Order. One-time token: a double click or the back
     * button can't place the order twice.
     */
    public function payment(): string
    {
        $data = $this->checkout_data();
        if (empty($data['customer']) || empty($data['delivery'])) {
            $this->flash('shop_notice', 'Please choose your delivery address first.');
            redirect_now($this->auth->current() ? 'checkout' : 'guest-checkout');
        }

        $summary = $this->checkout_summary();
        $method  = (string) array_key_first($this->shop->paymentMethods);
        $errors  = [];

        if ($this->request->getMethod() === 'POST') {
            $token = (string) $this->request->getPost('order_token');

            if ($token === '' || ! isset($data['token']) || ! hash_equals((string) $data['token'], $token)) {
                // Already placed (double click, back button) or an old form.
                $this->flash('shop_notice', 'This order form has expired. Please review your order and place it again.');
                redirect_now($this->cart->is_empty() ? 'cart' : 'payment');
            }
            $card = (string) $this->request->getPost('payment');
            if (! isset($this->shop->paymentCards[$card])) {
                $errors[] = 'Please choose how you would like to pay.';
            }
            if ($this->request->getPost('terms') !== '1') {
                $errors[] = 'Please accept the Terms & Conditions, Returns Policy and Refund Policy.';
            }
            if (! $errors) {
                $this->save_checkout('token', null); // one use
                $this->place_order($summary, $data, $method);
            }
        }

        $token = bin2hex(random_bytes(16));
        $this->save_checkout('token', $token);

        return $this->render('Modules\Cart\Views\payment', [
            'page_meta' => ['title' => 'Payment'],
            'summary'   => $summary,
            'delivery'  => $data['delivery'],
            'email'     => $data['customer']['email'],
            'shop'      => $this->shop,
            'card'      => (string) ($this->request->getPost('payment') ?? ''),
            'errors'    => $errors,
            'token'     => $token,
        ]);
    }

    /**
     * Save the order, take its stock, clear the cart and show the confirmation.
     *
     * The payment gateway is not connected yet (Config\Shop::$paymentMethods):
     * the order is placed Unpaid. When it is added, start the payment here
     * (after the order is saved) and mark the order paid only from the
     * gateway's verified return / callback.
     */
    protected function place_order(array $summary, array $data, string $method): never
    {
        $member   = $this->auth->current();
        $info     = $data['customer'];
        $delivery = $data['delivery'];
        $ip       = $this->request->getIPAddress();

        if ($member) {
            $customer = $member;
            $type     = 'Member';
        } else {
            $customer = (new CustomerModel())->guest_for_checkout($info['email'], $info['first_name'], $info['last_name'], $info['phone'], $ip);
            if ($customer === null) {
                $this->flash('shop_error', 'This email address can\'t be used for orders. Please use another email address.');
                redirect_now('guest-checkout');
            }
            if ($customer === false) {
                $this->flash('shop_error', 'Your order could not be placed right now. Please try again shortly.');
                redirect_now('payment');
            }
            $type = 'Guest';
        }

        $coupon = $summary['coupon'];
        $order  = [
            'customers_id'           => (int) $customer['customers_id'],
            'customer_type'          => $type,
            'first_name'             => $info['first_name'],
            'last_name'              => $info['last_name'],
            'email'                  => $member ? $member['user_name'] : $info['email'],
            'mobile_number'          => $info['phone'],
            'shipping_name'          => $delivery['full_name'],
            'shipping_address'       => $delivery['address'],
            'shipping_landmark'      => $delivery['landmark'] !== '' ? $delivery['landmark'] : null,
            'shipping_mobile'        => DeliveryAddress::digits($delivery['mobile']),
            'shipping_alt_mobile'    => $delivery['alt_mobile'] !== '' ? DeliveryAddress::digits($delivery['alt_mobile']) : null,
            'shipping_zipcode'       => $delivery['zipcode'],
            'shipping_country'       => $delivery['country'],
            'shipping_state'         => $delivery['state'],
            'shipping_city'          => $delivery['city'],
            'delivery_notes'         => null,
            'shipping_id'            => $summary['shipping_id'] ?? null,
            'shipping_method'        => $summary['shipping_method'] ?? ($summary['shipping_amount'] > 0 ? 'Standard Delivery' : 'Free Delivery'),
            'discount_coupon_id'     => $coupon ? (int) $coupon['coupon_id'] : null,
            'coupon_code'            => $coupon ? $summary['coupon_code'] : null,
            'coupon_discount_amount' => $summary['discount'],
            'shipping_amount'        => $summary['shipping_amount'],
            'delivery_min_days'      => $summary['delivery_min_days'] ?? null,
            'delivery_max_days'      => $summary['delivery_max_days'] ?? null,
            'total_amount'           => $summary['subtotal'],
            'vat_amount'             => $summary['gst_amount'],
            'vat_applied_cent'       => $summary['gst_percent'],
            'price_without_tax'      => $summary['price_without_tax'],
            'grand_total'            => $summary['grand_total'],
            'currency_code'          => site_currency()['code'], // the wl_currencies default the amounts were shown in
            'currency_symbol'        => mb_substr(site_currency()['symbol_left'] !== '' ? site_currency()['symbol_left'] : site_currency()['symbol_right'], 0, 10),
            'currency_value'         => 1,
            'payment_method'         => $method,
            'ip_address'             => $ip,
        ];

        $lines = [];

        foreach ($summary['lines'] as $line) {
            $lines[] = [
                'products_id'        => $line['product_id'],
                'stock_id'           => $line['stock_id'],
                'product_name'       => $line['name'],
                'product_code'       => $line['code'],
                'product_brand'      => $line['brand_name'],
                'product_color'      => $line['color_name'],
                'product_color_id'   => $line['color_id'] ?: null,
                'product_color_code' => $line['color_code'],
                'product_size'       => $line['size_name'],
                'product_size_id'    => $line['size_id'] ?: null,
                'product_image'      => $line['image'],
                'product_price'      => $line['price'],
                'quantity'           => $line['qty'],
            ];
        }

        $orders = new OrderModel();
        $placed = $orders->place_order($order, $lines);
        if (is_string($placed)) {
            $this->flash('shop_error', $placed);
            redirect_now('cart');
        }

        if ($member && ! (new AddressModel())->get_default_address((int) $member['customers_id'])) {
            (new AddressModel())->save_default_address((int) $member['customers_id'], DeliveryAddress::to_book($delivery));
        }
        if (($info['newsletter'] ?? '') === '1') {
            $this->subscribe_newsletter($order['email'], trim($info['first_name'] . ' ' . $info['last_name']));
        }

        $saved = $orders->get_order_by_id($placed['order_id']);
        $items = is_array($saved) ? $orders->get_items($placed['order_id']) : false;
        if (is_array($saved) && is_array($items) && class_exists('Modules\Cart\Libraries\OrderMailer')) {
            try {
                (new \Modules\Cart\Libraries\OrderMailer())->placed($saved, $items);
            } catch (\Throwable $e) {
                log_message('error', 'Cart::place_order: confirmation email of ' . $placed['invoice_number'] . ' failed: ' . $e->getMessage());
            }
        }

        $this->cart->clear();
        session()->remove(self::CHECKOUT_KEY);
        $placed_orders   = (array) session(self::ORDERS_KEY);
        $placed_orders[] = $placed['invoice_number'];
        session()->set(self::ORDERS_KEY, array_slice(array_values(array_unique($placed_orders)), -10));

        redirect_now('order-confirmation?order=' . OrderModel::reference($placed['invoice_number']));
    }

    /**
     * Step 4: the order just placed (?order=<reference>), for the visitor who
     * placed it (its invoice number is in the session) or the signed-in
     * customer it belongs to; 404 for anyone else and any unknown reference.
     * An old link with the invoice number gets the same check, then the
     * reference address.
     */
    public function confirmation(): string
    {
        $customer = $this->auth->current();
        $order    = $this->placed_order('order-confirmation');
        $orders   = new OrderModel();

        return $this->render('Modules\Cart\Views\confirmation', [
            'page_meta' => ['lookup' => false, 'title' => 'Order ' . $order['invoice_number'] . ' Confirmed', 'canonical' => site_url('order-confirmation')],
            'order'     => $order,
            'items'     => (array) $orders->get_items((int) $order['order_id']),
            'customer'     => $customer,
            'shop'         => $this->shop,
        ]);
    }

    /**
     * Invoice (design: invoice.htm) of an order the visitor may see on the
     * confirmation page: placed in this session (guests) or the signed-in
     * customer's own order. ?print=1: the printable invoice (print-invoice.htm),
     * shown in the PRINT INVOICE popup.
     */
    public function invoice(): string
    {
        $order = $this->placed_order('order-invoice');
        $data  = [
            'order'    => $order,
            'items'    => (array) (new OrderModel())->get_items((int) $order['order_id']),
            'shop'     => $this->shop,
            'settings' => service('siteSettings'),
        ];

        if ($this->request->getGet('print') === '1') {
            return view('Modules\Members\Views\invoice_print', $data);
        }

        return $this->render('Modules\Cart\Views\invoice', $data + [
            'page_meta'  => ['lookup' => false, 'title' => 'Invoice ' . $order['invoice_number'] . ' | CustomCart', 'canonical' => site_url('order-invoice')],
            'body_class' => 'kt-print-page',
            'ref'        => OrderModel::reference((string) $order['invoice_number']),
        ]);
    }

    /**
     * The order of ?order=<reference> the visitor may see: placed in this
     * session, or the signed-in customer's own. Not found (404) otherwise; an
     * old ?order=<invoice number> link is redirected to $page with the reference.
     */
    protected function placed_order(string $page): array
    {
        $ref      = (string) $this->request->getGet('order');
        $customer = $this->auth->current();
        $placed   = array_map('strval', (array) session(self::ORDERS_KEY));
        $orders   = new OrderModel();
        $legacy   = false;

        if (OrderModel::is_reference($ref)) {
            $invoice = null;
            foreach ($placed as $candidate) {
                hash_equals(OrderModel::reference($candidate), $ref) && $invoice = $candidate;
            }
            $order = $invoice !== null ? $orders->get_order_by_invoice($invoice)
                : ($customer ? $orders->get_customer_order_by_reference((int) $customer['customers_id'], $ref) : null);
        } elseif (preg_match('/^[A-Za-z0-9\-]{1,30}$/D', $ref)) {
            $legacy = true;
            $order  = in_array($ref, $placed, true) ? $orders->get_order_by_invoice($ref)
                : ($customer ? $orders->get_customer_order((int) $customer['customers_id'], $ref) : null);
        } else {
            $order = null;
        }

        if ($order === false) {
            throw \App\Exceptions\HaltException::error('Your order details are temporarily unavailable.', 503);
        }
        if (! is_array($order)) {
            $this->not_found_page();
        }
        if ($legacy) {
            redirect_now($page . '?order=' . OrderModel::reference($order['invoice_number']));
        }

        return $order;
    }

    /* -----------------------------------------------------------------
     * Helpers
     * ----------------------------------------------------------------- */

    /**
     * [customer id, email] for coupon limits: the signed-in member, else the email given at checkout.
     */
    protected function identity(): array
    {
        $customer = $this->auth->current();
        if ($customer) {
            return [(int) $customer['customers_id'], (string) $customer['user_name']];
        }

        return [0, (string) ($this->checkout_data()['customer']['email'] ?? '')];
    }

    protected function summary_or_fail(int $customer_id, string $email): array
    {
        $summary = $this->cart->summary($customer_id, $email);
        if ($summary === false) {
            throw \App\Exceptions\HaltException::error('The cart is temporarily unavailable. Please try again shortly.', 503);
        }
        $summary['coupon_error'] !== null && $this->view_data['coupon_notice'] = $summary['coupon_error'];

        return $summary;
    }

    /**
     * The summary of a checkout step: the cart must have items and no problems.
     */
    protected function checkout_summary(): array
    {
        [$customer_id, $email] = $this->identity();
        $summary = $this->summary_or_fail($customer_id, $email);

        if (! $summary['lines']) {
            $this->flash('shop_notice', 'Your cart is empty.');
            redirect_now('cart');
        }
        if ($summary['has_problems']) {
            $this->flash('shop_error', 'Some items in your cart need your attention before you check out.');
            redirect_now('cart');
        }

        return $summary;
    }

    protected function checkout_data(): array
    {
        $data = session(self::CHECKOUT_KEY);

        return is_array($data) ? $data : [];
    }

    /**
     * @param mixed $value
     */
    protected function save_checkout(string $key, $value): void
    {
        $data       = $this->checkout_data();
        $data[$key] = $value;
        session()->set(self::CHECKOUT_KEY, $data);
    }

    /**
     * Add the email to the newsletter list (wl_newsletters) unless it is already there.
     */
    protected function subscribe_newsletter(string $email, string $name): void
    {
        try {
            $db = \Config\Database::connect();
            if ($db->table('wl_newsletters')->where('subscriber_email', $email)->countAllResults() === 0) {
                $db->table('wl_newsletters')->insert([
                    'subscriber_name' => mb_substr($name, 0, 32), 'subscriber_email' => mb_substr($email, 0, 80),
                    'status' => '1', 'subscribe_date' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Cart::subscribe_newsletter: ' . $e->getMessage());
        }
    }

    /**
     * The Referer when it is a page of this site (back to the product after an error), else NULL.
     */
    protected function same_site_referer(): ?string
    {
        $referer = (string) $this->request->getServer('HTTP_REFERER');
        $base    = rtrim(site_url(), '/') . '/';

        if ($referer === '' || ! str_starts_with($referer, $base)) {
            return null;
        }

        return CustomerAuth::safe_path(substr($referer, strlen($base)));
    }

    protected function flash(string $key, string $message): void
    {
        session()->setFlashdata($key, $message);
    }
}
