<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use DateTime;
use Modules\Cart\Libraries\OrderMailer;
use Modules\Cart\Models\OrderModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Sitepanel > Manage Orders > Manage Orders (wl_order, wl_orders_products).
 *
 * The reference's Orders and Order Products screens in one place: the order
 * list (search by invoice, name, email or mobile; order status, payment
 * status, date range and customer filters), and an order page with the
 * customer, delivery address, lines and totals, where an administrator:
 *  - sets the order status (its lines follow; Canceled / Rejected give the
 *    stock back) — the reference's set_as cascade, without its bug of
 *    treating line ids as order ids;
 *  - marks it Paid / Unpaid (the reference's make_paid / make_unpaid, which
 *    took and restored stock in two different tables: stock is now taken
 *    when the order is placed and only given back on cancellation);
 *  - saves courier details (company, tracking number, tracking link) — the
 *    order becomes Dispatched, as in the reference;
 *  - answers a customer's cancel / return request on a line, or sets one
 *    line's status;
 *  - keeps an internal note.
 * The customer is emailed (OrderMailer) on status changes, on payment
 * received and on dispatch, as the reference did. Deleting an order is a
 * soft delete ('Deleted', as in the reference).
 *
 *   index           store/orders/order_list
 *   view/<id>       store/orders/order_view
 *   status/<id>     POST order status            (Edit privilege)
 *   payment/<id>    POST Paid / Unpaid           (Edit)
 *   courier/<id>    POST courier details         (Edit)
 *   comment/<id>    POST internal note           (Edit)
 *   item/<id>       POST a line: accept_cancel, reject_cancel, accept_return, reject_return, status (Edit)
 *   invoice/<id>    printable invoice (store/orders/order_invoice, no sitepanel layout)
 *   delete/<id>     POST soft delete             (Delete)
 *   bulk            POST mark paid / mark unpaid (Edit) or delete (Delete) the selected orders
 */
class Orders extends AdminController
{
    /**
     * Statuses in which saving the courier details also marks the order
     * Dispatched; later statuses (Dispatched, Delivered...) keep theirs.
     */
    public const COURIER_DISPATCHES = ['Pending', 'Ready For Dispatch'];

    public const PAYMENT_STATUSES = ['Paid', 'Unpaid'];

    /**
     * Order statuses the list can be filtered by (STATUS_FLOW plus the customer-driven 'Return').
     */
    public const FILTER_STATUSES = ['Pending', 'Ready For Dispatch', 'Dispatched', 'Delivered', 'Closed', 'Canceled', 'Rejected', 'Return'];

    /**
     * Line statuses an administrator can set directly.
     */
    public const ITEM_STATUSES = ['Pending', 'Ready For Dispatch', 'Dispatched', 'Delivered', 'Closed', 'Cancelled', 'Rejected', 'Returned'];

    /**
     * Email headline per new order status ({invoice} is replaced).
     */
    protected const STATUS_HEADLINES = [
        'Pending'            => 'Your order {invoice} is being processed',
        'Ready For Dispatch' => 'Your order {invoice} is ready for dispatch',
        'Dispatched'         => 'Your order {invoice} has been dispatched',
        'Delivered'          => 'Your order {invoice} has been delivered',
        'Closed'             => 'Your order {invoice} is complete',
        'Canceled'           => 'Your order {invoice} has been cancelled',
        'Rejected'           => 'Your order {invoice} could not be accepted',
    ];

    protected string $base           = 'sitepanel/orders';
    protected string $item           = 'Order';
    protected string $menu_highlight = 'orders';

    protected OrderModel $order_model;

    protected function init(): void
    {
        $this->order_model = new OrderModel();
        helper('shop');
    }

    public function index(): string
    {
        $filters = $this->filters($this->request->getGet());
        $page    = $this->current_page();
        $res     = $this->order_model->get_orders(($page - 1) * self::PAGESIZE, self::PAGESIZE, $filters);

        $customer = null;
        if (! empty($filters['customer_id'])) {
            $customer = (new \Modules\Users\Models\CustomerModel())->get_customer_by_id($filters['customer_id']);
        }

        $data['heading_title'] = 'Manage Orders';
        $data['breadcrumbs']   = ['Orders' => null];
        $data['res']           = $res;
        $data['total']         = $this->order_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['filters']       = $filters;
        $data['customer']      = is_array($customer) ? $customer : null;
        $data['stats']         = $this->order_model->stats();

        return $this->render('store/orders/order_list', $data);
    }

    /**
     * @param int|string $id
     */
    public function view($id = 0): string
    {
        $res   = $this->record_or_back($this->order_model->get_order_by_id($id));
        $items = $this->order_model->get_items((int) $res['order_id']);

        $data['heading_title'] = 'Order ' . $res['invoice_number'];
        $data['breadcrumbs']   = ['Orders' => site_url($this->base), $res['invoice_number'] => null];
        $data['res']           = $res;
        $data['items']         = $items;
        $data['errors']        = (array) $this->session->getFlashdata('order_errors');
        $data['shop']          = config(\Config\Shop::class);

        return $this->render('store/orders/order_view', $data);
    }

    /**
     * POST order_status (a key of OrderModel::STATUS_FLOW), notify=1 to email the customer.
     *
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $order = $this->editable_order($id);
        $status = (string) $this->request->getPost('order_status');

        if (! isset(OrderModel::STATUS_FLOW[$status])) {
            $this->to_order($order, 'Choose a valid order status.');
        }
        if ($status === $order['order_status']) {
            $this->to_order($order, null, 'The order is already ' . $status . '.');
        }
        if (! $this->order_model->set_order_status((int) $order['order_id'], $status)) {
            $this->to_order($order, 'The order status could not be changed. Please try again.');
        }

        $message = 'Order status changed to ' . $status . (in_array($status, ['Canceled', 'Rejected'], true) ? '; the stock of its items was given back.' : '.');
        $this->to_order($order, null, $message . $this->notify($order, self::STATUS_HEADLINES[$status]));
    }

    /**
     * POST payment_status Paid | Unpaid. The customer is emailed when a payment is received.
     *
     * @param int|string $id
     */
    public function payment($id = 0): void
    {
        $order  = $this->editable_order($id);
        $status = (string) $this->request->getPost('payment_status');

        if (! in_array($status, self::PAYMENT_STATUSES, true)) {
            $this->to_order($order, 'Choose Paid or Unpaid.');
        }
        if ($status === $order['payment_status']) {
            $this->to_order($order, null, 'The order is already marked ' . $status . '.');
        }
        if (! $this->order_model->set_payment_status((int) $order['order_id'], $status)) {
            $this->to_order($order, 'The payment status could not be changed. Please try again.');
        }

        $email = $status === 'Paid' ? $this->notify($order, 'We have received your payment for order {invoice}') : '';
        $this->to_order($order, null, 'Order marked ' . $status . '.' . $email);
    }

    /**
     * POST courier_company_name, bill_number (tracking number), bill_url (tracking link):
     * saved, and the order becomes Dispatched (the reference's courierdetails).
     *
     * @param int|string $id
     */
    public function courier($id = 0): void
    {
        $order = $this->editable_order($id);

        $v = new FormValidation($this->request, $this);
        $v->set_rules('courier_company_name', 'Courier company', 'trim|required|max_length[150]');
        $v->set_rules('bill_number', 'Tracking number', 'trim|required|max_length[50]');
        $v->set_rules('bill_url', 'Tracking link', ['trim', 'max_length[455]', ['valid_tracking_url', static fn ($value) => (string) $value === '' || (filter_var((string) $value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', (string) $value))]],
            ['valid_tracking_url' => 'The {field} must be a web address starting with http:// or https://.']);

        if (! $v->run()) {
            $this->session->setFlashdata('order_errors', array_values($v->error_array()));
            redirect_now($this->base . '/view/' . (int) $order['order_id'] . '#courier');
        }

        $post     = fn (string $field) => trim((string) $this->request->getPost($field));
        $dispatch = in_array($order['order_status'], self::COURIER_DISPATCHES, true);
        if (! $this->order_model->save_courier((int) $order['order_id'], $post('courier_company_name'), $post('bill_number'), $post('bill_url'), $dispatch)) {
            $this->to_order($order, 'The courier details could not be saved. Please try again.');
        }

        $dispatch
            ? $this->to_order($order, null, 'Courier details saved and the order marked Dispatched.' . $this->notify($order, self::STATUS_HEADLINES['Dispatched']))
            : $this->to_order($order, null, 'Courier details saved.');
    }

    /**
     * POST admin_comment: internal note, never shown to the customer.
     *
     * @param int|string $id
     */
    public function comment($id = 0): void
    {
        $order = $this->editable_order($id);

        $v = new FormValidation($this->request, $this);
        $v->set_rules('admin_comment', 'Internal note', 'trim|max_length[2000]');
        if (! $v->run()) {
            $this->session->setFlashdata('order_errors', array_values($v->error_array()));
            redirect_now($this->base . '/view/' . (int) $order['order_id'] . '#note');
        }

        $saved = $this->order_model->save_admin_comment((int) $order['order_id'], $this->nullable('admin_comment'));
        $this->to_order($order, $saved ? null : 'The note could not be saved. Please try again.', $saved ? 'Internal note saved.' : null);
    }

    /**
     * POST action for one line: accept_cancel | reject_cancel | accept_return |
     * reject_return (the customer's request), or status with item_status.
     *
     * @param int|string $id orders_products_id
     */
    public function item($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $item = $this->record_or_back($this->order_model->get_item((int) $id));
        $order = ['order_id' => $item['orders_id']];

        $action = (string) $this->request->getPost('action');
        $name   = '"' . $item['product_name'] . '"';

        if (in_array($action, ['accept_cancel', 'reject_cancel', 'accept_return', 'reject_return'], true)) {
            [$answer, $type] = explode('_', $action);
            $flag            = $type === 'cancel' ? 'is_cancel' : 'is_return';
            if ($item[$flag] !== '1') {
                $this->to_order($order, 'There is no open ' . $type . ' request on ' . $name . '.');
            }
            if (! $this->order_model->answer_item_request($item, $type, $answer === 'accept')) {
                $this->to_order($order, 'The request could not be answered. Please try again.');
            }

            $done    = $answer === 'accept' ? ($type === 'cancel' ? 'cancelled' : 'returned') : 'not ' . ($type === 'cancel' ? 'cancelled' : 'returned');
            $message = ucfirst($type) . ' request ' . ($answer === 'accept' ? 'accepted' : 'rejected') . ' for ' . $name . ($answer === 'accept' ? '; its stock was given back.' : '.');
            $this->to_order($order, null, $message . $this->notify($this->order_model->get_order_by_id((int) $item['orders_id']) ?: [], 'An item of your order {invoice} was ' . $done . ': ' . $item['product_name']));
        }

        if ($action === 'status') {
            $status = (string) $this->request->getPost('item_status');
            if (! in_array($status, self::ITEM_STATUSES, true)) {
                $this->to_order($order, 'Choose a valid item status.');
            }
            if ($status === $item['product_order_status']) {
                $this->to_order($order, null, $name . ' is already ' . $status . '.');
            }
            if (! $this->order_model->set_item_status((int) $item['orders_products_id'], $status)) {
                $this->to_order($order, 'The item status could not be changed. Please try again.');
            }
            $this->to_order($order, null, $name . ' is now ' . $status . (in_array($status, OrderModel::LINE_ENDED, true) ? '; its stock was given back.' : '.'));
        }

        $this->to_order($order, 'Choose a valid action.');
    }

    /**
     * Printable invoice (no sitepanel layout).
     *
     * @param int|string $id
     */
    public function invoice($id = 0): string
    {
        $res = $this->record_or_back($this->order_model->get_order_by_id($id));

        return $this->render('store/orders/order_invoice', [
            'res'   => $res,
            'items' => $this->order_model->get_items((int) $res['order_id']),
            'shop'  => config(\Config\Shop::class),
        ]);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);
        $row = $this->record_or_back($this->order_model->get_order_by_id($id));

        if ($this->order_model->bulk_delete([(int) $row['order_id']])) {
            $this->session->setFlashdata('sitepanel_success', 'Order ' . $row['invoice_number'] . ' deleted.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'The order could not be deleted. Please try again.');
        }

        $this->back();
    }

    /**
     * POST bulk_action paid | unpaid (Edit privilege) or delete (Delete privilege).
     */
    public function bulk(): void
    {
        $this->require_post();
        $filters = $this->filters($this->request->getPost());
        $filters['customer'] = $filters['customer_id'];
        unset($filters['customer_id']);
        $query = array_filter($filters, static fn ($value) => $value !== '' && $value !== 0);
        $action = (string) $this->request->getPost('bulk_action');

        if (! in_array($action, ['paid', 'unpaid', 'delete'], true)) {
            $this->session->setFlashdata('sitepanel_error', 'Choose a valid bulk action.');
            $this->back($query);
        }
        $this->require_privilege($action === 'delete' ? AdminAccess::PRIV_DELETE : AdminAccess::PRIV_EDIT);

        $ids = $this->selected_ids();
        if ($ids === false) {
            $this->session->setFlashdata('sitepanel_error', 'Select one or more valid orders on this page.');
            $this->back($query);
        }
        if (! $this->selected_rows_match($this->order_model->get_orders_by_ids($ids), $ids)) {
            $this->back($query);
        }

        $done = $action === 'delete' ? $this->order_model->bulk_delete($ids) : $this->order_model->bulk_update_status($ids, $action === 'paid' ? '1' : '0');
        $count = count($ids);
        $verb  = ['paid' => 'marked Paid', 'unpaid' => 'marked Unpaid', 'delete' => 'deleted'][$action];

        $done
            ? $this->session->setFlashdata('sitepanel_success', $count . ' ' . ($count === 1 ? 'order' : 'orders') . ' ' . $verb . '.')
            : $this->session->setFlashdata('sitepanel_error', 'The selected orders could not be changed. Please try again.');

        $this->back($query);
    }

    /* -----------------------------------------------------------------
     * Helpers
     * ----------------------------------------------------------------- */

    /**
     * The list filters from GET (index) or POST (bulk), validated.
     */
    protected function filters(?array $input): array
    {
        $input = is_array($input) ? $input : [];
        $get   = static fn (string $key) => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
        $date  = static function (string $value): string {
            $date = DateTime::createFromFormat('!Y-m-d', $value);

            return $date && $date->format('Y-m-d') === $value ? $value : '';
        };

        return [
            'keyword'        => mb_substr($get('keyword'), 0, 80),
            'order_status'   => in_array($get('order_status'), self::FILTER_STATUSES, true) ? $get('order_status') : '',
            'payment_status' => in_array($get('payment_status'), self::PAYMENT_STATUSES, true) ? $get('payment_status') : '',
            'from_date'      => $date($get('from_date')),
            'to_date'        => $date($get('to_date')),
            'customer_id'    => max(0, (int) $get('customer')),
        ];
    }

    /**
     * A POSTed change to an order: POST only, Edit privilege, the order must exist.
     *
     * @param int|string $id
     */
    protected function editable_order($id): array
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);

        return $this->record_or_back($this->order_model->get_order_by_id($id));
    }

    /**
     * Back to the order page with a message.
     */
    protected function to_order(array $order, ?string $error, ?string $success = null): never
    {
        $error !== null && $this->session->setFlashdata('sitepanel_error', $error);
        $success !== null && $this->session->setFlashdata('sitepanel_success', $success);
        redirect_now($this->base . '/view/' . (int) $order['order_id']);
    }

    /**
     * Email the customer about a change when the form's "Email the customer"
     * box was ticked (notify=1); a sentence for the flash message.
     *
     * @param array $order The order before the change (reloaded here)
     */
    protected function notify(array $order, string $headline): string
    {
        if (! $order || $this->request->getPost('notify') !== '1') {
            return '';
        }

        $fresh = $this->order_model->get_order_by_id((int) $order['order_id']);
        $items = is_array($fresh) ? $this->order_model->get_items((int) $fresh['order_id']) : false;
        if (! is_array($fresh) || ! is_array($items)) {
            return ' The customer could not be emailed.';
        }

        $sent = (new OrderMailer())->status_changed($fresh, $items, str_replace('{invoice}', $fresh['invoice_number'], $headline));

        return $sent ? ' The customer was emailed.' : ' The customer email could not be sent (check the contact email in Admin Settings and the mail set-up).';
    }
}
