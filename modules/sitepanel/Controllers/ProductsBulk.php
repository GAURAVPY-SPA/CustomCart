<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\HtmlSanitizer;
use App\Libraries\ImageUpload;
use App\Libraries\SimpleXlsx;
use Config\Shop;
use Config\Uploads;
use Modules\Brand\Models\BrandModel;
use Modules\Category\Models\CategoryModel;
use Modules\Color\Models\ColorModel;
use Modules\Material\Models\MaterialModel;
use Modules\Products\Models\ProductModel;
use Modules\Size\Models\SizeModel;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\MetaModel;

/**
 * Sitepanel > Product Management > Manage Bulk Upload Product (the
 * reference's sitepanel/products/bulk_uploading).
 *
 * As in the reference, an Excel sheet with one product per row is uploaded
 * (.xlsx, read by App\Libraries\SimpleXlsx; a CSV saved from Excel works
 * too), the products sample sheet shows the columns, and the "... to View
 * IDs" sheets list the categories, fabrics, colours, sizes (and brands) with
 * their IDs. A category, fabric, colour, size or brand is given by its ID (as
 * in the reference) or by its name. Each row is checked on its own and added
 * like a product saved in Manage Products: its categories and their links,
 * fabric, colours and sizes with the stock matrix and quantities, photos,
 * listing price and its page address in wl_meta_tags. Rows whose product
 * code already exists are skipped, as in the reference (duplicates are never
 * updated). A row that can't be saved completely is removed again, so a row
 * is added whole or not at all.
 *
 * Brand, fabric (material), colours, sizes, weight and the low-stock
 * threshold are columns only while their product feature is on
 * (Config\Shop::$productFeatures), as on the product form. The import never
 * creates categories, fabrics, colours, sizes or brands.
 *
 *   index       the upload form; POST imports the file and lists each row's result
 *   template    the products sample sheet (.xlsx; ?format=csv for CSV)
 *   ids/<list>  categories | fabrics | colors | sizes | brands with their IDs (.xlsx)
 */
class ProductsBulk extends AdminController
{
    public const MAX_ROWS = 500;
    public const MAX_KB   = 5120;
    public const MAX_STOCK = 999999;

    /**
     * Sheet columns: key => [header, required, product feature (NULL = always), help, other accepted headers].
     * A header is matched in any letter case, with spaces or underscores
     * ("Product Code" = product_code); the reference's headers are accepted too.
     */
    public const COLUMNS = [
        'category'            => ['Category', true, null, 'Category ID, or its name (path for a sub-category: Fire Retardant Coveralls > FR Coveralls). Several separated by |, the first is the main category.', ['category_id', 'category_ids', 'categories']],
        'product_name'        => ['Product Name', true, null, 'At most 200 characters.', ['name']],
        'product_code'        => ['Product Code', true, null, 'Unique code (SKU), at most 64 characters. A code that already exists is skipped.', ['code', 'sku']],
        'price'               => ['Price', true, null, 'Positive amount, at most 2 decimals, e.g. 180.00', ['product_price']],
        'discounted_price'    => ['Discounted Price', false, null, 'Optional; lower than the price.', ['product_discounted_price']],
        'is_featured'         => ['Is Featured', false, null, '1 = flag as Featured, 0 or empty = no.', ['featured']],
        'is_new'              => ['Is New', false, null, '1 = flag as New, 0 or empty = no.', ['new']],
        'status'              => ['Status', false, null, '1 = Active (default), 0 = Inactive.', []],
        'brand'               => ['Brand', false, 'brand', 'One brand ID or name from Manage Brands.', ['brand_id', 'brand_name']],
        'fabric'              => ['Fabric', false, 'material', 'One fabric ID or name from Manage Materials.', ['fabric_id', 'material', 'material_id']],
        'colors'              => ['Colors', false, 'colors', 'Colour IDs or names from Manage Colors, separated by | or comma, e.g. Navy|Red or 1,3.', ['color', 'colours', 'colour', 'color_ids', 'product_color_ids']],
        'sizes'               => ['Sizes', false, 'sizes', 'Size IDs or names from Manage Size, separated by | or comma, e.g. S|M|L.', ['size', 'size_ids', 'product_size_ids']],
        'stock'               => ['Stock', false, null, 'Available quantity. One whole number gives every colour × size option that quantity; or per option: Navy/S=10|Navy/M=5 (Colour/Size=quantity; only colours: Navy=10, only sizes: S=10). Options not listed get 0. Default 0.', ['quantity', 'product_quantity', 'available_quantity']],
        'low_stock_threshold' => ['Low Stock Threshold', false, 'stock', 'Optional whole number for every option; empty = the default threshold.', ['inventory', 'threshold']],
        'weight'              => ['Weight', false, 'weight', 'e.g. 1.2 kg (at most 55 characters).', ['product_weight']],
        'description'         => ['Description', false, null, 'Text or simple HTML.', ['products_description']],
        'image_1'             => ['Image 1', false, null, 'File name of a photo already uploaded to uploads/products/ (jpg, png, gif, webp), e.g. coverall.jpg. Image 1 is the default photo.', ['pic1', 'image1']],
        'image_2'             => ['Image 2', false, null, 'Optional second photo file name.', ['pic2', 'image2']],
        'image_3'             => ['Image 3', false, null, 'Optional third photo file name.', ['pic3', 'image3']],
        'image_4'             => ['Image 4', false, null, 'Optional fourth photo file name.', ['pic4', 'image4']],
    ];

    /**
     * Lookup column => [model class, label, Sitepanel page].
     */
    protected const LOOKUPS = [
        'brand'  => [BrandModel::class, 'Brand', 'Manage Brands'],
        'fabric' => [MaterialModel::class, 'Fabric', 'Manage Materials'],
        'colors' => [ColorModel::class, 'Colour', 'Manage Colors'],
        'sizes'  => [SizeModel::class, 'Size', 'Manage Size'],
    ];

    /**
     * The "... to View IDs" downloads: list => [title, product feature (NULL = always), lookup column].
     */
    public const ID_LISTS = [
        'categories' => ['Categories', null, null],
        'fabrics'    => ['Fabric', 'material', 'fabric'],
        'colors'     => ['Colors', 'colors', 'colors'],
        'sizes'      => ['Sizes', 'sizes', 'sizes'],
        'brands'     => ['Brands', 'brand', 'brand'],
    ];

    protected string $base           = 'sitepanel/products_bulk';
    protected string $item           = 'Product';
    protected string $menu_highlight = 'products_bulk';

    protected ProductModel $product_model;
    protected CategoryModel $category_model;
    protected MetaModel $meta_model;

    /**
     * Headers of the uploaded file that are not used (unknown, or their feature is off).
     *
     * @var list<string>
     */
    protected array $ignored = [];

    protected function init(): void
    {
        $this->product_model  = new ProductModel();
        $this->category_model = new CategoryModel();
        $this->meta_model     = new MetaModel();
    }

    public function index(): string
    {
        $data = ['results' => null, 'errors' => []];

        if ($this->request->getMethod() === 'POST') {
            $this->require_privilege(AdminAccess::PRIV_ADD);
            $rows   = $this->read_upload();
            $result = is_string($rows) ? $rows : $this->import($rows);
            if (is_string($result)) {
                $data['errors'] = [$result];
            } else {
                $data['results'] = $result;
                $added           = count(array_filter($result, static fn ($r) => $r[3] === null));
                // The reference's messages: success when every row was added, else the rows' errors.
                if ($added > 0) {
                    $data['flash_success'] = $added === count($result)
                        ? 'Bulk Record has been successfully updated: ' . $added . ' product' . ($added === 1 ? '' : 's') . ' added.'
                        : $added . ' of ' . count($result) . ' products added; the other rows are listed below.';
                }
            }
        }

        $data['heading_title'] = 'Manage Bulk Upload Product';
        $data['breadcrumbs']   = ['Products' => site_url('sitepanel/products'), 'Bulk Upload' => null];
        $data['columns']       = $this->columns();
        $data['id_lists']      = $this->id_lists();
        $data['ignored']       = $this->ignored;
        $data['max_rows']      = self::MAX_ROWS;
        $data['max_mb']        = self::MAX_KB / 1024;

        return $this->render('store/products/product_bulk', $data);
    }

    /**
     * The products sample sheet (the reference's download_product_format):
     * the column headers and two example rows made from this site's own
     * categories, fabrics, colours and sizes, plus an Instructions sheet.
     */
    public function template()
    {
        $columns = $this->columns();
        $keys    = array_keys($columns);
        $rows    = [array_column($columns, 0)];
        foreach ($this->example_rows() as $example) {
            $rows[] = array_map(static fn ($key) => $example[$key] ?? '', $keys);
        }

        if ($this->request->getGet('format') === 'csv') {
            $out = fopen('php://temp', 'w+');
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            rewind($out);
            $csv = "\xEF\xBB\xBF" . stream_get_contents($out);
            fclose($out);

            return $this->download('products_sample_sheet.csv', 'text/csv; charset=UTF-8', $csv);
        }

        $help = [['Column', 'Required', 'Value']];
        foreach ($columns as [$header, $required, , $text]) {
            $help[] = [$header, $required ? 'Yes' : 'No', $text];
        }
        $help[] = ['', '', ''];
        $help[] = ['Note', '', 'Only the first sheet (Products) is imported. Delete the example rows before uploading. Download the other sheets from Manage Bulk Upload Product to view the IDs.'];

        return $this->download('products_sample_sheet.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', SimpleXlsx::build(
            ['Products' => $rows, 'Instructions' => $help],
            ['Products' => array_map(static fn ($h) => max(14, strlen($h) + 4), $rows[0]), 'Instructions' => [22, 10, 110]],
        ));
    }

    /**
     * A "... to View IDs" sheet (the reference's download_cat_format,
     * download_color_format, ...): every record with its ID.
     */
    public function ids(string $list = '')
    {
        $lists = $this->id_lists();
        if (! isset($lists[$list])) {
            $this->session->setFlashdata('sitepanel_error', 'Choose one of the lists to download.');

            return redirect()->to(site_url($this->base));
        }

        $status = static fn ($row) => (string) $row['status'] === '1' ? 'Active' : 'Inactive';
        if ($list === 'categories') {
            $all = $this->category_model->get_all_categories();
            if ($all === false) {
                return $this->failed_download();
            }
            $rows = [['Category', 'Category Id', 'Status']];
            foreach ($this->category_paths_list($all) as $id => $path) {
                $rows[] = [$path, $id, $status($all[$id])];
            }
            $widths = [60, 14, 12];
        } else {
            [$class, $label] = self::LOOKUPS[self::ID_LISTS[$list][2]];
            $model           = new $class();
            $options         = $model->get_options();
            if ($options === false) {
                return $this->failed_download();
            }
            $rows = [[$label . ' Name', $label . ' Id', 'Status']];
            foreach ($options as $id => $row) {
                $rows[] = [$row[$model::NAME_FIELD], $id, $status($row)];
            }
            $widths = [36, 14, 12];
        }

        return $this->download($list . '_' . date('dMY') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            SimpleXlsx::build([$lists[$list] => $rows], [$lists[$list] => $widths]));
    }

    /**
     * The ID lists offered (those of a product feature that is off are left out): list => title.
     *
     * @return array<string, string>
     */
    protected function id_lists(): array
    {
        $shop  = config(Shop::class);
        $lists = [];
        foreach (self::ID_LISTS as $list => [$title, $feature]) {
            ($feature === null || $shop->feature($feature)) && $lists[$list] = $title;
        }

        return $lists;
    }

    protected function download(string $name, string $type, string $body)
    {
        return $this->response->setHeader('Content-Type', $type)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($body);
    }

    protected function failed_download()
    {
        $this->session->setFlashdata('sitepanel_error', 'The list could not be loaded. Please try again.');

        return redirect()->to(site_url($this->base));
    }

    /**
     * The columns in use: those of a product feature that is off are left out.
     *
     * @return array<string, array>
     */
    protected function columns(): array
    {
        $shop = config(Shop::class);

        return array_filter(self::COLUMNS, static fn ($column) => $column[2] === null || $shop->feature($column[2]));
    }

    /**
     * Two example rows from this site's data: one with the same stock for
     * every option, one with stock per option.
     *
     * @return list<array<string, string>>
     */
    protected function example_rows(): array
    {
        $all    = $this->category_model->get_all_categories() ?: [];
        $paths  = $this->category_paths_list($all);
        $leaf   = array_key_last($paths);
        $first  = static fn ($class, int $n) => array_slice(array_column((array) (new $class())->get_options(true), $class::NAME_FIELD), 0, $n);
        $colors = $first(ColorModel::class, 2) ?: ['Navy', 'Red'];
        $sizes  = $first(SizeModel::class, 3) ?: ['S', 'M', 'L'];
        $fabric = $first(MaterialModel::class, 1)[0] ?? '';
        $brand  = $first(BrandModel::class, 1)[0] ?? '';
        $cols   = $this->columns();

        $per_option = [];
        foreach (isset($cols['colors']) ? $colors : [''] as $c) {
            foreach (isset($cols['sizes']) ? array_slice($sizes, 0, 2) : [''] as $s) {
                $per_option[] = ltrim(($c !== '' && $s !== '' ? $c . '/' . $s : $c . $s), '/') . '=' . (count($per_option) + 1) * 5;
            }
        }

        $base = ['fabric' => $fabric, 'brand' => $brand, 'status' => '1', 'is_featured' => '0', 'weight' => '1 kg'];

        return [
            $base + [
                'category' => $leaf !== null ? $paths[$leaf] : 'Category name', 'product_name' => 'Sample Workwear Coverall', 'product_code' => 'SAMPLE-001',
                'price' => '180.00', 'discounted_price' => '150.00', 'is_new' => '1', 'colors' => implode('|', $colors), 'sizes' => implode('|', $sizes),
                'stock' => '10', 'low_stock_threshold' => '3', 'description' => 'Delete this example row before uploading.',
            ],
            $base + [
                'category' => $leaf !== null ? (string) $leaf : '1', 'product_name' => 'Sample Work Shirt', 'product_code' => 'SAMPLE-002',
                'price' => '95', 'discounted_price' => '', 'is_new' => '0', 'colors' => implode('|', $colors), 'sizes' => implode('|', array_slice($sizes, 0, 2)),
                'stock' => implode('|', $per_option), 'low_stock_threshold' => '', 'description' => 'Category given by its ID; stock given per colour / size.',
            ],
        ];
    }

    /**
     * The rows of the uploaded sheet as column => value (only the columns in
     * use, with their sheet line number under '#'), or an error message.
     *
     * @return list<array>|string
     */
    protected function read_upload()
    {
        $file = $this->request->getFile('bulk_data');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return 'Upload Excel Data is required: choose the Excel (.xlsx) or CSV file to upload.';
        }
        if (! $file->isValid()) {
            return in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'The file is larger than ' . (self::MAX_KB / 1024) . ' MB.'
                : 'The file could not be uploaded. Please try again.';
        }

        $ext  = strtolower($file->getClientExtension());
        $mime = $file->getMimeType();
        $path = $file->getTempName();
        if ($ext === 'xls') {
            return 'Excel 97-2003 (.xls) files can\'t be read. In Excel use File > Save As and choose "Excel Workbook (.xlsx)" or "CSV UTF-8".';
        }
        if (! in_array($ext, ['xlsx', 'csv'], true)) {
            return 'The filetype you are attempting to upload is not allowed: upload an Excel (.xlsx) or CSV (.csv) file.';
        }
        if ($file->getSize() / 1024 > self::MAX_KB) {
            return 'The file is larger than ' . (self::MAX_KB / 1024) . ' MB. Split it into smaller files.';
        }

        if ($ext === 'xlsx') {
            if (! in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'], true) || ! SimpleXlsx::is_xlsx($path)) {
                return 'The file is not a valid Excel (.xlsx) workbook.';
            }
            $sheet = SimpleXlsx::read($path, self::MAX_ROWS + 1);
        } else {
            if (! (str_starts_with($mime, 'text/') || in_array($mime, ['application/csv', 'application/vnd.ms-excel', 'application/octet-stream'], true))
                || str_contains((string) file_get_contents($path, false, null, 0, 8192), "\0")) {
                return 'The file is not a valid CSV file. In Excel use File > Save As > CSV UTF-8.';
            }
            $sheet = $this->read_csv($path);
        }
        if (is_string($sheet)) {
            return $sheet;
        }

        // The header: the first row with a value.
        while ($sheet && implode('', array_map('trim', $sheet[array_key_first($sheet)])) === '') {
            unset($sheet[array_key_first($sheet)]);
        }
        if (! $sheet) {
            return 'The file is empty. Download the products sample sheet to start from.';
        }
        $header      = $this->map_header($sheet[array_key_first($sheet)]);
        unset($sheet[array_key_first($sheet)]); // keys stay the sheet's line numbers - 1
        $columns     = $this->columns();
        $missing     = array_diff_key(array_filter($columns, static fn ($c) => $c[1]), array_flip($header));
        if ($missing) {
            return 'The first row must name the columns; missing: ' . implode(', ', array_column($missing, 0)) . '. Download the products sample sheet to start from.';
        }

        $rows = [];
        foreach ($sheet as $n => $values) {
            if (implode('', array_map('trim', $values)) === '') {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                return 'The file has more than ' . self::MAX_ROWS . ' products. Split it into smaller files.';
            }
            $row = array_fill_keys(array_keys($columns), '');
            foreach ($header as $i => $column) {
                $row[$column] = trim((string) ($values[$i] ?? ''));
            }
            $row['#'] = $n + 1; // the sheet's line number
            $rows[]   = $row;
        }

        return $rows ?: 'The file has no product rows below the column names.';
    }

    /**
     * A CSV file's rows (UTF-8; Excel's "CSV" in Windows-1252 and the ";"
     * separator of some regional settings are accepted too).
     *
     * @return list<list<string>>|string
     */
    protected function read_csv(string $path)
    {
        $text = (string) file_get_contents($path);
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }
        $first     = strtok($text, "\n");
        $separator = substr_count((string) $first, ';') > substr_count((string) $first, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'w+');
        fwrite($handle, $text);
        rewind($handle);
        $rows = [];
        while (($values = fgetcsv($handle, 0, $separator, '"', '')) !== false) {
            $rows[] = $values === [null] ? [] : array_map('strval', $values);
            if (count($rows) > self::MAX_ROWS + 1) {
                break;
            }
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Header cells => column keys (by position); unknown headers are noted in $ignored.
     *
     * @return array<int, string>
     */
    protected function map_header(array $cells): array
    {
        $names = [];
        foreach ($this->columns() as $key => $column) {
            foreach (array_merge([$key, $column[0]], $column[4]) as $name) {
                $names[$this->header_key($name)] = $key;
            }
        }
        $off = array_diff_key(self::COLUMNS, $this->columns());

        $header = [];
        foreach ($cells as $i => $cell) {
            $name = $this->header_key((string) $cell);
            if ($name === '') {
                continue;
            }
            if (isset($names[$name]) && ! in_array($names[$name], $header, true)) {
                $header[$i] = $names[$name];
            } else {
                $this->ignored[] = trim((string) $cell) . (isset($off[$name]) ? ' (feature off)' : '');
            }
        }
        $this->ignored = array_values(array_unique($this->ignored));

        return $header;
    }

    protected function header_key(string $name): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($name))), '_');
    }

    /**
     * Add each row. Returns per row: [line, code, name, error|null], or an
     * error message when nothing could be added.
     *
     * @param list<array> $rows
     *
     * @return list<array>|string
     */
    protected function import(array $rows)
    {
        $all     = $this->category_model->get_all_categories();
        $lookups = $this->lookups();
        if ($all === false || $lookups === null) {
            return 'The categories, fabrics, colours or sizes could not be loaded, so nothing was added. Please try again.';
        }
        $categories = $this->category_lookup($all);
        $clean      = new HtmlSanitizer();
        $images     = new ImageUpload();
        $results    = [];

        foreach ($rows as $row) {
            $line = $row['#'];
            $code = $row['product_code'];
            $name = $row['product_name'];
            $plan = $this->check_row($row, $categories, $lookups, $images);

            if (is_array($plan)) {
                $taken = $this->product_model->code_taken($code);
                $plan  = $taken === null ? 'Could not be checked; try again.' : ($taken ? 'Product code already exists; skipped.' : $plan);
            }
            $slug = is_array($plan) ? Products::free_slug($this->product_model, $this->meta_model, $name) : null;
            if (is_array($plan) && ! is_string($slug)) {
                $plan = $slug === false ? 'The URL slug could not be checked; try again.' : 'The product name needs at least one letter or number.';
            }
            if (is_string($plan)) {
                $results[] = [$line, $code, $name, $plan];

                continue;
            }

            $now         = date('Y-m-d H:i:s');
            $description = $row['description'] === '' ? null
                : $clean->clean(str_contains($row['description'], '<') ? $row['description'] : '<p>' . nl2br(html_escape($row['description'])) . '</p>');
            $data = [
                'product_name'             => $name,
                'friendly_url'             => $slug,
                'product_code'             => $code,
                'product_alt'              => mb_substr($name, 0, 100),
                'product_price'            => $row['price'],
                'product_discounted_price' => $row['discounted_price'] !== '' ? $row['discounted_price'] : '0.00',
                'products_description'     => $description,
                'is_new'                   => $row['is_new'] === '1' ? '1' : '0',
                'is_featured'              => $row['is_featured'] === '1' ? '1' : '0',
                'status'                   => $row['status'] === '0' ? '0' : '1',
                'product_added_date'       => $now,
                'product_updated_date'     => $now,
                // Columns of a product feature that is off are not in $row: the product gets no value, as from the form.
                'brand_id'                 => $plan['brand'][0] ?? null,
                'material_id'              => $plan['fabric'][0] ?? null,
                'product_weight'           => ($row['weight'] ?? '') !== '' ? $row['weight'] : null,
            ];

            $ancestors = [];
            foreach ($plan['categories'] as $category_id) {
                $ancestors[$category_id] = $this->category_model->ancestor_ids($category_id) ?: [$category_id];
            }

            $id = $this->product_model->save_product(0, $data, $plan['categories'], $ancestors);
            if ($id === false) {
                $results[] = [$line, $code, $name, 'Could not be saved; try again.'];

                continue;
            }

            // Colours and sizes with their stock matrix, quantities, photos and page address.
            $saved = $this->product_model->save_variants($id, $plan['colors'], $plan['sizes']);
            $stock = [];
            foreach ($saved ? (array) $this->product_model->get_stock_rows($id) : [] as $stock_row) {
                $key = (int) $stock_row['product_color'] . '-' . (int) $stock_row['product_size'];
                $stock[(int) $stock_row['stock_id']] = [
                    'product_price' => 0, 'product_discounted_price' => 0, // 0 = the product's prices
                    'product_quantity' => $plan['stock'][$key] ?? $plan['stock']['*'] ?? 0,
                    'inventory' => $plan['threshold'],
                ];
            }
            $saved = $saved && $stock && $this->product_model->save_stock($id, $stock);
            foreach ($saved ? $plan['images'] : [] as $image) {
                $saved = $saved && $this->product_model->add_media($id, $image) !== false;
            }
            $saved = $saved && $this->meta_model->save_entity_meta(ProductModel::META_TYPE, $id, ProductModel::URL_PREFIX . $slug, [
                'meta_title'       => mb_substr($name, 0, 450),
                'meta_keyword'     => mb_substr($name, 0, 450),
                'meta_description' => mb_substr(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $description)))), 0, 160) ?: $name,
            ]);

            if (! $saved) {
                // Added whole or not at all: remove the partly saved product (its photo files stay, they were uploaded separately).
                $this->product_model->bulk_delete([$id]);
                $results[] = [$line, $code, $name, 'Its colours, sizes, stock, photos or SEO data could not be saved, so it was not added; try again.'];

                continue;
            }

            $results[] = [$line, $code, $name, null];
        }

        return $results;
    }

    /**
     * Each lookup column in use => ['names' => lower-cased name => id, 'ids' => id => name]; NULL when one could not be loaded.
     *
     * @return array<string, array{names: array<string, int>, ids: array<int, string>}>|null
     */
    protected function lookups(): ?array
    {
        $lookups = [];

        foreach (array_intersect_key(self::LOOKUPS, $this->columns()) as $column => [$class]) {
            $model   = new $class();
            $options = $model->get_options();
            if ($options === false) {
                return null;
            }
            $lookups[$column] = ['names' => [], 'ids' => []];
            foreach ($options as $id => $option) {
                $lookups[$column]['names'][$this->lookup_key($option[$model::NAME_FIELD])] = (int) $id;
                $lookups[$column]['ids'][(int) $id] = $option[$model::NAME_FIELD];
            }
        }

        return $lookups;
    }

    /**
     * What a row adds — categories, lookup ids, stock per option, threshold,
     * photo files — or why it can't be added (the first problem found).
     *
     * @return array|string
     */
    protected function check_row(array $row, array $categories, array $lookups, ImageUpload $images)
    {
        foreach ($this->columns() as $column => [$header, $required]) {
            if ($required && $row[$column] === '') {
                return $header . ' is required.';
            }
        }
        if (mb_strlen($row['product_name']) > 200) {
            return 'Product Name is too long (at most 200 characters).';
        }
        if (mb_strlen($row['product_code']) > 64) {
            return 'Product Code is too long (at most 64 characters).';
        }

        $plan = ['categories' => []];
        $cell = preg_match('/^[0-9\s,]+$/', $row['category']) ? str_replace(',', '|', $row['category']) : $row['category'];
        foreach ($this->names($cell, '|') as $value) {
            $id = $categories['paths'][mb_strtolower($this->normalise_path($value))] ?? (ctype_digit($value) ? ($categories['ids'][(int) $value] ?? null) : null);
            if ($id === null) {
                return 'Category "' . $value . '" not found' . (isset($categories['ambiguous'][mb_strtolower($value)]) ? ': several categories have that name, give its path (Parent > ' . $value . ') or its ID.' : '.') . ' See Download Categories to View IDs.';
            }
            $plan['categories'][] = $id;
        }
        $plan['categories'] = array_values(array_unique($plan['categories']));

        if (! preg_match(Products::AMOUNT_PATTERN, $row['price']) || (float) $row['price'] <= 0) {
            return 'Price must be a positive amount with at most 2 decimals, e.g. 180.00.';
        }
        if ($row['discounted_price'] !== '' && (float) $row['discounted_price'] != 0
            && (! preg_match(Products::AMOUNT_PATTERN, $row['discounted_price']) || (float) $row['discounted_price'] >= (float) $row['price'])) {
            return 'Discounted Price must be an amount lower than the price.';
        }
        foreach (['is_featured' => 'Is Featured', 'is_new' => 'Is New', 'status' => 'Status'] as $flag => $label) {
            if (! in_array($row[$flag], ['', '0', '1'], true)) {
                return $label . ' must be 1 or 0.';
            }
        }
        if (isset($row['weight']) && mb_strlen($row['weight']) > 55) {
            return 'Weight is too long (at most 55 characters).';
        }

        // Brand, fabric, colours, sizes: by name, else by ID.
        foreach (array_keys(self::LOOKUPS) as $column) {
            $plan[$column] = [];
            if (! isset($lookups[$column])) {
                continue;
            }
            [, $label, $page] = self::LOOKUPS[$column];
            $names            = $this->names($row[$column], '|,');
            if (in_array($column, ['brand', 'fabric'], true) && count($names) > 1) {
                return 'Give one ' . strtolower($label) . ' only; a product has one ' . strtolower($label) . '.';
            }
            foreach ($names as $value) {
                $id = $this->lookup_id($value, $lookups[$column]);
                if ($id === null) {
                    $list = array_column(self::ID_LISTS, 0, 2)[$column];

                    return $label . ' "' . $value . '" not found in ' . $page . '. See Download ' . $list . ' to View IDs.';
                }
                $plan[$column][] = $id;
            }
            $plan[$column] = array_values(array_unique($plan[$column]));
        }

        $stock = $this->parse_stock($row['stock'], $plan['colors'], $plan['sizes'], $lookups);
        if (is_string($stock)) {
            return $stock;
        }
        $plan['stock'] = $stock;

        $threshold = $row['low_stock_threshold'] ?? '';
        if ($threshold !== '' && (! ctype_digit($threshold) || (int) $threshold > self::MAX_STOCK)) {
            return 'Low Stock Threshold must be a whole number of 0 or more.';
        }
        $plan['threshold'] = (int) $threshold;

        $plan['images'] = [];
        foreach (['image_1', 'image_2', 'image_3', 'image_4'] as $n => $column) {
            if ($row[$column] === '') {
                continue;
            }
            $error = $this->image_error($row[$column], $images);
            if ($error !== null) {
                return 'Image ' . ($n + 1) . ' "' . $row[$column] . '": ' . $error;
            }
            $plan['images'][] = $row[$column];
        }
        $plan['images'] = array_values(array_unique($plan['images']));

        return $plan;
    }

    /**
     * The Stock cell as quantities: '*' => quantity for every option, or
     * "colour-size" ids (0 = none) => quantity; or an error message.
     *
     * @param list<int> $colors
     * @param list<int> $sizes
     *
     * @return array<string, int>|string
     */
    protected function parse_stock(string $cell, array $colors, array $sizes, array $lookups)
    {
        if ($cell === '' || ctype_digit($cell)) {
            if ($cell !== '' && (int) $cell > self::MAX_STOCK) {
                return 'Stock must be at most ' . self::MAX_STOCK . '.';
            }

            return ['*' => (int) $cell];
        }

        $format = $colors && $sizes ? 'Colour/Size=quantity, e.g. Navy/S=10|Navy/M=5' : ($colors ? 'Colour=quantity, e.g. Navy=10|Red=5' : ($sizes ? 'Size=quantity, e.g. S=10|M=5' : 'one whole number (the product has no colours or sizes)'));
        if (! $colors && ! $sizes) {
            return 'Stock must be ' . $format . '.';
        }

        $stock = [];
        foreach ($this->names($cell, '|,') as $entry) {
            if (! preg_match('/^(.+?)\s*[=:]\s*([0-9]{1,6})$/u', $entry, $m)) {
                return 'Stock "' . $entry . '" is not valid; use ' . $format . '.';
            }
            $parts = array_map('trim', explode('/', $m[1]));
            if (count($parts) !== ($colors && $sizes ? 2 : 1)) {
                return 'Stock "' . $entry . '" is not valid; use ' . $format . '.';
            }
            $color = $colors ? $this->lookup_id($parts[0], $lookups['colors'] ?? ['names' => [], 'ids' => []]) : 0;
            $size  = $sizes ? $this->lookup_id($parts[$colors ? 1 : 0], $lookups['sizes'] ?? ['names' => [], 'ids' => []]) : 0;
            if (($colors && ! in_array($color, $colors, true)) || ($sizes && ! in_array($size, $sizes, true))) {
                return 'Stock "' . $entry . '": that option is not one of the product\'s colours / sizes.';
            }
            $key = $color . '-' . $size;
            if (isset($stock[$key])) {
                return 'Stock "' . $entry . '": that option is listed twice.';
            }
            $stock[$key] = (int) $m[2];
        }

        return $stock + ['*' => 0]; // options not listed: 0
    }

    /**
     * Why a photo file name can't be used, or NULL: it must name a real image
     * (an accepted type) already in the products upload folder.
     */
    protected function image_error(string $file, ImageUpload $images): ?string
    {
        $mimes = config(Uploads::class)->mimes;
        $ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($file !== basename($file) || str_starts_with($file, '.') || preg_match('#[\\\\/:*?"<>|\x00-\x1F]#', $file)) {
            return 'give the file name only, without a folder.';
        }
        if (! isset($mimes[$ext])) {
            return 'not an accepted image type (' . implode(', ', array_keys($mimes)) . ').';
        }
        $path = $images->absolute(Products::IMAGE_FOLDER, $file);
        if ($path === '' || ! is_file($path)) {
            return 'file not found in uploads/' . Products::IMAGE_FOLDER . '/; upload it there first.';
        }
        $size = @getimagesize($path);
        if ($size === false || ! in_array((string) $size['mime'], $mimes[$ext], true)) {
            return 'the file is not a valid image.';
        }
        if ($size[0] > Products::IMAGE_MAX_SIZE[0] || $size[1] > Products::IMAGE_MAX_SIZE[1]) {
            return 'the image is larger than ' . Products::IMAGE_MAX_SIZE[0] . ' × ' . Products::IMAGE_MAX_SIZE[1] . ' pixels.';
        }

        return null;
    }

    /**
     * Every category's name path ("Fire Retardant Coveralls > FR Coveralls") by id, in display order.
     *
     * @param array<int, array> $all
     *
     * @return array<int, string>
     */
    protected function category_paths_list(array $all): array
    {
        $paths = [];
        foreach (array_keys($all) as $id) {
            $paths[$id] = implode(' > ', array_column((array) $this->category_model->path($id), 'category_name'));
        }
        asort($paths, SORT_NATURAL | SORT_FLAG_CASE);

        return $paths;
    }

    /**
     * How a Category cell is matched: 'paths' => lower-cased path (or plain
     * name when unique) => id, 'ids' => id => id, 'ambiguous' => names of several categories.
     *
     * @param array<int, array> $all
     */
    protected function category_lookup(array $all): array
    {
        $paths = [];
        $names = [];
        foreach ($this->category_paths_list($all) as $id => $path) {
            $paths[mb_strtolower($this->normalise_path($path))] = $id;
            $names[mb_strtolower($this->normalise_path((string) $all[$id]['category_name']))][] = $id;
        }

        $ambiguous = [];
        foreach ($names as $name => $ids) {
            if (count($ids) === 1) {
                $paths[$name] ??= $ids[0];
            } elseif (! isset($paths[$name])) {
                $ambiguous[$name] = true;
            }
        }

        return ['paths' => $paths, 'ids' => array_combine(array_keys($all), array_keys($all)), 'ambiguous' => $ambiguous];
    }

    protected function normalise_path(string $path): string
    {
        return implode(' > ', array_filter(array_map(fn ($part) => preg_replace('/\s+/u', ' ', trim($part)), preg_split('/\s*(?:-->|>|›|»)\s*/u', $path)), 'strlen'));
    }

    /**
     * The values of a cell, split at any of $separators, trimmed, empty ones dropped.
     *
     * @return list<string>
     */
    protected function names(string $cell, string $separators): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[' . preg_quote($separators, '/') . ']/', $cell)), 'strlen'));
    }

    /**
     * How a name is matched: letter case and repeated spaces don't matter.
     */
    protected function lookup_key(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }

    /**
     * A record's id from its name, else from its ID; NULL when neither matches.
     */
    protected function lookup_id(string $value, array $lookup): ?int
    {
        return $lookup['names'][$this->lookup_key($value)] ?? (ctype_digit($value) && isset($lookup['ids'][(int) $value]) ? (int) $value : null);
    }
}
