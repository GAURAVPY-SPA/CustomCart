<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Gallery\Models\GalleryModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Sitepanel > Other Management > Manage Images Gallery (wl_gallery), the
 * photos of the website's /images-gallery page (module gallery; design:
 * html/images-gallery.htm, a masonry grid with a lightbox).
 *
 * As the reference's gallery module, without albums: list with title
 * search, status filter and display order; add / edit (Title: required, at
 * most 200 characters, it is the photo's alt text; Image: required when
 * adding, JPG / PNG / GIF / WebP up to 2 MB, any proportions); activate,
 * deactivate and delete, one row or the selected rows. A new photo is
 * active and goes after the last one. Deleting a photo (or replacing its
 * image) removes the old file.
 *
 *   index          gallery/view_gallery_list
 *   add            gallery/view_gallery_form
 *   edit/<id>      gallery/view_gallery_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 *   bulk           POST, activate / deactivate / delete the selected rows
 *   sort           POST ord[<gallery_id>] = display order, rows of the current page
 */
class Gallery extends AdminController
{
    public const IMAGE_FOLDER = 'gallery';

    /**
     * Image size hint shown with the upload field (the design's photos are 600 px wide, portrait or landscape).
     */
    public const BEST_SIZE = '600 px wide or more';

    protected string $base           = 'sitepanel/gallery';
    protected string $item           = 'Photo';
    protected string $menu_highlight = 'gallery';

    protected GalleryModel $gallery_model;
    protected ImageUpload $image_upload;

    protected function init(): void
    {
        $this->gallery_model = new GalleryModel();
        $this->image_upload  = new ImageUpload();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->gallery_model->get_galleries(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage Images Gallery';
        $data['breadcrumbs']   = ['Images Gallery' => null];
        $data['res']           = $res;
        $data['total']         = $this->gallery_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('gallery/view_gallery_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Photo added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Photo';
        $data['breadcrumbs']   = ['Images Gallery' => site_url($this->base), 'Add Photo' => null];
        $data['res']           = ['gallery_title' => ''];
        $data['image']         = '';

        return $this->render('gallery/view_gallery_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->gallery_model->get_gallery_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Photo updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Photo';
        $data['breadcrumbs']   = ['Images Gallery' => site_url($this->base), 'Edit Photo' => null];
        $data['res']           = $res;
        $data['image']         = $this->image_upload->path(self::IMAGE_FOLDER, $res['gallery_image']);

        return $this->render('gallery/view_gallery_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(GalleryModel::TABLE, GalleryModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record(GalleryModel::TABLE, GalleryModel::PRIMARY_KEY, $id, 'gallery_image', self::IMAGE_FOLDER);
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->gallery_model, 'get_galleries_by_ids', 'Photo', $this->list_query(), 'gallery_image', self::IMAGE_FOLDER);
    }

    /**
     * POST ord[<gallery_id>] => display order (0-9999) for the photos of one list page.
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
            $this->session->setFlashdata('sitepanel_error', 'Enter a display order from 0 to 9999 for each photo.');
            $this->back($query);
        }

        $ids = array_keys($orders);
        if (! $this->selected_rows_match($this->gallery_model->get_galleries_by_ids($ids), $ids)) {
            $this->back($query);
        }

        if ($this->gallery_model->update_sort_orders($orders)) {
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
        $v->set_rules('gallery_title', 'Title', 'trim|required|max_length[200]');

        $errors = $v->run() ? [] : array_values($v->error_array());

        $has_file = $this->image_upload->has_file('gallery_image');
        if (! $row && ! $has_file) {
            $errors[] = 'Choose an image to upload.';
        }
        if ($errors) {
            return $errors;
        }

        $new_file = null;
        if ($has_file) {
            $upload = $this->image_upload->save('gallery_image', self::IMAGE_FOLDER, 'Image');
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }

        $now  = date('Y-m-d H:i:s');
        $data = [
            'gallery_title'        => trim((string) $this->request->getPost('gallery_title')),
            'gallery_date_updated' => $now,
        ];
        if ($new_file !== null) {
            $data['gallery_image'] = $new_file;
        }

        if ($row) {
            $saved = $this->gallery_model->safe_update(GalleryModel::TABLE, $data, [GalleryModel::PRIMARY_KEY => (int) $row['gallery_id']]);
        } else {
            $order = $this->gallery_model->next_sort_order();
            $saved = $order !== false && (bool) $this->gallery_model->safe_insert(GalleryModel::TABLE, $data + [
                'sort_order'         => $order,
                'status'             => '1',
                'gallery_date_added' => $now,
            ]);
        }

        if (! $saved) {
            $new_file !== null && $this->image_upload->delete(self::IMAGE_FOLDER, $new_file);

            return ['Photo could not be saved. Please try again.'];
        }

        if ($row && $new_file !== null) {
            $this->image_upload->delete(self::IMAGE_FOLDER, $row['gallery_image']);
        }

        return [];
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
