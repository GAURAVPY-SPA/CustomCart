<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use Modules\Cart\Models\OrderModel;
use Modules\Members\Models\AddressModel;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Users\Models\CustomerModel;

/**
 * Sitepanel > Members Management > Manage Members (wl_customers).
 *
 * As in the reference's Members module: the store's customer accounts with
 * a keyword search (email, name, mobile) and a status filter, a details page
 * (profile, delivery address, recent orders) and activate / deactivate /
 * delete. Guest checkouts (status '3') are listed under their own filter;
 * they have no password, so they can't be activated (they become members by
 * registering with the same email). A deactivated member is signed out at
 * once (CustomerAuth checks the status on every request).
 *
 * Unlike the reference, which hard-deleted members with their order history
 * left pointing at nobody, a customer who has orders can't be deleted: they
 * are deactivated instead.
 *
 *   index         store/members/member_list
 *   view/<id>     store/members/member_view
 *   status/<id>   POST activate / deactivate
 *   delete/<id>   POST, refused when the customer has orders
 *   bulk          POST, activate / deactivate / delete the selected rows
 */
class Members extends AdminController
{
    public const STATUSES = ['1' => 'Active', '0' => 'Inactive', '3' => 'Guests'];

    protected string $base           = 'sitepanel/members';
    protected string $item           = 'Customer';
    protected string $menu_highlight = 'members';

    protected CustomerModel $customer_model;

    protected function init(): void
    {
        $this->customer_model = new CustomerModel();
        helper('shop');
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->customer_model->get_customers(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage Customers';
        $data['breadcrumbs']   = ['Customers' => null];
        $data['res']           = $res;
        $data['total']         = $this->customer_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;
        $data['can_orders']    = $this->can_open('orders');

        return $this->render('store/members/member_list', $data);
    }

    /**
     * @param int|string $id
     */
    public function view($id = 0): string
    {
        $res    = $this->record_or_back($this->customer_model->get_customer_by_id($id));
        $orders = new OrderModel();

        $data['heading_title'] = 'Customer Details';
        $data['breadcrumbs']   = ['Customers' => site_url($this->base), trim($res['first_name'] . ' ' . $res['last_name']) ?: $res['user_name'] => null];
        $data['res']           = $res;
        $data['address']       = (new AddressModel())->get_default_address((int) $res['customers_id']);
        $data['orders']        = $orders->get_orders(0, 10, ['customer_id' => (int) $res['customers_id']]);
        $data['order_total']   = $orders->total_rec_found;
        $data['can_orders']    = $this->can_open('orders');

        return $this->render('store/members/member_view', $data);
    }

    /**
     * POST status=1 activates, status=0 deactivates. Guests can't be activated.
     *
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->require_post();
        $row = $this->record_or_back($this->customer_model->get_customer_by_id($id));
        if ($row['status'] === CustomerModel::STATUS_GUEST) {
            $this->session->setFlashdata('sitepanel_error', 'Guest checkouts have no password, so they can\'t be activated. The customer can register with the same email.');
            $this->back(['status' => '3']);
        }

        $this->update_status(CustomerModel::TABLE, CustomerModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);
        $row = $this->record_or_back($this->customer_model->get_customer_by_id($id));

        $error = $this->orders_block([(int) $row['customers_id']]);
        if ($error !== null) {
            $this->session->setFlashdata('sitepanel_error', $error);
            $this->back();
        }

        $this->delete_record(CustomerModel::TABLE, CustomerModel::PRIMARY_KEY, $id);
    }

    public function bulk(): void
    {
        $keyword = mb_substr(trim((string) $this->request->getPost('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getPost('status_filter'));
        $query   = array_filter(['keyword' => $keyword, 'status' => $status], 'strlen');
        $action  = (string) $this->request->getPost('bulk_action');

        if ($action === 'delete') {
            $this->bulk_delete_checked($this->customer_model, 'get_customers_by_ids', 'Customer', $query, function (array $rows) {
                return $this->orders_block(array_map('intval', array_column($rows, 'customers_id')));
            });
        }

        if ($action === 'activate') {
            $ids  = $this->selected_ids();
            $rows = $ids ? $this->customer_model->get_customers_by_ids($ids) : false;
            if (is_array($rows) && in_array(CustomerModel::STATUS_GUEST, array_column($rows, 'status'), true)) {
                $this->session->setFlashdata('sitepanel_error', 'Guest checkouts can\'t be activated. Untick them and try again.');
                $this->back($query);
            }
        }

        $this->process_bulk_action($this->customer_model, 'get_customers_by_ids', 'Customer', $query);
    }

    /**
     * Why these customers can't be deleted (they have orders), or NULL.
     *
     * @param list<int> $ids
     */
    protected function orders_block(array $ids): ?string
    {
        $with_orders = $this->customer_model->ids_with_orders($ids);
        if ($with_orders === false) {
            return 'Orders could not be checked, so nothing was deleted. Please try again.';
        }

        if ($with_orders) {
            return count($ids) === 1
                ? 'This customer has orders, so the account can\'t be deleted. Deactivate it instead.'
                : count($with_orders) . ' of the selected customers have orders, so nothing was deleted. Deactivate them instead.';
        }

        return null;
    }

    /**
     * Whether the admin may open another sitepanel section (links to orders).
     */
    protected function can_open(string $section): bool
    {
        $codes = $this->admin_access->permissions_for($this->admin, $section);

        return is_array($codes) && in_array(AdminAccess::PRIV_VIEW, $codes, true);
    }

    protected function status_param(string $value): string
    {
        return isset(self::STATUSES[$value]) ? $value : '';
    }
}
