<?php

namespace Modules\Cart\Models;

use App\Models\BaseModel;

/**
 * Delivery methods (wl_shipping): a flat rate per method, as in the
 * reference, plus free_above (free once the cart reaches that amount; the
 * design's "free on orders of S$150 and above"). The default method is
 * preselected in the cart.
 */
class ShippingModel extends BaseModel
{
    public const TABLE       = 'wl_shipping';
    public const PRIMARY_KEY = 'shipping_id';

    /**
     * Admin list in display order; the total is left in $this->total_rec_found.
     *
     * @return array|false
     */
    public function get_shipping_methods(int $offset, int $per_page, string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'sort_order ASC, shipping_id ASC', $offset, $per_page, static function ($db) use ($status) {
            $db->where('status <>', '2');
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_shipping_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id, 'status <>' => '2']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_shipping_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('status <>', '2')->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * Active methods keyed by id, the default first.
     *
     * @return array<int, array>|false
     */
    public function get_active_methods()
    {
        if (! $this->db_ready()) {
            return false;
        }

        $rows = $this->all_rows($this->table(self::TABLE)->where('status', '1')->orderBy('is_default DESC, sort_order ASC, shipping_id ASC')->get());
        if ($rows === false) {
            return false;
        }

        $methods = [];

        foreach ($rows as $row) {
            $methods[(int) $row['shipping_id']] = $row;
        }

        return $methods;
    }

    /**
     * The delivery method the store charges (Sitepanel > Manage Shipping):
     * the default active method, else the first active one; NULL when no
     * method is active (delivery is then free).
     *
     * @return array|null
     */
    public function get_store_method(): ?array
    {
        $methods = $this->get_active_methods();

        return is_array($methods) && $methods ? reset($methods) : null;
    }

    /**
     * Make one method the default (the others stop being it).
     */
    public function set_default(int $id): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        return ! $this->write_failed(fn () => (bool) $this->table(self::TABLE)->set('is_default', "IF(shipping_id = {$id}, '1', '0')", false)->update());
    }

    public function next_sort_order(): int
    {
        if (! $this->db_ready()) {
            return 0;
        }
        $row = $this->single_row($this->table(self::TABLE)->select('MAX(sort_order) AS n', false)->get());

        return is_array($row) ? (int) $row['n'] + 1 : 0;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    /**
     * Orders keep the method's name and rate, so methods can always be removed.
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }
}
