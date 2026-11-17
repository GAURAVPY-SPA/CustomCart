<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\CouponModel;

/**
 * Sitepanel > Other Management > Manage Discount Coupons (wl_coupons).
 *
 * As in the reference module: list with coupon code search (here also the
 * title, and a status filter), add / edit, activate, deactivate and delete,
 * one row or the selected rows. A new coupon gets a random 8-character code
 * (unique here; the reference didn't check). Rules, as in the reference:
 *  - Coupon title required, at most 150 characters; type Percentage or Amount;
 *  - Discount: a positive amount; a percentage must be less than 99;
 *  - Percentage coupons need a Maximum coupon discount (> 0);
 *  - Minimum order amount optional; when given, an amount coupon's discount
 *    and a percentage coupon's maximum discount may not exceed it;
 *  - Start date not before today, end date not before the start date. On
 *    edit, an unchanged start date is accepted even if it has passed (the
 *    reference forced already-started coupons to move their start date).
 *  - Usage: single use, or multiple uses with the number of uses per customer.
 * Coupons are for all customers (the reference's "Members only" option was
 * disabled, and this site has no member accounts).
 *
 *   index          discount_coupons/view_coupon_list
 *   add            discount_coupons/view_coupon_form
 *   edit/<id>      discount_coupons/view_coupon_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 *   bulk           POST, activate / deactivate / delete the selected rows
 */
class Coupons extends AdminController
{
    public const TYPES  = ['p' => 'Percentage', 'a' => 'Amount'];
    public const USAGES = ['single' => 'Single use', 'multiple' => 'Multiple use'];

    protected string $base           = 'sitepanel/coupons';
    protected string $item           = 'Coupon';
    protected string $menu_highlight = 'coupons';

    protected CouponModel $coupon_model;

    protected function init(): void
    {
        $this->coupon_model = new CouponModel();
    }

    public function index(): string
    {
        $keyword = $this->keyword_param();
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->coupon_model->get_coupons(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage Discount Coupons';
        $data['breadcrumbs']   = ['Discount Coupons' => null];
        $data['res']           = $res;
        $data['total']         = $this->coupon_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;
        $data['today']         = date('Y-m-d');

        return $this->render('discount_coupons/view_coupon_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Coupon added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Discount Coupon';
        $data['breadcrumbs']   = ['Discount Coupons' => site_url($this->base), 'Add Coupon' => null];
        $data['res']           = [
            'coupon_title' => '', 'coupon_type' => 'p', 'coupon_discount' => '', 'maximum_coupon_discount' => '',
            'minimum_order_amount' => '', 'coupon_usage' => 'single', 'usage_count' => '1', 'start_date' => '', 'end_date' => '',
        ];

        return $this->render('discount_coupons/view_coupon_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->coupon_model->get_coupon_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Coupon updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Discount Coupon';
        $data['breadcrumbs']   = ['Discount Coupons' => site_url($this->base), 'Edit Coupon' => null];
        $data['res']           = $res;

        return $this->render('discount_coupons/view_coupon_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status('wl_coupons', 'coupon_id', $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record('wl_coupons', 'coupon_id', $id);
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->coupon_model, 'get_coupons_by_ids', 'Coupon', $this->list_query());
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $post    = fn (string $field) => trim((string) $this->request->getPost($field));
        $type    = $post('coupon_type');
        $usage   = $post('coupon_usage');
        $minimum = $post('minimum_order_amount');
        $today   = date('Y-m-d');
        $amount  = ['valid_amount', static fn ($value) => (string) $value === '' || preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/D', (string) $value) === 1];
        $date    = ['valid_date', static fn ($value) => (string) $value === '' || sp_valid_date((string) $value)];
        $messages = ['valid_amount' => 'The {field} must be a positive number with at most 2 decimals.', 'valid_date' => 'The {field} must be a date (YYYY-MM-DD).'];

        $v = new FormValidation($this->request, $this);
        $v->set_rules('coupon_title', 'Coupon title', 'trim|required|max_length[150]');
        $v->set_rules('coupon_type', 'Discount coupon type', ['required', 'in_list[p,a]'], ['in_list' => 'Choose a valid {field}.']);

        // A minimum order amount, when given, caps an amount coupon's discount
        // and a percentage coupon's maximum discount.
        $within_minimum = static fn ($value) => ! self::is_amount($minimum) || (float) $minimum <= 0 || (float) $value <= (float) $minimum;

        $discount_rules = ['trim', 'required', $amount, 'greater_than[0]'];
        if ($type === 'p') {
            $discount_rules[] = 'less_than[99]';
            $v->set_rules('maximum_coupon_discount', 'Maximum coupon discount', ['trim', 'required', $amount, 'greater_than[0]', ['max_within_minimum', $within_minimum]],
                $messages + ['max_within_minimum' => 'Maximum coupon discount must be less than minimum order amount.']);
        } else {
            $discount_rules[] = ['discount_within_minimum', $within_minimum];
        }
        $v->set_rules('coupon_discount', 'Coupon discount', $discount_rules, $messages + ['discount_within_minimum' => 'Coupon discount must be less than minimum order amount.']);
        $v->set_rules('minimum_order_amount', 'Minimum order amount', ['trim', $amount], $messages);

        $v->set_rules('coupon_usage', 'Coupon usage', ['required', 'in_list[single,multiple]'], ['in_list' => 'Choose a valid {field}.']);
        if ($usage === 'multiple') {
            $v->set_rules('usage_count', 'Number of times coupon can be used', 'trim|required|is_natural_no_zero|less_than_equal_to[9999]');
        }

        $start_kept = $row && $post('start_date') === (string) $row['start_date'];
        $v->set_rules('start_date', 'Coupon start date', ['trim', 'required', $date,
            ['start_not_past', static fn ($value) => $start_kept || ! sp_valid_date((string) $value) || (string) $value >= $today]],
            $messages + ['start_not_past' => 'Coupon start date should not be less than current date.']);
        $start = $post('start_date');
        $v->set_rules('end_date', 'Coupon end date', ['trim', 'required', $date,
            ['end_after_start', static fn ($value) => ! sp_valid_date((string) $value) || ! sp_valid_date($start) || (string) $value >= $start]],
            $messages + ['end_after_start' => 'Coupon end date should not be less than coupon start date.']);

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $data = [
            'coupon_title'            => $post('coupon_title'),
            'coupon_type'             => $type,
            'coupon_discount'         => $post('coupon_discount'),
            'maximum_coupon_discount' => $type === 'p' ? $post('maximum_coupon_discount') : '0.00',
            'minimum_order_amount'    => $minimum !== '' ? $minimum : '0.00',
            'coupon_usage'            => $usage,
            'usage_count'             => $usage === 'multiple' ? (int) $post('usage_count') : 1,
            'coupon_for'              => '0',
            'start_date'              => $post('start_date'),
            'end_date'                => $post('end_date'),
        ];

        if ($row) {
            $saved = $this->coupon_model->safe_update('wl_coupons', $data, ['coupon_id' => (int) $row['coupon_id']]);
        } else {
            $code  = $this->coupon_model->new_coupon_code();
            $saved = $code !== false && (bool) $this->coupon_model->safe_insert('wl_coupons', $data + [
                'coupon_code' => $code,
                'status'      => '1',
                'date_added'  => date('Y-m-d H:i:s'),
            ]);
        }

        return $saved ? [] : ['Coupon could not be saved. Please try again.'];
    }

    protected static function is_amount(string $value): bool
    {
        return preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/D', $value) === 1;
    }
}
