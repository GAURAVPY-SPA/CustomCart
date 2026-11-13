<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Cart\Models\ShippingModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Sitepanel > Manage Orders > Manage Shipping (delivery methods) (wl_shipping).
 *
 * As in the reference's Shipping module: a method has a name and a flat rate;
 * added here, as the design asks for "free on orders of S$150 and above", an
 * optional amount above which the method is free, a display order and one
 * default method (preselected in the cart), and its delivery days (from - to:
 * the expected delivery shown on the order pages). Orders keep the method's
 * name, rate and delivery days, so a method can always be changed or deleted.
 *
 *   index               store/shipping/shipping_list
 *   add, edit/<id>      store/shipping/shipping_form
 *   status/<id>         POST, AdminController::update_status()
 *   make_default/<id>   POST, the method becomes the default (Edit privilege)
 *   delete/<id>         POST, AdminController::delete_record()
 *   bulk                POST, activate / deactivate / delete the selected rows
 */
class Shipping extends AdminController
{
    protected string $base           = 'sitepanel/shipping';
    protected string $item           = 'Delivery method';
    protected string $menu_highlight = 'shipping';

    protected ShippingModel $shipping_model;

    protected function init(): void
    {
        $this->shipping_model = new ShippingModel();
        helper('shop');
    }

    public function index(): string
    {
        $status = (string) $this->request->getGet('status');
        $status = in_array($status, ['1', '0'], true) ? $status : '';
        $page   = $this->current_page();
        $res    = $this->shipping_model->get_shipping_methods(($page - 1) * self::PAGESIZE, self::PAGESIZE, $status);

        $data['heading_title'] = 'Delivery Methods';
        $data['breadcrumbs']   = ['Delivery Methods' => null];
        $data['res']           = $res;
        $data['total']         = $this->shipping_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['status']        = $status;

        return $this->render('store/shipping/shipping_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Delivery method added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Delivery Method';
        $data['breadcrumbs']   = ['Delivery Methods' => site_url($this->base), 'Add Delivery Method' => null];
        $data['res']           = [
            'shipping_type' => '', 'shipment_rate' => '', 'free_above' => '', 'delivery_min_days' => '3', 'delivery_max_days' => '5',
            'sort_order' => (string) $this->shipping_model->next_sort_order(), 'status' => '1',
        ];

        return $this->render('store/shipping/shipping_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->shipping_model->get_shipping_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Delivery method updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Delivery Method';
        $data['breadcrumbs']   = ['Delivery Methods' => site_url($this->base), 'Edit Delivery Method' => null];
        $data['res']           = $res;

        return $this->render('store/shipping/shipping_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(ShippingModel::TABLE, ShippingModel::PRIMARY_KEY, $id);
    }

    /**
     * POST: make the method the default one (it is activated too).
     *
     * @param int|string $id
     */
    public function make_default($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $row = $this->record_or_back($this->shipping_model->get_shipping_by_id($id));

        if ($this->shipping_model->set_default((int) $row['shipping_id'])
            && $this->shipping_model->safe_update(ShippingModel::TABLE, ['status' => '1'], [ShippingModel::PRIMARY_KEY => (int) $row['shipping_id']])) {
            $this->session->setFlashdata('sitepanel_success', '"' . $row['shipping_type'] . '" is now the default delivery method.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'The default delivery method could not be changed. Please try again.');
        }

        $this->back();
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record(ShippingModel::TABLE, ShippingModel::PRIMARY_KEY, $id);
    }

    public function bulk(): void
    {
        $status = (string) $this->request->getPost('status_filter');
        $this->process_bulk_action($this->shipping_model, 'get_shipping_by_ids', 'Delivery method', array_filter(['status' => in_array($status, ['1', '0'], true) ? $status : '']));
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $amount   = ['valid_amount', static fn ($value) => (string) $value === '' || preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/D', (string) $value) === 1];
        $messages = ['valid_amount' => 'The {field} must be an amount of 0 or more with at most 2 decimals, e.g. 6.00.'];

        $v = new FormValidation($this->request, $this);
        $v->set_rules('shipping_type', 'Delivery method name', 'trim|required|max_length[100]');
        $v->set_rules('shipment_rate', 'Rate', ['trim', 'required', $amount], $messages);
        $v->set_rules('free_above', 'Free delivery from', ['trim', $amount], $messages);
        $v->set_rules('delivery_min_days', 'Delivery days (from)', 'trim|required|is_natural|less_than_equal_to[365]');
        $v->set_rules('delivery_max_days', 'Delivery days (to)', ['trim', 'required', 'is_natural', 'less_than_equal_to[365]',
            ['not_before_min', fn ($value) => ! ctype_digit((string) $this->request->getPost('delivery_min_days')) || (int) $value >= (int) $this->request->getPost('delivery_min_days')]],
            ['not_before_min' => 'The {field} must be the same as or more than Delivery days (from).']);
        $v->set_rules('sort_order', 'Display order', 'trim|is_natural|less_than_equal_to[9999]');
        $v->set_rules('status', 'Status', 'required|in_list[1,0]');

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $post = fn (string $field) => trim((string) $this->request->getPost($field));
        $data = [
            'shipping_type' => $post('shipping_type'),
            'shipment_rate' => $post('shipment_rate'),
            'free_above'    => $post('free_above') !== '' ? $post('free_above') : '0.00',
            'delivery_min_days' => (int) $post('delivery_min_days'),
            'delivery_max_days' => (int) $post('delivery_max_days'),
            'sort_order'    => (int) $post('sort_order'),
            'status'        => $post('status'),
        ];

        if ($row) {
            $saved = $this->shipping_model->safe_update(ShippingModel::TABLE, $data, [ShippingModel::PRIMARY_KEY => (int) $row['shipping_id']]);
        } else {
            $saved = $this->shipping_model->safe_insert(ShippingModel::TABLE, $data + ['is_default' => '0', 'added_date' => date('Y-m-d H:i:s')]) !== false;
        }

        return $saved ? [] : ['Delivery method could not be saved. Please try again.'];
    }
}
