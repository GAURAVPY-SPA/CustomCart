<?php

namespace Modules\Cart\Models;

use App\Models\BaseModel;
use Config\Shop;
use Modules\Products\Models\ProductModel;
use Throwable;

/**
 * Orders (wl_order) and their lines (wl_orders_products) — the reference's
 * cart/process, order model and sitepanel Orders — with its stock bugs fixed:
 *
 *  - stock is taken from the line's stock row when the order is placed, in
 *    the order's transaction and only if that many units are available
 *    (the reference never checked, and only reduced stock on a gateway
 *    success that never happened);
 *  - a cancelled, rejected or returned line gives its units back to the same
 *    stock row once (is_stock_back), whichever way it was cancelled.
 *
 * Statuses: order_status Pending → Ready For Dispatch → Dispatched →
 * Delivered (or Closed), and Canceled / Rejected; a line follows its order
 * unless it was cancelled, rejected or returned on its own. 'Deleted' orders
 * are hidden everywhere (soft delete, as in the reference).
 */
class OrderModel extends BaseModel
{
    public const TABLE       = 'wl_order';
    public const PRIMARY_KEY = 'order_id';
    public const ITEMS       = 'wl_orders_products';

    /**
     * Order statuses an administrator can set => the status of the lines that follow it.
     */
    public const STATUS_FLOW = [
        'Pending'            => 'Pending',
        'Ready For Dispatch' => 'Ready For Dispatch',
        'Dispatched'         => 'Dispatched',
        'Delivered'          => 'Delivered',
        'Closed'             => 'Closed',
        'Canceled'           => 'Cancelled',
        'Rejected'           => 'Rejected',
    ];

    /**
     * Line statuses that leave the order's flow (and whose stock went back).
     */
    public const LINE_ENDED = ['Cancelled', 'Rejected', 'Returned'];

    /**
     * Line statuses a customer may still ask to cancel (the reference's rule).
     */
    public const CANCELLABLE = ['Pending'];

    protected ProductModel $products;

    public function __construct()
    {
        $this->products = new ProductModel();
    }

    /**
     * What the customer pays: the grand total (items - coupon + delivery + VAT);
     * orders saved without one: items - coupon + delivery.
     */
    public static function payable(array $order): float
    {
        if ((float) ($order['grand_total'] ?? 0) > 0) {
            return round((float) $order['grand_total'], 2);
        }

        return round((float) $order['total_amount'] - (float) $order['coupon_discount_amount'] + (float) $order['shipping_amount'], 2);
    }

    /* -----------------------------------------------------------------
     * Placing an order
     * ----------------------------------------------------------------- */

    /**
     * Save an order and take its stock, all or nothing.
     *
     * @param array $order wl_order columns (without invoice_number, order_status, payment_status)
     * @param list<array> $lines each: products_id, stock_id, product_name, product_code, product_brand,
     *                           product_color(_id/_code), product_size(_id), product_image, product_price, quantity
     *
     * @return array|string ['order_id', 'invoice_number'], or the message to show
     */
    public function place_order(array $order, array $lines)
    {
        if (! $lines) {
            return 'Your cart is empty.';
        }
        if (! $this->db_ready()) {
            return 'Your order could not be placed right now. Please try again shortly.';
        }

        $shop     = config(Shop::class);
        $discount = (float) ($order['coupon_discount_amount'] ?? 0);
        $total    = (float) $order['total_amount'];
        $percent  = $total > 0 ? round($discount / $total * 100, 2) : 0;

        try {
            $this->db->transException(true)->transStart();

            $this->table(self::TABLE)->insert($order + [
                'invoice_number'      => 'TMP-' . bin2hex(random_bytes(8)),
                'order_status'        => 'Pending',
                'payment_status'      => 'Unpaid',
                'order_received_date' => date('Y-m-d H:i:s'),
            ]);
            $order_id = (int) $this->db->insertID();
            $invoice  = $shop->invoicePrefix . ($shop->invoiceOffset + $order_id);
            $this->table(self::TABLE)->where(self::PRIMARY_KEY, $order_id)->update(['invoice_number' => $invoice]);

            foreach (array_values($lines) as $n => $line) {
                if (! $this->products->take_stock((int) $line['stock_id'], (int) $line['quantity'])) {
                    $this->db->transRollback();

                    return 'Sorry, "' . $line['product_name'] . '" no longer has ' . (int) $line['quantity'] . ' in stock. Please update your cart.';
                }

                $line_total = (float) $line['product_price'] * (int) $line['quantity'];
                $this->table(self::ITEMS)->insert([
                    'orders_id'                  => $order_id,
                    'sub_order_id'               => $invoice . '-' . ($n + 1),
                    'coupon_discount_percentage' => $percent,
                    'coupon_discount_amounts'    => round($line_total * $percent / 100, 2),
                    'product_order_status'       => 'Pending',
                ] + array_intersect_key($line, array_flip([
                    'products_id', 'stock_id', 'product_name', 'product_code', 'product_brand', 'product_color', 'product_color_id',
                    'product_color_code', 'product_size', 'product_size_id', 'product_image', 'product_price', 'quantity',
                ])));
            }

            // A coupon given to this member (wl_coupon_customers) is used up as in the reference.
            if (! empty($order['discount_coupon_id']) && ! empty($order['customers_id'])) {
                $this->db->query(
                    "UPDATE wl_coupon_customers SET used_count = used_count + 1, status = '1' WHERE coupon_id = ? AND customer_id = ? AND status = '0'",
                    [(int) $order['discount_coupon_id'], (int) $order['customers_id']],
                );
            }

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'OrderModel::place_order: ' . $e->getMessage());

            return 'Your order could not be placed right now. Please try again shortly.';
        } finally {
            $this->db->transException(false);
        }

        foreach (array_unique(array_column($lines, 'products_id')) as $product_id) {
            $this->products->refresh_summary((int) $product_id);
        }

        return ['order_id' => $order_id, 'invoice_number' => $invoice];
    }

    /**
     * How many orders (not cancelled or deleted) used a coupon, by this
     * customer account or email (coupon usage limits).
     *
     * @return false|int
     */
    public function coupon_uses(int $coupon_id, int $customer_id, string $email)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $db = $this->table(self::TABLE)->select('COUNT(*) AS n', false)
            ->where('discount_coupon_id', $coupon_id)->whereNotIn('order_status', ['Canceled', 'Rejected', 'Deleted'])->groupStart();
        $customer_id > 0 && $db->where('customers_id', $customer_id);
        $email !== '' && $db->orWhere('email', strtolower($email));
        $customer_id <= 0 && $email === '' && $db->where('1 = 0', null, false);
        $row = $this->single_row($db->groupEnd()->get());

        return $row === false ? false : (int) $row['n'];
    }

    /* -----------------------------------------------------------------
     * Reading orders
     * ----------------------------------------------------------------- */

    /**
     * Admin list, newest first; the total is left in $this->total_rec_found.
     *
     * @param array $filters keyword (invoice, name, email, mobile), order_status, payment_status,
     *                       from_date / to_date (Y-m-d), customer_id
     *
     * @return array|false
     */
    public function get_orders(int $offset, int $per_page, array $filters = [])
    {
        return $this->paged_rows(self::TABLE, 'order_id DESC', $offset, $per_page, static function ($db) use ($filters) {
            $db->where('order_status <>', 'Deleted');
            if (($filters['keyword'] ?? '') !== '') {
                $db->groupStart()->like('invoice_number', $filters['keyword'])->orLike('email', $filters['keyword'])->orLike('mobile_number', $filters['keyword'])
                    ->orLike("CONCAT(first_name, ' ', last_name)", $filters['keyword'], 'both', null, true)->groupEnd();
            }
            ($filters['order_status'] ?? '') !== '' && $db->where('order_status', $filters['order_status']);
            ($filters['payment_status'] ?? '') !== '' && $db->where('payment_status', $filters['payment_status']);
            ($filters['from_date'] ?? '') !== '' && $db->where('order_received_date >=', $filters['from_date'] . ' 00:00:00');
            ($filters['to_date'] ?? '') !== '' && $db->where('order_received_date <=', $filters['to_date'] . ' 23:59:59');
            ! empty($filters['customer_id']) && $db->where('customers_id', (int) $filters['customer_id']);
        });
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_order_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id, 'order_status <>' => 'Deleted']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_orders_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('order_status <>', 'Deleted')->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * An order by its invoice number (any customer: guests' confirmation pages check ownership themselves).
     *
     * @return array|false|null
     */
    public function get_order_by_invoice(string $invoice)
    {
        return $this->find(self::TABLE, ['invoice_number' => $invoice, 'order_status <>' => 'Deleted']);
    }

    /**
     * Lines of an order in the order they were placed.
     *
     * @return list<array>|false
     */
    public function get_items(int $order_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::ITEMS)->where('orders_id', $order_id)->orderBy('orders_products_id')->get());
    }

    /**
     * One line with its order's customer, invoice and statuses.
     *
     * @return array|false|null
     */
    public function get_item(int $item_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table(self::ITEMS . ' i')
            ->select('i.*, o.customers_id, o.invoice_number, o.order_status, o.payment_status, o.email')
            ->join(self::TABLE . ' o', 'o.order_id = i.orders_id')
            ->where(['i.orders_products_id' => $item_id, 'o.order_status <>' => 'Deleted'])->limit(1)->get());
    }

    /**
     * A customer's orders, newest first, each with 'products' (names × quantities)
     * and 'payable'; the total is left in $this->total_rec_found.
     *
     * @param string $status '' | an order_status
     *
     * @return list<array>|false
     */
    public function get_customer_orders(int $customer_id, int $offset, int $limit, string $keyword = '', string $status = '')
    {
        $rows = $this->paged_rows(self::TABLE, 'order_id DESC', $offset, $limit, static function ($db) use ($customer_id, $keyword, $status) {
            $db->where(['customers_id' => $customer_id, 'order_status <>' => 'Deleted']);
            $keyword !== '' && $db->like('invoice_number', $keyword);
            $status !== '' && $db->where('order_status', $status);
        });
        if (! $rows) {
            return $rows;
        }

        $items = $this->all_rows($this->table(self::ITEMS)->select('orders_products_id, orders_id, product_name, product_code, product_color, product_size, product_image, product_price, quantity, product_order_status, is_cancel, is_return, refund_status')
            ->whereIn('orders_id', array_column($rows, 'order_id'))->orderBy('orders_products_id')->get());
        if ($items === false) {
            return false;
        }

        $names = $lines = [];

        foreach ($items as $item) {
            $names[$item['orders_id']][] = $item['product_name'] . ((int) $item['quantity'] > 1 ? ' × ' . (int) $item['quantity'] : '');
            $lines[$item['orders_id']][] = $item;
        }

        foreach ($rows as &$row) {
            $row['products'] = implode(', ', $names[$row['order_id']] ?? []);
            $row['items']    = $lines[$row['order_id']] ?? [];
            $row['payable']  = self::payable($row);
        }

        return $rows;
    }

    /**
     * An order for Track Order: its invoice number and the email it was placed with.
     *
     * @return array|false|null
     */
    public function get_order_for_tracking(string $invoice, string $email)
    {
        return $this->find(self::TABLE, ['invoice_number' => $invoice, 'email' => strtolower($email), 'order_status <>' => 'Deleted']);
    }

    /**
     * One of the customer's own orders by invoice number.
     *
     * @return array|false|null
     */
    public function get_customer_order(int $customer_id, string $invoice)
    {
        return $this->find(self::TABLE, ['customers_id' => $customer_id, 'invoice_number' => $invoice, 'order_status <>' => 'Deleted']);
    }

    /**
     * The order reference used in customer-facing URLs instead of the invoice
     * number (?order=<reference>): md5 of the invoice number. It only hides the
     * number: every lookup still checks that the order belongs to the visitor.
     */
    public static function reference(string $invoice): string
    {
        return md5($invoice);
    }

    /**
     * Whether $value has the form of a reference() (32 lowercase hex characters).
     */
    public static function is_reference(string $value): bool
    {
        return preg_match('/^[a-f0-9]{32}$/D', $value) === 1;
    }

    /**
     * One of the customer's own orders by its reference().
     *
     * @return array|false|null
     */
    public function get_customer_order_by_reference(int $customer_id, string $reference)
    {
        if (! self::is_reference($reference)) {
            return null;
        }

        return $this->find(self::TABLE, ['customers_id' => $customer_id, 'MD5(invoice_number)' => $reference, 'order_status <>' => 'Deleted']);
    }

    /**
     * @return false|int
     */
    public function count_customer_orders(int $customer_id)
    {
        return $this->findCount(self::TABLE, ['customers_id' => $customer_id, 'order_status <>' => 'Deleted']);
    }

    /**
     * Dashboard figures: orders per status, unpaid orders and the takings of paid orders.
     *
     * @return array|false
     */
    public function stats()
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::TABLE)->select('order_status, payment_status, COUNT(*) AS n, SUM(IF(grand_total > 0, grand_total, total_amount - coupon_discount_amount + shipping_amount)) AS amount', false)
            ->where('order_status <>', 'Deleted')->groupBy('order_status, payment_status')->get());
        if ($rows === false) {
            return false;
        }

        $stats = ['total' => 0, 'unpaid' => 0, 'paid_amount' => 0.0, 'by_status' => []];

        foreach ($rows as $row) {
            $stats['total'] += (int) $row['n'];
            $stats['by_status'][$row['order_status']] = ($stats['by_status'][$row['order_status']] ?? 0) + (int) $row['n'];
            if ($row['payment_status'] === 'Paid') {
                $stats['paid_amount'] += (float) $row['amount'];
            } elseif (! in_array($row['order_status'], ['Canceled', 'Rejected'], true)) {
                $stats['unpaid'] += (int) $row['n'];
            }
        }

        return $stats;
    }

    /* -----------------------------------------------------------------
     * Changing orders (sitepanel and customers)
     * ----------------------------------------------------------------- */

    /**
     * Set an order's status; its lines follow (except lines already cancelled,
     * rejected or returned). Cancelling or rejecting gives the stock back.
     */
    public function set_order_status(int $order_id, string $status): bool
    {
        if (! isset(self::STATUS_FLOW[$status]) || ! $this->db_ready()) {
            return false;
        }

        $data = ['order_status' => $status];
        $status === 'Delivered' && $data['order_delivery_date'] = date('Y-m-d H:i:s');

        try {
            $this->db->transException(true)->transStart();
            $this->table(self::TABLE)->where(self::PRIMARY_KEY, $order_id)->update($data);
            $this->table(self::ITEMS)->where('orders_id', $order_id)->whereNotIn('product_order_status', self::LINE_ENDED)
                ->update(['product_order_status' => self::STATUS_FLOW[$status]]);
            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'OrderModel::set_order_status(' . $order_id . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }

        if (in_array($status, ['Canceled', 'Rejected'], true)) {
            return $this->restock_order($order_id);
        }

        return true;
    }

    /**
     * @param 'Paid'|'Unpaid' $status
     */
    public function set_payment_status(int $order_id, string $status): bool
    {
        return in_array($status, ['Paid', 'Unpaid'], true)
            && $this->safe_update(self::TABLE, ['payment_status' => $status, 'payment_date' => $status === 'Paid' ? date('Y-m-d H:i:s') : null], [self::PRIMARY_KEY => $order_id]);
    }

    /**
     * Courier details (the reference's courierdetails): saved, and the order is Dispatched.
     */
    public function save_courier(int $order_id, string $company, string $number, string $url, bool $dispatch = true): bool
    {
        return $this->safe_update(self::TABLE, ['courier_company_name' => $company, 'bill_number' => $number, 'bill_url' => $url], [self::PRIMARY_KEY => $order_id])
            && (! $dispatch || $this->set_order_status($order_id, 'Dispatched'));
    }

    public function save_admin_comment(int $order_id, ?string $comment): bool
    {
        return $this->safe_update(self::TABLE, ['admin_comment' => $comment], [self::PRIMARY_KEY => $order_id]);
    }

    /**
     * A customer asks to cancel (or return) a line: is_cancel / is_return = '1'.
     *
     * @param 'cancel'|'return' $type
     */
    public function request_item(int $item_id, string $type, string $reason, string $comment): bool
    {
        return $this->safe_update(self::ITEMS, [
            $type === 'cancel' ? 'is_cancel' : 'is_return' => '1',
            'reason'         => $reason,
            'reason_comment' => $comment !== '' ? $comment : null,
            'request_date'   => date('Y-m-d H:i:s'),
        ], ['orders_products_id' => $item_id]);
    }

    /**
     * Sitepanel answer to a line's cancel / return request. Accepting
     * cancels (or returns) the line and gives its stock back.
     *
     * @param 'cancel'|'return' $type
     */
    public function answer_item_request(array $item, string $type, bool $accept): bool
    {
        $flag = $type === 'cancel' ? 'is_cancel' : 'is_return';
        $data = [$flag => $accept ? '2' : '3'];
        $accept && $data['product_order_status'] = $type === 'cancel' ? 'Cancelled' : 'Returned';

        if (! $this->safe_update(self::ITEMS, $data, ['orders_products_id' => (int) $item['orders_products_id']])) {
            return false;
        }

        return ! $accept || $this->restock_item((int) $item['orders_products_id']);
    }

    /**
     * Set one line's status (sitepanel). Cancelled / Rejected / Returned give its stock back.
     */
    public function set_item_status(int $item_id, string $status): bool
    {
        if (! in_array($status, array_merge(array_values(self::STATUS_FLOW), ['Returned']), true)) {
            return false;
        }
        if (! $this->safe_update(self::ITEMS, ['product_order_status' => $status], ['orders_products_id' => $item_id])) {
            return false;
        }

        return ! in_array($status, self::LINE_ENDED, true) || $this->restock_item($item_id);
    }

    /**
     * Give a line's units back to its stock row, once.
     */
    public function restock_item(int $item_id): bool
    {
        $item = $this->find(self::ITEMS, ['orders_products_id' => $item_id]);
        if (! is_array($item)) {
            return $item !== false;
        }
        if ($item['is_stock_back'] === '1') {
            return true;
        }

        // Claim the line first, so the stock can only ever go back once.
        if ($this->write_failed(fn () => (bool) $this->table(self::ITEMS)->where(['orders_products_id' => $item_id, 'is_stock_back' => '0'])->update(['is_stock_back' => '1']))) {
            return false;
        }
        if ($this->db->affectedRows() !== 1) {
            return true;
        }

        if (! empty($item['stock_id'])) {
            $this->products->return_stock((int) $item['stock_id'], (int) $item['quantity']);
            ! empty($item['products_id']) && $this->products->refresh_summary((int) $item['products_id']);
        }

        return true;
    }

    /**
     * Give back the stock of every line of an order.
     */
    public function restock_order(int $order_id): bool
    {
        $items = $this->get_items($order_id);
        if ($items === false) {
            return false;
        }

        $ok = true;

        foreach ($items as $item) {
            $ok = $this->restock_item((int) $item['orders_products_id']) && $ok;
        }

        return $ok;
    }

    /**
     * Soft delete (order_status 'Deleted', as in the reference). Orders that
     * had not left the store yet (Pending, Ready For Dispatch) give their
     * stock back; shipped ones don't.
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        $orders = $this->get_orders_by_ids($ids);
        if ($orders === false || ! $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['order_status' => 'Deleted'])) {
            return false;
        }

        foreach ($orders as $order) {
            in_array($order['order_status'], ['Pending', 'Ready For Dispatch'], true) && $this->restock_order((int) $order['order_id']);
        }

        return true;
    }

    /**
     * Order lines of every order (Sitepanel > Manage Orders > Manage Orders
     * Products; the reference's get_orders_products), newest first, each with
     * its order's invoice, customer, date and statuses; the total is left in
     * $this->total_rec_found.
     *
     * @param array $filters keyword (product name / code, sub order id, invoice, customer email),
     *                       item_status, request ('cancel' | 'return': open requests), from_date / to_date
     *
     * @return array|false
     */
    public function get_order_items(int $offset, int $per_page, array $filters = [])
    {
        $this->total_rec_found = 0;
        if (! $this->db_ready()) {
            return false;
        }

        $build = function () use ($filters) {
            $db = $this->table(self::ITEMS . ' i')->join(self::TABLE . ' o', 'o.order_id = i.orders_id')->where('o.order_status <>', 'Deleted');
            if (($filters['keyword'] ?? '') !== '') {
                $db->groupStart()->like('i.product_name', $filters['keyword'])->orLike('i.product_code', $filters['keyword'])
                    ->orLike('i.sub_order_id', $filters['keyword'])->orLike('o.invoice_number', $filters['keyword'])->orLike('o.email', $filters['keyword'])->groupEnd();
            }
            ($filters['item_status'] ?? '') !== '' && $db->where('i.product_order_status', $filters['item_status']);
            ($filters['request'] ?? '') === 'cancel' && $db->where('i.is_cancel', '1');
            ($filters['request'] ?? '') === 'return' && $db->where('i.is_return', '1');
            // Manage Returns & Refunds: every line with a cancel / return request or a refund.
            ($filters['request'] ?? '') === 'any' && $db->groupStart()->where('i.is_cancel <>', '0')->orWhere('i.is_return <>', '0')->orWhere('i.refund_status <>', '')->groupEnd();
            in_array($filters['refund'] ?? '', ['Pending', 'Refunded', 'Rejected'], true) && $db->where('i.refund_status', $filters['refund']);
            ($filters['from_date'] ?? '') !== '' && $db->where('o.order_received_date >=', $filters['from_date'] . ' 00:00:00');
            ($filters['to_date'] ?? '') !== '' && $db->where('o.order_received_date <=', $filters['to_date'] . ' 23:59:59');

            return $db;
        };

        $count = $this->single_row($build()->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        return $this->all_rows($build()
            ->select('i.*, o.invoice_number, o.order_received_date, o.order_status, o.payment_status, o.first_name, o.last_name, o.email, o.customer_type')
            ->orderBy('i.orders_products_id', 'DESC')->limit($per_page, $offset)->get());
    }

    /**
     * Record the refund of an order line (Manage Returns & Refunds).
     *
     * @param string $status 'Pending' | 'Refunded' | 'Rejected'
     */
    public function save_refund(int $item_id, string $status, float $amount, string $note): bool
    {
        return $this->safe_update(self::ITEMS, [
            'refund_status' => $status,
            'refund_amount' => round(max(0, $amount), 2),
            'refund_note'   => $note !== '' ? mb_substr($note, 0, 255) : null,
            'refund_date'   => $status === 'Refunded' ? date('Y-m-d H:i:s') : null,
        ], ['orders_products_id' => $item_id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false Lines of orders that are not deleted
     */
    public function get_items_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::ITEMS . ' i')->select('i.*')->join(self::TABLE . ' o', 'o.order_id = i.orders_id')
            ->where('o.order_status <>', 'Deleted')->whereIn('i.orders_products_id', $ids)->get());
    }

    /**
     * Bulk "Paid" / "Unpaid" (AdminController::process_bulk_action() calls this
     * with '1' / '0').
     *
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, [
            'payment_status' => $status === '1' ? 'Paid' : 'Unpaid',
            'payment_date'   => $status === '1' ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
