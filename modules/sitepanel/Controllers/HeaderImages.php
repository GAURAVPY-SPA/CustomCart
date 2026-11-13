<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\HeaderImagesModel;

/**
 * Sitepanel > Other Management > Manage Header Images (wl_header_images),
 * the home page slider.
 *
 * As in the reference module: list with a status filter, add / edit (image
 * required when adding; optional link; three caption lines), activate,
 * deactivate and delete, one row or the selected rows. A link typed without
 * http:// or https:// gets http:// (the reference's prep_url). Unlike the
 * reference, deleting a header image (or replacing its image) removes the
 * old file, and the caption lines may be as long as their columns (80, 255
 * and 255 characters): the home hero (Modules\Home\Controllers\Home::slides())
 * shows them as its small heading, title and text.
 *
 *   index          header_images/view_header_images_list
 *   add            header_images/view_header_images_form
 *   edit/<id>      header_images/view_header_images_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 *   bulk           POST, activate / deactivate / delete the selected rows
 */
class HeaderImages extends AdminController
{
    public const IMAGE_FOLDER = 'header_images';

    /**
     * Best image size (hint shown with the upload field).
     */
    public const BEST_SIZE = '1920 × 820';

    protected string $base           = 'sitepanel/header_images';
    protected string $item           = 'Header image';
    protected string $menu_highlight = 'header_images';

    protected HeaderImagesModel $header_images_model;
    protected ImageUpload $image_upload;

    protected function init(): void
    {
        $this->header_images_model = new HeaderImagesModel();
        $this->image_upload        = new ImageUpload();
    }

    public function index(): string
    {
        $status = $this->status_param((string) $this->request->getGet('status'));
        $page   = $this->current_page();
        $res    = $this->header_images_model->get_header_images(($page - 1) * self::PAGESIZE, self::PAGESIZE, $status);

        $data['heading_title'] = 'Manage Header Images';
        $data['breadcrumbs']   = ['Header Images' => null];
        $data['res']           = $res;
        $data['total']         = $this->header_images_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['status']        = $status;

        return $this->render('header_images/view_header_images_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Header image added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Header Image';
        $data['breadcrumbs']   = ['Header Images' => site_url($this->base), 'Add Header Image' => null];
        $data['res']           = ['header_url' => '', 'line_one' => '', 'line_two' => '', 'line_three' => ''];
        $data['image']         = '';

        return $this->render('header_images/view_header_images_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->header_images_model->get_header_image_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Header image updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Header Image';
        $data['breadcrumbs']   = ['Header Images' => site_url($this->base), 'Edit Header Image' => null];
        $data['res']           = $res;
        $data['image']         = $this->image_upload->path(self::IMAGE_FOLDER, $res['header_image']);

        return $this->render('header_images/view_header_images_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status('wl_header_images', 'id', $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record('wl_header_images', 'id', $id, 'header_image', self::IMAGE_FOLDER);
    }

    public function bulk(): void
    {
        $status = $this->status_param((string) $this->request->getPost('status_filter'));
        $this->process_bulk_action($this->header_images_model, 'get_header_images_by_ids', 'Header image', array_filter(['status' => $status]), 'header_image', self::IMAGE_FOLDER);
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        // The reference's prep_url: a link without a scheme gets http://.
        $url = trim((string) $this->request->getPost('header_url'));
        if ($url !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $this->request->setGlobal('post', array_merge((array) $this->request->getPost(), ['header_url' => 'http://' . $url]));
        }

        $v = new FormValidation($this->request, $this);
        $v->set_rules(
            'header_url',
            'Link URL',
            ['trim', 'max_length[255]', ['valid_web_url', 'sp_is_web_url']],
            ['valid_web_url' => 'The %s field must be a complete web address, e.g. https://example.com/page.'],
        );
        // As long as their columns allow: the home hero shows them as its small heading, title and text.
        $v->set_rules('line_one', 'First line', 'trim|max_length[80]');
        $v->set_rules('line_two', 'Second line', 'trim|max_length[255]');
        $v->set_rules('line_three', 'Third line', 'trim|max_length[255]');

        $errors = $v->run() ? [] : array_values($v->error_array());

        $has_file = $this->image_upload->has_file('header_image');
        if (! $row && ! $has_file) {
            $errors[] = 'Choose a header image to upload.';
        }
        if ($errors) {
            return $errors;
        }

        $new_file = null;
        if ($has_file) {
            $upload = $this->image_upload->save('header_image', self::IMAGE_FOLDER, 'Header image');
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }

        $data = [
            'header_url' => $this->nullable('header_url'),
            'line_one'   => $this->nullable('line_one'),
            'line_two'   => $this->nullable('line_two'),
            'line_three' => $this->nullable('line_three'),
        ];
        if ($new_file !== null) {
            $data['header_image'] = $new_file;
        }

        if ($row) {
            $saved = $this->header_images_model->safe_update('wl_header_images', $data, ['id' => (int) $row['id']]);
        } else {
            $data += ['status' => '1', 'added_date' => date('Y-m-d H:i:s')];
            $saved = (bool) $this->header_images_model->safe_insert('wl_header_images', $data);
        }

        if (! $saved) {
            $new_file !== null && $this->image_upload->delete(self::IMAGE_FOLDER, $new_file);

            return ['Header image could not be saved. Please try again.'];
        }

        if ($row && $new_file !== null) {
            $this->image_upload->delete(self::IMAGE_FOLDER, $row['header_image']);
        }

        return [];
    }

    protected function status_param(string $value): string
    {
        return in_array($value, ['1', '0'], true) ? $value : '';
    }
}
