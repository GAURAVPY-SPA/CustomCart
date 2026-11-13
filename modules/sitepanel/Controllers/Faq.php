<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\FaqModel;

/**
 * Sitepanel > Other Management > Manage FAQs (wl_faq).
 *
 * As in the reference module: list with question search (here also a
 * status filter), display order, add / edit (Question: required, at most
 * 220 characters, unique; Answer: required plain text, at most 8500
 * characters), activate, deactivate and delete, one row or the selected
 * rows. A new FAQ goes after the last one. The reference's display-order
 * inputs had no save button; here "Update order" saves them (Edit privilege).
 *
 *   index          faq/view_faq_list
 *   add            faq/view_faq_form
 *   edit/<id>      faq/view_faq_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 *   bulk           POST, activate / deactivate / delete the selected rows
 *   sort           POST ord[<faq_id>] = display order, rows of the current page
 */
class Faq extends AdminController
{
    protected string $base           = 'sitepanel/faq';
    protected string $item           = 'FAQ';
    protected string $menu_highlight = 'faq';

    protected FaqModel $faq_model;

    protected function init(): void
    {
        $this->faq_model = new FaqModel();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->faq_model->get_faq(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage FAQs';
        $data['breadcrumbs']   = ['FAQs' => null];
        $data['res']           = $res;
        $data['total']         = $this->faq_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('faq/view_faq_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'FAQ added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add FAQ';
        $data['breadcrumbs']   = ['FAQs' => site_url($this->base), 'Add FAQ' => null];
        $data['res']           = ['faq_question' => '', 'faq_answer' => '', 'is_general' => '1'];
        $data['products']      = $this->product_options();
        $data['assigned']      = [];

        return $this->render('faq/view_faq_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->faq_model->get_faq_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'FAQ updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit FAQ';
        $data['breadcrumbs']   = ['FAQs' => site_url($this->base), 'Edit FAQ' => null];
        $data['res']           = $res;
        $data['products']      = $this->product_options();
        $data['assigned']      = (array) $this->faq_model->get_product_ids((int) $res['faq_id']);

        return $this->render('faq/view_faq_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status('wl_faq', 'faq_id', $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record('wl_faq', 'faq_id', $id);
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->faq_model, 'get_faqs_by_ids', 'FAQ', $this->list_query());
    }

    /**
     * POST ord[<faq_id>] => display order (0-9999) for the FAQs of one list page.
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
            $this->session->setFlashdata('sitepanel_error', 'Enter a display order from 0 to 9999 for each FAQ.');
            $this->back($query);
        }

        $ids = array_keys($orders);
        if (! $this->selected_rows_match($this->faq_model->get_faqs_by_ids($ids), $ids)) {
            $this->back($query);
        }

        if ($this->faq_model->update_sort_orders($orders)) {
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
        $except = $row ? (int) $row['faq_id'] : 0;
        $v      = new FormValidation($this->request, $this);
        $v->set_rules(
            'faq_question',
            'Question',
            ['trim', 'required', 'max_length[220]', ['unique_question', fn ($value) => $this->faq_model->question_exists(trim((string) $value), $except) === false]],
            ['unique_question' => 'The {field} already exists.'],
        );
        $v->set_rules('faq_answer', 'Answer', 'trim|required|max_length[8500]');

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $data = [
            'faq_question' => (string) $this->request->getPost('faq_question'),
            'faq_answer'   => (string) $this->request->getPost('faq_answer'),
            'is_general'   => $this->request->getPost('is_general') === '1' ? '1' : '0',
        ];
        // Products this FAQ is shown on (only existing products).
        $posted   = $this->request->getPost('products');
        $products = array_values(array_intersect(array_map('intval', is_array($posted) ? $posted : []), array_keys($this->product_options())));

        if ($row) {
            $id    = (int) $row['faq_id'];
            $saved = $this->faq_model->safe_update('wl_faq', $data, ['faq_id' => $id]);
        } else {
            $order = $this->faq_model->next_sort_order();
            $id    = $order !== false ? (int) $this->faq_model->safe_insert('wl_faq', $data + [
                'sort_order'     => $order,
                'status'         => '1',
                'faq_date_added' => date('Y-m-d H:i:s'),
            ]) : 0;
            $saved = $id > 0;
        }
        $saved = $saved && $this->faq_model->save_product_ids($id, $products);

        return $saved ? [] : ['FAQ could not be saved. Please try again.'];
    }

    /**
     * Products an FAQ can be assigned to: products_id => name (code).
     *
     * @return array<int, string>
     */
    protected function product_options(): array
    {
        static $options = null;
        if ($options === null) {
            $rows    = (new \Modules\Products\Models\ProductModel())->get_related_options();
            $options = is_array($rows) ? $rows : [];
        }

        return $options;
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
