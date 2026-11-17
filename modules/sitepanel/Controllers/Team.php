<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Team\Controllers\Team as TeamPage;
use Modules\Team\Models\TeamModel;

/**
 * Sitepanel > Other Management > Manage Team & Partners (wl_team): the
 * website's /our-team (team members) and /partners (partner logos, also on
 * the homepage).
 *
 * List with name search, type and status filters and display order; add /
 * edit (Type, Name and Photo / logo required; Designation and Description
 * optional); activate, deactivate and delete, one row or the selected rows.
 * A new entry goes after the last one. friendly_url (a unique column of the
 * table) is built from the name; the entries have no pages of their own.
 *
 *   index          team/view_team_list
 *   add, edit/<id> team/view_team_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, also removes the image
 *   bulk           POST, activate / deactivate / delete the selected rows
 *   sort           POST ord[<team_id>] = display order, rows of the current page
 */
class Team extends AdminController
{
    /**
     * Image size hints: square member photos, landscape partner logos.
     */
    public const BEST_SIZE = '600 × 600 pixels for a photo; a logo on a white or transparent background for a partner';

    protected string $base           = 'sitepanel/team';
    protected string $item           = 'Entry';
    protected string $menu_highlight = 'team';

    protected TeamModel $team_model;
    protected ImageUpload $image_upload;

    protected function init(): void
    {
        $this->team_model   = new TeamModel();
        $this->image_upload = new ImageUpload();
    }

    public function index(): string
    {
        $keyword = $this->keyword_param();
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $type    = $this->type_param((string) $this->request->getGet('type'));
        $page    = $this->current_page();
        $res     = $this->team_model->get_team(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status, $type);

        $data['heading_title'] = 'Manage Team & Partners';
        $data['breadcrumbs']   = ['Team & Partners' => null];
        $data['res']           = $res;
        $data['total']         = $this->team_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;
        $data['type']          = $type;

        return $this->render('team/view_team_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Entry added.');
                redirect_now($this->base);
            }
        }

        $type = $this->type_param((string) $this->request->getGet('type'));

        $data['heading_title'] = 'Add Team Member / Partner';
        $data['breadcrumbs']   = ['Team & Partners' => site_url($this->base), 'Add' => null];
        $data['res']           = ['name' => '', 'designation' => '', 'team_type' => $type !== '' ? $type : TeamModel::TYPE_TEAM, 'biography' => ''];
        $data['image']         = '';

        return $this->render('team/view_team_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->team_model->get_member_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Entry updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Team Member / Partner';
        $data['breadcrumbs']   = ['Team & Partners' => site_url($this->base), 'Edit' => null];
        $data['res']           = $res;
        $data['image']         = $this->image_upload->path(TeamPage::IMAGE_FOLDER, $res['photo']);

        return $this->render('team/view_team_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(TeamModel::TABLE, TeamModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record(TeamModel::TABLE, TeamModel::PRIMARY_KEY, $id, 'photo', TeamPage::IMAGE_FOLDER);
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->team_model, 'get_members_by_ids', 'Entry', $this->list_query(), 'photo', TeamPage::IMAGE_FOLDER);
    }

    /**
     * POST ord[<team_id>] => display order (0-9999) for the rows of one list page.
     */
    public function sort(): void
    {
        $this->save_display_order($this->team_model, 'get_members_by_ids', 'entry');
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules('team_type', 'Type', 'required|in_list[' . implode(',', array_keys(TeamModel::TYPES)) . ']');
        $v->set_rules('name', 'Name', 'trim|required|max_length[150]');
        $v->set_rules('designation', 'Designation', 'trim|max_length[150]');
        $v->set_rules('biography', 'Description', 'trim|max_length[2000]');

        $errors = $v->run() ? [] : array_values($v->error_array());

        $has_file = $this->image_upload->has_file('photo');
        if (! $row && ! $has_file) {
            $errors[] = 'Choose a photo or logo to upload.';
        }
        if ($errors) {
            return $errors;
        }

        $post = fn ($field) => trim((string) $this->request->getPost($field));
        $id   = $row ? (int) $row['team_id'] : 0;
        $slug = $this->team_model->free_slug(sp_slug($post('name'), 140), $id);
        if ($slug === false) {
            return ['The entry could not be checked. Please try again.'];
        }

        $new_file = null;
        if ($has_file) {
            $upload = $this->image_upload->save('photo', TeamPage::IMAGE_FOLDER, 'Photo / logo');
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }

        $now  = date('Y-m-d H:i:s');
        $data = [
            'team_type'         => $post('team_type'),
            'name'              => $post('name'),
            'designation'       => $post('designation'),
            'friendly_url'      => $slug,
            'biography'         => $this->nullable('biography'),
            'team_date_updated' => $now,
        ];
        if ($new_file !== null) {
            $data['photo'] = $new_file;
        }

        if ($row) {
            $saved = $this->team_model->safe_update(TeamModel::TABLE, $data, [TeamModel::PRIMARY_KEY => $id]);
        } else {
            $order = $this->team_model->next_sort_order();
            $saved = $order !== false && (bool) $this->team_model->safe_insert(TeamModel::TABLE, $data + [
                'sort_order'      => $order,
                'status'          => '1',
                'team_date_added' => $now,
            ]);
        }

        if (! $saved) {
            $new_file !== null && $this->image_upload->delete(TeamPage::IMAGE_FOLDER, $new_file);

            return ['Entry could not be saved. Please try again.'];
        }
        if ($row && $new_file !== null) {
            $this->image_upload->delete(TeamPage::IMAGE_FOLDER, $row['photo']);
        }

        return [];
    }

    /**
     * List filters to return to after an action (posted with it).
     */
    protected function list_query(): array
    {
        $type = $this->type_param((string) $this->request->getPost('type_filter'));

        return parent::list_query() + ($type !== '' ? ['type' => $type] : []);
    }

    protected function type_param(string $value): string
    {
        return isset(TeamModel::TYPES[$value]) ? $value : '';
    }
}
