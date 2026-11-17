<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Cart\Models\OrderModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Sitepanel > Manage Orders > Manage Returns & Refunds: every order line with
 * a customer's cancel / return request (My Account: item-request popup) or a
 * refund. Requests are accepted or rejected on the order's page (Manage
 * Orders: Orders::item(), which also gives the stock back); here the refund
 * of a line is recorded: its status (Pending, Refunded, Rejected), amount and
 * a note, shown to the customer on the order page.
 *
 *   index          store/returns/returns_list (keyword, request, refund filters)
 *   view/<id>      store/returns/return_view (request details, refund form)
 *   refund/<id>    POST refund_status, refund_amount, refund_note (Edit privilege)
 */
class Returns extends AdminController
{
    public const REFUND_STATUSES = ['Pending', 'Refunded', 'Rejected'];

    protected string $base           = 'sitepanel/returns';
    protected string $item           = 'Order item';
    protected string $menu_highlight = 'returns';

    protected OrderModel $order_model;

    protected function init(): void
    {
        $this->order_model = new OrderModel();
        helper('shop');
    }

    public function index(): string
    {
        $get     = $this->request->getGet();
        $refund  = in_array($get['refund'] ?? '', self::REFUND_STATUSES, true) ? (string) $get['refund'] : '';
        $request = in_array($get['request'] ?? '', ['cancel', 'return'], true) ? (string) $get['request'] : 'any';
        $filters = ['keyword' => $this->keyword_param((string) ($get['keyword'] ?? '')), 'request' => $request, 'refund' => $refund];
        $page    = $this->current_page();
        $res     = $this->order_model->get_order_items(($page - 1) * self::PAGESIZE, self::PAGESIZE, $filters);

        $data['heading_title'] = 'Manage Returns & Refunds';
        $data['breadcrumbs']   = ['Orders' => site_url('sitepanel/orders'), 'Returns & Refunds' => null];
        $data['res']           = $res;
        $data['total']         = $this->order_model->total_rec_found;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['filters']       = $filters;

        return $this->render('store/returns/returns_list', $data);
    }

    /**
     * @param int|string $id orders_products_id
     */
    public function view($id = 0): string
    {
        $item  = $this->record_or_back($this->order_model->get_item((int) $id));
        $order = $this->order_model->get_order_by_id((int) $item['orders_id']);

        $data['heading_title'] = 'Return / Refund: ' . $item['product_name'];
        $data['breadcrumbs']   = ['Orders' => site_url('sitepanel/orders'), 'Returns & Refunds' => site_url($this->base), $item['product_name'] => null];
        $data['row']           = $item;
        $data['order']         = is_array($order) ? $order : null;
        $data['statuses']      = self::REFUND_STATUSES;

        return $this->render('store/returns/return_view', $data);
    }

    /**
     * @param int|string $id orders_products_id
     */
    public function refund($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $item = $this->record_or_back($this->order_model->get_item((int) $id));
        $max  = round((float) $item['product_price'] * (int) $item['quantity'], 2);

        $v = new FormValidation($this->request, $this);
        $v->set_rules('refund_status', 'Refund status', 'required|in_list[' . implode(',', self::REFUND_STATUSES) . ']');
        $v->set_rules('refund_amount', 'Refund amount', ['required', 'regex_match[/^\d{1,8}(\.\d{1,2})?$/]', ['max_amount', static fn ($value) => (float) $value <= $max]],
            ['max_amount' => 'The refund amount may not be more than the item total (' . number_format($max, 2) . ').']);
        $v->set_rules('refund_note', 'Note', 'max_length[255]');

        if (! $v->run()) {
            $this->session->setFlashdata('sitepanel_error', implode(' ', $v->error_array()));
        } elseif ($this->order_model->save_refund((int) $item['orders_products_id'], (string) $this->request->getPost('refund_status'),
            (float) $this->request->getPost('refund_amount'), trim((string) $this->request->getPost('refund_note')))) {
            $this->session->setFlashdata('sitepanel_success', 'The refund of "' . $item['product_name'] . '" was saved.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'The refund could not be saved. Please try again.');
        }
        redirect_now($this->base . '/view/' . (int) $item['orders_products_id']);
    }
}
