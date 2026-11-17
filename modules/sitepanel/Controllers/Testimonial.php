<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Libraries\AdminAccess;
use App\Libraries\ImageUpload;
use Modules\Testimonials\Models\TestimonialModel;

/**
 * Sitepanel > Other Management > Manage Testimonials (wl_testimonial).
 *
 * Fields as in the reference module: name (required, up to 30 characters),
 * company / role (optional, shown on /testimonials), email (required),
 * comment (required) and an optional photo (2 MB,
 * 2000 × 2000 px at most) that can be replaced or removed. Testimonials sent
 * from the public "Share Your Experience" form arrive inactive (pending):
 * activating one publishes it on /testimonials. The page URL
 * (friendly_url) is generated from the name when the testimonial is added
 * and kept unique. Comments are plain text. Rows can be activated,
 * deactivated and deleted.
 *
 *   index          testimonial/view_testimonial_list
 *   add            testimonial/view_post_testimonials (the reference names this action "post")
 *   edit/<id>      testimonial/view_edit_testimonials
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 */
class Testimonial extends AdminController
{
    public const PHOTO_MAX_SIZE = [2000, 2000];
    public const IMAGE_FOLDER   = 'testimonial';

    protected string $base           = 'sitepanel/testimonial';
    protected string $item           = 'Testimonial';
    protected string $menu_highlight = 'testimonial';
    protected TestimonialModel $testimonial_model;
    protected ImageUpload $image_upload;

    protected function init(): void
    {
        $this->testimonial_model = new TestimonialModel();
        $this->image_upload      = new ImageUpload();
    }

    public function index(): string
    {
        $filter = [
            'keyword' => $this->keyword_param(),
            'status'  => $this->status_param((string) $this->request->getGet('status')),
            'source'  => $this->source_param((string) $this->request->getGet('source')),
        ];
        $page = $this->current_page();
        $res  = $this->testimonial_model->get_testimonial(self::PAGESIZE, ($page - 1) * self::PAGESIZE, $filter);

        $data                  = $filter;
        $data['heading_title'] = 'Manage Testimonials';
        $data['breadcrumbs']   = ['Testimonials' => null];
        $data['res']           = $res;
        $data['total']         = $this->testimonial_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);

        return $this->render('testimonial/view_testimonial_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Testimonial added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Testimonial';
        $data['breadcrumbs']   = ['Testimonials' => site_url($this->base), 'Add Testimonial' => null];
        $data['max_size']      = self::PHOTO_MAX_SIZE;

        return $this->render('testimonial/view_post_testimonials', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->testimonial_model->get_testimonial_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Testimonial updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Testimonial';
        $data['breadcrumbs']   = ['Testimonials' => site_url($this->base), 'Edit Testimonial' => null];
        $data['res']           = $res;
        $data['image']         = $this->image_upload->path(self::IMAGE_FOLDER, $res['photo']);
        $data['max_size']      = self::PHOTO_MAX_SIZE;

        return $this->render('testimonial/view_edit_testimonials', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status('wl_testimonial', 'testimonial_id', $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record('wl_testimonial', 'testimonial_id', $id, 'photo', self::IMAGE_FOLDER);
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->testimonial_model, 'get_testimonials_by_ids', 'Testimonial', $this->list_query(), 'photo', self::IMAGE_FOLDER);
    }

    /**
     * List filters to return to after an action (posted with it).
     */
    protected function list_query(): array
    {
        $source = $this->source_param((string) $this->request->getPost('source'));

        return parent::list_query() + ($source !== '' ? ['source' => $source] : []);
    }

    protected function source_param(string $value): string
    {
        return in_array($value, ['public', 'admin'], true) ? $value : '';
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules(
            'poster_name',
            'Name',
            "trim|required|max_length[30]|regex_match[/^[\\p{L}][\\p{L} .'\\-]*$/u]",
            ['regex_match' => 'The %s field may contain only letters, spaces and . \' -'],
        );
        $v->set_rules('poster_role', 'Company / Role', 'trim|max_length[100]');
        $v->set_rules('email', 'Email', 'trim|required|max_length[80]|valid_email');
        $v->set_rules('testimonial_description', 'Comment', 'trim|required|max_length[5000]');

        $errors = $v->run() ? [] : array_values($v->error_array());
        if ($errors) {
            return $errors;
        }

        $new_file = null;
        if ($this->image_upload->has_file('photo')) {
            $upload = $this->image_upload->save('photo', self::IMAGE_FOLDER, 'Photo', self::PHOTO_MAX_SIZE);
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }

        $remove_photo = $row && $new_file === null && $this->request->getPost('remove_photo') === '1';
        $name         = trim((string) $this->request->getPost('poster_name'));

        $data = [
            'poster_name'             => $name,
            'poster_role'             => $this->nullable('poster_role'),
            'email'                   => trim((string) $this->request->getPost('email')),
            'testimonial_description' => trim((string) $this->request->getPost('testimonial_description')),
        ];
        if ($new_file !== null || $remove_photo) {
            $data['photo'] = $new_file;
        }

        if ($row) {
            $saved = $this->testimonial_model->safe_update('wl_testimonial', $data, ['testimonial_id' => (int) $row['testimonial_id']]);
        } else {
            $slug = $this->testimonial_model->unique_slug($name);
            $data += ['friendly_url' => $slug, 'status' => '1', 'posted_date' => date('Y-m-d H:i:s')];
            $saved = ($slug !== null) && $this->testimonial_model->safe_insert('wl_testimonial', $data);
        }

        if (! $saved) {
            $new_file !== null && $this->image_upload->delete(self::IMAGE_FOLDER, $new_file);

            return ['Testimonial could not be saved. Please try again.'];
        }

        if ($row && ($new_file !== null || $remove_photo)) {
            $this->image_upload->delete(self::IMAGE_FOLDER, $row['photo']);
        }

        return [];
    }
}
