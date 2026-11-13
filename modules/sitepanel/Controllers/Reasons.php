<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Cart\Models\ReasonModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Sitepanel > Manage Orders > Manage Reasons (wl_reasons).
 *
 * As in the reference's Reasons module: the reasons a customer picks from
 * when asking to cancel or return an order item (My Account > Order
 * Details). A reason has a name (required, at most 255 characters, unique per
 * type), a type (Cancel / Return), a display order and a status; only active
 * ones are offered. The chosen text is copied onto the order item, so a
 * reason can be edited or deleted without changing past requests.
 *
 *   index           store/reasons/reason_list (name search, type + status filters)
 *   add, edit/<id>  store/reasons/reason_form
 *   status/<id>     POST, AdminController::update_status()
 *   delete/<id>     POST, AdminController::delete_record()
 *   bulk            POST, activate / deactivate / delete the selected rows
 */
class Reasons extends AdminController
{
    protected string $base           = 'sitepanel/reasons';
    protected string $item           = 'Reason';
    protected string $menu_highlight = 'reasons';

    protected ReasonModel $reason_model;

    protected function init(): void
    {
        $this->reason_model = new ReasonModel();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $type    = $this->type_param((string) $this->request->getGet('type'));
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->reason_model->get_reasons(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $type, $status);

        $data['heading_title'] = 'Manage Reasons';
        $data['breadcrumbs']   = ['Reasons' => null];
        $data['res']           = $res;
        $data['total']         = $this->reason_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['type']          = $type;
        $data['status']        = $status;

        return $this->render('store/reasons/reason_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Reason added.');
                redirect_now($this->base);
            }
        }

        $type = $this->type_param((string) $this->request->getGet('type')) ?: '1';

        $data['heading_title'] = 'Add Reason';
        $data['breadcrumbs']   = ['Reasons' => site_url($this->base), 'Add Reason' => null];
        $data['res']           = ['reason_name' => '', 'reason_type' => $type, 'sort_order' => (string) $this->reason_model->next_sort_order($type), 'status' => '1'];

        return $this->render('store/reasons/reason_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->reason_model->get_reason_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Reason updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Reason';
        $data['breadcrumbs']   = ['Reasons' => site_url($this->base), 'Edit Reason' => null];
        $data['res']           = $res;

        return $this->render('store/reasons/reason_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(ReasonModel::TABLE, ReasonModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record(ReasonModel::TABLE, ReasonModel::PRIMARY_KEY, $id);
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->reason_model, 'get_reasons_by_ids', 'Reason', array_filter([
            'keyword' => mb_substr(trim((string) $this->request->getPost('keyword')), 0, 80),
            'type'    => $this->type_param((string) $this->request->getPost('type')),
            'status'  => $this->status_param((string) $this->request->getPost('status_filter')),
        ]));
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules('reason_name', 'Reason', 'trim|required|max_length[255]');
        $v->set_rules('reason_type', 'Type', ['required', 'in_list[1,2]'], ['in_list' => 'Choose a valid {field}.']);
        $v->set_rules('sort_order', 'Display order', 'trim|is_natural|less_than_equal_to[9999]');
        $v->set_rules('status', 'Status', 'required|in_list[1,0]');

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $post  = fn (string $field) => trim((string) $this->request->getPost($field));
        $taken = $this->reason_model->name_taken($post('reason_name'), $post('reason_type'), $row ? (int) $row['reason_id'] : 0);
        if ($taken === null) {
            return ['The reason could not be checked. Please try again.'];
        }
        if ($taken) {
            return ['This ' . strtolower(ReasonModel::TYPES[$post('reason_type')]) . ' reason already exists.'];
        }

        $data = [
            'reason_name'         => $post('reason_name'),
            'reason_type'         => $post('reason_type'),
            'sort_order'          => (int) $post('sort_order'),
            'status'              => $post('status'),
            'reason_date_updated' => date('Y-m-d H:i:s'),
        ];

        if ($row) {
            $saved = $this->reason_model->safe_update(ReasonModel::TABLE, $data, [ReasonModel::PRIMARY_KEY => (int) $row['reason_id']]);
        } else {
            $saved = $this->reason_model->safe_insert(ReasonModel::TABLE, $data + ['reason_date_added' => $data['reason_date_updated']]) !== false;
        }

        return $saved ? [] : ['Reason could not be saved. Please try again.'];
    }

    protected function type_param(string $value): string
    {
        return isset(ReasonModel::TYPES[$value]) ? $value : '';
    }

    protected function status_param(string $value): string
    {
        return in_array($value, ['1', '0'], true) ? $value : '';
    }
}
