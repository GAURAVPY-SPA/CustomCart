<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\HtmlSanitizer;
use App\Libraries\ImageUpload;
use Config\Shop;
use Modules\Brand\Models\BrandModel;
use Modules\Category\Models\CategoryModel;
use Modules\Color\Models\ColorModel;
use Modules\Material\Models\MaterialModel;
use Modules\Products\Models\ProductModel;
use Modules\Size\Models\SizeModel;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\MetaModel;

/**
 * Sitepanel > Product Management > Manage Products (wl_products and its
 * colours, sizes, stock matrix, photos and related products).
 *
 * As in the reference (sitepanel/products): category (any level), brand,
 * material, name, unique product code, URL slug, prices, description and
 * specification (CKEditor, cleaned by HtmlSanitizer), weight, flags (New,
 * Hot, Featured, Popular), colours and sizes, status and SEO. Rules:
 *  - Product name required (≤ 200), product code required (≤ 64) and unique;
 *  - URL slug unique (built from the name when empty); the page address is
 *    products/<slug>, the product's wl_meta_tags row (entity_type META_TYPE);
 *  - Price required and above 0; a discounted price must be below the price.
 * Saving the colours and sizes keeps the stock matrix in line (one row per
 * colour × size, ProductModel::save_variants()); the Stock page sets each
 * row's own price / discounted price (0 = the product's), available quantity
 * and low-stock threshold (the reference's update_stock). A new product goes
 * to its Stock page next, as its quantities start at 0.
 *
 * Categories: a product is filed in one or more categories (category_ids[],
 * wl_product_categories); the product and its categories are saved in one
 * transaction (ProductModel::save_product()).
 *
 * Optional features (Config\Shop::$productFeatures: brand, material, colours,
 * sizes, weight, button type, labels, stock, reviews) that are off are left
 * out of the form, the list and the validation, and a product's existing
 * values for them are never overwritten. With 'stock' off the form has one
 * "Available quantity" (the product's single stock row); a product that
 * already has colour / size options keeps its Stock page instead.
 *
 *   index          store/products/product_list (keyword, status, category, brand, stock)
 *   add, edit/<id> store/products/product_form
 *   stock/<id>     store/products/product_stock (POST saves the matrix)
 *   media/<id>     store/products/product_media (photos: upload, colour, order, default, delete)
 *   related/<id>   store/products/product_related (up to MAX_RELATED products)
 *   status/<id>    POST activate / deactivate
 *   delete/<id>    POST: the product, its SEO row, and its photos (files kept while an order uses them)
 *   bulk           POST, Active / Deactive / delete the selected rows
 */
class Products extends AdminController
{
    public const META_TYPE      = ProductModel::META_TYPE;
    public const IMAGE_FOLDER   = 'products';
    public const IMAGE_MAX_SIZE = [4000, 4000];
    public const NEW_PHOTOS     = 6;
    public const MAX_PHOTOS     = 12;
    public const MAX_RELATED    = 12;
    public const PAGE_SIZES     = [20, 40, 60, 80, 100, 120, 140, 160, 180, 200, 400];
    public const FLAGS          = ['is_new' => 'New', 'is_hot' => 'Sale / Hot', 'is_featured' => 'Featured', 'is_popular' => 'Popular'];
    public const BUTTON_TYPES   = ['1' => 'Add To Cart', '2' => 'Buy Now', '3' => 'Send Enquiry', '4' => 'All'];
    public const BULK_SET_AS    = ['is_recently_added' => 'Recently Added'];
    public const BULK_UNSET_AS  = ['is_recently_added' => 'Recently Added'];
    public const SLUG_PATTERN   = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    public const AMOUNT_PATTERN = '/^[0-9]{1,8}(\.[0-9]{1,2})?$/D';

    protected string $base           = 'sitepanel/products';
    protected string $item           = 'Product';
    protected string $menu_highlight = 'products';

    protected ProductModel $product_model;
    protected CategoryModel $category_model;
    protected MetaModel $meta_model;
    protected ImageUpload $image_upload;
    protected Shop $shop;

    protected function init(): void
    {
        $this->product_model  = new ProductModel();
        $this->category_model = new CategoryModel();
        $this->meta_model     = new MetaModel();
        $this->image_upload   = new ImageUpload();
        $this->shop           = config(Shop::class);
        helper('shop');
    }

    public function index(): string
    {
        $filters = $this->list_filters($this->request->getGet());
        $page    = $this->current_page();
        $pagesize = $filters['pagesize'] !== '' ? (int) $filters['pagesize'] : self::PAGESIZE;
        $res     = $this->product_model->get_products(($page - 1) * $pagesize, $pagesize, $filters['keyword'], $filters['status'],
            (int) $filters['category'], (int) $filters['brand'], $filters['stock']);

        $data['heading_title'] = 'Manage Products';
        $data['breadcrumbs']   = ['Products' => null];
        $data['res']           = $res;
        $data['total']         = $this->product_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], $pagesize);
        $data['pagesize']      = $pagesize;
        $ids                   = is_array($res) ? array_map('intval', array_column($res, 'products_id')) : [];
        $data['filters']       = $filters;
        $data['categories']    = $this->category_model->options() ?: [];
        $data['brands']        = $this->shop->feature('brand') ? ((new BrandModel())->get_options() ?: []) : [];
        $data['low_stock']     = $this->low_stock_ids($ids);
        $data['product_categories'] = $this->product_model->get_category_ids_by_products($ids) ?: [];
        $data['variant_ids']   = (array) $this->product_model->variant_product_ids($ids);
        $data['features']      = $this->shop->productFeatures;

        return $this->render('store/products/product_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $result = $this->save(null);
            if (is_int($result)) {
                if ($this->shop->feature('stock')) {
                    $this->session->setFlashdata('sitepanel_success', 'Product added. Now set the available quantity of each option below — new products start with no stock.');
                    redirect_now($this->base . '/stock/' . $result);
                }
                $this->session->setFlashdata('sitepanel_success', 'Product added.');
                redirect_now($this->base);
            }
            $data['errors'] = $result;
        }

        $data['heading_title'] = 'Add Product';
        $data['breadcrumbs']   = ['Products' => site_url($this->base), 'Add Product' => null];
        $data['res']           = [
            'category_id' => (int) $this->request->getGet('category'), 'brand_id' => '', 'material_id' => '', 'product_name' => '', 'product_code' => '',
            'friendly_url' => '', 'product_alt' => '', 'product_price' => '', 'product_discounted_price' => '', 'product_weight' => '',
            'products_description' => '', 'products_specification' => '', 'is_new' => '0', 'is_hot' => '0', 'is_featured' => '0', 'is_popular' => '0',
            'status' => '1', 'is_show_button' => '4', 'meta_title' => '', 'meta_keyword' => '', 'meta_description' => '', 'product_quantity' => '',
        ];
        $data['category_ids'] = $data['res']['category_id'] > 0 ? [$data['res']['category_id']] : [];
        $data['color_ids']    = [];
        $data['size_ids']     = [];
        $data['stock_rows']   = 0;
        $data['meta']         = null;

        return $this->render('store/products/product_form', $data + $this->form_options());
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->product_model->get_product_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $meta           = $this->meta_model->get_entity_meta(self::META_TYPE, $res['products_id']);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $result = $this->save($res, $meta);
            if (is_int($result)) {
                $this->session->setFlashdata('sitepanel_success', 'Product updated.');
                redirect_now($this->base);
            }
            $data['errors'] = $result;
        }

        foreach (['meta_title', 'meta_keyword', 'meta_description'] as $field) {
            $res[$field] = is_array($meta) ? (string) $meta[$field] : '';
        }
        // No discount (0.00) shows an empty field.
        if ((float) $res['product_discounted_price'] === 0.0) {
            $res['product_discounted_price'] = '';
        }

        $data['heading_title'] = 'Edit Product';
        $data['breadcrumbs']   = ['Products' => site_url($this->base), $res['product_name'] => null];
        $data['res']           = $res;
        $data['category_ids']  = $this->product_model->get_category_ids((int) $res['products_id']) ?: [];
        $data['color_ids']     = $this->product_model->get_color_ids((int) $res['products_id']) ?: [];
        $data['size_ids']      = $this->product_model->get_size_ids((int) $res['products_id']) ?: [];
        $data['stock_rows']    = count((array) $this->product_model->get_stock_rows((int) $res['products_id']));
        $data['meta']          = $meta;

        return $this->render('store/products/product_form', $data + $this->form_options());
    }

    /**
     * The stock matrix: each colour × size row's prices, available quantity
     * and low-stock threshold.
     *
     * @param int|string $id
     */
    public function stock($id = 0): string
    {
        $product = $this->record_or_back($this->product_model->get_product_by_id($id));
        $pid     = (int) $product['products_id'];
        $rows    = $this->product_model->get_stock_rows($pid);
        $errors  = [];

        if ($this->request->getMethod() === 'POST' && is_array($rows)) {
            $this->require_privilege(AdminAccess::PRIV_EDIT);
            [$values, $errors] = $this->posted_stock($product, $rows);
            if (! $errors) {
                if ($this->product_model->save_stock($pid, $values)) {
                    $this->session->setFlashdata('sitepanel_success', 'Stock and prices saved.');
                    redirect_now($this->base . '/stock/' . $pid);
                }
                $errors[] = 'The stock could not be saved. Please try again.';
            }
        }

        return $this->render('store/products/product_stock', [
            'heading_title' => 'Product Stock',
            'breadcrumbs'   => ['Products' => site_url($this->base), $product['product_name'] => site_url($this->base . '/edit/' . $pid), 'Stock' => null],
            'product'       => $product,
            'rows'          => $rows,
            'errors'        => $errors,
        ]);
    }

    /**
     * Photos: upload (optionally for one colour), colour, order, default, delete.
     *
     * @param int|string $id
     */
    public function media($id = 0): string
    {
        $product = $this->record_or_back($this->product_model->get_product_by_id($id));
        $pid     = (int) $product['products_id'];
        $media   = $this->product_model->get_media($pid);
        $colors  = $this->product_colors($pid);
        $errors  = [];

        if ($this->request->getMethod() === 'POST' && is_array($media)) {
            $this->require_privilege(AdminAccess::PRIV_EDIT);
            $errors = $this->save_media($pid, $media, $colors);
            if (! $errors) {
                $this->session->setFlashdata('sitepanel_success', 'Photos saved.');
                redirect_now($this->base . '/media/' . $pid);
            }
            $media = $this->product_model->get_media($pid);
        }

        return $this->render('store/products/product_media', [
            'heading_title' => 'Product Photos',
            'breadcrumbs'   => ['Products' => site_url($this->base), $product['product_name'] => site_url($this->base . '/edit/' . $pid), 'Photos' => null],
            'product'       => $product,
            'media'         => $media,
            'colors'        => $colors,
            'errors'        => $errors,
            'max_photos'    => self::MAX_PHOTOS,
            'max_size'      => self::IMAGE_MAX_SIZE,
            'features'      => $this->shop->productFeatures,
        ]);
    }

    /**
     * Related products ("You may also like"), in the order picked.
     *
     * @param int|string $id
     */
    public function related($id = 0): string
    {
        $product = $this->record_or_back($this->product_model->get_product_by_id($id));
        $pid     = (int) $product['products_id'];
        $options = $this->product_model->get_related_options($pid);
        $errors  = [];
        $ids     = $this->product_model->get_related_ids($pid) ?: [];

        if ($this->request->getMethod() === 'POST' && is_array($options)) {
            $this->require_privilege(AdminAccess::PRIV_EDIT);
            $ids    = $this->posted_ids('related_ids');
            $posted = $this->request->getPost('related_ids');
            if ($posted !== null && (! is_array($posted) || count($ids) !== count($posted))) {
                $errors[] = 'The related products selection is not valid. Please choose them again.';
            } elseif (count($ids) > self::MAX_RELATED) {
                $errors[] = 'Choose at most ' . self::MAX_RELATED . ' related products.';
            } elseif (array_diff($ids, array_keys($options))) {
                $errors[] = 'One or more of the selected products no longer exist. Please review the selection.';
            } elseif ($this->product_model->save_related($pid, $ids)) {
                $this->session->setFlashdata('sitepanel_success', 'Related products saved.');
                redirect_now($this->base . '/related/' . $pid);
            } else {
                $errors[] = 'The related products could not be saved. Please try again.';
            }
        }

        return $this->render('store/products/product_related', [
            'heading_title' => 'Related Products',
            'breadcrumbs'   => ['Products' => site_url($this->base), $product['product_name'] => site_url($this->base . '/edit/' . $pid), 'Related products' => null],
            'product'       => $product,
            'options'       => $options,
            'related_ids'   => $ids,
            'max_related'   => self::MAX_RELATED,
            'errors'        => $errors,
        ]);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(ProductModel::TABLE, ProductModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);
        $row   = $this->record_or_back($this->product_model->get_product_by_id($id));
        $error = $this->before_delete([$row]);
        if ($error !== null) {
            $this->session->setFlashdata('sitepanel_error', $error);
            $this->back();
        }

        // The product and its wl_meta_tags row in one transaction; its dependent rows go by foreign key cascade.
        if ($this->product_model->bulk_delete([(int) $row['products_id']])) {
            $this->session->setFlashdata('sitepanel_success', '"' . $row['product_name'] . '" has been deleted.');
        } else {
            $this->session->setFlashdata('sitepanel_error', '"' . $row['product_name'] . '" could not be deleted, so nothing was changed. Please try again.');
        }

        $this->back();
    }

    public function bulk(): void
    {
        $filters = $this->list_filters($this->request->getPost());
        $query = array_filter($filters, 'strlen');

        $action = (string) $this->request->getPost('bulk_action');
        if ($action === 'flag') {
            $this->require_post();
            $this->require_privilege(AdminAccess::PRIV_EDIT);
            $set_as   = (string) $this->request->getPost('set_as');
            $unset_as = (string) $this->request->getPost('unset_as');
            if ($set_as === '') {
                $set_as = (string) $this->request->getPost('set_as_bottom');
            }
            if ($unset_as === '') {
                $unset_as = (string) $this->request->getPost('unset_as_bottom');
            }
            $set_allowed   = isset(self::BULK_SET_AS[$set_as]);
            $unset_allowed = isset(self::BULK_UNSET_AS[$unset_as]);
            if ($set_allowed === $unset_allowed) {
                $this->session->setFlashdata('sitepanel_error', 'Choose Recently Added in either Product Set As or Product Unset As.');
                $this->back($query);
            }
            $ids = $this->posted_ids('ids');
            if (! $ids) {
                $this->session->setFlashdata('sitepanel_error', 'Select at least one product first.');
                $this->back($query);
            }
            $flag  = $set_allowed ? $set_as : $unset_as;
            $value = $set_allowed ? '1' : '0';
            if ($this->product_model->bulk_update_flag($ids, $flag, $value)) {
                $this->session->setFlashdata('sitepanel_success', count($ids) . ' product' . (count($ids) === 1 ? '' : 's') . ' updated.');
            } else {
                $this->session->setFlashdata('sitepanel_error', 'The selected products could not be updated. Please try again.');
            }
            $this->back($query);
        }

        if ($action !== 'delete') {
            $this->process_bulk_action($this->product_model, 'get_products_by_ids', 'Product', $query);
        }

        $this->bulk_delete_checked($this->product_model, 'get_products_by_ids', 'Product', $query, fn (array $rows) => $this->before_delete($rows));
    }

    /**
     * Before deleting products: arrange for their
     * photo files to be removed once the rows are gone (ImageUpload keeps a
     * file while any row — e.g. an order line — still names it, so this is
     * safe even when the delete fails).
     */
    protected function before_delete(array $rows): ?string
    {
        $files = [];

        foreach ($rows as $row) {
            $media = $this->product_model->get_media((int) $row['products_id']);
            if ($media === false) {
                return 'The photos of "' . $row['product_name'] . '" could not be loaded, so nothing was deleted. Please try again.';
            }
            $files = array_merge($files, array_column($media, 'media'));
        }

        // The SEO rows are deleted with the products, in ProductModel::bulk_delete()'s transaction.
        if ($files) {
            $images = $this->image_upload;
            register_shutdown_function(static function () use ($images, $files): void {
                foreach ($files as $file) {
                    $images->delete(self::IMAGE_FOLDER, $file);
                }
            });
        }

        return null;
    }

    /**
     * Save the product form. The steps, in this order: load and validate the
     * input (nothing is written while anything is wrong), upload the photos
     * of a new product, save the product row with its categories, then its
     * colour / size options, its photos and its SEO row.
     *
     * @param array|null       $row  NULL when adding
     * @param array|false|null $meta Current SEO row (edit)
     *
     * @return int|list<string> The product id when saved, else the errors
     */
    protected function save(?array $row, $meta = null)
    {
        if ($meta === false) {
            return ['The product\'s SEO metadata could not be loaded. Please try again.'];
        }
        $options = $this->form_options();
        if ($options['categories'] === false || $options['colors'] === false || $options['sizes'] === false || $options['brands'] === false || $options['materials'] === false) {
            return ['The form options could not be loaded. Please try again.'];
        }

        $input = $this->checked_product_input($row, $options);
        if (isset($input['errors'])) {
            return $input['errors'];
        }

        $new_files = $row ? [] : $this->upload_new_photos();
        if (isset($new_files['error'])) {
            return [$new_files['error']];
        }

        // The product, its categories, its single stock row and its listing price: one transaction.
        $id    = $row ? (int) $row['products_id'] : 0;
        $data  = $this->product_row($row, $input);
        $saved = $this->product_model->save_product($id, $data, $input['category_ids'], $input['ancestors'], $input['with_quantity'] ? (int) $this->posted_text('product_quantity') : null);
        if ($saved === false) {
            foreach ($new_files as $file) {
                $this->image_upload->delete(self::IMAGE_FOLDER, $file);
            }

            return ['Product could not be saved. Please try again.'];
        }
        $id = $saved;

        $ok = $this->save_product_variants($id, $row, $input);
        foreach ($new_files as $file) {
            $ok = $this->product_model->add_media($id, $file) !== false && $ok;
        }
        $ok = $this->save_product_meta($id, $input['slug'], $input['description']) && $ok;

        if (! $ok) {
            $this->session->setFlashdata('sitepanel_error', 'The product was saved, but its options, photos or SEO data could not all be saved. Please check it again.');
        }

        return (int) $id;
    }

    /**
     * Trimmed POST value.
     */
    protected function posted_text(string $field): string
    {
        return trim((string) $this->request->getPost($field));
    }

    /**
     * Load and check everything the product form needs before anything is
     * written: categories, the "Available quantity" case, the form rules,
     * colours / sizes, the product code, the URL slug, the sanitized texts
     * and the categories' ancestors. Field errors are collected so they show
     * at once; a value that could not be loaded ends the checks with one message.
     *
     * @param array $options form_options()
     *
     * @return array ['errors' => list<string>] or the checked input: category_ids,
     *               ancestors, with_quantity, colors, sizes, code, slug, description, specs
     */
    protected function checked_product_input(?array $row, array $options): array
    {
        $on = fn (string $feature) => $this->shop->feature($feature);
        $id = $row ? (int) $row['products_id'] : 0;

        // Categories (one or more), checked before the other fields so every error shows at once.
        $category_ids    = $this->posted_ids('category_ids');
        $category_errors = [];
        if (! $category_ids) {
            $category_errors[] = 'Choose at least one category.';
        } elseif (array_diff($category_ids, array_keys($options['categories']))) {
            $category_errors[] = 'One or more of the selected categories no longer exist. Please choose them again.';
        }

        // "Available quantity": products without colour / size options (one stock row) when the Stock page is off.
        $stock_rows = $row ? $this->product_model->get_stock_rows($id) : [];
        if ($stock_rows === false) {
            return ['errors' => ['The product\'s stock could not be loaded. Please try again.']];
        }
        $with_quantity = ! $on('stock') && count($stock_rows) <= 1;

        $rule_errors = $this->product_rule_errors($options, $with_quantity);
        if ($rule_errors) {
            return ['errors' => array_merge($category_errors, $rule_errors)];
        }

        $errors = $category_errors;

        // Colours / sizes: posted when offered, else the product keeps its own.
        $colors = $on('colors') ? $this->posted_ids('color_ids') : ($row ? ($this->product_model->get_color_ids($id) ?: []) : []);
        $sizes  = $on('sizes') ? $this->posted_ids('size_ids') : ($row ? ($this->product_model->get_size_ids($id) ?: []) : []);
        if (($on('colors') && array_diff($colors, array_keys($options['colors']))) || ($on('sizes') && array_diff($sizes, array_keys($options['sizes'])))) {
            $errors[] = 'One or more of the selected colours or sizes no longer exist. Please review them.';
        }

        $code  = $this->posted_text('product_code');
        $taken = $this->product_model->code_taken($code, $id);
        if ($taken === null) {
            return ['errors' => ['The product code could not be checked. Please try again.']];
        }
        $taken && $errors[] = 'Another product already uses the product code "' . $code . '".';

        // The URL slug: made here from the name, never from the form (slug_for()).
        $slug = $this->slug_for($this->posted_text('product_name'), $row);
        if ($slug === false) {
            return ['errors' => ['The URL slug could not be checked. Please try again.']];
        }
        if ($slug === '') {
            $errors[] = 'The product name needs at least one letter or number to make its URL slug.';
        }

        $sanitizer   = new HtmlSanitizer();
        $description = $sanitizer->clean((string) $this->request->getPost('products_description'));
        $specs       = $sanitizer->clean((string) $this->request->getPost('products_specification'));
        $ancestors   = [];
        foreach ($errors ? [] : $category_ids as $category_id) {
            $ancestors[$category_id] = $this->category_model->ancestor_ids($category_id);
            if ($ancestors[$category_id] === false) {
                return ['errors' => ['The categories could not be loaded. Please try again.']];
            }
        }
        if ($errors) {
            return ['errors' => $errors];
        }

        return compact('category_ids', 'ancestors', 'with_quantity', 'colors', 'sizes', 'code', 'slug', 'description', 'specs');
    }

    /**
     * Run the product form rules (only those of the features that are on).
     *
     * @param array $options form_options()
     *
     * @return list<string> Errors (empty = valid)
     */
    protected function product_rule_errors(array $options, bool $with_quantity): array
    {
        $amount   = ['valid_amount', static fn ($value) => (string) $value === '' || preg_match(self::AMOUNT_PATTERN, (string) $value) === 1];
        $messages = ['valid_amount' => 'The {field} must be a positive number with at most 2 decimals.'];
        $price    = $this->posted_text('product_price');
        $on       = fn (string $feature) => $this->shop->feature($feature);

        $v = new FormValidation($this->request, $this);
        $on('brand') && $v->set_rules('brand_id', 'Brand', ['trim', ['valid_brand', static fn ($value) => (string) $value === '' || isset($options['brands'][(int) $value])]], ['valid_brand' => 'Choose a brand from the list.']);
        $on('material') && $v->set_rules('material_id', 'Material', ['trim', ['valid_material', static fn ($value) => (string) $value === '' || isset($options['materials'][(int) $value])]], ['valid_material' => 'Choose a material from the list.']);
        $v->set_rules('product_name', 'Product name', 'trim|required|max_length[200]');
        $v->set_rules('product_code', 'Product code (SKU)', 'trim|required|max_length[64]');
        // No rule for friendly_url: the URL slug is never taken from the form (see slug_for()).
        $v->set_rules('product_alt', 'Image alt text', 'trim|max_length[100]');
        $v->set_rules('product_price', 'Price', ['trim', 'required', $amount, 'greater_than[0]'], $messages);
        $v->set_rules('product_discounted_price', 'Discounted price', ['trim', $amount,
            ['below_price', static fn ($value) => (string) $value === '' || (float) $value === 0.0 || ! preg_match(self::AMOUNT_PATTERN, $price) || (float) $value < (float) $price]],
            $messages + ['below_price' => 'The discounted price must be less than the price.']);
        $on('weight') && $v->set_rules('product_weight', 'Weight', 'trim|max_length[55]');
        $v->set_rules('products_description', 'Description', 'max_length[200000]');
        $v->set_rules('products_specification', 'Additional information', 'max_length[200000]');
        $v->set_rules('status', 'Status', 'required|in_list[1,0]');
        $on('button_type') && $v->set_rules('is_show_button', 'Button type', 'required|in_list[1,2,3,4]');
        $with_quantity && $v->set_rules('product_quantity', 'Available quantity', 'trim|required|is_natural|less_than_equal_to[999999]');
        $v->set_rules('meta_title', 'Meta title', 'trim|max_length[450]');
        $v->set_rules('meta_keyword', 'Meta keywords', 'trim|max_length[450]');
        $v->set_rules('meta_description', 'Meta description', 'trim|max_length[650]');

        return $v->run() ? [] : array_values($v->error_array());
    }

    /**
     * Upload the photos of a new product (photo_1 .. photo_N). On an upload
     * error the photos already uploaded are deleted again.
     *
     * @return array list of saved file names, or ['error' => message]
     */
    protected function upload_new_photos(): array
    {
        $new_files = [];

        for ($n = 1; $n <= self::NEW_PHOTOS; $n++) {
            if (! $this->image_upload->has_file('photo_' . $n)) {
                continue;
            }
            $upload = $this->image_upload->save('photo_' . $n, self::IMAGE_FOLDER, 'Photo ' . $n, self::IMAGE_MAX_SIZE);
            if (isset($upload['error'])) {
                foreach ($new_files as $file) {
                    $this->image_upload->delete(self::IMAGE_FOLDER, $file);
                }

                return ['error' => $upload['error']];
            }
            $new_files[] = $upload['file'];
        }

        return $new_files;
    }

    /**
     * The product's wl_products columns from the form.
     *
     * @param array $input checked_product_input()
     */
    protected function product_row(?array $row, array $input): array
    {
        $on   = fn (string $feature) => $this->shop->feature($feature);
        $post = fn (string $field) => $this->posted_text($field);

        $data = [
            'product_name'             => $post('product_name'),
            'friendly_url'             => $input['slug'],
            'product_code'             => $input['code'],
            'product_alt'              => $post('product_alt') !== '' ? $post('product_alt') : $post('product_name'),
            'product_price'            => $post('product_price'),
            'product_discounted_price' => $post('product_discounted_price') !== '' ? $post('product_discounted_price') : '0.00',
            'products_description'     => trim(strip_tags($input['description'], '<img>')) !== '' ? $input['description'] : null,
            'products_specification'   => trim(strip_tags($input['specs'], '<img>')) !== '' ? $input['specs'] : null,
            'status'                   => $post('status'),
            'is_recently_added'       => $row ? ($row['is_recently_added'] ?? '0') : '1',
            'product_updated_date'     => date('Y-m-d H:i:s'),
        ];
        // Hidden features: a new product gets the defaults; an edited one keeps its values (the columns are left out).
        if ($on('brand') || ! $row) {
            $data['brand_id'] = $on('brand') && $post('brand_id') !== '' ? (int) $post('brand_id') : null;
        }
        if ($on('material') || ! $row) {
            $data['material_id'] = $on('material') && $post('material_id') !== '' ? (int) $post('material_id') : null;
        }
        if ($on('weight') || ! $row) {
            $data['product_weight'] = $on('weight') && $post('product_weight') !== '' ? $post('product_weight') : null;
        }
        if ($on('button_type') || ! $row) {
            $data['is_show_button'] = $on('button_type') ? $post('is_show_button') : '4';
        }
        if ($on('labels') || ! $row) {
            foreach (array_keys(self::FLAGS) as $flag) {
                $data[$flag] = $on('labels') && $this->request->getPost($flag) === '1' ? '1' : '0';
            }
        }
        $row || $data['product_added_date'] = $data['product_updated_date'];

        return $data;
    }

    /**
     * Save the product's colour / size options (its stock matrix). A new
     * product without options or "Available quantity" gets its single stock
     * row, set on the Stock page.
     *
     * @param array $input checked_product_input()
     */
    protected function save_product_variants(int $id, ?array $row, array $input): bool
    {
        if ($this->shop->feature('colors') || $this->shop->feature('sizes')) {
            return $this->product_model->save_variants($id, $input['colors'], $input['sizes']);
        }
        if (! $row && ! $input['with_quantity']) {
            return $this->product_model->save_variants($id, [], []);
        }

        return true;
    }

    /**
     * Save the product's wl_meta_tags row: its address on the website, so it
     * always exists. Empty SEO fields get defaults from the name and description.
     */
    protected function save_product_meta(int $id, string $slug, string $description): bool
    {
        $post = fn (string $field) => $this->posted_text($field);
        $name = $post('product_name');
        $site = trim($this->site_settings->get('site_name'));

        $seo = [
            'meta_title'       => $post('meta_title') !== '' ? $post('meta_title') : $name . ' | ' . ($site !== '' ? $site : 'CustomCart'),
            'meta_keyword'     => $post('meta_keyword') !== '' ? $post('meta_keyword') : $name,
            'meta_description' => $post('meta_description') !== '' ? $post('meta_description') : (mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($description))), 0, 160) ?: $name),
        ];

        return $this->meta_model->save_entity_meta(self::META_TYPE, $id, ProductModel::URL_PREFIX . $slug, $seo);
    }

    /**
     * Most characters of a product's URL slug.
     */
    public const SLUG_MAX = 150;

    /**
     * The URL slug of a product being saved, always made here from its name
     * (a posted friendly_url is ignored), as the category Page URL is:
     *  - edit, name unchanged: its current slug, untouched;
     *  - otherwise: free_slug().
     *
     * @return false|string '' when the name has no letters or digits, FALSE when it could not be checked
     */
    protected function slug_for(string $name, ?array $row)
    {
        if ($row && $name === (string) $row['product_name']) {
            return (string) $row['friendly_url'];
        }

        return self::free_slug($this->product_model, $this->meta_model, $name, $row);
    }

    /**
     * A product URL slug made from a name (also used by Products Bulk Upload):
     * sp_page_slug(name), or the first free one of slug-2, slug-3... — free
     * meaning no other product has it and its address (products live at the
     * site root) is used by no other row of wl_meta_tags (categories, pages)
     * and is not reserved. An edited product ($row) may keep its own slug.
     *
     * @param array|null $row The product being edited, NULL for a new one
     *
     * @return false|string '' when the name has no letters or digits, FALSE when it could not be checked
     */
    public static function free_slug(ProductModel $product_model, MetaModel $meta_model, string $name, ?array $row = null)
    {
        $id   = $row ? (int) $row['products_id'] : 0;
        $base = sp_page_slug($name, self::SLUG_MAX);
        if ($base === '') {
            return '';
        }

        for ($n = 1; $n <= 1000; $n++) {
            $suffix = $n === 1 ? '' : '-' . $n;
            $slug   = rtrim(substr($base, 0, self::SLUG_MAX - strlen($suffix)), '-') . $suffix;
            if ($row && $slug === $row['friendly_url']) {
                return $slug; // its own slug already
            }

            $taken = $product_model->url_taken($slug, $id);
            $other = $meta_model->get_by_page_url(ProductModel::URL_PREFIX . $slug);
            if ($taken === null || $other === false) {
                return false;
            }
            $own = is_array($other) && $other['entity_type'] === self::META_TYPE && (int) $other['entity_id'] === $id && $id > 0;
            if (! $taken && (! is_array($other) || $own) && ! MetaModel::reserved(ProductModel::URL_PREFIX . $slug)) {
                return $slug;
            }
        }

        return false;
    }

    /**
     * Validate the stock matrix form.
     *
     * @return array{0: array<int, array>, 1: list<string>} [stock_id => values, errors]
     */
    protected function posted_stock(array $product, array $rows): array
    {
        $values = [];
        $errors = [];
        $field  = function (string $name, int $stock_id): string {
            $posted = $this->request->getPost($name);

            return is_array($posted) && isset($posted[$stock_id]) && is_string($posted[$stock_id]) ? trim($posted[$stock_id]) : '';
        };

        foreach ($rows as $row) {
            $sid   = (int) $row['stock_id'];
            $label = self::variant_label($row);
            $price = $field('price', $sid);
            $disc  = $field('discounted', $sid);
            $qty   = $field('quantity', $sid);
            $low   = $field('inventory', $sid);

            foreach (['price' => $price, 'discounted price' => $disc] as $name => $value) {
                if ($value !== '' && ! preg_match(self::AMOUNT_PATTERN, $value)) {
                    $errors[] = $label . ': the ' . $name . ' must be a positive number with at most 2 decimals (or empty).';
                }
            }
            foreach (['available quantity' => $qty, 'low-stock threshold' => $low] as $name => $value) {
                if ($value !== '' && (! ctype_digit($value) || (int) $value > 999999)) {
                    $errors[] = $label . ': the ' . $name . ' must be a whole number from 0 to 999999.';
                }
            }

            $effective = (float) $price > 0 ? (float) $price : (float) $product['product_price'];
            if ((float) $disc > 0 && (float) $disc >= $effective) {
                $errors[] = $label . ': the discounted price must be less than the price (' . number_format($effective, 2) . ').';
            }

            $values[$sid] = [
                'product_price'            => $price === '' ? 0 : $price,
                'product_discounted_price' => $disc === '' ? 0 : $disc,
                'product_quantity'         => (int) $qty,
                'inventory'                => (int) $low,
            ];
        }

        return [$values, $errors];
    }

    /**
     * Apply the photo form: deletions, colour / order / default of the kept
     * photos, then new uploads.
     *
     * @param array<int, string> $colors color_id => name
     *
     * @return list<string> Errors
     */
    protected function save_media(int $pid, array $media, array $colors): array
    {
        $by_id   = array_column($media, null, 'id');
        $delete  = array_values(array_intersect(array_map('intval', array_keys($by_id)), $this->posted_ids('delete')));
        $posted  = fn (string $name, int $id) => is_array($this->request->getPost($name)) ? (string) ($this->request->getPost($name)[$id] ?? '') : '';
        $default = (int) $this->request->getPost('default_id');
        $errors  = [];
        // Colours off (Config\Shop::$productFeatures): the form has no colour fields; every photo keeps its colour.
        $with_colors = $this->shop->feature('colors');

        $updates = [];
        foreach ($by_id as $id => $row) {
            $id = (int) $id;
            if (in_array($id, $delete, true)) {
                continue;
            }
            $color = $with_colors ? (int) $posted('color', $id) : (int) $row['color_id'];
            $sort  = $posted('sort', $id);
            if ($with_colors && $color !== 0 && ! isset($colors[$color])) {
                $errors[] = 'Choose a colour from the list for each photo.';
                break;
            }
            if ($sort !== '' && (! ctype_digit($sort) || (int) $sort > 9999)) {
                $errors[] = 'The order of each photo must be a whole number from 0 to 9999.';
                break;
            }
            $updates[$id] = ['color_id' => $color, 'sort_order' => $sort === '' ? (int) $row['sort_order'] : (int) $sort];
        }

        $new_color = $with_colors ? (int) $this->request->getPost('new_color') : 0;
        if ($new_color !== 0 && ! isset($colors[$new_color])) {
            $errors[] = 'Choose a colour from the list for the new photos.';
        }
        $new_fields = array_values(array_filter(['new_photo_1', 'new_photo_2', 'new_photo_3', 'new_photo_4'], fn ($field) => $this->image_upload->has_file($field)));
        if (count($media) - count($delete) + count($new_fields) > self::MAX_PHOTOS) {
            $errors[] = 'A product can have at most ' . self::MAX_PHOTOS . ' photos. Delete some first.';
        }
        if ($errors) {
            return $errors;
        }

        if ($delete) {
            $removed = $this->product_model->delete_media($pid, $delete);
            if ($removed === false) {
                return ['The photos could not be deleted. Please try again.'];
            }
            foreach ($removed as $row) {
                $this->image_upload->delete(self::IMAGE_FOLDER, $row['media']);
            }
        }
        if ($updates && ! $this->product_model->update_media($pid, $updates, isset($updates[$default]) ? $default : 0)) {
            return ['The photo details could not be saved. Please try again.'];
        }

        foreach ($new_fields as $n => $field) {
            $upload = $this->image_upload->save($field, self::IMAGE_FOLDER, 'New photo ' . ($n + 1), self::IMAGE_MAX_SIZE);
            if (isset($upload['error'])) {
                $errors[] = $upload['error'];

                continue;
            }
            if ($this->product_model->add_media($pid, $upload['file'], $new_color) === false) {
                $this->image_upload->delete(self::IMAGE_FOLDER, $upload['file']);
                $errors[] = 'A new photo could not be saved. Please try again.';
            }
        }

        return $errors;
    }

    /**
     * The product's colours: color_id => name (photos can be tied to one).
     *
     * @return array<int, string>
     */
    protected function product_colors(int $pid): array
    {
        $ids     = $this->product_model->get_color_ids($pid) ?: [];
        $options = (new ColorModel())->get_options() ?: [];
        $colors  = [];
        foreach ($ids as $id) {
            isset($options[$id]) && $colors[$id] = $options[$id]['color_name'];
        }

        return $colors;
    }

    /**
     * Options of the product form (FALSE entries when a list could not be loaded).
     */
    protected function form_options(): array
    {
        return [
            'categories' => $this->category_model->options(),
            'category_tree' => $this->category_model->product_tree(),
            'brands'     => (new BrandModel())->get_options(),
            'materials'  => (new MaterialModel())->get_options(),
            'colors'     => (new ColorModel())->get_options(),
            'sizes'      => (new SizeModel())->get_options(),
            'flags'      => self::FLAGS,
            'features'   => $this->shop->productFeatures,
        ];
    }

    /**
     * List filters from GET (list) or POST (bulk): keyword, status, category, brand, stock.
     */
    protected function list_filters(array $input): array
    {
        $status = (string) ($input['status'] ?? $input['status_filter'] ?? '');
        $stock  = (string) ($input['stock'] ?? '');

        return [
            'keyword'  => $this->keyword_param((string) ($input['keyword'] ?? '')),
            'status'   => in_array($status, ['1', '0'], true) ? $status : '',
            'category' => max(0, (int) ($input['category'] ?? 0)) ?: '',
            'brand'    => $this->shop->feature('brand') ? (max(0, (int) ($input['brand'] ?? 0)) ?: '') : '',
            'stock'    => $this->shop->feature('stock') && in_array($stock, ['low', 'out'], true) ? $stock : '',
            'pagesize' => in_array((int) ($input['pagesize'] ?? 0), self::PAGE_SIZES, true) ? (string) (int) $input['pagesize'] : '',
        ];
    }

    /**
     * Products among $ids with a stock row at or under its low-stock threshold.
     *
     * @param list<int|string> $ids
     *
     * @return list<int>
     */
    protected function low_stock_ids(array $ids): array
    {
        $low       = [];
        $threshold = $this->site_settings->low_stock_threshold();
        foreach ($ids as $id) {
            foreach ((array) $this->product_model->get_stock_rows((int) $id) as $row) {
                if ((int) $row['product_quantity'] > 0 && (int) $row['product_quantity'] <= ((int) $row['inventory'] > 0 ? (int) $row['inventory'] : $threshold)) {
                    $low[] = (int) $id;
                    break;
                }
            }
        }

        return $low;
    }

    /**
     * Positive whole-number ids posted as a list, duplicates removed, order kept.
     *
     * @return list<int>
     */
    protected function posted_ids(string $field): array
    {
        $posted = $this->request->getPost($field);
        $ids    = [];

        foreach (is_array($posted) ? $posted : [] as $value) {
            if (is_string($value) && preg_match('/^[1-9][0-9]{0,9}$/D', $value)) {
                $ids[(int) $value] = (int) $value;
            }
        }

        return array_values($ids);
    }

    /**
     * "Red / XL", "Red", "XL" or "Standard" for a stock row.
     */
    public static function variant_label(array $row): string
    {
        $parts = array_filter([(string) ($row['color_name'] ?? ''), (string) ($row['size_name'] ?? '')], 'strlen');

        return $parts ? implode(' / ', $parts) : 'Standard';
    }
}
