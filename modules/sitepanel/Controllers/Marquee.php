<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\MarqueeModel;

/**
 * Sitepanel > Other Management > Manage Running Marquee (wl_marquee): the
 * announcements scrolling above the website header (site_marquee()).
 *
 * List with text search, status filter and display order; add / edit (Text
 * required, Link optional: a page of this site or an http(s) address);
 * activate, deactivate and delete, one row or the selected rows. A new item
 * goes after the last one.
 *
 *   index          marquee/view_marquee_list
 *   add, edit/<id> marquee/view_marquee_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 *   bulk           POST, activate / deactivate / delete the selected rows
 *   sort           POST ord[<marquee_id>] = display order, rows of the current page
 */
class Marquee extends AdminController
{
    protected string $base           = 'sitepanel/marquee';
    protected string $item           = 'Marquee item';
    protected string $menu_highlight = 'marquee';

    protected MarqueeModel $marquee_model;

    protected function init(): void
    {
        $this->marquee_model = new MarqueeModel();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->marquee_model->get_items(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage Running Marquee';
        $data['breadcrumbs']   = ['Running Marquee' => null];
        $data['res']           = $res;
        $data['total']         = $this->marquee_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('marquee/view_marquee_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Marquee item added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Marquee Item';
        $data['breadcrumbs']   = ['Running Marquee' => site_url($this->base), 'Add Item' => null];
        $data['res']           = ['marquee_text' => '', 'marquee_url' => ''];

        return $this->render('marquee/view_marquee_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->marquee_model->get_item($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Marquee item updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Marquee Item';
        $data['breadcrumbs']   = ['Running Marquee' => site_url($this->base), 'Edit Item' => null];
        $data['res']           = $res;

        return $this->render('marquee/view_marquee_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(MarqueeModel::TABLE, MarqueeModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record(MarqueeModel::TABLE, MarqueeModel::PRIMARY_KEY, $id);
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->marquee_model, 'get_items_by_ids', 'Marquee item', $this->list_query());
    }

    /**
     * POST ord[<marquee_id>] => display order (0-9999) for the rows of one list page.
     */
    public function sort(): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $query = $this->list_query();

        $posted = $this->request->getPost('ord');
        $orders = [];
        $valid  = is_array($posted) && $posted && count($posted) <= self::PAGESIZE;

        if ($valid) {
            foreach ($posted as $id => $order) {
                $order = is_string($order) ? trim($order) : '';
                if (! preg_match('/^[1-9][0-9]*$/D', (string) $id) || ! preg_match('/^[0-9]{1,4}$/D', $order)) {
                    $valid = false;
                    break;
                }
                $orders[(int) $id] = (int) $order;
            }
        }
        if (! $valid) {
            $this->session->setFlashdata('sitepanel_error', 'Enter a display order from 0 to 9999 for each item.');
            $this->back($query);
        }

        $ids = array_keys($orders);
        if (! $this->selected_rows_match($this->marquee_model->get_items_by_ids($ids), $ids)) {
            $this->back($query);
        }

        if ($this->marquee_model->update_sort_orders($orders)) {
            $this->session->setFlashdata('sitepanel_success', 'Display order updated.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'Display order could not be updated. Please try again.');
        }
        $this->back($query);
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules('marquee_text', 'Text', 'trim|required|max_length[255]');
        $v->set_rules(
            'marquee_url',
            'Link',
            ['trim', 'max_length[255]', ['safe_link', static fn ($value) => trim((string) $value) === '' || preg_match('#^(https?://[^\s<>"]+|/[^\s<>"]*)$#i', trim((string) $value)) === 1]],
            ['safe_link' => 'Enter the {field} as a full address starting with https:// (or http://), or a path of this site starting with /.'],
        );

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $data = [
            'marquee_text' => trim((string) $this->request->getPost('marquee_text')),
            'marquee_url'  => $this->nullable('marquee_url'),
            'date_updated' => date('Y-m-d H:i:s'),
        ];

        if ($row) {
            $saved = $this->marquee_model->safe_update(MarqueeModel::TABLE, $data, [MarqueeModel::PRIMARY_KEY => (int) $row['marquee_id']]);
        } else {
            $order = $this->marquee_model->next_sort_order();
            $saved = $order !== false && (bool) $this->marquee_model->safe_insert(MarqueeModel::TABLE, $data + [
                'sort_order' => $order,
                'status'     => '1',
                'date_added' => $data['date_updated'],
            ]);
        }

        return $saved ? [] : ['Marquee item could not be saved. Please try again.'];
    }

    /**
     * List filters to return to after an action (posted with it).
     */
    protected function list_query(): array
    {
        $keyword = mb_substr(trim((string) $this->request->getPost('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getPost('status_filter'));

        return array_filter(['keyword' => $keyword, 'status' => $status], static fn ($v) => $v !== '');
    }

    protected function status_param(string $value): string
    {
        return in_array($value, ['1', '0'], true) ? $value : '';
    }
}
