<?php

namespace Modules\Lookup\Models;

use App\Models\BaseModel;
use Modules\Products\Models\ProductModel;

/**
 * Shared list / lookup methods of the product attribute tables (colours,
 * sizes, brands, materials — the reference's Color, Size, Brand and Material
 * modules): a name, a display order and a status. Subclasses set TABLE,
 * PRIMARY_KEY, NAME_FIELD, the dates columns and USAGE (the table + column
 * that link a product to a value, for "in use" checks). Deleted ('2') rows
 * are never read.
 */
abstract class LookupModel extends BaseModel
{
    public const TABLE        = '';
    public const PRIMARY_KEY  = '';
    public const NAME_FIELD   = '';
    public const ADDED_FIELD  = '';
    public const UPDATE_FIELD = '';

    /**
     * [table, column] linking products to a value.
     */
    public const USAGE = ['', ''];

    /**
     * Admin list in display order; the total is left in $this->total_rec_found.
     *
     * @return array|false
     */
    public function get_list(int $offset, int $per_page, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(static::TABLE, 'sort_order ASC, ' . static::NAME_FIELD . ' ASC', $offset, $per_page, static function ($db) use ($keyword, $status) {
            $db->where('status <>', '2');
            $keyword !== '' && $db->like(static::NAME_FIELD, $keyword);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_by_id($id)
    {
        return $this->find(static::TABLE, [static::PRIMARY_KEY => (int) $id, 'status <>' => '2']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(static::TABLE)->where('status <>', '2')->whereIn(static::PRIMARY_KEY, $ids)->get());
    }

    /**
     * Rows for a product form or a filter: id => row, in display order.
     *
     * @param bool $active_only Only active rows (public filters); else every non-deleted one
     *
     * @return array<int, array>|false
     */
    public function get_options(bool $active_only = false)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $rows = $this->all_rows($this->table(static::TABLE)->where($active_only ? ['status' => '1'] : ['status <>' => '2'])
            ->orderBy('sort_order ASC, ' . static::NAME_FIELD . ' ASC')->get());
        if ($rows === false) {
            return false;
        }

        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row[static::PRIMARY_KEY]] = $row;
        }

        return $options;
    }

    /**
     * @return bool|null NULL when it could not be checked
     */
    public function name_taken(string $name, int $except_id = 0): ?bool
    {
        $count = $this->findCount(static::TABLE, [static::NAME_FIELD => $name, 'status <>' => '2', static::PRIMARY_KEY . ' <>' => $except_id]);

        return $count === false ? null : $count > 0;
    }

    /**
     * Number of non-deleted products using the value.
     *
     * @return false|int
     */
    public function usage_count(int $id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        [$table, $column] = static::USAGE;
        $builder          = $this->table($table . ' u')->select('COUNT(*) AS n', false)->where('u.' . $column, $id);
        if ($table !== ProductModel::TABLE) {
            $builder->join(ProductModel::TABLE . ' p', 'p.products_id = u.product_id');
        }
        $row = $this->single_row($builder->where(($table !== ProductModel::TABLE ? 'p.' : 'u.') . 'status <>', '2')->get());

        return $row === false ? false : (int) $row['n'];
    }

    /**
     * Display order for a new row: after the last one.
     */
    public function next_sort_order(): int
    {
        if (! $this->db_ready()) {
            return 0;
        }
        $row = $this->single_row($this->table(static::TABLE)->select('MAX(sort_order) AS n', false)->get());

        return is_array($row) ? (int) $row['n'] + 1 : 0;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(static::TABLE, static::PRIMARY_KEY, $ids, ['status' => $status, static::UPDATE_FIELD => date('Y-m-d H:i:s')]);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(static::TABLE, static::PRIMARY_KEY, $ids);
    }
}
