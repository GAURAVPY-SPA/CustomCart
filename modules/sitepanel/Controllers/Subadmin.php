<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Libraries\AdminPasswordPolicy;
use Modules\Sitepanel\Models\SubadminModel;

/**
 * Sitepanel > Other Management > Manage Subadmins (tbl_admin, admin_type 2,
 * and their section grants in tbl_admin_allowed_sections).
 *
 * As in the reference module: list with search (name, username, email) and
 * a status filter; add / edit a sub-admin (name, email, phone, username,
 * password + confirmation; sections to allow, each with the privileges
 * View / Add / Edit / Activate / Deactivate / Delete the section offers);
 * activate, deactivate and delete, one row or the selected rows. A
 * sub-admin signs in on the normal sitepanel login and sees only the
 * granted sections (AdminAccess).
 *
 * Differences from the reference, for safety: only the super admin can open
 * this section; passwords are stored hashed (password_hash()) and never
 * shown; on edit the password is only changed when a new one is entered;
 * email addresses are unique; deleting a sub-admin also deletes their grants;
 * Manage Subadmins and Admin Settings can't be granted (the reference
 * also kept Admin Settings out), nor the Dashboard and Change Password,
 * which every administrator has.
 *
 *   index          subadmin/view_subadmin_list
 *   add            subadmin/view_subadmin_form
 *   edit/<id>      subadmin/view_subadmin_form
 *   status/<id>    POST {status: 1|0}
 *   delete/<id>    POST
 *   bulk           POST, activate / deactivate / delete the selected rows
 */
class Subadmin extends AdminController
{
    /**
     * Sections a sub-admin can't be given (besides AdminAccess::ALWAYS_ALLOWED).
     */
    public const NOT_GRANTABLE = ['subadmin', 'settings'];

    public const PRIVILEGES = [
        AdminAccess::PRIV_VIEW       => 'View',
        AdminAccess::PRIV_ADD        => 'Add',
        AdminAccess::PRIV_EDIT       => 'Edit',
        AdminAccess::PRIV_ACTIVATE   => 'Activate',
        AdminAccess::PRIV_DEACTIVATE => 'Deactivate',
        AdminAccess::PRIV_DELETE     => 'Delete',
    ];

    protected string $base           = 'sitepanel/subadmin';
    protected string $item           = 'Sub-admin';
    protected string $menu_highlight = 'subadmin';

    protected SubadminModel $subadmin_model;

    protected function init(): void
    {
        if (! $this->admin_access->is_super_admin($this->admin)) {
            $this->session->setFlashdata('sitepanel_error', 'Only the super admin can manage sub-admins.');
            redirect_now('sitepanel/dashboard');
        }
        $this->subadmin_model = new SubadminModel();
    }

    public function index(): string
    {
        $keyword = $this->keyword_param();
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page = $this->current_page();
        $res  = $this->subadmin_model->get_subadmins(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage Subadmins';
        $data['breadcrumbs']   = ['Sub-admins' => null];
        $data['res']           = $res;
        $data['total']         = $this->subadmin_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('subadmin/view_subadmin_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $sections       = $this->sections_or_back();
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null, $sections);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Sub-admin added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Sub-admin';
        $data['breadcrumbs']   = ['Sub-admins' => site_url($this->base), 'Add Sub-admin' => null];
        $data['res']           = ['admin_name' => '', 'admin_email' => '', 'phone' => '', 'admin_username' => ''];
        $data['sections']      = $sections;
        $data['grants']        = $this->posted_grants($sections) ?? [];

        return $this->render('subadmin/view_subadmin_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->subadmin_model->get_subadmin_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $sections       = $this->sections_or_back();
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res, $sections);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Sub-admin updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Sub-admin';
        $data['breadcrumbs']   = ['Sub-admins' => site_url($this->base), 'Edit Sub-admin' => null];
        $data['res']           = $res;
        $data['sections']      = $sections;
        $data['grants']        = $this->posted_grants($sections) ?? (array) $this->subadmin_model->get_grants((int) $res['admin_id']);

        return $this->render('subadmin/view_subadmin_form', $data);
    }

    /**
     * POST {status: 1|0}
     *
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->require_post();
        $active = $this->request->getPost('status') === '1';
        $this->require_privilege($active ? AdminAccess::PRIV_ACTIVATE : AdminAccess::PRIV_DEACTIVATE);
        $res = $this->record_or_back($this->subadmin_model->get_subadmin_by_id($id));

        if ($this->subadmin_model->bulk_update_status([(int) $res['admin_id']], $active ? '1' : '0')) {
            $this->session->setFlashdata('sitepanel_success', 'Sub-admin ' . ($active ? 'activated' : 'deactivated') . '.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'Sub-admin could not be updated. Please try again.');
        }
        $this->back();
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);
        $res = $this->record_or_back($this->subadmin_model->get_subadmin_by_id($id));

        if ($this->subadmin_model->bulk_delete([(int) $res['admin_id']])) {
            $this->session->setFlashdata('sitepanel_success', 'Sub-admin deleted.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'Sub-admin could not be deleted. Please try again.');
        }
        $this->back();
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->subadmin_model, 'get_subadmins_by_ids', 'Sub-admin', $this->list_query());
    }

    /**
     * @return array heading => sections (SubadminModel::grantable_sections())
     */
    protected function sections_or_back(): array
    {
        $sections = $this->subadmin_model->grantable_sections(array_merge(self::NOT_GRANTABLE, AdminAccess::ALWAYS_ALLOWED));
        if ($sections === false) {
            $this->session->setFlashdata('sitepanel_error', 'Sections could not be loaded. Please try again.');
            $this->back();
        }

        return $sections;
    }

    /**
     * The submitted grants, limited to grantable sections and to the
     * privileges each section offers; View is always included.
     *
     * @return array|null section id => list<int> codes; NULL when nothing was submitted
     */
    protected function posted_grants(array $sections): ?array
    {
        if ($this->request->getMethod() !== 'POST') {
            return null;
        }

        $chosen = array_map('intval', array_filter((array) $this->request->getPost('sec_id'), 'is_scalar'));
        $posted = (array) $this->request->getPost('permissions');
        $grants = [];

        foreach ($sections as $group) {
            foreach ($group as $section) {
                $id = (int) $section['id'];
                if (! in_array($id, $chosen, true)) {
                    continue;
                }
                $codes = array_map('intval', array_filter((array) ($posted[$id] ?? []), 'is_scalar'));
                $codes = array_values(array_intersect($section['privileges'], array_merge([AdminAccess::PRIV_VIEW], $codes)));
                sort($codes);
                $grants[$id] = $codes;
            }
        }

        return $grants;
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row, array $sections): array
    {
        $except   = $row ? (int) $row['admin_id'] : 0;
        $password = (string) $this->request->getPost('password');

        $v = new FormValidation($this->request, $this);
        $v->set_rules('admin_name', 'Name', ['trim', 'required', 'max_length[50]', "regex_match[/^[\\p{L}\\p{M} .'-]+$/u]"],
            ['regex_match' => "The {field} may only contain letters, spaces and . ' -"]);
        $v->set_rules('admin_email', 'Email', ['trim', 'required', 'max_length[191]', 'valid_email',
            ['unique_email', fn ($value) => $this->subadmin_model->is_taken('admin_email', trim((string) $value), $except) === false]],
            ['unique_email' => 'The {field} is already used by another administrator.']);
        $v->set_rules('phone', 'Phone no.', ['trim', 'required', 'max_length[15]', 'regex_match[/^\+?[0-9][0-9 -]{5,14}$/]'],
            ['regex_match' => 'The {field} may only contain digits, spaces, - and a leading +.']);
        $v->set_rules('admin_username', 'Username', ['trim', 'required', 'min_length[3]', 'max_length[50]', 'regex_match[/^[A-Za-z0-9._-]+$/]',
            ['unique_username', fn ($value) => $this->subadmin_model->is_taken('admin_username', trim((string) $value), $except) === false]],
            ['regex_match' => 'The {field} may only contain letters, digits, . _ and -.', 'unique_username' => 'The {field} already exists.']);

        if (! $row || $password !== '') {
            // Length and characters: AdminPasswordPolicy (Config\Sitepanel), checked below.
            $v->set_rules('password', 'Password', 'required');
            $v->set_rules('cpassword', 'Confirm password', 'required|matches[password]');
        }

        $errors = $v->run() ? [] : array_values($v->error_array());
        if ($password !== '') {
            $errors = array_merge($errors, (new AdminPasswordPolicy())->errors($password));
        }

        $grants = $this->posted_grants($sections) ?? [];
        if (! $grants) {
            $errors[] = 'Choose at least one section for this sub-admin.';
        }
        if ($errors) {
            return $errors;
        }

        $parents = [];

        foreach ($sections as $group) {
            foreach ($group as $section) {
                $parents[(int) $section['id']] = (int) $section['parent_id'];
            }
        }

        $data = [
            'admin_name'     => (string) $this->request->getPost('admin_name'),
            'admin_email'    => (string) $this->request->getPost('admin_email'),
            'phone'          => (string) $this->request->getPost('phone'),
            'admin_username' => (string) $this->request->getPost('admin_username'),
        ];
        if ($password !== '') {
            $data['password_hash']  = password_hash($password, PASSWORD_DEFAULT);
            $data['admin_password'] = null;
        }

        $rows = [];

        foreach ($grants as $sec_id => $codes) {
            $rows[$sec_id] = [$parents[$sec_id], $codes];
        }

        return $this->subadmin_model->save($data, $rows, $row ? (int) $row['admin_id'] : null) !== false
            ? []
            : ['Sub-admin could not be saved. Please try again.'];
    }
}
