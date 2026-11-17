<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use Modules\Cart\Models\OrderModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Sitepanel > Manage Orders > Manage Orders Products (wl_orders_products).
 *
 * As in the reference's orders/products page: every order line of every
 * order, with search (product name / code, sub order id, invoice, customer
 * email), line status, open cancel / return requests and order date filters,
 * and a bulk status change for the selected lines. The reference's bulk
 * change also wrote the line ids into wl_order as order ids (and emailed
 * those customers); here only the selected lines change, through
 * OrderModel::set_item_status(), which gives the stock of a cancelled,
 * rejected or returned line back once. Cancel / return requests are answered
 * on the order's page (Manage Orders), where the reason is shown.
 *
 *   index   store/orders/order_products_list
 *   bulk    POST, set item_status on the selected lines (Edit privilege)
 */
class OrderProducts extends AdminController
{
    protected string $base           = 'sitepanel/order_products';
    protected string $item           = 'Order item';
    protected string $menu_highlight = 'order_products';

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
        $res     = $this->order_model->get_order_items(($page - 1) * self::PAGESIZE, self::PAGESIZE, $filters);

        $data['heading_title'] = 'Manage Orders Products';
        $data['breadcrumbs']   = ['Orders' => site_url('sitepanel/orders'), 'Order Items' => null];
        $data['res']           = $res;
        $data['total']         = $this->order_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['filters']       = $filters;
        $data['statuses']      = Orders::ITEM_STATUSES;
        // Opening an order needs the Manage Orders section.
        $data['can_orders']    = $this->can_open_section('orders');

        return $this->render('store/orders/order_products_list', $data);
    }

    /**
     * POST bulk_action=status, item_status, ids[]: set the status of the selected lines.
     */
    public function bulk(): void
    {
        $this->require_post();
        $query = array_filter($this->filters($this->request->getPost()), 'strlen');
        $this->require_privilege(AdminAccess::PRIV_EDIT);

        $status = (string) $this->request->getPost('item_status');
        if ($this->request->getPost('bulk_action') !== 'status' || ! in_array($status, Orders::ITEM_STATUSES, true)) {
            $this->session->setFlashdata('sitepanel_error', 'Choose a status for the selected items.');
            $this->back($query);
        }

        $ids = $this->selected_ids();
        if ($ids === false) {
            $this->session->setFlashdata('sitepanel_error', 'Select one or more valid items on this page.');
            $this->back($query);
        }
        $rows = $this->order_model->get_items_by_ids($ids);
        if (! $this->selected_rows_match($rows, $ids)) {
            $this->back($query);
        }

        $changed = 0;
        $failed  = 0;

        foreach ($rows as $row) {
            if ($row['product_order_status'] === $status) {
                continue;
            }
            $this->order_model->set_item_status((int) $row['orders_products_id'], $status) ? $changed++ : $failed++;
        }

        if ($failed) {
            $this->session->setFlashdata('sitepanel_error', $failed . ' item' . ($failed === 1 ? '' : 's') . ' could not be changed. Please try again.');
        }
        $this->session->setFlashdata('sitepanel_success', $changed . ' item' . ($changed === 1 ? ' is' : 's are') . ' now ' . $status
            . (in_array($status, OrderModel::LINE_ENDED, true) && $changed ? '; their stock was given back.' : '.'));
        $this->back($query);
    }

    /**
     * Valid list filters from GET or POST input.
     */
    protected function filters(?array $input): array
    {
        $input  = $input ?? [];
        $date   = static function ($value): string {
            $value = trim((string) $value);

            return sp_valid_date($value) ? $value : '';
        };
        $status  = (string) ($input['item_status'] ?? '');
        $request = (string) ($input['request'] ?? '');

        return [
            'keyword'     => $this->keyword_param((string) ($input['keyword'] ?? '')),
            'item_status' => in_array($status, Orders::ITEM_STATUSES, true) ? $status : '',
            'request'     => in_array($request, ['cancel', 'return'], true) ? $request : '',
            'from_date'   => $date($input['from_date'] ?? ''),
            'to_date'     => $date($input['to_date'] ?? ''),
        ];
    }
}
