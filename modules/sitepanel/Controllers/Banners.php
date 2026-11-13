<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Config\Sitepanel as SitepanelConfig;
use Modules\Sitepanel\Libraries\AdminAccess;
use App\Libraries\ImageUpload;
use Modules\Sitepanel\Models\BannerModel;

/**
 * Sitepanel > Other Management > Manage Banners (wl_banners).
 *
 * As in the reference module: a position (required, from
 * Config\Sitepanel::$bannerPositions), an image (required when adding), banner text of up to
 * 30 characters and an optional link. The list can be filtered by position;
 * rows can be activated, deactivated and deleted.
 *
 *   index          banner/view_banner_list
 *   add            banner/view_banner_add
 *   edit/<id>      banner/view_banner_edit
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 */
class Banners extends AdminController
{
    public const IMAGE_FOLDER = 'banner';

    protected string $base           = 'sitepanel/banners';
    protected string $item           = 'Banner';
    protected string $menu_highlight = 'banners';

    /**
     * position => size hint
     */
    protected array $positions = [];

    protected BannerModel $banner_model;
    protected ImageUpload $image_upload;

    protected function init(): void
    {
        $this->banner_model = new BannerModel();
        $this->image_upload = new ImageUpload();
        $this->positions    = config(SitepanelConfig::class)->bannerPositions;
    }

    public function index(): string
    {
        $position = (string) $this->request->getGet('position');
        if (! isset($this->positions[$position])) {
            $position = '';
        }

        $page = $this->current_page();
        $res  = $this->banner_model->get_banner(($page - 1) * self::PAGESIZE, self::PAGESIZE, $position);

        $data['heading_title'] = 'Manage Banners';
        $data['breadcrumbs']   = ['Banners' => null];
        $data['res']           = $res;
        $data['total']         = $this->banner_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['positions']     = $this->positions;
        $data['position']      = $position;

        return $this->render('banner/view_banner_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Banner added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Banner';
        $data['breadcrumbs']   = ['Banners' => site_url($this->base), 'Add Banner' => null];
        $data['positions']     = $this->positions;
        $data['categories']    = $this->category_options();

        return $this->render('banner/view_banner_add', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->banner_model->get_banner_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Banner updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Banner';
        $data['breadcrumbs']   = ['Banners' => site_url($this->base), 'Edit Banner' => null];
        $data['res']           = $res;
        $data['positions']     = $this->positions;
        $data['categories']    = $this->category_options();
        $data['image']         = $this->image_upload->path(self::IMAGE_FOLDER, $res['banner_image']);

        return $this->render('banner/view_banner_edit', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status('wl_banners', 'banner_id', $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record('wl_banners', 'banner_id', $id, 'banner_image', self::IMAGE_FOLDER);
    }

    public function bulk(): void
    {
        $position = (string) $this->request->getPost('position');
        $position = isset($this->positions[$position]) ? $position : '';
        $this->process_bulk_action($this->banner_model, 'get_banners_by_ids', 'Banner', ['position' => $position], 'banner_image', self::IMAGE_FOLDER);
    }

    /**
     * Categories for a Category Pages banner: category_id => path name.
     *
     * @return array<int, string>
     */
    protected function category_options(): array
    {
        static $options = null;
        if ($options === null) {
            $rows    = (new \Modules\Category\Models\CategoryModel())->options(0, false);
            $options = is_array($rows) ? $rows : [];
        }

        return $options;
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules(
            'banner_position',
            'Banner position',
            ['required', 'in_list[' . implode(',', array_keys($this->positions)) . ']'],
            ['in_list' => 'Choose a valid %s.'],
        );
        $v->set_rules('banner_title', 'Banner text', 'trim|max_length[30]');
        $category = (int) $this->request->getPost('category_id');
        $v->set_rules('sort_order', 'Display order', 'permit_empty|is_natural|less_than_equal_to[9999]');
        if ($this->request->getPost('banner_position') === BannerModel::CATEGORY_POSITION) {
            $v->set_rules('category_id', 'Category', ['required', ['valid_category', fn ($value) => isset($this->category_options()[(int) $value])]], ['required' => 'Choose the category of this banner.', 'valid_category' => 'Choose a valid category.']);
        }
        $v->set_rules(
            'banner_url',
            'Link URL',
            ['trim', 'max_length[255]', ['valid_web_url', 'sp_is_web_url']],
            ['valid_web_url' => 'The %s field must be a complete web address starting with http:// or https://.'],
        );

        $errors = $v->run() ? [] : array_values($v->error_array());

        $has_file = $this->image_upload->has_file('banner_image');
        if (! $row && ! $has_file) {
            $errors[] = 'Choose a banner image to upload.';
        }

        if ($errors) {
            return $errors;
        }

        $new_file = null;
        if ($has_file) {
            $upload = $this->image_upload->save('banner_image', self::IMAGE_FOLDER, 'Banner image');
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }

        $data = [
            'banner_position' => (string) $this->request->getPost('banner_position'),
            'banner_title'    => $this->nullable('banner_title'),
            'banner_url'      => $this->nullable('banner_url'),
            'category_id'     => $this->request->getPost('banner_position') === BannerModel::CATEGORY_POSITION && $category > 0 ? $category : null,
            'sort_order'      => (int) $this->request->getPost('sort_order'),
        ];
        if ($new_file !== null) {
            $data['banner_image'] = $new_file;
        }

        if ($row) {
            $saved = $this->banner_model->safe_update('wl_banners', $data, ['banner_id' => (int) $row['banner_id']]);
        } else {
            $data += ['status' => '1', 'banner_added_date' => date('Y-m-d H:i:s')];
            $saved = (bool) $this->banner_model->safe_insert('wl_banners', $data);
        }

        if (! $saved) {
            $new_file !== null && $this->image_upload->delete(self::IMAGE_FOLDER, $new_file);

            return ['Banner could not be saved. Please try again.'];
        }

        if ($row && $new_file !== null) {
            $this->image_upload->delete(self::IMAGE_FOLDER, $row['banner_image']);
        }

        return [];
    }
}
