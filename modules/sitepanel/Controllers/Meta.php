<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\MetaModel;

/**
 * Sitepanel > Other Management > Meta Tags (wl_meta_tags).
 *
 * Supports searchable listing, add/edit, and row-level status changes. The
 * reference's shared status handler also supports deleting selected records.
 */
class Meta extends AdminController
{
    protected string $base           = 'sitepanel/meta';
    protected string $item           = 'Meta tag';
    protected string $menu_highlight = 'meta';
    protected MetaModel $meta_model;

    protected function init(): void
    {
        $this->meta_model = new MetaModel();
    }

    public function index(): string
    {
        $keyword = $this->keyword_param();
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $rows    = $this->meta_model->get_meta(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Meta Tags Management';
        $data['breadcrumbs']   = ['Meta Tags' => null];
        $data['res']           = $rows;
        $data['total']         = $this->meta_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('metatag/meta_list_view', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Meta tag added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Meta Tag';
        $data['breadcrumbs']   = ['Meta Tags' => site_url($this->base), 'Add Meta Tag' => null];
        $data['res']           = ['page_url' => '', 'meta_title' => '', 'meta_keyword' => '', 'meta_description' => ''];

        return $this->render('metatag/meta_form_view', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->meta_model->get_record_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Meta tag updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Meta Tag';
        $data['breadcrumbs']   = ['Meta Tags' => site_url($this->base), 'Edit Meta Tag' => null];
        $data['res']           = $res;

        return $this->render('metatag/meta_form_view', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status('wl_meta_tags', 'meta_id', $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $row  = $this->record_or_back($this->meta_model->get_record_by_id($id));
        $type = (string) $row['entity_type'];

        // A page's or module's row is its address (the website routes it through wl_meta_tags).
        if (MetaModel::is_route($row)) {
            $this->session->setFlashdata('sitepanel_error', match (true) {
                str_starts_with($type, MetaModel::PAGE_TYPE_PREFIX) => 'This meta tag is the address of a static page; it is removed with the page (Manage Static Pages).',
                (int) $row['entity_id'] > 0                         => 'This meta tag is the address of a record\'s page (' . $type . ' #' . (int) $row['entity_id'] . '); it is removed with that record.',
                default                                             => 'This meta tag is the address of a page of the website and can\'t be deleted. Its SEO data can be edited.',
            });
            $this->back();
        }

        $this->delete_record('wl_meta_tags', 'meta_id', $id);
    }

    /**
     * @param array|null $row NULL when adding
     *
     * @return list<string> Validation or persistence errors
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        // The URL is chosen when adding; on edit the stored URL is kept (it may
        // be a module-owned path such as blogs/<slug>).
        $row || $v->set_rules('page_url', 'URL', 'trim|required|max_length[200]|callback_valid_meta_page_url');
        $v->set_message('valid_meta_page_url', 'Enter a page slug such as aboutus or terms-conditions, without slashes or a domain.');
        $v->set_rules('meta_title', 'Title', 'trim|required|max_length[450]');
        $v->set_rules('meta_keyword', 'Keyword', 'trim|required|max_length[450]');
        $v->set_rules('meta_description', 'Description', 'trim|required|max_length[650]');

        $errors = $v->run() ? [] : array_values($v->error_array());
        if ($errors) {
            return $errors;
        }

        $page_url = $row
            ? trim((string) $row['page_url'], " \t\n\r\0\x0B/")
            : strtolower(trim((string) $this->request->getPost('page_url'), " \t\n\r\0\x0B/"));

        $check = $row ? 'available' : $this->meta_model->check_page_url($page_url);
        if ($check === 'unavailable') {
            return ['Meta tag storage is unavailable because its database table is missing. Contact the site administrator.'];
        }
        if ($check === 'error') {
            return ['The meta tag URL could not be checked because of a database error. Please try again.'];
        }
        if ($check === 'invalid') {
            return ['Enter a valid page slug.'];
        }
        if ($check === 'exists') {
            return ['A meta tag already exists for this URL.'];
        }

        $meta = [
            'page_url'         => $page_url,
            'meta_title'       => trim((string) $this->request->getPost('meta_title')),
            'meta_keyword'     => trim((string) $this->request->getPost('meta_keyword')),
            'meta_description' => trim((string) $this->request->getPost('meta_description')),
        ];

        if ($row) {
            $saved = $this->meta_model->safe_update('wl_meta_tags', $meta, ['meta_id' => (int) $row['meta_id']]);
        } else {
            $meta += ['is_fixed' => 'N', 'entity_id' => 0, 'status' => '1'];
            $saved = $this->meta_model->safe_insert('wl_meta_tags', $meta) !== false;
        }

        return $saved ? [] : ['Meta tag could not be saved. Please try again.'];
    }

    /**
     * Validation callback (callback_valid_meta_page_url): one normalized public
     * slug; external URLs and path prefixes are not stored in this module.
     * Public so the validator can call it; it has no route.
     *
     * @param string|null $value
     */
    public function valid_meta_page_url($value): bool
    {
        $value = strtolower(trim((string) $value, " \t\n\r\0\x0B/"));

        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) === 1 && strlen($value) <= 50;
    }
}
