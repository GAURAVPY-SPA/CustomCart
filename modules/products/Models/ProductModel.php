<?php

namespace Modules\Products\Models;

use App\Models\BaseModel;
use Modules\Brand\Models\BrandModel;
use Modules\Category\Models\CategoryModel;
use Modules\Color\Models\ColorModel;
use Modules\Material\Models\MaterialModel;
use Modules\Size\Models\SizeModel;
use Throwable;

/**
 * Products (wl_products) with their colours and sizes (wl_product_colors,
 * wl_product_sizes), stock matrix (wl_product_stock), photos
 * (wl_products_media) and related products (wl_products_related).
 *
 * Pricing, as in the reference: a stock row (colour × size, 0 = none) may set
 * its own price and discounted price; 0 means "use the product's". The selling
 * price is the discounted price when it is set and lower than the price.
 * Stock: wl_product_stock.product_quantity is what is available and
 * used_quantity what was sold; wl_products.product_quantity is their total.
 *
 * Categories: a product is filed in one or more categories
 * (wl_product_categories, product × category, unique). category_id is its
 * main category (breadcrumbs, the category on its card), always one of them;
 * category_links is the union of its categories and their ancestors (kept
 * for the reference's FIND_IN_SET queries). A category page lists the
 * products filed in it or below it, each once (EXISTS, never a join).
 *
 * A product is public when it is active and at least one of its categories
 * (with every ancestor) is active. Deleted ('2') rows are never read.
 */
class ProductModel extends BaseModel
{
    public const TABLE       = 'wl_products';
    public const PRIMARY_KEY = 'products_id';

    /**
     * Product × category assignments.
     */
    public const CATEGORY_TABLE = 'wl_product_categories';

    /**
     * entity_type of a product page's wl_meta_tags row (Products::detail).
     */
    public const META_TYPE = 'products/detail';

    /**
     * Address prefix of a product page: none, as in the reference — a
     * product's page is its friendly_url at the site root (the page_url of
     * its wl_meta_tags row, entity_type products/detail). Kept for the code
     * that builds addresses as URL_PREFIX . friendly_url.
     */
    public const URL_PREFIX = '';

    /**
     * Sort options of the public listing => ORDER BY.
     */
    public const SORTS = [
        'default'    => 'is_featured DESC, products_id DESC',
        'price-asc'  => 'display_price ASC, products_id DESC',
        'price-desc' => 'display_price DESC, products_id DESC',
        'newest'     => 'product_added_date DESC, products_id DESC',
        'name'       => 'product_name ASC',
        'popular'    => 'products_viewed DESC, products_id DESC',
    ];

    /**
     * The selling price of a product (or one of its stock rows):
     * ['regular' => price, 'sale' => what the customer pays, 'saving' => regular - sale, 'percent' => rounded saving %].
     *
     * @param array      $product wl_products row
     * @param array|null $stock   wl_product_stock row
     */
    public static function prices(array $product, ?array $stock = null): array
    {
        $regular    = $stock && (float) $stock['product_price'] > 0 ? (float) $stock['product_price'] : (float) $product['product_price'];
        $discounted = $stock && (float) $stock['product_discounted_price'] > 0 ? (float) $stock['product_discounted_price'] : (float) $product['product_discounted_price'];
        $sale       = $discounted > 0 && $discounted < $regular ? $discounted : $regular;

        return [
            'regular' => round($regular, 2),
            'sale'    => round($sale, 2),
            'saving'  => round($regular - $sale, 2),
            'percent' => $regular > 0 ? (int) round(($regular - $sale) / $regular * 100) : 0,
        ];
    }

    /* -----------------------------------------------------------------
     * Sitepanel
     * ----------------------------------------------------------------- */

    /**
     * Admin list, newest first; the total is left in $this->total_rec_found.
     *
     * @param string $stock '' | 'low' (at or under the low-stock threshold) | 'out'
     *
     * @return array|false
     */
    public function get_products(int $offset, int $per_page, string $keyword = '', string $status = '', int $category_id = 0, int $brand_id = 0, string $stock = '')
    {
        $this->total_rec_found = 0;
        if (! $this->db_ready()) {
            return false;
        }

        $subtree = [];
        if ($category_id > 0) {
            $subtree = (new CategoryModel())->descendant_ids($category_id);
            if ($subtree === false) {
                return false;
            }
            $subtree[] = $category_id;
        }

        $filter = function () use ($keyword, $status, $subtree, $brand_id, $stock) {
            $db = $this->table(self::TABLE . ' p')->where('p.status <>', '2');
            if ($keyword !== '') {
                $db->groupStart()->like('p.product_name', $keyword)->orLike('p.product_code', $keyword)->groupEnd();
            }
            $status !== '' && $db->where('p.status', $status);
            $subtree && $db->where($this->in_categories($subtree), null, false);
            $brand_id > 0 && $db->where('p.brand_id', $brand_id);
            if ($stock === 'out') {
                $db->where('p.product_quantity <=', 0);
            } elseif ($stock === 'low') {
                $db->where('EXISTS (SELECT 1 FROM wl_product_stock s WHERE s.product_id = p.products_id AND s.product_quantity <= IF(s.inventory > 0, s.inventory, ' . service('siteSettings')->low_stock_threshold() . '))', null, false);
            }

            return $db;
        };

        $count = $this->single_row($filter()->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        return $this->all_rows($filter()
            ->select('p.*, c.category_name, b.brand_name, ' . $this->image_select('p'), false)
            ->join(CategoryModel::TABLE . ' c', 'c.category_id = p.category_id', 'left')
            ->join(BrandModel::TABLE . ' b', 'b.brand_id = p.brand_id', 'left')
            ->orderBy('p.products_id', 'DESC')->limit($per_page, $offset)->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_product_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id, 'status <>' => '2']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_products_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('status <>', '2')->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * @return bool|null NULL when it could not be checked
     */
    public function code_taken(string $code, int $except_id = 0): ?bool
    {
        $count = $this->findCount(self::TABLE, ['product_code' => $code, self::PRIMARY_KEY . ' <>' => $except_id]);

        return $count === false ? null : $count > 0;
    }

    /**
     * @return bool|null NULL when it could not be checked
     */
    public function url_taken(string $friendly_url, int $except_id = 0): ?bool
    {
        $count = $this->findCount(self::TABLE, ['friendly_url' => $friendly_url, self::PRIMARY_KEY . ' <>' => $except_id]);

        return $count === false ? null : $count > 0;
    }

    /* -----------------------------------------------------------------
     * Categories (wl_product_categories)
     * ----------------------------------------------------------------- */

    /**
     * The categories a product is filed in, the main one first.
     *
     * @return list<int>|false
     */
    public function get_category_ids(int $product_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::CATEGORY_TABLE . ' pc')->select('pc.category_id')
            ->join(self::TABLE . ' p', 'p.products_id = pc.product_id')->where('pc.product_id', $product_id)
            ->orderBy('(pc.category_id = p.category_id) DESC, pc.id ASC', '', false)->get());

        return $rows === false ? false : array_map('intval', array_column($rows, 'category_id'));
    }

    /**
     * The categories of several products (a list page): products_id => category ids, the main one first.
     *
     * @param list<int> $ids
     *
     * @return array<int, list<int>>|false
     */
    public function get_category_ids_by_products(array $ids)
    {
        if (! $ids) {
            return [];
        }
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::CATEGORY_TABLE . ' pc')->select('pc.product_id, pc.category_id')
            ->join(self::TABLE . ' p', 'p.products_id = pc.product_id')->whereIn('pc.product_id', array_map('intval', $ids))
            ->orderBy('pc.product_id ASC, (pc.category_id = p.category_id) DESC, pc.id ASC', '', false)->get());
        if ($rows === false) {
            return false;
        }

        $by_product = [];

        foreach ($rows as $row) {
            $by_product[(int) $row['product_id']][] = (int) $row['category_id'];
        }

        return $by_product;
    }

    /**
     * Insert or update a product and file it in exactly $category_ids, in one
     * transaction (nothing is saved when any part fails). The main category
     * (category_id) stays when it is still chosen, else it is the first one;
     * category_links becomes the chosen categories and their ancestors.
     *
     * $quantity (NULL = leave the stock alone) is the "Available quantity" of
     * a product without colour / size options: its single stock row is
     * created or updated. The listing price and total stock are then
     * recomputed (refresh_summary()) inside the same transaction.
     *
     * @param int                   $id           0 = a new product
     * @param array                 $data         wl_products columns (without category_id / category_links)
     * @param list<int>             $category_ids At least one
     * @param array<int, list<int>> $ancestors    category_id => its ancestor ids, self first (CategoryModel::ancestor_ids())
     *
     * @return false|int The product id
     */
    public function save_product(int $id, array $data, array $category_ids, array $ancestors, ?int $quantity = null)
    {
        $category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids), static fn ($n) => $n > 0)));
        if (! $category_ids || ! $this->db_ready()) {
            return false;
        }

        $current = $id > 0 ? $this->find(self::TABLE, [self::PRIMARY_KEY => $id]) : null;
        if ($id > 0 && ! is_array($current)) {
            return false;
        }
        $main = $current && in_array((int) $current['category_id'], $category_ids, true) ? (int) $current['category_id'] : $category_ids[0];

        $links = [];
        foreach (array_merge([$main], $category_ids) as $category_id) {
            foreach ($ancestors[$category_id] ?? [$category_id] as $link) {
                $links[(int) $link] = (int) $link;
            }
        }
        $data['category_id']    = $main;
        $data['category_links'] = implode(',', $links);

        try {
            $this->db->transException(true)->transStart();

            if ($id > 0) {
                $this->table(self::TABLE)->where(self::PRIMARY_KEY, $id)->update($data);
            } else {
                $this->table(self::TABLE)->insert($data);
                $id = (int) $this->db->insertID();
            }

            $this->sync_categories($id, $category_ids);

            if ($quantity !== null) {
                $rows = $this->table('wl_product_stock')->where('product_id', $id)->get()->getResultArray();
                if (! $rows) {
                    $this->table('wl_product_stock')->insert(['product_id' => $id, 'product_color' => 0, 'product_size' => 0, 'product_quantity' => max(0, $quantity)]);
                } elseif (count($rows) === 1) {
                    $this->table('wl_product_stock')->where('stock_id', (int) $rows[0]['stock_id'])->update(['product_quantity' => max(0, $quantity)]);
                }
            }

            if (! $this->refresh_summary($id)) {
                throw new \RuntimeException('the listing price and stock could not be recomputed');
            }

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'ProductModel::save_product(' . $id . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }

        return $id;
    }

    /**
     * File a product in exactly these categories (the bulk import), keeping
     * its main category when it is one of them.
     *
     * @param list<int> $category_ids
     */
    public function save_categories(int $product_id, array $category_ids): bool
    {
        if (! $category_ids || ! $this->db_ready()) {
            return false;
        }

        try {
            $this->db->transException(true)->transStart();
            $this->sync_categories($product_id, array_values(array_unique(array_map('intval', $category_ids))));
            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'ProductModel::save_categories(' . $product_id . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }

        return true;
    }

    /**
     * Inside a transaction: remove the product's other categories and add the
     * missing ones. Rows of other products are never touched; the unique key
     * (product_id, category_id) rules out duplicates.
     *
     * @param list<int> $category_ids
     */
    protected function sync_categories(int $product_id, array $category_ids): void
    {
        $this->table(self::CATEGORY_TABLE)->where('product_id', $product_id)->whereNotIn('category_id', $category_ids)->delete();

        $have = array_map('intval', array_column($this->table(self::CATEGORY_TABLE)->select('category_id')->where('product_id', $product_id)->get()->getResultArray(), 'category_id'));

        foreach (array_diff($category_ids, $have) as $category_id) {
            $this->table(self::CATEGORY_TABLE)->insert(['product_id' => $product_id, 'category_id' => (int) $category_id]);
        }
    }

    /**
     * WHERE condition: the product (alias p) is filed in one of these categories.
     * One EXISTS per product, so a product in several of them is still one row.
     *
     * @param list<int> $category_ids
     */
    protected function in_categories(array $category_ids): string
    {
        $ids = array_map('intval', $category_ids) ?: [0];

        return 'EXISTS (SELECT 1 FROM ' . self::CATEGORY_TABLE . ' pc WHERE pc.product_id = p.products_id AND pc.category_id IN (' . implode(',', $ids) . '))';
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status, 'product_updated_date' => date('Y-m-d H:i:s')]);
    }

    /**
     * Update the CI3 list flag used by the Product Set As / Unset As controls.
     * Only the supported product flag can be changed through this method.
     *
     * @param list<int> $ids
     */
    public function bulk_update_flag(array $ids, string $flag, string $value): bool
    {
        if ($flag !== 'is_recently_added' || ! in_array($value, ['0', '1'], true)) {
            return false;
        }

        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, [
            $flag                  => $value,
            'product_updated_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete products and their wl_meta_tags rows (their page addresses) in
     * one transaction: both go, or nothing does. The foreign keys also remove
     * their categories, colours, sizes, stock rows, photo rows, related links,
     * reviews and wishlist entries; the caller removes the photo files. Order
     * lines keep their copy of the product.
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn ($n) => $n > 0));
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        try {
            $this->db->transException(true)->transStart();
            $this->table('wl_meta_tags')->where('entity_type', self::META_TYPE)->whereIn('entity_id', $ids)->delete();
            $this->table(self::TABLE)->whereIn(self::PRIMARY_KEY, $ids)->delete();
            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'ProductModel::bulk_delete(' . implode(',', $ids) . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }

        return true;
    }

    /**
     * Products that are filed only in these categories (their main category
     * or every assignment among them, any status): deleting the categories
     * would leave them without one. products_id => product_name.
     *
     * @param list<int> $category_ids
     *
     * @return array<int, string>|false
     */
    public function products_only_in(array $category_ids)
    {
        $ids = implode(',', array_map('intval', $category_ids) ?: [0]);
        if (! $this->db_ready()) {
            return false;
        }

        $rows = $this->all_rows($this->db->query(
            'SELECT p.products_id, p.product_name FROM ' . self::TABLE . ' p'
            . ' WHERE (p.category_id IN (' . $ids . ') OR EXISTS (SELECT 1 FROM ' . self::CATEGORY_TABLE . ' a WHERE a.product_id = p.products_id AND a.category_id IN (' . $ids . ')))'
            . ' AND NOT EXISTS (SELECT 1 FROM ' . self::CATEGORY_TABLE . ' b WHERE b.product_id = p.products_id AND b.category_id NOT IN (' . $ids . '))',
        ));

        return $rows === false ? false : array_column($rows, 'product_name', 'products_id');
    }

    /**
     * Inside the caller's transaction (it throws on failure): take these
     * categories off every product filed in them. Each product keeps its other
     * categories; its main category stays when it isn't one of these, else
     * becomes its first remaining one, and category_links is rebuilt. Call only
     * when products_only_in() found none.
     *
     * @param list<int> $category_ids
     * @param callable  $ancestors    fn (int $category_id): list<int> (the category, then its ancestors)
     *
     * @return int Products changed
     */
    public function unfile_categories(array $category_ids, callable $ancestors): int
    {
        if (! $this->db_ready()) {
            throw new \RuntimeException('no database connection');
        }
        $ids      = array_map('intval', $category_ids);
        $products = $this->table(self::TABLE . ' p')->select('p.products_id, p.category_id')
            ->groupStart()->whereIn('p.category_id', $ids)
            ->orWhere('EXISTS (SELECT 1 FROM ' . self::CATEGORY_TABLE . ' a WHERE a.product_id = p.products_id AND a.category_id IN (' . implode(',', $ids) . '))', null, false)
            ->groupEnd()->get()->getResultArray();

        foreach ($products as $product) {
            $pid  = (int) $product['products_id'];
            $left = array_map('intval', array_column($this->table(self::CATEGORY_TABLE)->select('category_id')->where('product_id', $pid)
                ->whereNotIn('category_id', $ids)->orderBy('id', 'ASC')->get()->getResultArray(), 'category_id'));
            if (! $left) {
                throw new \RuntimeException('product ' . $pid . ' would be left without a category');
            }
            $main  = in_array((int) $product['category_id'], $left, true) ? (int) $product['category_id'] : $left[0];
            $links = [];
            foreach (array_merge([$main], $left) as $category_id) {
                foreach ($ancestors($category_id) as $link) {
                    $links[(int) $link] = (int) $link;
                }
            }

            $this->table(self::CATEGORY_TABLE)->where('product_id', $pid)->whereIn('category_id', $ids)->delete();
            $this->table(self::TABLE)->where(self::PRIMARY_KEY, $pid)->update(['category_id' => $main, 'category_links' => implode(',', $links)]);
        }

        return count($products);
    }

    /* -----------------------------------------------------------------
     * Colours, sizes and the stock matrix
     * ----------------------------------------------------------------- */

    /**
     * @return list<int>|false
     */
    public function get_color_ids(int $product_id)
    {
        return $this->link_ids('wl_product_colors', 'color_id', $product_id);
    }

    /**
     * @return list<int>|false
     */
    public function get_size_ids(int $product_id)
    {
        return $this->link_ids('wl_product_sizes', 'size_id', $product_id);
    }

    /**
     * @return list<int>|false
     */
    protected function link_ids(string $table, string $column, int $product_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table($table)->select($column)->where('product_id', $product_id)->get());

        return $rows === false ? false : array_map('intval', array_column($rows, $column));
    }

    /**
     * Save a product's colours and sizes and bring its stock matrix in line,
     * as the reference did: one stock row per colour × size (colours only:
     * size 0; sizes only: colour 0; neither: one 0 × 0 row). Existing rows
     * keep their prices and quantities; rows of removed combinations go.
     *
     * @param list<int> $color_ids
     * @param list<int> $size_ids
     */
    public function save_variants(int $product_id, array $color_ids, array $size_ids): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        $colors = $color_ids ?: [0];
        $sizes  = $size_ids ?: [0];
        $wanted = [];

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $wanted[$color . '-' . $size] = ['product_color' => (int) $color, 'product_size' => (int) $size];
            }
        }

        try {
            $this->db->transException(true)->transStart();

            foreach (['wl_product_colors' => ['color_id', $color_ids], 'wl_product_sizes' => ['size_id', $size_ids]] as $table => [$column, $ids]) {
                $this->table($table)->where('product_id', $product_id)->delete();
                foreach ($ids as $id) {
                    $this->table($table)->insert(['product_id' => $product_id, $column => (int) $id]);
                }
            }

            $existing = $this->table('wl_product_stock')->where('product_id', $product_id)->get()->getResultArray();

            foreach ($existing as $row) {
                $key = $row['product_color'] . '-' . $row['product_size'];
                if (isset($wanted[$key])) {
                    unset($wanted[$key]);
                } else {
                    $this->table('wl_product_stock')->where('stock_id', (int) $row['stock_id'])->delete();
                }
            }

            foreach ($wanted as $combination) {
                $this->table('wl_product_stock')->insert($combination + ['product_id' => $product_id]);
            }

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'ProductModel::save_variants(' . $product_id . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }

        return $this->refresh_summary($product_id);
    }

    /**
     * The stock matrix with colour and size names, in colour then size order.
     *
     * @return list<array>|false
     */
    public function get_stock_rows(int $product_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table('wl_product_stock s')
            ->select('s.*, c.color_name, c.color_code, z.size_name')
            ->join(ColorModel::TABLE . ' c', 'c.color_id = s.product_color', 'left')
            ->join(SizeModel::TABLE . ' z', 'z.size_id = s.product_size', 'left')
            ->where('s.product_id', $product_id)
            ->orderBy('c.sort_order ASC, c.color_name ASC, z.sort_order ASC, z.size_name ASC')->get());
    }

    /**
     * One stock row of a product by colour and size (0 = none).
     *
     * @return array|false|null
     */
    public function get_stock_row(int $product_id, int $color_id, int $size_id)
    {
        return $this->find('wl_product_stock', ['product_id' => $product_id, 'product_color' => $color_id, 'product_size' => $size_id]);
    }

    /**
     * Save the stock matrix editor (the reference's update_stock): stock_id =>
     * [product_price, product_discounted_price, product_quantity, inventory].
     * Only rows of this product are changed.
     *
     * @param array<int, array> $rows
     */
    public function save_stock(int $product_id, array $rows): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        try {
            $this->db->transException(true)->transStart();

            foreach ($rows as $stock_id => $row) {
                $price      = (float) $row['product_price'];
                $discounted = (float) $row['product_discounted_price'];
                $this->table('wl_product_stock')->where(['stock_id' => (int) $stock_id, 'product_id' => $product_id])->update([
                    'product_price'            => $price,
                    'product_discounted_price' => $discounted,
                    'product_discount_percent' => $price > 0 && $discounted > 0 && $discounted < $price ? round(($price - $discounted) / $price * 100, 2) : 0,
                    'product_quantity'         => (int) $row['product_quantity'],
                    'inventory'                => (int) $row['inventory'],
                ]);
            }

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'ProductModel::save_stock(' . $product_id . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }

        return $this->refresh_summary($product_id);
    }

    /**
     * Recompute the product's total stock and its listing price (the lowest
     * selling price over its stock rows) after prices or stock changed.
     */
    public function refresh_summary(int $product_id): bool
    {
        $product = $this->find(self::TABLE, [self::PRIMARY_KEY => $product_id]);
        $rows    = $this->db_ready() ? $this->all_rows($this->table('wl_product_stock')->where('product_id', $product_id)->get()) : false;
        if (! is_array($product) || $rows === false) {
            return false;
        }

        $best     = self::prices($product);
        $quantity = 0;

        foreach ($rows as $index => $row) {
            $prices = self::prices($product, $row);
            ($index === 0 || $prices['sale'] < $best['sale']) && $best = $prices;
            $quantity += max(0, (int) $row['product_quantity']);
        }

        $regular  = (float) $product['product_price'];
        $discount = (float) $product['product_discounted_price'];

        return $this->safe_update(self::TABLE, [
            'product_quantity'         => $quantity,
            'display_price'            => $best['sale'],
            'display_regular_price'    => $best['regular'],
            'product_discount_percent' => $regular > 0 && $discount > 0 && $discount < $regular ? round(($regular - $discount) / $regular * 100, 2) : 0,
        ], [self::PRIMARY_KEY => $product_id]);
    }

    /**
     * Take sold units from a stock row, only when that many are available
     * (one atomic update, so two orders can't both take the last unit).
     * Call inside the order's transaction.
     */
    public function take_stock(int $stock_id, int $quantity): bool
    {
        if ($quantity < 1 || ! $this->db_ready()) {
            return false;
        }

        $this->db->query(
            'UPDATE wl_product_stock SET product_quantity = product_quantity - ?, used_quantity = used_quantity + ? WHERE stock_id = ? AND product_quantity >= ?',
            [$quantity, $quantity, $stock_id, $quantity],
        );

        return $this->db->affectedRows() === 1;
    }

    /**
     * Give units back to a stock row (cancelled or returned items). A stock row
     * removed since the order is simply skipped.
     */
    public function return_stock(int $stock_id, int $quantity): bool
    {
        if ($quantity < 1 || ! $this->db_ready()) {
            return false;
        }

        return $this->db->query(
            'UPDATE wl_product_stock SET product_quantity = product_quantity + ?, used_quantity = GREATEST(used_quantity - ?, 0) WHERE stock_id = ?',
            [$quantity, $quantity, $stock_id],
        ) !== false;
    }

    /* -----------------------------------------------------------------
     * Photos
     * ----------------------------------------------------------------- */

    /**
     * Photos in display order, the default first.
     *
     * @return list<array>|false
     */
    public function get_media(int $product_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table('wl_products_media')->where('products_id', $product_id)
            ->orderBy("is_default = 'Y'", 'DESC', false)->orderBy('sort_order ASC, id ASC')->get());
    }

    /**
     * Add a photo (last in order; the default when it is the first).
     *
     * @return false|int
     */
    public function add_media(int $product_id, string $file, int $color_id = 0)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $last = $this->single_row($this->table('wl_products_media')->select('COUNT(*) AS n, MAX(sort_order) AS m', false)->where('products_id', $product_id)->get());
        if ($last === false) {
            return false;
        }

        return $this->safe_insert('wl_products_media', [
            'products_id'      => $product_id,
            'color_id'         => $color_id,
            'media_type'       => 'photo',
            'media'            => $file,
            'is_default'       => (int) $last['n'] === 0 ? 'Y' : 'N',
            'sort_order'       => (int) $last['m'] + 1,
            'media_date_added' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Save each photo's colour and order, and which one is the default.
     *
     * @param array<int, array{color_id: int, sort_order: int}> $rows media id => values
     */
    public function update_media(int $product_id, array $rows, int $default_id): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        foreach ($rows as $id => $row) {
            $data = ['color_id' => (int) $row['color_id'], 'sort_order' => (int) $row['sort_order'], 'is_default' => (int) $id === $default_id ? 'Y' : 'N'];
            if ($this->write_failed(fn () => (bool) $this->table('wl_products_media')->where(['id' => (int) $id, 'products_id' => $product_id])->update($data))) {
                return false;
            }
        }

        return $this->ensure_default_media($product_id);
    }

    /**
     * Remove photos of the product; returns the removed rows (their files are
     * deleted by the caller), or FALSE.
     *
     * @param list<int> $ids
     *
     * @return list<array>|false
     */
    public function delete_media(int $product_id, array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return [];
        }

        $rows = $this->all_rows($this->table('wl_products_media')->where('products_id', $product_id)->whereIn('id', $ids)->get());
        if (! $rows) {
            return $rows;
        }
        if ($this->write_failed(fn () => $this->table('wl_products_media')->where('products_id', $product_id)->whereIn('id', array_column($rows, 'id'))->delete() !== false)) {
            return false;
        }
        $this->ensure_default_media($product_id);

        return $rows;
    }

    /**
     * The first photo becomes the default when none is.
     */
    protected function ensure_default_media(int $product_id): bool
    {
        if ($this->findCount('wl_products_media', ['products_id' => $product_id, 'is_default' => 'Y']) !== 0) {
            return true;
        }
        $first = $this->single_row($this->table('wl_products_media')->where('products_id', $product_id)->orderBy('sort_order ASC, id ASC')->limit(1)->get());

        return ! is_array($first) || $this->safe_update('wl_products_media', ['is_default' => 'Y'], ['id' => (int) $first['id']]);
    }

    /**
     * SELECT expression: the product's default (else first) photo as 'image'.
     */
    protected function image_select(string $alias): string
    {
        return "(SELECT m.media FROM wl_products_media m WHERE m.products_id = {$alias}.products_id ORDER BY m.is_default = 'Y' DESC, m.sort_order ASC, m.id ASC LIMIT 1) AS image";
    }

    /* -----------------------------------------------------------------
     * Related products (the reference's wl_products_related, one direction)
     * ----------------------------------------------------------------- */

    /**
     * @return list<int>|false
     */
    public function get_related_ids(int $product_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table('wl_products_related')->select('related_id')->where('product_id', $product_id)->orderBy('sort_order ASC, id ASC')->get());

        return $rows === false ? false : array_map('intval', array_column($rows, 'related_id'));
    }

    /**
     * Products that can be picked as related: id => "name (code)".
     *
     * @return array<int, string>|false
     */
    public function get_related_options(int $except_id = 0)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::TABLE)->select('products_id, product_name, product_code, status')
            ->where(['status <>' => '2', self::PRIMARY_KEY . ' <>' => $except_id])->orderBy('product_name')->get());
        if ($rows === false) {
            return false;
        }

        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['products_id']] = $row['product_name'] . ' (' . $row['product_code'] . ')' . ($row['status'] === '1' ? '' : ' (inactive)');
        }

        return $options;
    }

    /**
     * Replace the related products, in the given order.
     *
     * @param list<int> $ids
     */
    public function save_related(int $product_id, array $ids): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        try {
            $this->db->transException(true)->transStart();
            $this->table('wl_products_related')->where('product_id', $product_id)->delete();

            foreach (array_values($ids) as $position => $id) {
                $this->table('wl_products_related')->insert([
                    'product_id' => $product_id, 'related_id' => (int) $id, 'sort_order' => $position, 'related_date_added' => date('Y-m-d H:i:s'),
                ]);
            }
            $this->db->transComplete();

            return true;
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'ProductModel::save_related(' . $product_id . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }
    }

    /* -----------------------------------------------------------------
     * Public pages
     * ----------------------------------------------------------------- */

    /**
     * Query builder over the public products (alias p): active and filed in
     * at least one public category. Select with public_select().
     *
     * @param list<int> $category_ids Public category ids
     */
    protected function public_builder(array $category_ids)
    {
        return $this->table(self::TABLE . ' p')
            ->join(BrandModel::TABLE . ' b', "b.brand_id = p.brand_id AND b.status = '1'", 'left')
            ->where('p.status', '1')
            ->where($this->in_categories($category_ids), null, false);
    }

    /**
     * Columns of a public product: p.*, brand_name, image, and the category
     * shown with it (category_name, category_url): its main category, or its
     * first public one when the main one is hidden. Subqueries, so a product
     * in several categories stays one row.
     *
     * @param list<int> $category_ids Public category ids
     */
    protected function public_select(array $category_ids): string
    {
        $ids   = implode(',', array_map('intval', $category_ids) ?: [0]);
        $shown = static fn (string $column) => '(SELECT c.' . $column . ' FROM ' . self::CATEGORY_TABLE . ' pc JOIN ' . CategoryModel::TABLE . ' c ON c.category_id = pc.category_id'
            . ' WHERE pc.product_id = p.products_id AND pc.category_id IN (' . $ids . ') ORDER BY (pc.category_id = p.category_id) DESC, pc.id ASC LIMIT 1)';

        return 'p.*, ' . $shown('category_name') . ' AS category_name, ' . $shown('friendly_url') . ' AS category_url, b.brand_name, ' . $this->image_select('p');
    }

    /**
     * Public categories in and below $category_id.
     *
     * @param list<int> $public Public category ids
     *
     * @return list<int>|false
     */
    protected function public_subtree(int $category_id, array $public)
    {
        $below = (new CategoryModel())->descendant_ids($category_id);

        return $below === false ? false : array_values(array_intersect(array_merge([$category_id], $below), $public));
    }

    /**
     * One page of public products. The total is left in $this->total_rec_found.
     *
     * @param array $filters category_id (int, includes sub-categories), keyword,
     *                       brand_ids / material_ids / color_ids / size_ids (list<int>),
     *                       prices (list of [min, max|null]), availability ('in' | 'out' | ''),
     *                       flag ('is_new' | 'is_hot' | 'is_featured' | 'is_popular')
     * @param string $sort   A key of SORTS
     *
     * @return list<array>|false
     */
    public function search_public(array $filters, string $sort, int $offset, int $limit)
    {
        $this->total_rec_found = 0;
        $public                = (new CategoryModel())->public_ids();
        if ($public === false || ! $this->db_ready()) {
            return false;
        }

        $subtree = [];
        if (! empty($filters['category_id'])) {
            $subtree = $this->public_subtree((int) $filters['category_id'], $public);
            if ($subtree === false) {
                return false;
            }
        }

        $build = function () use ($filters, $public, $subtree) {
            $db = $this->public_builder($public);

            if (! empty($filters['category_id'])) {
                $db->where($this->in_categories($subtree), null, false);
            }
            if (($filters['keyword'] ?? '') !== '') {
                $db->groupStart()->like('p.product_name', $filters['keyword'])->orLike('p.product_code', $filters['keyword'])->groupEnd();
            }
            ! empty($filters['brand_ids']) && $db->whereIn('p.brand_id', array_map('intval', $filters['brand_ids']));
            ! empty($filters['material_ids']) && $db->whereIn('p.material_id', array_map('intval', $filters['material_ids']));
            foreach (['color_ids' => ['wl_product_colors', 'color_id'], 'size_ids' => ['wl_product_sizes', 'size_id']] as $key => [$table, $column]) {
                if (! empty($filters[$key])) {
                    $db->where('EXISTS (SELECT 1 FROM ' . $table . ' v WHERE v.product_id = p.products_id AND v.' . $column . ' IN (' . implode(',', array_map('intval', $filters[$key])) . '))', null, false);
                }
            }
            if (! empty($filters['prices'])) {
                $db->groupStart();
                foreach ($filters['prices'] as [$min, $max]) {
                    $db->orGroupStart()->where('p.display_price >=', (float) $min);
                    $max !== null && $db->where('p.display_price <', (float) $max);
                    $db->groupEnd();
                }
                $db->groupEnd();
            }
            ($filters['availability'] ?? '') === 'in' && $db->where('p.product_quantity >', 0);
            ($filters['availability'] ?? '') === 'out' && $db->where('p.product_quantity <=', 0);
            if (in_array($filters['flag'] ?? '', ['is_new', 'is_hot', 'is_featured', 'is_popular'], true)) {
                $db->where('p.' . $filters['flag'], '1');
            }

            return $db;
        };

        $count = $this->single_row($build()->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        $order = implode(', ', array_map(static fn ($part) => 'p.' . trim($part), explode(',', self::SORTS[$sort] ?? self::SORTS['default'])));

        return $this->all_rows($build()->select($this->public_select($public), false)->orderBy($order, '', false)->limit($limit, $offset)->get());
    }

    /**
     * A public product with category_name, category_url, brand_name and material_name.
     *
     * @return array|false|null
     */
    public function get_public_product(int $id)
    {
        $public = (new CategoryModel())->public_ids();
        if ($public === false || ! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->public_builder($public)
            ->select($this->public_select($public) . ', mt.material_name', false)
            ->join(MaterialModel::TABLE . ' mt', "mt.material_id = p.material_id AND mt.status = '1'", 'left')
            ->where('p.products_id', $id)->limit(1)->get());
    }

    /**
     * Public products by id (cart, wishlist), keyed by products_id.
     *
     * @param list<int> $ids
     *
     * @return array<int, array>|false
     */
    public function get_public_by_ids(array $ids)
    {
        if (! $ids) {
            return [];
        }
        $public = (new CategoryModel())->public_ids();
        if ($public === false || ! $this->db_ready()) {
            return false;
        }

        $rows = $this->all_rows($this->public_builder($public)->select($this->public_select($public), false)->whereIn('p.products_id', $ids)->get());
        if ($rows === false) {
            return false;
        }

        $by_id = [];

        foreach ($rows as $row) {
            $by_id[(int) $row['products_id']] = $row;
        }

        return $by_id;
    }

    /**
     * Related products of a public product: the ones picked in the sitepanel
     * that are public, in their order; when none are picked, the newest that
     * share one of its categories (the design always shows the section).
     *
     * @return list<array>|false
     */
    public function get_public_related(array $product, int $limit = 4)
    {
        $ids = $this->get_related_ids((int) $product['products_id']);
        if ($ids === false) {
            return false;
        }

        if ($ids) {
            $rows = $this->get_public_by_ids($ids);
            if ($rows === false) {
                return false;
            }
            $related = [];
            foreach ($ids as $id) {
                isset($rows[$id]) && $related[] = $rows[$id];
            }

            return array_slice($related, 0, $limit);
        }

        $public = (new CategoryModel())->public_ids();
        $own    = $this->get_category_ids((int) $product['products_id']);
        if ($public === false || $own === false) {
            return false;
        }

        return $this->all_rows($this->public_builder($public)->select($this->public_select($public), false)
            ->where($this->in_categories(array_values(array_intersect($own, $public))), null, false)->where('p.products_id <>', (int) $product['products_id'])
            ->orderBy('p.products_id', 'DESC')->limit($limit)->get());
    }

    /**
     * Newest public products flagged with one of the is_* flags (home page,
     * store page); the newest public products when none is flagged.
     *
     * @return list<array>|false
     */
    public function get_public_flagged(string $flag, int $limit = 8)
    {
        $rows = $this->search_public(['flag' => $flag], 'newest', 0, $limit);
        if (is_array($rows) && ! $rows) {
            $rows = $this->search_public([], 'newest', 0, $limit);
        }

        return $rows;
    }

    /**
     * Colours (or sizes) a product is offered in: id => row, in display order.
     *
     * @return array<int, array>|false
     */
    public function get_product_colors(int $product_id)
    {
        return $this->linked_rows('wl_product_colors', ColorModel::TABLE, 'color_id', $product_id);
    }

    /**
     * @return array<int, array>|false
     */
    public function get_product_sizes(int $product_id)
    {
        return $this->linked_rows('wl_product_sizes', SizeModel::TABLE, 'size_id', $product_id);
    }

    /**
     * Product cards' swatches and sizes for a list of products, in two
     * queries: products_id => ['colors' => list of [name, code], 'sizes' => size_id => name].
     *
     * @param list<int> $ids
     *
     * @return array<int, array{colors: list<array{0: string, 1: string}>, sizes: array<int, string>}>
     */
    public function card_options(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $out = array_fill_keys($ids, ['colors' => [], 'sizes' => []]);
        if (! $ids || ! $this->db_ready()) {
            return $out;
        }

        $colors = $this->all_rows($this->table('wl_product_colors l')->select('l.product_id, t.color_name, t.color_code')
            ->join(ColorModel::TABLE . ' t', 't.color_id = l.color_id')->whereIn('l.product_id', $ids)->where('t.status', '1')
            ->orderBy('t.sort_order ASC, t.color_id ASC')->get());
        foreach (is_array($colors) ? $colors : [] as $row) {
            $out[(int) $row['product_id']]['colors'][] = [(string) $row['color_name'], (string) $row['color_code']];
        }

        $sizes = $this->all_rows($this->table('wl_product_sizes l')->select('l.product_id, t.size_id, t.size_name')
            ->join(SizeModel::TABLE . ' t', 't.size_id = l.size_id')->whereIn('l.product_id', $ids)->where('t.status', '1')
            ->orderBy('t.sort_order ASC, t.size_id ASC')->get());
        foreach (is_array($sizes) ? $sizes : [] as $row) {
            $out[(int) $row['product_id']]['sizes'][(int) $row['size_id']] = (string) $row['size_name'];
        }

        return $out;
    }

    /**
     * @return array<int, array>|false
     */
    protected function linked_rows(string $link_table, string $table, string $column, int $product_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table($link_table . ' l')->select('t.*')->join($table . ' t', 't.' . $column . ' = l.' . $column)
            ->where(['l.product_id' => $product_id, 't.status' => '1'])->orderBy('t.sort_order ASC, t.' . $column . ' ASC')->get());
        if ($rows === false) {
            return false;
        }

        $by_id = [];

        foreach ($rows as $row) {
            $by_id[(int) $row[$column]] = $row;
        }

        return $by_id;
    }

    /**
     * Count a page view.
     */
    public function increment_viewed(int $product_id): void
    {
        if ($this->db_ready()) {
            $this->write_failed(fn () => (bool) $this->table(self::TABLE)->set('products_viewed', 'products_viewed + 1', false)->where(self::PRIMARY_KEY, $product_id)->update());
        }
    }

    /**
     * Which of these products are offered in colours or sizes (their listing
     * card links to the product page to choose instead of adding to the cart).
     *
     * @param list<int> $ids
     *
     * @return list<int>|false
     */
    public function variant_product_ids(array $ids)
    {
        if (! $ids) {
            return [];
        }
        if (! $this->db_ready()) {
            return false;
        }

        $ids  = implode(',', array_map('intval', $ids));
        $rows = $this->all_rows($this->db->query(
            'SELECT product_id FROM wl_product_colors WHERE product_id IN (' . $ids . ') UNION SELECT product_id FROM wl_product_sizes WHERE product_id IN (' . $ids . ')',
        ));

        return $rows === false ? false : array_map('intval', array_column($rows, 'product_id'));
    }
}
