<?php

namespace Modules\Members\Controllers;

use App\Controllers\PublicController;
use App\Exceptions\HaltException;
use App\Libraries\FormValidation;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Shop;
use Modules\Cart\Models\OrderModel;
use Modules\Cart\Models\ReasonModel;
use Modules\Cart\Libraries\DeliveryAddress;
use Modules\Members\Models\AddressModel;
use Modules\Members\Models\WishlistModel;
use Modules\Sitepanel\Models\CouponModel;
use Modules\Sitepanel\Models\NewsletterModel;
use Modules\Products\Models\ProductModel;
use Modules\Users\Controllers\Users;
use Modules\Users\Libraries\CustomerAuth;
use Modules\Users\Models\CustomerModel;

/**
 * My Account (the reference's members module), built from html/dashboard.htm
 * and the acc-*.htm pages. Signed-in customers only (CustomerAuth); each
 * address is a wl_meta_tags row (StoreSeeder):
 *
 *   /dashboard            dashboard        counts and the latest orders
 *   /acc-order-history    orders           ?q=<invoice>&status=<order status>&page=
 *   /acc-order-invoice    order            ?order=<reference>: one of the customer's orders (OrderModel::reference());
 *                                          POST action=cancel|return + item_id: ask to cancel / return a line
 *   /invoice-print        invoice_print    ?order=<reference>: printable invoice
 *   /acc-wishlist         wishlist         POST action=add|remove + product_id (+ ref): JSON for AJAX
 *   /acc-edit             edit_account     name, mobile, address, newsletter
 *   /change-password      change_password
 *
 * Every order and order line is looked up with the customer's id, so a
 * customer only ever reaches their own (the reference didn't check). Cancel /
 * return requests follow the reference's rules; Sitepanel > Orders accepts
 * or rejects them (and gives the stock back).
 */
class Members extends PublicController
{
    public const PER_PAGE = 10;

    /**
     * Order statuses as customers see them.
     */
    public const STATUS_LABELS = [
        'Pending'            => 'Processing',
        'Ready For Dispatch' => 'Ready for Dispatch',
        'Dispatched'         => 'Shipped',
        'Delivered'          => 'Delivered',
        'Closed'             => 'Completed',
        'Canceled'           => 'Cancelled',
        'Rejected'           => 'Rejected',
        'Return'             => 'Returned',
    ];

    /**
     * Order statuses after which nothing can be cancelled any more.
     */
    public const NOT_CANCELLABLE_ORDER = ['Dispatched', 'Delivered', 'Closed', 'Canceled', 'Rejected', 'Return'];

    protected $helpers = ['shop'];

    protected CustomerAuth $auth;
    protected CustomerModel $customers;
    protected OrderModel $orders;
    protected WishlistModel $wishlist;
    protected ReasonModel $reasons;

    protected function init(): void
    {
        $this->auth      = new CustomerAuth();
        $this->customers = new CustomerModel();
        $this->orders    = new OrderModel();
        $this->wishlist  = new WishlistModel();
        $this->reasons   = new ReasonModel();

        // Personal pages: never cached by the browser or a proxy.
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
    }

    /**
     * Render an account page with the account navigation data.
     */
    protected function page(string $view, string $active, string $title, array $data): string
    {
        return $this->render('Modules\Members\Views\\' . $view, $data + [
            'page_meta'  => ['title' => $title . ' | CustomCart'],
            'body_class' => 'page-inner page-cms',
            'nav_active' => $active,
        ]);
    }

    public function dashboard(): string
    {
        $customer = $this->auth->require_login();
        $id       = (int) $customer['customers_id'];

        $recent = $this->orders->get_customer_orders($id, 0, 5);

        return $this->page('dashboard', 'dashboard', 'My Account', [
            'customer'       => $customer,
            'order_count'    => $this->orders->count_customer_orders($id),
            'wishlist_count' => $this->wishlist->count_items($id),
            'coupon_count'   => count((array) (new CouponModel())->get_current_coupons(true)),
            'orders'         => $recent,
        ]);
    }

    public function orders(): string
    {
        $customer = $this->auth->require_login();
        $keyword  = mb_substr(trim((string) $this->request->getGet('q')), 0, 30);
        $status   = (string) $this->request->getGet('status');
        $status   = isset(self::STATUS_LABELS[$status]) ? $status : '';
        $page     = max(1, (int) $this->request->getGet('page'));

        $orders = $this->orders->get_customer_orders((int) $customer['customers_id'], ($page - 1) * self::PER_PAGE, self::PER_PAGE, $keyword, $status);

        return $this->page('orders', 'orders', 'Order History', [
            'customer' => $customer,
            'orders'   => $orders,
            'total'    => $this->orders->total_rec_found,
            'keyword'  => $keyword,
            'status'   => $status,
            'pager'    => ['page' => $page, 'total' => $this->orders->total_rec_found, 'per_page' => self::PER_PAGE,
                'url' => site_url('acc-order-history'), 'query' => ['q' => $keyword, 'status' => $status]],
        ]);
    }

    public function order(): string
    {
        $customer = $this->auth->require_login();
        [$order, $items] = $this->own_order($customer);

        if ($this->request->getMethod() === 'POST') {
            $this->submit_request($customer, $order, $items);
        }

        $requests = [];
        foreach ($items as $item) {
            $requests[$item['orders_products_id']] = [
                'cancel' => $this->can_cancel($order, $item),
                'return' => $this->can_return($order, $item),
            ];
        }

        return $this->page('order', 'orders', 'Order ' . $order['invoice_number'], [
            'customer'  => $customer,
            'order'     => $order,
            'items'     => $items,
            'requests'  => $requests,
            'shop'      => config(Shop::class),
            // Sitepanel > Manage Orders > Manage Reasons (active ones).
            'reasons'   => ['cancel' => $this->reasons->active_names('cancel'), 'return' => $this->reasons->active_names('return')],
            'open_form' => (string) $this->request->getPost('item_id'),
        ]);
    }

    public function invoice_print(): string
    {
        $customer = $this->auth->require_login();
        [$order, $items] = $this->own_order($customer);

        return $this->render('Modules\Members\Views\invoice_print', [
            'order'    => $order,
            'items'    => $items,
            'shop'     => config(Shop::class),
            'settings' => $this->site_settings,
        ]);
    }

    /**
     * The signed-in customer's order named by ?order=<reference> with its lines;
     * 404 for anything else. An old link with the invoice number (order emails)
     * is looked up the same way, among the customer's own orders, and sent to
     * the reference address.
     *
     * @return array{0: array, 1: list<array>}
     */
    protected function own_order(array $customer): array
    {
        $ref = trim((string) $this->request->getGet('order'));
        if (OrderModel::is_reference($ref)) {
            $order = $this->orders->get_customer_order_by_reference((int) $customer['customers_id'], $ref);
        } elseif ($ref !== '' && strlen($ref) <= 30) {
            $order = $this->orders->get_customer_order((int) $customer['customers_id'], $ref);
            is_array($order) && redirect_now(uri_string() . '?order=' . OrderModel::reference($order['invoice_number']));
        } else {
            $order = null;
        }
        if ($order === null) {
            $this->not_found_page();
        }
        $items = is_array($order) ? $this->orders->get_items((int) $order['order_id']) : false;
        if ($order === false || $items === false) {
            throw HaltException::error('Your order is temporarily unavailable. Please try again shortly.', 503);
        }

        return [$order, $items];
    }

    /**
     * The reference's Cancel button rule: the line is still Pending, nothing
     * was asked for it yet, and the order hasn't left the store.
     */
    protected function can_cancel(array $order, array $item): bool
    {
        return in_array($item['product_order_status'], OrderModel::CANCELLABLE, true)
            && $item['is_cancel'] === '0' && $item['is_return'] === '0'
            && ! in_array($order['order_status'], self::NOT_CANCELLABLE_ORDER, true);
    }

    /**
     * The reference's Return button rule: the line was delivered and no return was asked for.
     */
    protected function can_return(array $order, array $item): bool
    {
        return $item['product_order_status'] === 'Delivered' && $item['is_return'] === '0';
    }

    /**
     * POST action=cancel|return, item_id, reason, comment: ask to cancel or
     * return one line of this order. Redirects with the result, or in the
     * popup ($frame) returns it.
     *
     * @return array{0: bool, 1: string} [sent, message]
     */
    protected function submit_request(array $customer, array $order, array $items, bool $frame = false): array
    {
        // In the popup ($frame) [ok, message] is returned instead of redirecting.
        $finish = static function (bool $ok, string $message, string $back) use ($frame): array {
            if ($frame) {
                return [$ok, $message];
            }
            session()->setFlashdata($ok ? 'shop_success' : 'shop_error', $message);
            redirect_now($back);
        };
        $action = (string) $this->request->getPost('action');
        $id     = (int) $this->request->getPost('item_id');
        $back   = 'acc-order-invoice?order=' . OrderModel::reference($order['invoice_number']);
        $item   = null;

        foreach ($items as $row) {
            (int) $row['orders_products_id'] === $id && $item = $row;
        }

        // The line must belong to this order of this customer, and still allow the request.
        $owned = $item !== null ? $this->orders->get_item($id) : null;
        if (! in_array($action, ['cancel', 'return'], true) || ! is_array($owned) || (int) $owned['customers_id'] !== (int) $customer['customers_id']
            || ! ($action === 'cancel' ? $this->can_cancel($order, $item) : $this->can_return($order, $item))) {
            return $finish(false, 'That request is not available for this item.', $back);
        }

        $reasons = $this->reasons->active_names($action);
        $v       = new FormValidation($this->request, $this);
        $v->set_rules('reason', 'Reason', ['required', ['valid_reason', static fn ($value) => in_array((string) $value, $reasons, true)]], ['valid_reason' => 'Choose a reason from the list.']);
        $v->set_rules('comment', 'Comments', 'trim|max_length[1000]');
        if (! $v->run()) {
            return $finish(false, implode(' ', $v->error_array()), $back);
        }

        // The comment, after a "Request type: ..." line when a known request type was chosen.
        $kind    = trim((string) $this->request->getPost('request_type'));
        $comment = in_array($kind, ['Return & Refund', 'Exchange'], true) ? 'Request type: ' . $kind . "\n" : '';
        $comment = trim($comment . trim((string) $this->request->getPost('comment')));

        $saved = $this->orders->request_item($id, $action, (string) $this->request->getPost('reason'), $comment);

        return $finish($saved, $saved
            ? 'Your ' . ($action === 'cancel' ? 'cancellation' : 'return') . ' request for "' . $item['product_name'] . '" has been sent. We will let you know once it has been reviewed.'
            : 'Your request could not be sent right now. Please try again shortly.', $back);
    }

    /**
     * @return ResponseInterface|string
     */
    public function wishlist()
    {
        if ($this->request->getMethod() === 'POST') {
            return $this->wishlist_action();
        }

        $customer = $this->auth->require_login();
        $products = $this->wishlist->get_wishlist((int) $customer['customers_id']);

        return $this->page('wishlist', 'wishlist', 'Wishlist', [
            'customer' => $customer,
            'products' => $products,
            'variants' => is_array($products) ? $this->variant_products(array_column($products, 'products_id')) : [],
        ]);
    }

    /**
     * Ids among $ids that have colours or sizes (Add to Cart needs a choice on
     * the product page), in one query (ProductModel::variant_product_ids()).
     *
     * @param list<int|string> $ids
     *
     * @return array<int, true>
     */
    protected function variant_products(array $ids): array
    {
        $found = (new ProductModel())->variant_product_ids(array_map('intval', $ids));

        return array_fill_keys(is_array($found) ? $found : [], true);
    }

    /**
     * POST action=add|remove, product_id, ref (Track: product pages' heart
     * buttons and the wishlist's Remove). AJAX requests get JSON.
     *
     * @return ResponseInterface
     */
    protected function wishlist_action()
    {
        $ajax       = $this->request->isAJAX();
        $ref        = CustomerAuth::safe_path((string) $this->request->getPost('ref'));
        $action     = (string) $this->request->getPost('action');
        $product_id = (int) $this->request->getPost('product_id');
        $customer   = $this->auth->current();

        if ($customer === null) {
            if ($ajax) {
                session()->set(CustomerAuth::REF_KEY, $ref ?? 'acc-wishlist');

                return $this->response->setJSON(['ok' => false, 'login' => true, 'message' => 'Please log in to save products to your wishlist.', 'redirect' => site_url('login')]);
            }
            $this->auth->require_login($ref ?? 'acc-wishlist');
        }

        $id = (int) $customer['customers_id'];
        if (! in_array($action, ['add', 'remove'], true) || $product_id < 1) {
            $result = [false, 'That wishlist request is not valid.', null];
        } elseif ($action === 'add') {
            $product = (new ProductModel())->get_public_product($product_id);
            if (! is_array($product)) {
                $result = [false, $product === false ? 'Your wishlist is not available right now. Please try again shortly.' : 'This product is no longer available.', false];
            } else {
                $ok     = $this->wishlist->add($id, $product_id);
                $result = [$ok, $ok ? '"' . $product['product_name'] . '" was added to your wishlist.' : 'Your wishlist could not be updated. Please try again.', $ok];
            }
        } else {
            $ok     = $this->wishlist->remove($id, $product_id);
            $result = [$ok, $ok ? 'The product was removed from your wishlist.' : 'Your wishlist could not be updated. Please try again.', $ok ? false : null];
        }

        if ($ajax) {
            $count = $this->wishlist->count_items($id);

            return $this->response->setJSON(['ok' => $result[0], 'in_wishlist' => $result[2], 'message' => $result[1], 'count' => $count === false ? null : $count]);
        }

        session()->setFlashdata($result[0] ? 'shop_success' : 'shop_error', $result[1]);
        redirect_now($ref ?? 'acc-wishlist');
    }

    public function edit_account(): string
    {
        $customer = $this->auth->require_login();
        $values   = [
            'full_name'     => trim($customer['first_name'] . ' ' . $customer['last_name']),
            'mobile_number' => (string) $customer['mobile_number'],
            'newsletter'    => (string) $customer['newsletter'],
        ];
        $errors = [];

        if ($this->request->getMethod() === 'POST') {
            $values['full_name']     = trim((string) $this->request->getPost('full_name'));
            $values['mobile_number'] = DeliveryAddress::digits(trim((string) $this->request->getPost('mobile_number')));
            $values['newsletter']    = $this->request->getPost('newsletter') === '1' ? '1' : '0';

            $v = new FormValidation($this->request, $this);
            $v->set_data($values);
            $v->set_rules('full_name', 'Full Name', 'required|min_length[3]|max_length[100]|' . FormValidation::PERSON_NAME, ['regex_match' => 'The {field} may contain only letters, spaces and . \' -']);
            $v->set_rules('mobile_number', 'Mobile Number', ['required', ['valid_mobile', static fn ($value) => DeliveryAddress::valid_mobile((string) $value)]],
                ['valid_mobile' => DeliveryAddress::mobile_message('')]);

            if (! $v->run()) {
                $errors = array_values($v->error_array());
            } elseif (($mobile_taken = $this->customers->member_mobile_taken($values['mobile_number'], (int) $customer['customers_id'])) !== false) {
                $errors = [$mobile_taken === null ? 'Your details could not be saved right now. Please try again shortly.' : 'Another account already uses this mobile number.'];
            } else {
                [$first, $last] = split_name($values['full_name']);
                $data = ['first_name' => mb_substr($first, 0, 50), 'last_name' => mb_substr($last, 0, 50), 'mobile_number' => $values['mobile_number'], 'newsletter' => $values['newsletter']];
                if ($this->customers->update_profile((int) $customer['customers_id'], $data)) {
                    if ($values['newsletter'] === '1' && $customer['newsletter'] !== '1') {
                        (new NewsletterModel())->subscribe(strtolower(trim((string) $customer['user_name'])), $first);
                    }
                    session()->setFlashdata('shop_success', 'Your profile has been updated.');
                    redirect_now('acc-edit');
                }
                $errors = ['Your details could not be saved right now. Please try again shortly.'];
            }
        }

        return $this->page('edit_account', 'edit', 'Edit Profile', [
            'customer'        => $customer,
            'values'          => $values,
            'errors'          => $errors,
            'password_errors' => (array) session()->getFlashdata('password_errors'),
        ]);
    }

    /**
     * POST from Edit Profile's Change Password form; back to Edit Profile.
     */
    public function change_password(): void
    {
        $customer = $this->auth->require_login();
        if ($this->request->getMethod() !== 'POST') {
            redirect_now('acc-edit#ktPassword');
        }

        $v = new FormValidation($this->request, $this);
        $v->set_rules('current_password', 'Current Password', 'required|max_length[' . Users::PASSWORD_MAX . ']');
        Users::password_rules($v, 'new_password', 'New Password');
        $v->set_rules('confirm_password', 'Confirm New Password', 'required|matches[new_password]', ['matches' => 'The new passwords do not match.']);

        if (! $v->run()) {
            $errors = array_values($v->error_array());
        } elseif (! password_verify((string) $this->request->getPost('current_password'), (string) $customer['password'])) {
            $errors = ['Your current password is incorrect.'];
        } elseif ($this->customers->update_password((int) $customer['customers_id'], (string) $this->request->getPost('new_password'))) {
            session()->setFlashdata('shop_success', 'Your password has been changed.');
            redirect_now('acc-edit');
        } else {
            $errors = ['Your password could not be changed right now. Please try again shortly.'];
        }
        session()->setFlashdata('password_errors', $errors);
        redirect_now('acc-edit#ktPassword');
    }

    /**
     * My Address (design: address.htm): the address book. POST action=save
     * (add, or edit with address_id), delete, default. ?edit=<id> opens an
     * address in the form.
     */
    public function address(): string
    {
        $customer = $this->auth->require_login();
        $id       = (int) $customer['customers_id'];
        $book     = new AddressModel();
        $values   = DeliveryAddress::blank();
        $edit     = (int) $this->request->getGet('edit');
        $errors   = [];

        if ($this->request->getMethod() === 'POST') {
            $action  = (string) $this->request->getPost('action');
            $address = (int) $this->request->getPost('address_id');
            if ($action === 'delete' || $action === 'default') {
                $ok = $action === 'delete' ? $book->delete_address($id, $address) : $book->set_default($id, $address);
                session()->setFlashdata($ok ? 'shop_success' : 'shop_error', $ok ? ($action === 'delete' ? 'The address was deleted.' : 'Your default address was changed.') : 'The address could not be changed. Please try again.');
                redirect_now('acc-address');
            }

            $values = DeliveryAddress::posted($this->request);
            $edit   = $address;
            $v      = new FormValidation($this->request, $this);
            $v->set_data($values);
            DeliveryAddress::rules($v, $values);
            if (! $v->run()) {
                $errors = array_values($v->error_array());
            } elseif ($address < 1 && count((array) $book->get_addresses($id)) >= AddressModel::MAX_ADDRESSES) {
                $errors = ['You can keep up to ' . AddressModel::MAX_ADDRESSES . ' addresses. Please delete one first.'];
            } elseif ($address > 0 && ! is_array($book->get_address($id, $address))) {
                $this->not_found_page();
            } elseif ($book->save_address($id, DeliveryAddress::to_book($values), $address, $this->request->getPost('make_default') === '1') === false) {
                $errors = ['The address could not be saved. Please try again.'];
            } else {
                session()->setFlashdata('shop_success', 'Your address has been saved.');
                redirect_now('acc-address');
            }
        } elseif ($edit > 0) {
            $row = $book->get_address($id, $edit);
            is_array($row) ? $values = DeliveryAddress::from_book($row) : $edit = 0;
        }

        $addresses = $book->get_addresses($id);
        if ($addresses === false) {
            throw HaltException::error('Your addresses are temporarily unavailable. Please try again shortly.', 503);
        }

        return $this->page('address', 'address', 'My Address', [
            'customer'  => $customer,
            'addresses' => $addresses,
            'values'    => $values,
            'edit'      => $edit,
            'errors'    => $errors,
            'locations' => DeliveryAddress::locations(),
            'shop'      => config(Shop::class),
        ]);
    }

    /**
     * My Coupons (design: coupons.htm): the coupons valid today.
     */
    public function coupons(): string
    {
        $customer = $this->auth->require_login();
        $coupons  = (new CouponModel())->get_current_coupons(true);

        return $this->page('coupons', 'coupons', 'My Coupons', [
            'customer' => $customer,
            'coupons'  => is_array($coupons) ? $coupons : [],
        ]);
    }

    /**
     * Track Order popup (design: track-order.htm): a signed-in customer's own
     * order by ?order=<invoice number>; anyone else enters the order number and
     * the email it was placed with.
     */
    public function track_order(): string
    {
        $customer = $this->auth->current();
        $invoice  = ltrim(mb_substr(trim((string) ($this->request->getPost('order') ?? $this->request->getGet('order'))), 0, 30), '#');
        $email    = mb_substr(trim((string) $this->request->getPost('email')), 0, 120);
        $order    = null;
        $error    = '';

        if ($invoice !== '' && $customer) {
            $order = $this->orders->get_customer_order((int) $customer['customers_id'], $invoice);
        }
        if ($order === null && $this->request->getMethod() === 'POST') {
            $order = filter_var($email, FILTER_VALIDATE_EMAIL) && $invoice !== '' ? $this->orders->get_order_for_tracking($invoice, $email) : null;
            $order === null && $error = 'We could not find an order with this order number and email address.';
        }
        if ($order === false) {
            $error = 'Order tracking is not available right now. Please try again shortly.';
            $order = null;
        }

        return $this->render('Modules\Members\Views\track_order', [
            'page_meta' => ['title' => 'Track Order | CustomCart', 'lookup' => false],
            'is_frame'  => (bool) $this->request->getGet('frame') || $this->request->getHeaderLine('Sec-Fetch-Dest') === 'iframe',
            'order'     => $order,
            'items'     => is_array($order) ? (array) $this->orders->get_items((int) $order['order_id']) : [],
            'invoice'   => $invoice,
            'email'     => $email,
            'error'     => $error,
            'customer'  => $customer,
        ]);
    }

    /**
     * Cancel Item / Return Item popup (design: cancel-item.htm, return-item.htm):
     * ?order=<reference>&item=<orders_products_id>&type=cancel|return; POST
     * reason + comment (submit_request()). Signed-in customers, their own lines only.
     */
    public function item_request(): string
    {
        $customer = $this->auth->require_login();
        [$order, $items] = $this->own_order($customer);
        $type = (string) ($this->request->getPost('action') ?? $this->request->getGet('type'));
        $type = $type === 'return' ? 'return' : 'cancel';
        $id   = (int) ($this->request->getPost('item_id') ?? $this->request->getGet('item'));
        $item = null;
        foreach ($items as $row) {
            (int) $row['orders_products_id'] === $id && $item = $row;
        }
        if ($item === null) {
            $this->not_found_page();
        }
        $allowed = $type === 'cancel' ? $this->can_cancel($order, $item) : $this->can_return($order, $item);
        [$sent, $message] = $allowed && $this->request->getMethod() === 'POST'
            ? $this->submit_request($customer, $order, $items, true)
            : [false, ''];

        return $this->render('Modules\Members\Views\item_request', [
            'page_meta' => ['title' => ($type === 'cancel' ? 'Cancel' : 'Return') . ' Item | CustomCart', 'lookup' => false],
            'is_frame'  => true,
            'body_attrs' => $sent ? ['data-kt-sent' => $message] : [],
            'order'     => $order,
            'item'      => $item,
            'type'      => $type,
            'allowed'   => $allowed,
            'error'     => $sent ? '' : $message,
            'reasons'   => $this->reasons->active_names($type),
        ]);
    }
}
