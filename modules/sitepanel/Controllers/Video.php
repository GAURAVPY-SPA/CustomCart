<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Video\Models\VideoModel;

/**
 * Sitepanel > Other Management > Manage Video Gallery (wl_video), the
 * videos of the website's /videos-gallery page (module video; design:
 * html/videos-gallery.htm, cards that play the video in a modal).
 *
 * As the reference's video module, without albums: list with title search,
 * status filter and display order; add / edit; activate, deactivate and
 * delete, one row or the selected rows. A new video is active and goes
 * after the last one. Fields, from the design's video cards:
 *   Title       required, at most 200 characters
 *   Video link  required: a YouTube or Vimeo address (VideoModel::parse_link())
 *   Category    optional, at most 50 characters  } the card's
 *   Duration    optional, mm:ss or h:mm:ss       } "Basics · 08:24" line
 *   Thumbnail   JPG / PNG / GIF / WebP up to 2 MB, best 640 × 360; optional for
 *               YouTube (its own thumbnail is shown), required for Vimeo
 * Deleting a video (or replacing / removing its thumbnail) removes the old file.
 *
 *   index          video/view_video_list
 *   add            video/view_video_form
 *   edit/<id>      video/view_video_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 *   bulk           POST, activate / deactivate / delete the selected rows
 *   sort           POST ord[<video_id>] = display order, rows of the current page
 */
class Video extends AdminController
{
    public const IMAGE_FOLDER = 'video';

    /**
     * Thumbnail size hint (the design's cards are 16:9).
     */
    public const BEST_SIZE = '640 × 360';

    /**
     * mm:ss or h:mm:ss, e.g. 08:24, 8:24, 1:02:15.
     */
    public const DURATION_PATTERN = '/^(?:[0-9]{1,2}:)?[0-5]?[0-9]:[0-5][0-9]$/D';

    protected string $base           = 'sitepanel/video';
    protected string $item           = 'Video';
    protected string $menu_highlight = 'video';

    protected VideoModel $video_model;
    protected ImageUpload $image_upload;

    protected function init(): void
    {
        $this->video_model  = new VideoModel();
        $this->image_upload = new ImageUpload();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->video_model->get_videos(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage Video Gallery';
        $data['breadcrumbs']   = ['Video Gallery' => null];
        $data['res']           = $res;
        $data['total']         = $this->video_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('video/view_video_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Video added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Video';
        $data['breadcrumbs']   = ['Video Gallery' => site_url($this->base), 'Add Video' => null];
        $data['res']           = ['video_title' => '', 'video_link' => '', 'video_category' => '', 'video_duration' => ''];
        $data['image']         = '';

        return $this->render('video/view_video_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->video_model->get_video_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Video updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Video';
        $data['breadcrumbs']   = ['Video Gallery' => site_url($this->base), 'Edit Video' => null];
        $data['res']           = $res;
        $data['image']         = $this->image_upload->path(self::IMAGE_FOLDER, $res['video_image']);

        return $this->render('video/view_video_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(VideoModel::TABLE, VideoModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record(VideoModel::TABLE, VideoModel::PRIMARY_KEY, $id, 'video_image', self::IMAGE_FOLDER);
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->video_model, 'get_videos_by_ids', 'Video', $this->list_query(), 'video_image', self::IMAGE_FOLDER);
    }

    /**
     * POST ord[<video_id>] => display order (0-9999) for the videos of one list page.
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
            $this->session->setFlashdata('sitepanel_error', 'Enter a display order from 0 to 9999 for each video.');
            $this->back($query);
        }

        $ids = array_keys($orders);
        if (! $this->selected_rows_match($this->video_model->get_videos_by_ids($ids), $ids)) {
            $this->back($query);
        }

        if ($this->video_model->update_sort_orders($orders)) {
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
        $v->set_rules('video_title', 'Title', 'trim|required|max_length[200]');
        $v->set_rules(
            'video_link',
            'Video link',
            ['trim', 'required', 'max_length[255]', ['video_link', static fn ($value) => VideoModel::parse_link((string) $value) !== null]],
            ['video_link' => 'The {field} must be a YouTube or Vimeo video address, e.g. https://www.youtube.com/watch?v=Fc-yl9_XXtI.'],
        );
        $v->set_rules('video_category', 'Category', 'trim|max_length[50]');
        $v->set_rules(
            'video_duration',
            'Duration',
            ['trim', 'max_length[8]', ['duration', static fn ($value) => (string) $value === '' || preg_match(self::DURATION_PATTERN, (string) $value) === 1]],
            ['duration' => 'The {field} must be minutes and seconds (08:24) or hours, minutes and seconds (1:02:15).'],
        );

        $errors = $v->run() ? [] : array_values($v->error_array());

        $link     = trim((string) $this->request->getPost('video_link'));
        $has_file = $this->image_upload->has_file('video_image');
        $remove   = $row && ! $has_file && $this->request->getPost('remove_image') === '1';
        $keeps    = $row && ! $remove && (string) $row['video_image'] !== '';
        if (! $errors && ! $has_file && ! $keeps && VideoModel::thumbnail_url($link) === '') {
            $errors[] = 'Upload a thumbnail: only YouTube videos can show their own.';
        }
        if ($errors) {
            return $errors;
        }

        $new_file = null;
        if ($has_file) {
            $upload = $this->image_upload->save('video_image', self::IMAGE_FOLDER, 'Thumbnail');
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }

        $now  = date('Y-m-d H:i:s');
        $data = [
            'video_title'        => trim((string) $this->request->getPost('video_title')),
            'video_link'         => $link,
            'video_category'     => $this->nullable('video_category'),
            'video_duration'     => $this->nullable('video_duration'),
            'video_date_updated' => $now,
        ];
        if ($new_file !== null || $remove) {
            $data['video_image'] = $new_file;
        }

        if ($row) {
            $saved = $this->video_model->safe_update(VideoModel::TABLE, $data, [VideoModel::PRIMARY_KEY => (int) $row['video_id']]);
        } else {
            $order = $this->video_model->next_sort_order();
            $saved = $order !== false && (bool) $this->video_model->safe_insert(VideoModel::TABLE, $data + [
                'sort_order'       => $order,
                'status'           => '1',
                'video_date_added' => $now,
            ]);
        }

        if (! $saved) {
            $new_file !== null && $this->image_upload->delete(self::IMAGE_FOLDER, $new_file);

            return ['Video could not be saved. Please try again.'];
        }

        if ($row && ($new_file !== null || $remove)) {
            $this->image_upload->delete(self::IMAGE_FOLDER, $row['video_image']);
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
