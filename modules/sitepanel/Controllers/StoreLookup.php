<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Lookup\Models\LookupModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Shared actions of the product attribute modules (Manage Entities >
 * Manage Colours / Sizes / Brands / Materials; the reference's Color, Size,
 * Brand and Material controllers): a name (unique), a display order and a
 * status; colours add a swatch (#rrggbb), brands an optional logo.
 * A value still used by a product can't be deleted (the reference checked
 * this for sizes and brands only); deactivating it hides it from the store
 * filters and from new product forms' "active" list.
 *
 *   index          store/attributes/attribute_list (keyword, status)
 *   add, edit/<id> store/attributes/attribute_form
 *   status/<id>    POST activate / deactivate
 *   delete/<id>    POST, refused while products use it
 *   bulk           POST, Active / Deactive / delete the selected rows
 */
abstract class StoreLookup extends AdminController
{
    /**
     * 'color' | 'size' | 'brand' | 'material' (column prefix and view switches).
     */
    protected const KIND = '';

    /**
     * Longest name.
     */
    protected const NAME_MAX = 32;

    /**
     * Upload folder of the optional image ('' = no image).
     */
    protected const IMAGE_FOLDER = '';

    /**
     * Plural noun for the list ("colours").
     */
    protected string $plural = '';

    protected LookupModel $model;

    abstract protected function make_model(): LookupModel;

    protected function init(): void
    {
        $this->model = $this->make_model();
    }

    public function index(): string
    {
        $keyword = $this->keyword_param();
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->model->get_list(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $usage = [];
        foreach (is_array($res) ? $res : [] as $row) {
            $id         = (int) $row[$this->model::PRIMARY_KEY];
            $usage[$id] = (int) $this->model->usage_count($id);
        }

        $data['heading_title'] = 'Manage ' . ucfirst($this->plural);
        $data['breadcrumbs']   = [ucfirst($this->plural) => null];
        $data['res']           = $res;
        $data['total']         = $this->model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;
        $data['usage']         = $usage;

        return $this->render('store/attributes/attribute_list', $data + $this->view_config());
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', $this->item . ' added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add ' . $this->item;
        $data['breadcrumbs']   = [ucfirst($this->plural) => site_url($this->base), 'Add ' . $this->item => null];
        $data['res']           = ['name' => '', 'color_code' => '#d71920', 'sort_order' => $this->model->next_sort_order(), 'status' => '1'];
        $data['image']         = '';

        return $this->render('store/attributes/attribute_form', $data + $this->view_config());
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $row = $this->record_or_back($this->model->get_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($row);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', $this->item . ' updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit ' . $this->item;
        $data['breadcrumbs']   = [ucfirst($this->plural) => site_url($this->base), 'Edit ' . $this->item => null];
        $data['res']           = [
            'id'         => (int) $row[$this->model::PRIMARY_KEY],
            'name'       => $row[$this->model::NAME_FIELD],
            'color_code' => $row['color_code'] ?? '',
            'sort_order' => $row['sort_order'],
            'status'     => $row['status'],
        ];
        $data['image'] = static::IMAGE_FOLDER !== '' ? (new ImageUpload())->path(static::IMAGE_FOLDER, $row[static::KIND . '_image'] ?? null) : '';

        return $this->render('store/attributes/attribute_form', $data + $this->view_config());
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status($this->model::TABLE, $this->model::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);
        $row   = $this->record_or_back($this->model->get_by_id($id));
        $error = $this->in_use_error([$row]);
        if ($error !== null) {
            $this->session->setFlashdata('sitepanel_error', $error);
            $this->back();
        }

        $this->delete_record($this->model::TABLE, $this->model::PRIMARY_KEY, $id, ...$this->image_args());
    }

    public function bulk(): void
    {
        $query = $this->list_query();

        if ($this->request->getPost('bulk_action') !== 'delete') {
            $this->process_bulk_action($this->model, 'get_by_ids', $this->item, $query);
        }

        $this->bulk_delete_checked($this->model, 'get_by_ids', $this->item, $query, fn (array $rows) => $this->in_use_error($rows), ...$this->image_args());
    }

    /**
     * Why these rows can't be deleted (used by products), or NULL.
     */
    protected function in_use_error(array $rows): ?string
    {
        foreach ($rows as $row) {
            $count = $this->model->usage_count((int) $row[$this->model::PRIMARY_KEY]);
            if ($count === false) {
                return 'The ' . strtolower($this->item) . ' could not be checked. Please try again.';
            }
            if ($count > 0) {
                return '"' . $row[$this->model::NAME_FIELD] . '" is used by ' . $count . ' product' . ($count === 1 ? '' : 's') . '. Remove it from those products first, or deactivate it instead.';
            }
        }

        return null;
    }

    /**
     * delete_record() / bulk_delete_checked() image arguments: [column, folder] or none.
     *
     * @return list<string>
     */
    protected function image_args(): array
    {
        return static::IMAGE_FOLDER !== '' ? [static::KIND . '_image', static::IMAGE_FOLDER] : [];
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $id = $row ? (int) $row[$this->model::PRIMARY_KEY] : 0;
        $v  = new FormValidation($this->request, $this);
        $v->set_rules('name', $this->item . ' name', 'trim|required|max_length[' . static::NAME_MAX . ']');
        if (static::KIND === 'color') {
            $v->set_rules('color_code', 'Colour code', 'trim|required|regex_match[/^#[0-9a-fA-F]{6}$/]', ['regex_match' => 'Enter the {field} as #rrggbb, e.g. #d71920.']);
        }
        $v->set_rules('sort_order', 'Display order', 'trim|required|is_natural|less_than_equal_to[999999]');
        $v->set_rules('status', 'Status', 'required|in_list[1,0]');
        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $post  = fn (string $field) => trim((string) $this->request->getPost($field));
        $taken = $this->model->name_taken($post('name'), $id);
        if ($taken === null) {
            return ['The name could not be checked. Please try again.'];
        }
        if ($taken) {
            return ['Another ' . strtolower($this->item) . ' is already called "' . $post('name') . '".'];
        }

        $images   = new ImageUpload();
        $new_file = null;
        if (static::IMAGE_FOLDER !== '' && $images->has_file('image')) {
            $upload = $images->save('image', static::IMAGE_FOLDER, $this->item . ' image', [2000, 2000]);
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }
        $remove_image = static::IMAGE_FOLDER !== '' && $row && $new_file === null && $this->request->getPost('remove_image') === '1';

        $data = [
            $this->model::NAME_FIELD   => $post('name'),
            'sort_order'               => (int) $post('sort_order'),
            'status'                   => $post('status'),
            $this->model::UPDATE_FIELD => date('Y-m-d H:i:s'),
        ];
        static::KIND === 'color' && $data['color_code'] = strtolower($post('color_code'));
        ($new_file !== null || $remove_image) && $data[static::KIND . '_image'] = $new_file;

        if ($row) {
            $saved = $this->model->safe_update($this->model::TABLE, $data, [$this->model::PRIMARY_KEY => $id]);
        } else {
            $data[$this->model::ADDED_FIELD] = $data[$this->model::UPDATE_FIELD];
            $saved                           = $this->model->safe_insert($this->model::TABLE, $data) !== false;
        }

        if (! $saved) {
            $new_file !== null && $images->delete(static::IMAGE_FOLDER, $new_file);

            return [$this->item . ' could not be saved. Please try again.'];
        }
        if ($row && ($new_file !== null || $remove_image)) {
            $images->delete(static::IMAGE_FOLDER, $row[static::KIND . '_image']);
        }

        return [];
    }

    /**
     * What the shared views need to know about this module.
     */
    protected function view_config(): array
    {
        return [
            'kind'      => static::KIND,
            'base'      => $this->base,
            'item'      => $this->item,
            'plural'    => $this->plural,
            'name_max'  => static::NAME_MAX,
            'has_image' => static::IMAGE_FOLDER !== '',
            'pk'        => $this->model::PRIMARY_KEY,
            'name_key'  => $this->model::NAME_FIELD,
        ];
    }
}
