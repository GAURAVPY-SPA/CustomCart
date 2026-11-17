<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Category\Models\CategoryModel;
use Modules\Products\Models\ProductModel;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\MetaModel;

/**
 * Sitepanel > Product Management > Manage Categories (wl_categories).
 *
 * The reference's sitepanel/category, page for page:
 *
 *   index/<parent_id>   store/category/category_list: one level (0 = top: "Category",
 *                       else "Subcategory" of <parent_id>), name search, status
 *                       filter, records per page, Name / Image / Display Order /
 *                       Current Status / Action, Subcategory [n] / Products [n] links
 *   add/<parent_id>     store/category/category_form: Name, Page URL (after the
 *                       parent's address), Image, Alt
 *   edit/<id>           store/category/category_form: the same, with View / Delete of the image
 *   bulk                POST from the list: Activate, Deactivate, Update Order
 *                       (ord[id]), Category Set As / Unset As (Home, Menu)
 *   sort                POST ord[id] (Update Order, also reachable on its own)
 *   status/<id>         POST activate / deactivate one category
 *   delete/<id>         POST, the list's delete icon
 *
 * Rules, as in the reference: Category Name required, at most 100
 * characters, unique among its siblings; Page URL made by the server from the
 * name (slug_of(), as the reference's seo_url_title(); page_url_of()): shown
 * read-only, never taken from the form, kept while the name is unchanged,
 * and given the next free -2, -3... suffix when its address is used by
 * another page; image JPG / PNG / GIF / WebP, at most 1 MB and
 * 2000 × 2000 pixels; Alt at most 100 characters (empty = the name). A new
 * category is active and last in display order; its page (wl_meta_tags row,
 * entity_type META_TYPE) gets the name as SEO data. A category with
 * sub-categories can't be deleted ("Please delete all subcategories first
 * before deleting this category."), nor one holding a product that is in no
 * other category; deleting removes its wl_meta_tags row too (delete_error(),
 * CategoryModel::bulk_delete()).
 *
 * Levels: at most CategoryModel::MAX_DEPTH (category › sub category › sub
 * sub category); a sub sub category can't get sub-categories (add/ and
 * index/ refuse it). A category's parent is chosen when it is added and
 * never changes, so no category can end up below itself.
 *
 * Addresses, as in the reference: no prefix, a category's address is its
 * parent's address then its own slug (<category>/<sub>/<sub-sub>); it must
 * not be used by any other page (wl_meta_tags.page_url: pages, products,
 * other categories) nor be reserved (MetaModel::reserved()).
 *
 * Kept from this project rather than the reference: a sub-category's Page URL
 * is shown after its parent's address and changing a category's Page URL moves
 * the addresses of the categories below it too (the reference flattened them);
 * activating / deactivating also switches its wl_meta_tags row (the reference did).
 */
class Category extends AdminController
{
    public const META_TYPE      = CategoryModel::META_TYPE;
    public const IMAGE_FOLDER   = 'category';
    public const IMAGE_MAX_SIZE = [2000, 2000];
    public const IMAGE_MAX_KB   = 1024;

    /**
     * The reference's image note (config category.best.image.view).
     */
    public const IMAGE_NOTE = '( File should be .jpg, .png, .gif format and file size should not be more then 1 MB (1024 KB)) ( Best image size 222X222)';

    /**
     * Records per page options (the reference's adminPageOpt).
     */
    public const PAGE_SIZES = [20, 40, 60, 80, 100, 120, 140, 160, 180, 200, 400];

    protected string $base           = 'sitepanel/category';
    protected string $item           = 'Category';
    protected string $menu_highlight = 'category';

    protected CategoryModel $category_model;
    protected MetaModel $meta_model;
    protected ImageUpload $image_upload;

    /**
     * Level to return to after a row action.
     */
    protected int $return_parent = 0;

    protected function init(): void
    {
        $this->category_model = new CategoryModel();
        $this->meta_model     = new MetaModel();
        $this->image_upload   = new ImageUpload();
    }

    /**
     * @param int|string $parent_id From the URL (index/<id>) or ?parent=
     */
    public function index($parent_id = 0): string
    {
        $parent_id = max(0, (int) ($parent_id ?: $this->request->getGet('parent')));
        $keyword   = $this->keyword_param();
        $status    = $this->status_param((string) $this->request->getGet('status'));
        $pagesize  = (int) $this->request->getGet('pagesize');
        $pagesize  = in_array($pagesize, self::PAGE_SIZES, true) ? $pagesize : self::PAGESIZE;
        $path      = [];

        if ($parent_id > 0) {
            $path = $this->category_model->path($parent_id);
            if (! $path) {
                $this->session->setFlashdata('sitepanel_error', 'That category no longer exists.');
                redirect_now($this->base);
            }
            // A sub sub category has no level below it (MAX_DEPTH).
            if (count($path) >= CategoryModel::MAX_DEPTH) {
                $this->session->setFlashdata('sitepanel_error', 'Categories go at most ' . CategoryModel::MAX_DEPTH . ' levels deep: a sub sub category has no subcategories.');
                redirect_now($this->list_uri((int) end($path)['parent_id']));
            }
        }

        // One level, also when searching (the reference: AND parent_id = <parent_id>).
        $page = $this->current_page();
        $res  = $this->category_model->get_level(($page - 1) * $pagesize, $pagesize, $parent_id, $keyword, $status);

        // Sub-categories and products of each listed category, counted separately (CategoryModel::list_counts()).
        $counts = $this->category_model->list_counts(is_array($res) ? array_column($res, 'category_id') : []) ?: [];

        $data['heading_title'] = $parent_id > 0 ? (count($path) >= 2 ? 'Sub Subcategory' : 'Subcategory') : 'Category';
        $data['breadcrumbs']   = $this->crumbs($path);
        // Level of the listed categories (1-3); the last level gets no Subcategory links.
        $data['level']         = count($path) + 1;
        $data['max_depth']     = CategoryModel::MAX_DEPTH;
        $data['res']           = $res;
        $data['total']         = $this->category_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->list_uri($parent_id), $data['total'], $pagesize);
        $data['keyword']       = $keyword;
        $data['status']        = $status;
        $data['pagesize']      = $pagesize;
        $data['parent_id']     = $parent_id;
        // The listing view uses the resolved ancestor chain to identify the
        // current level and build its parent-navigation link.
        $data['path']          = $path;
        $data['counts']        = $counts;

        return $this->render('store/category/category_list', $data);
    }

    /**
     * @param int|string $parent_id From the URL (add/<id>) or ?parent=
     */
    public function add($parent_id = 0): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $parent_id = max(0, (int) ($parent_id ?: $this->request->getGet('parent')));
        $parent    = null;
        if ($parent_id > 0) {
            $parent = $this->category_model->get_category_by_id($parent_id);
            if (! is_array($parent)) {
                $this->session->setFlashdata('sitepanel_error', 'That category no longer exists.');
                redirect_now($this->base);
            }
            // At most MAX_DEPTH levels: category › sub category › sub sub category.
            $allowed = $this->category_model->can_have_children($parent_id);
            if ($allowed !== true) {
                $this->session->setFlashdata('sitepanel_error', $allowed === null
                    ? 'The category could not be checked. Please try again.'
                    : '"' . $parent['category_name'] . '" is a sub sub category: categories go at most ' . CategoryModel::MAX_DEPTH . ' levels deep.');
                redirect_now($this->list_uri((int) $parent['parent_id']));
            }
        }
        $this->return_parent = $parent_id;
        $data['errors']      = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null, $parent);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Record has been added successfully.');
                redirect_now($this->list_uri($parent_id));
            }
        }

        $data['heading_title'] = $parent ? ((int) $parent['parent_id'] > 0 ? 'Add Sub Subcategory' : 'Add Subcategory') : 'Add Category';
        $data['breadcrumbs']   = ['Back To Listing' => site_url($this->list_uri($parent_id)), $data['heading_title'] => null];
        $data['res']           = [
            'category_name'        => '',
            // Read-only preview of the generated segment (the form never sends one).
            'friendly_url'         => self::slug_of(trim((string) $this->request->getPost('category_name'))),
            'category_alt'         => '',
            'category_description' => '',
            'category_image'       => null,
            'parent_id'            => $parent_id,
        ];
        $data['parent']        = $parent;
        $data['image']         = '';
        $data['back_link']     = $this->list_uri($parent_id);
        $data['action']        = 'sitepanel/category/add' . ($parent_id > 0 ? '/' . $parent_id : '');
        $data['note']          = self::IMAGE_NOTE;

        return $this->render('store/category/category_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->category_model->get_category_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $parent              = (int) $res['parent_id'] > 0 ? $this->category_model->get_category_by_id((int) $res['parent_id']) : null;
        $this->return_parent = (int) $res['parent_id'];
        $data['errors']      = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res, is_array($parent) ? $parent : null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Record has been updated successfully.');
                redirect_now($this->list_uri((int) $res['parent_id']));
            }
        }

        // Read-only: the category's own segment (after a failed save, the one its new name would get).
        $data['saved_segment'] = self::last_segment($res['friendly_url']);
        $posted_name         = trim((string) $this->request->getPost('category_name'));
        $res['friendly_url'] = $posted_name !== '' && $posted_name !== $res['category_name'] ? self::slug_of($posted_name) : self::last_segment($res['friendly_url']);

        $data['heading_title'] = (int) $res['parent_id'] > 0 ? 'Subcategory' : 'Category';
        $data['breadcrumbs']   = ['Back To Listing' => site_url($this->list_uri((int) $res['parent_id'])), $data['heading_title'] => null];
        $data['res']           = $res;
        $data['parent']        = is_array($parent) ? $parent : null;
        $data['image']         = $this->image_upload->path(self::IMAGE_FOLDER, $res['category_image']);
        $data['back_link']     = $this->list_uri((int) $res['parent_id']);
        $data['action']        = 'sitepanel/category/edit/' . (int) $res['category_id'];
        $data['note']          = self::IMAGE_NOTE;

        return $this->render('store/category/category_form', $data);
    }

    /**
     * POST ord[category_id] = position: the display order of one level (Update Order).
     */
    public function sort(): void
    {
        $this->require_post();
        $this->return_parent = max(0, (int) $this->request->getPost('parent'));
        $this->save_order();
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->require_post();
        $active = ($this->request->getPost('status') === '1');
        $this->require_privilege($active ? AdminAccess::PRIV_ACTIVATE : AdminAccess::PRIV_DEACTIVATE);
        $row                 = $this->record_or_back($this->category_model->get_category_by_id($id));
        $this->return_parent = (int) $row['parent_id'];

        // Through the model, so the page's wl_meta_tags row follows (as the reference did).
        if ($this->category_model->bulk_update_status([(int) $row['category_id']], $active ? '1' : '0')) {
            $this->session->setFlashdata('sitepanel_success', 'Category ' . $this->status_words[$active ? 0 : 1] . '.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'Category could not be updated. Please try again.');
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
        $row                 = $this->record_or_back($this->category_model->get_category_by_id($id));
        $this->return_parent = (int) $row['parent_id'];

        $error = $this->delete_error([$row]);
        if ($error !== null) {
            $this->session->setFlashdata('sitepanel_error', $error);
            $this->back();
        }

        // The category, its wl_meta_tags row and its products' assignments, in one transaction (CategoryModel::bulk_delete()).
        if ($this->category_model->bulk_delete([(int) $row['category_id']])) {
            $this->image_upload->delete(self::IMAGE_FOLDER, $row['category_image']);
            $this->session->setFlashdata('sitepanel_success', '"' . $row['category_name'] . '" has been deleted.');
        } else {
            $this->session->setFlashdata('sitepanel_error', '"' . $row['category_name'] . '" could not be deleted, so nothing was changed. Please try again.');
        }

        $this->back();
    }

    /**
     * The list form (the reference's data_form): bulk_action = activate |
     * deactivate | order (Update Order) | flag (Category Set As / Unset As) | delete.
     */
    public function bulk(): void
    {
        $this->require_post();
        $this->return_parent = max(0, (int) $this->request->getPost('parent'));
        $query               = array_filter([
            'keyword'  => $this->keyword_param((string) $this->request->getPost('keyword')),
            'status'   => $this->status_param((string) $this->request->getPost('status_filter')),
            'pagesize' => in_array((int) $this->request->getPost('pagesize'), self::PAGE_SIZES, true) ? (string) (int) $this->request->getPost('pagesize') : '',
        ], 'strlen');

        // Each action redirects when done.
        $action = (string) $this->request->getPost('bulk_action');
        if ($action === 'order') {
            $this->save_order($query);
        } elseif ($action === 'flag') {
            $this->set_flag($query);
        } elseif ($action === 'delete') {
            $this->bulk_delete_checked($this->category_model, 'get_categories_by_ids', 'Category', $query, fn (array $rows) => $this->delete_error($rows), 'category_image', self::IMAGE_FOLDER);
        } else {
            $this->process_bulk_action($this->category_model, 'get_categories_by_ids', 'Category', $query);
        }
    }

    /**
     * Save ord[id] = position of the listed categories.
     */
    protected function save_order(array $query = []): never
    {
        $this->require_privilege(AdminAccess::PRIV_EDIT);

        $orders = [];
        $posted = $this->request->getPost('ord');
        foreach (is_array($posted) ? $posted : [] as $id => $position) {
            if (preg_match('/^[1-9][0-9]*$/D', (string) $id) && is_string($position) && preg_match('/^[0-9]{1,6}$/D', trim($position))) {
                $orders[(int) $id] = (int) $position;
            }
        }

        $rows = $orders ? $this->category_model->get_categories_by_ids(array_keys($orders)) : [];
        if (! $orders || ! is_array($rows) || count($rows) !== count($orders) || count($orders) !== count((array) $posted)) {
            $this->session->setFlashdata('sitepanel_error', 'Enter a whole number as the display order of each category.');
            $this->back($query);
        }

        if ($this->category_model->save_sort_order($orders)) {
            $this->session->setFlashdata('sitepanel_success', 'Display order has been updated successfully.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'The display order could not be saved. Please try again.');
        }
        $this->back($query);
    }

    /**
     * "Category Set As" / "Category Unset As" (the reference's set_as /
     * unset_as: Home, Menu) on the selected rows. The CI4 combined flag
     * format remains supported, as do the CI3 set_as / unset_as fields.
     */
    protected function set_flag(array $query): never
    {
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $flag_value = trim((string) $this->request->getPost('flag'));
        if ($flag_value === '') {
            $set_as = trim((string) $this->request->getPost('set_as'));
            $unset_as = trim((string) $this->request->getPost('unset_as'));
            if ($set_as === '') {
                $set_as = trim((string) $this->request->getPost('set_as_top'));
            }
            if ($unset_as === '') {
                $unset_as = trim((string) $this->request->getPost('unset_as_top'));
            }
            if ($set_as !== '' && $unset_as === '') {
                $flag_value = 'is_' . $set_as . ':1';
            } elseif ($unset_as !== '' && $set_as === '') {
                $flag_value = 'is_' . $unset_as . ':0';
            }
        }
        [$flag, $value] = array_pad(explode(':', $flag_value, 2), 2, '');
        if (! isset(CategoryModel::FLAGS[$flag]) || ! in_array($value, ['1', '0'], true)) {
            $this->session->setFlashdata('sitepanel_error', 'Choose what to set or unset the selected categories as.');
            $this->back($query);
        }

        $ids = $this->selected_ids();
        if ($ids === false) {
            $this->session->setFlashdata('sitepanel_error', 'Select one or more valid records on this page.');
            $this->back($query);
        }
        if (! $this->selected_rows_match($this->category_model->get_categories_by_ids($ids), $ids)) {
            $this->back($query);
        }

        if ($this->category_model->bulk_set_flag($ids, $flag, $value === '1')) {
            $count = count($ids);
            $this->session->setFlashdata('sitepanel_success', $count . ' ' . ($count === 1 ? 'category' : 'categories') . ($value === '1' ? ' set as ' : ' unset as ') . CategoryModel::FLAGS[$flag] . '.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'The categories could not be changed. Please try again.');
        }

        $this->back($query);
    }

    /**
     * Why these categories can't be deleted, or NULL (single and bulk delete;
     * nothing is changed here — CategoryModel::bulk_delete() deletes them
     * with their wl_meta_tags rows in one transaction):
     *  - one has sub-categories (its direct children in wl_categories);
     *  - a product is filed only in them (it would be left without a category;
     *    products also filed elsewhere just lose these categories).
     */
    protected function delete_error(array $rows): ?string
    {
        $ids = array_map(static fn (array $row) => (int) $row['category_id'], $rows);

        foreach ($rows as $row) {
            $children = $this->category_model->child_count((int) $row['category_id']);
            if ($children === false) {
                return 'The category could not be checked. Please try again.';
            }
            if ($children > 0) {
                return (count($rows) > 1 ? '"' . $row['category_name'] . '": ' : '') . 'Please delete all subcategories first before deleting this category.';
            }
        }

        $only_here = (new ProductModel())->products_only_in($ids);
        if ($only_here === false) {
            return 'The category\'s products could not be checked. Please try again.';
        }
        if ($only_here) {
            $count = count($only_here);
            $names = array_slice($only_here, 0, 3);

            return $count . ' product' . ($count === 1 ? ' is' : 's are') . ' in no other category ("' . implode('", "', $names) . '"' . ($count > 3 ? ', …' : '')
                . '). Assign ' . ($count === 1 ? 'it' : 'them') . ' to another category or delete ' . ($count === 1 ? 'it' : 'them') . ' first.';
        }

        return null;
    }

    /**
     * Validate and save the add / edit form.
     *
     * @param array|null $row    NULL when adding
     * @param array|null $parent The parent category (NULL = top level)
     *
     * @return array<string, string> field => error (empty = saved)
     */
    protected function save(?array $row, ?array $parent): array
    {
        $parent_id = $parent ? (int) $parent['category_id'] : 0;
        $id        = $row ? (int) $row['category_id'] : 0;

        // No rule for friendly_url: the Page URL is never taken from the form (see page_url_of()).
        $v = new FormValidation($this->request, $this);
        $v->set_rules('category_name', 'Category Name', 'trim|required|max_length[100]');
        $v->set_rules('category_alt', 'Alt', 'trim|max_length[100]');
        $errors = $v->run() ? [] : $v->error_array();

        $post = fn (string $field) => trim((string) $this->request->getPost($field));
        $name = $post('category_name');

        if (! isset($errors['category_name'])) {
            $taken = $this->category_model->name_taken($name, $parent_id, $id);
            if ($taken === null) {
                return ['category_name' => 'The Category Name could not be checked. Please try again.'];
            }
            $taken && $errors['category_name'] = 'The Category Name field must contain a unique value.';
        }

        // The Page URL: generated from the name (unchanged name = unchanged address), with the
        // next free suffix when taken; plus the addresses of everything below when it changes.
        $url   = $row ? (string) $row['friendly_url'] : '';
        $moves = [];
        if (! $errors) {
            $address = $this->page_url_of($name, $row, $parent);
            if (isset($address['error'])) {
                $errors['friendly_url'] = $address['error'];
            } else {
                [$url, $moves] = [$address['url'], $address['moves']];
            }
        }

        $has_file = $this->image_upload->has_file('category_image');
        if ($has_file) {
            $file = $this->request->getFile('category_image');
            if ($file && $file->isValid() && $file->getSize() / 1024 > self::IMAGE_MAX_KB) {
                $errors['category_image'] = 'Image: the file size should not be more then 1 MB (1024 KB).';
            }
        }
        if ($errors) {
            return $errors;
        }

        $new_file = null;
        if ($has_file) {
            $upload = $this->image_upload->save('category_image', self::IMAGE_FOLDER, 'Image', self::IMAGE_MAX_SIZE);
            if (isset($upload['error'])) {
                return ['category_image' => $upload['error']];
            }
            $new_file = $upload['file'];
        }
        $remove_image = $row && $new_file === null && $this->request->getPost('cat_img_delete') === 'Y';

        $data = [
            'category_name' => $name,
            'friendly_url'  => $url,
            'category_alt'  => $post('category_alt') !== '' ? $post('category_alt') : $name,
            'date_modified' => date('Y-m-d H:i:s'),
        ];
        ($new_file !== null || $remove_image) && $data['category_image'] = $new_file;

        if ($row) {
            $saved = $this->category_model->safe_update(CategoryModel::TABLE, $data, [CategoryModel::PRIMARY_KEY => $id]);
        } else {
            $id    = $this->category_model->safe_insert(CategoryModel::TABLE, $data + [
                'parent_id'  => $parent_id,
                'sort_order' => $this->category_model->next_sort_order($parent_id),
                'status'     => '1',
                'date_added' => $data['date_modified'],
            ]);
            $saved = ($id !== false);
        }

        if (! $saved) {
            $new_file !== null && $this->image_upload->delete(self::IMAGE_FOLDER, $new_file);

            return ['category_name' => 'Category could not be saved. Please try again.'];
        }
        if ($row && ($new_file !== null || $remove_image)) {
            $this->image_upload->delete(self::IMAGE_FOLDER, $row['category_image']);
        }

        // The category's wl_meta_tags row is its page address: a new category
        // gets the name as SEO data (the reference's create_meta()); an edited
        // one keeps its SEO data (Meta Tags Management) and gets the new address.
        $ok = $this->save_category_meta($id, $url, $name);

        if ($row && $url !== $row['friendly_url']) {
            $ok = $this->move_descendants($moves, $id) && $ok;
        }
        if (! $ok) {
            $this->session->setFlashdata('sitepanel_error', 'The category was saved, but its page address could not be fully updated. Please edit it again.');
        }

        return [];
    }

    /**
     * New addresses of every category below one whose address changed: category_id => url.
     */
    protected function moved_urls(string $old_url, string $new_url, int $id): array
    {
        $moves = [];
        $all   = $this->category_model->get_all_categories();

        foreach ((array) $this->category_model->descendant_ids($id) as $cid) {
            $current     = (string) ($all[$cid]['friendly_url'] ?? '');
            $moves[$cid] = str_starts_with($current, $old_url . '/')
                ? $new_url . substr($current, strlen($old_url))
                : $new_url . '/' . self::last_segment($current);
        }

        return $moves;
    }

    /**
     * Most characters of a category's own Page URL segment.
     */
    public const SLUG_MAX = 100;

    /**
     * A Page URL segment from a category name (the reference's
     * seo_url_title()): lower case, apostrophes dropped ("Men's" => "mens"),
     * every other run of non-letters / digits one hyphen, no hyphen at
     * either end; "Books & Media" => "books-media".
     */
    public static function slug_of(string $name, int $max = self::SLUG_MAX): string
    {
        return sp_page_slug($name, $max);
    }

    /**
     * The Page URL of a category being saved, always made here from its name
     * (a posted friendly_url is ignored):
     *  - edit, name unchanged: the current address, untouched;
     *  - otherwise: slug_of(name) after the parent's address, or the first
     *    free one of slug-2, slug-3... — free meaning the full address (and,
     *    when it moves, every address below it) is used by no other
     *    category, product or page in wl_meta_tags and is not reserved.
     * Only this category's own segment is generated; its parent's address
     * stays in front, so the 3-level hierarchy is kept.
     *
     * @return array{url: string, moves: array<int, string>}|array{error: string}
     */
    protected function page_url_of(string $name, ?array $row, ?array $parent): array
    {
        $id = $row ? (int) $row['category_id'] : 0;
        if ($row && $name === (string) $row['category_name']) {
            return ['url' => (string) $row['friendly_url'], 'moves' => []];
        }

        $base = self::slug_of($name);
        if ($base === '') {
            return ['error' => 'The Category Name needs at least one letter or number to make its Page URL.'];
        }

        $prefix = $parent ? $parent['friendly_url'] . '/' : CategoryModel::URL_PREFIX;
        for ($n = 1; $n <= 1000; $n++) {
            $suffix = $n === 1 ? '' : '-' . $n;
            $url    = $prefix . rtrim(substr($base, 0, self::SLUG_MAX - strlen($suffix)), '-') . $suffix;
            if ($row && $url === $row['friendly_url']) {
                return ['url' => $url, 'moves' => []]; // its own address already
            }

            $moves = [$id => $url] + ($row ? $this->moved_urls($row['friendly_url'], $url, $id) : []);
            $kind  = null;
            $error = $this->addresses_error($moves, $id, $kind);
            if ($error === null) {
                return ['url' => $url, 'moves' => $moves];
            }
            if ($kind !== 'taken') {
                return ['error' => $error];
            }
        }

        return ['error' => 'No free Page URL could be made from this name. Please choose another Category Name.'];
    }

    /**
     * Why one of the new addresses can't be used, or NULL ("URL already exists.", as the reference's checkurl()).
     * $kind: 'taken' (used or reserved: another suffix may do), 'long' or 'error'.
     *
     * @param array<int, string> $moves category_id (0 = new) => address
     */
    protected function addresses_error(array $moves, int $id, ?string &$kind = null): ?string
    {
        foreach ($moves as $cid => $url) {
            if (strlen($url) > 200) {
                $kind = 'long';

                return 'The address "' . $url . '" would be longer than 200 characters. Use a shorter Category Name.';
            }
            if (MetaModel::reserved($url)) {
                $kind = 'taken';

                return 'The address "' . $url . '" is reserved by the website. Choose another Category Name.';
            }
            $taken = $this->category_model->url_taken($url, $cid);
            if ($taken === null) {
                $kind = 'error';

                return 'The Page URL could not be checked. Please try again.';
            }
            $other = $taken ? null : $this->meta_model->get_by_page_url($url);
            if ($other === false) {
                $kind = 'error';

                return 'The Page URL could not be checked. Please try again.';
            }
            $own = is_array($other) && $other['entity_type'] === self::META_TYPE && (int) $other['entity_id'] === $cid && $cid > 0;
            // Another row may hold the address only when it is one of these categories' own rows (they all move together).
            // A new category is $moves[0]: entity_id 0 is a module page's row (online-store, cart...), never a category's.
            $moving = is_array($other) && $other['entity_type'] === self::META_TYPE && (int) $other['entity_id'] > 0 && isset($moves[(int) $other['entity_id']]);
            if ($taken || (is_array($other) && ! $own && ! $moving)) {
                $kind = 'taken';

                return $cid === $id ? 'URL already exists.' : 'URL already exists (the address "' . $url . '" of a subcategory).';
            }
        }

        return null;
    }

    /**
     * Save the new addresses of the categories below one whose address
     * changed, and their SEO rows' page_url. (Products keep their
     * category_links: no category changes parent here.)
     *
     * @param array<int, string> $moves category_id => new address (the edited one included)
     */
    protected function move_descendants(array $moves, int $id): bool
    {
        $ok  = true;
        $all = $this->category_model->get_all_categories();

        foreach ($moves as $cid => $url) {
            if ($cid === $id) {
                continue;
            }
            $ok = $this->category_model->safe_update(CategoryModel::TABLE, ['friendly_url' => $url], [CategoryModel::PRIMARY_KEY => $cid]) && $ok;
            $ok = $this->save_category_meta($cid, $url, (string) ($all[$cid]['category_name'] ?? '')) && $ok;
        }

        return $ok;
    }

    /**
     * Save a category's wl_meta_tags row with its page address $url, keeping
     * the SEO fields already set there (Meta Tags Management); empty or
     * missing ones become the category name.
     *
     * @return bool FALSE when the row could not be read or saved
     */
    protected function save_category_meta(int $id, string $url, string $name): bool
    {
        $meta = $this->meta_model->get_entity_meta(self::META_TYPE, $id);
        if ($meta === false) {
            return false;
        }

        return $this->meta_model->save_entity_meta(self::META_TYPE, $id, $url, [
            'meta_title'       => is_array($meta) && $meta['meta_title'] !== '' ? $meta['meta_title'] : $name,
            'meta_keyword'     => is_array($meta) && $meta['meta_keyword'] !== '' ? $meta['meta_keyword'] : $name,
            'meta_description' => is_array($meta) && $meta['meta_description'] !== '' ? $meta['meta_description'] : $name,
        ]);
    }

    /**
     * List breadcrumbs (the reference: Home / Category, or the category path).
     */
    protected function crumbs(array $path): array
    {
        if (! $path) {
            return ['Category' => null];
        }

        $crumbs = ['Category' => site_url($this->base)];

        foreach (array_values($path) as $i => $category) {
            $crumbs[$category['category_name']] = $i === count($path) - 1 ? null : site_url($this->list_uri((int) $category['category_id']));
        }

        return $crumbs;
    }

    /**
     * The list page of a level.
     */
    protected function list_uri(int $parent_id): string
    {
        return $this->base . ($parent_id > 0 ? '/index/' . $parent_id : '');
    }

    /**
     * Back to the level the action was started from.
     */
    protected function back(array $query = []): never
    {
        $page  = (int) $this->request->getPost('page');
        $query = $page > 1 ? ['page' => $page] + $query : $query;
        redirect_now($this->list_uri($this->return_parent) . ($query ? '?' . http_build_query($query) : ''));
    }

    protected static function last_segment(string $url): string
    {
        $pos = strrpos($url, '/');

        return $pos === false ? $url : substr($url, $pos + 1);
    }
}
