<?php

namespace Modules\Category\Models;

use App\Models\BaseModel;
use Modules\Products\Models\ProductModel;
use Throwable;

/**
 * Store categories (wl_categories), nested by parent_id up to MAX_DEPTH
 * levels (category › sub category › sub sub category). As in the reference,
 * friendly_url is the category's full address from the site root, its
 * ancestors' slugs then its own (<category>/<sub>/<sub-sub>), and also the
 * page_url of its wl_meta_tags row (entity_type META_TYPE, entity_id =
 * category_id), through which the request is routed to Category::index.
 * Deleted ('2') rows are never read.
 */
class CategoryModel extends BaseModel
{
    public const TABLE       = 'wl_categories';
    public const PRIMARY_KEY = 'category_id';

    /**
     * Address prefix of a top-level category: none, the address starts at
     * the site root (the reference's seo_url_title(friendly_url)). Kept for
     * the code that builds addresses as URL_PREFIX . slug.
     */
    public const URL_PREFIX = '';

    /**
     * entity_type of a category page's wl_meta_tags row, as in the
     * reference: Modules\Category\Controllers\Category::index/<category_id>.
     */
    public const META_TYPE = 'category/index';

    /**
     * Most levels of categories: category, sub category, sub sub category.
     */
    public const MAX_DEPTH = 3;

    /**
     * Every category (any status but deleted), parents before children is not
     * guaranteed; use tree() / options() to order them.
     *
     * @var array<int, array>|null
     */
    protected ?array $all = null;

    /**
     * Admin list of one level (the reference's sitepanel/category/index/<parent_id>),
     * in display order. The total is left in $this->total_rec_found.
     *
     * @return array|false
     */
    public function get_categories(int $offset, int $per_page, int $parent_id = 0, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'sort_order ASC, category_id ASC', $offset, $per_page, static function ($db) use ($parent_id, $keyword, $status) {
            $db->where('status <>', '2');
            // CI3 searches within the currently open level, not across the tree.
            $db->where('parent_id', $parent_id);
            $keyword !== '' && $db->like('category_name', $keyword);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * CI3-compatible name used by the sitepanel category controller.
     *
     * @return array|false
     */
    public function get_level(int $offset, int $per_page, int $parent_id = 0, string $keyword = '', string $status = '')
    {
        return $this->get_categories($offset, $per_page, $parent_id, $keyword, $status);
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_category_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id, 'status <>' => '2']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_categories_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('status <>', '2')->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * Every non-deleted category keyed by id, in display order.
     *
     * @return array<int, array>|false
     */
    public function get_all_categories()
    {
        if ($this->all !== null) {
            return $this->all;
        }
        if (! $this->db_ready()) {
            return false;
        }

        $rows = $this->all_rows($this->table(self::TABLE)->where('status <>', '2')->orderBy('sort_order ASC, category_name ASC')->get());
        if ($rows === false) {
            return false;
        }

        $this->all = [];

        foreach ($rows as $row) {
            $this->all[(int) $row['category_id']] = $row;
        }

        return $this->all;
    }

    /**
     * The category and its ancestors, the category first (wl_products.category_links order).
     *
     * @return list<int>|false
     */
    public function ancestor_ids(int $id)
    {
        $all = $this->get_all_categories();
        if ($all === false) {
            return false;
        }

        $ids = [];

        while ($id > 0 && isset($all[$id]) && ! in_array($id, $ids, true)) {
            $ids[] = $id;
            $id    = (int) $all[$id]['parent_id'];
        }

        return $ids;
    }

    /**
     * Level of a category: 1 = top level, 2 = sub category, 3 = sub sub category.
     *
     * @return false|int
     */
    public function depth(int $id)
    {
        $ids = $this->ancestor_ids($id);

        return $ids === false ? false : count($ids);
    }

    /**
     * Whether a category may get sub-categories (it is above the last level).
     *
     * @return bool|null NULL when it could not be checked
     */
    public function can_have_children(int $id): ?bool
    {
        $depth = $this->depth($id);

        return $depth === false ? null : ($depth > 0 && $depth < self::MAX_DEPTH);
    }

    /**
     * The address a category must have: its ancestors' slugs then its own,
     * joined by "/" (each slug is the last segment of its friendly_url).
     * FALSE when the categories could not be read, NULL for an unknown one.
     *
     * @return false|string|null
     */
    public function expected_url(int $id)
    {
        $path = $this->path($id);
        if ($path === false) {
            return false;
        }
        if (! $path) {
            return null;
        }

        return implode('/', array_map(static fn (array $row) => substr((string) strrchr('/' . $row['friendly_url'], '/'), 1), $path));
    }

    /**
     * The ancestors of a category from the top level down (breadcrumbs), the category last.
     *
     * @return list<array>|false
     */
    public function path(int $id)
    {
        $ids = $this->ancestor_ids($id);

        return $ids === false ? false : array_map(fn ($cid) => $this->all[$cid], array_reverse($ids));
    }

    /**
     * Ids of every category below $id (any depth).
     *
     * @return list<int>|false
     */
    public function descendant_ids(int $id)
    {
        $all = $this->get_all_categories();
        if ($all === false) {
            return false;
        }

        $found = [];
        $queue = [$id];

        while ($queue) {
            $parent = array_shift($queue);

            foreach ($all as $cid => $row) {
                if ((int) $row['parent_id'] === $parent && ! in_array($cid, $found, true)) {
                    $found[] = $cid;
                    $queue[] = $cid;
                }
            }
        }

        return $found;
    }

    /**
     * Dropdown options: category_id => "Parent › Child" in tree order.
     *
     * @param int  $exclude      A category to leave out with everything below it (moving a category)
     * @param bool $mark_inactive Append " (inactive)" to inactive ones
     *
     * @return array<int, string>|false
     */
    public function options(int $exclude = 0, bool $mark_inactive = true)
    {
        $all = $this->get_all_categories();
        if ($all === false) {
            return false;
        }
        $skip = $exclude > 0 ? array_merge([$exclude], (array) $this->descendant_ids($exclude)) : [];

        $options = [];
        $walk    = function (int $parent, string $prefix) use (&$walk, &$options, $all, $skip, $mark_inactive): void {
            foreach ($all as $cid => $row) {
                if ((int) $row['parent_id'] !== $parent || in_array($cid, $skip, true)) {
                    continue;
                }
                $label         = $prefix . $row['category_name'];
                $options[$cid] = $label . ($mark_inactive && $row['status'] !== '1' ? ' (inactive)' : '');
                $walk($cid, $label . ' › ');
            }
        };
        $walk(0, '');

        return $options;
    }

    /**
     * Product form category tree, preserving the reference site's optgroup
     * hierarchy and indentation while keeping category ids available to the
     * native select control.
     *
     * @return array<int, array>|false
     */
    public function product_tree(int $exclude = 0): array|false
    {
        $all = $this->get_all_categories();
        if ($all === false) {
            return false;
        }

        $skip = $exclude > 0 ? array_merge([$exclude], (array) $this->descendant_ids($exclude)) : [];
        $children = [];
        foreach ($all as $id => $row) {
            if (in_array((int) $id, $skip, true)) {
                continue;
            }
            $children[(int) $row['parent_id']][] = (int) $id;
        }

        $tree = function (int $parent) use (&$tree, &$children, $all): array {
            $nodes = [];
            foreach ($children[$parent] ?? [] as $id) {
                $row = $all[$id];
                $nodes[] = [
                    'id'       => $id,
                    'name'     => (string) $row['category_name'],
                    'status'   => (string) $row['status'],
                    'children' => $tree($id),
                ];
            }

            return $nodes;
        };

        return $tree(0);
    }

    /**
     * @return false|int
     */
    public function child_count(int $id)
    {
        return $this->findCount(self::TABLE, ['parent_id' => $id, 'status <>' => '2']);
    }

    /**
     * Sub-categories and products of several categories (a list page), each
     * counted on its own relationship: children = non-deleted categories whose
     * parent_id is the category; products = non-deleted products filed directly
     * in it (wl_product_categories), each once. One query each.
     *
     * @param list<int> $ids
     *
     * @return array<int, array{children: int, products: int}>|false
     */
    public function list_counts(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($n) => $n > 0)));
        if (! $ids) {
            return [];
        }
        if (! $this->db_ready()) {
            return false;
        }

        $children = $this->all_rows($this->table(self::TABLE)->select('parent_id AS id, COUNT(*) AS n', false)
            ->whereIn('parent_id', $ids)->where('status <>', '2')->groupBy('parent_id')->get());
        $products = $this->all_rows($this->table(ProductModel::CATEGORY_TABLE . ' pc')->select('pc.category_id AS id, COUNT(DISTINCT pc.product_id) AS n', false)
            ->join(ProductModel::TABLE . ' p', 'p.products_id = pc.product_id')->whereIn('pc.category_id', $ids)->where('p.status <>', '2')->groupBy('pc.category_id')->get());
        if ($children === false || $products === false) {
            return false;
        }

        $counts = array_fill_keys($ids, ['children' => 0, 'products' => 0]);
        foreach ($children as $row) {
            $counts[(int) $row['id']]['children'] = (int) $row['n'];
        }
        foreach ($products as $row) {
            $counts[(int) $row['id']]['products'] = (int) $row['n'];
        }

        return $counts;
    }

    /**
     * Products filed directly in the category (any status but deleted), each
     * counted once (wl_product_categories).
     *
     * @return false|int
     */
    public function product_count(int $id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $row = $this->single_row($this->table(ProductModel::CATEGORY_TABLE . ' pc')->select('COUNT(DISTINCT pc.product_id) AS n', false)
            ->join(ProductModel::TABLE . ' p', 'p.products_id = pc.product_id')->where(['pc.category_id' => $id, 'p.status <>' => '2'])->get());

        return $row === false ? false : (int) $row['n'];
    }

    /**
     * Whether another category already has this address.
     *
     * @return bool|null NULL when it could not be checked
     */
    public function url_taken(string $friendly_url, int $except_id = 0): ?bool
    {
        $count = $this->findCount(self::TABLE, ['friendly_url' => $friendly_url, self::PRIMARY_KEY . ' <>' => $except_id]);

        return $count === false ? null : $count > 0;
    }

    /**
     * Whether a sibling already has this name (the reference: unique per parent).
     *
     * @return bool|null
     */
    public function name_taken(string $name, int $parent_id, int $except_id = 0): ?bool
    {
        $count = $this->findCount(self::TABLE, ['category_name' => $name, 'parent_id' => $parent_id, 'status <>' => '2', self::PRIMARY_KEY . ' <>' => $except_id]);

        return $count === false ? null : $count > 0;
    }

    /**
     * Display order for a new category: last of its level.
     */
    public function next_sort_order(int $parent_id): int
    {
        if (! $this->db_ready()) {
            return 0;
        }
        $row = $this->single_row($this->table(self::TABLE)->select('MAX(sort_order) AS n', false)->where('parent_id', $parent_id)->get());

        return is_array($row) ? (int) $row['n'] + 1 : 0;
    }

    /**
     * Save the display order of one level: category_id => position.
     *
     * @param array<int, int> $orders
     */
    public function save_sort_order(array $orders): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        foreach ($orders as $id => $position) {
            if ($this->write_failed(fn () => (bool) $this->table(self::TABLE)->where(self::PRIMARY_KEY, (int) $id)->update(['sort_order' => (int) $position]))) {
                return false;
            }
        }

        return true;
    }

    /* -----------------------------------------------------------------
     * Public pages
     * ----------------------------------------------------------------- */

    /**
     * An active category whose ancestors are all active (a category under an
     * inactive one is hidden with it).
     *
     * @return array|false|null
     */
    public function get_public_category(int $id)
    {
        $path = $this->path($id);
        if ($path === false) {
            return false;
        }
        if (! $path) {
            return null;
        }

        foreach ($path as $row) {
            if ($row['status'] !== '1') {
                return null;
            }
        }

        return end($path);
    }

    /**
     * Active categories directly below $parent_id, each with 'product_count'
     * (active products filed in it or in an active category below it, each
     * product once however many of those categories it is in).
     *
     * @return list<array>|false
     */
    public function get_public_children(int $parent_id)
    {
        $all    = $this->get_all_categories();
        $public = $this->public_ids();
        if ($all === false || $public === false || ! $this->db_ready()) {
            return false;
        }

        $children = [];

        foreach ($all as $cid => $row) {
            if ((int) $row['parent_id'] === $parent_id && $row['status'] === '1') {
                $subtree = array_intersect(array_merge([$cid], (array) $this->descendant_ids($cid)), $public) ?: [0];
                $count   = $this->single_row($this->table(ProductModel::TABLE . ' p')->select('COUNT(*) AS n', false)->where('p.status', '1')
                    ->where('EXISTS (SELECT 1 FROM ' . ProductModel::CATEGORY_TABLE . ' pc WHERE pc.product_id = p.products_id AND pc.category_id IN (' . implode(',', array_map('intval', $subtree)) . '))', null, false)->get());
                $row['product_count'] = is_array($count) ? (int) $count['n'] : 0;
                $children[]           = $row;
            }
        }

        return $children;
    }

    /**
     * Ids of the active categories whose ancestors are all active.
     *
     * @return list<int>|false
     */
    public function public_ids()
    {
        $all = $this->get_all_categories();
        if ($all === false) {
            return false;
        }

        $ids = [];

        foreach (array_keys($all) as $cid) {
            if (is_array($this->get_public_category($cid))) {
                $ids[] = $cid;
            }
        }

        return $ids;
    }

    /* -----------------------------------------------------------------
     * Bulk actions
     * ----------------------------------------------------------------- */

    /**
     * Activate / deactivate categories; their wl_meta_tags rows (SEO data of
     * the category pages) follow, as in the reference.
     *
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        if (! $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status, 'date_modified' => date('Y-m-d H:i:s')])) {
            return false;
        }

        return ! $this->write_failed(fn () => (bool) $this->table('wl_meta_tags')
            ->where('entity_type', self::META_TYPE)->whereIn('entity_id', $ids)->update(['status' => $status]));
    }

    /**
     * Flags a category can be set or unset as (the reference's
     * category_set_as_config): column => label.
     */
    public const FLAGS = ['is_home' => 'Home', 'is_menu' => 'Menu'];

    /**
     * Set or unset a flag (is_home / is_menu) on categories.
     *
     * @param list<int> $ids
     */
    public function bulk_set_flag(array $ids, string $flag, bool $on): bool
    {
        return isset(self::FLAGS[$flag])
            && $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, [$flag => $on ? '1' : '0', 'date_modified' => date('Y-m-d H:i:s')]);
    }

    /**
     * Delete categories in one transaction (all of it, or nothing): products
     * also filed elsewhere are taken off them (ProductModel::unfile_categories()),
     * then their wl_meta_tags rows (entity_type META_TYPE, entity_id = the
     * category) and the categories go. The caller first checks that none has
     * sub-categories and that no product is filed only in them; the
     * database refuses the rest (foreign keys). The caller removes the images.
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn ($n) => $n > 0));
        if (! $ids || $this->get_all_categories() === false || ! $this->db_ready()) {
            return false;
        }

        try {
            $this->db->transException(true)->transStart();
            if ($this->table(self::TABLE)->whereIn('parent_id', $ids)->countAllResults() > 0) {
                throw new \RuntimeException('a category still has sub-categories');
            }
            (new ProductModel())->unfile_categories($ids, fn (int $id): array => (array) $this->ancestor_ids($id));
            $this->table('wl_meta_tags')->where('entity_type', self::META_TYPE)->whereIn('entity_id', $ids)->delete();
            $this->table(self::TABLE)->whereIn(self::PRIMARY_KEY, $ids)->delete();
            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'CategoryModel::bulk_delete(' . implode(',', $ids) . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }
        $this->all = null;

        return true;
    }
}
