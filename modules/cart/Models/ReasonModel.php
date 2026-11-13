<?php

namespace Modules\Cart\Models;

use App\Models\BaseModel;
use Config\Shop;

/**
 * Cancel / return reasons (wl_reasons; reference: Sitepanel > Manage Orders >
 * Manage Reasons). A customer picks one when asking to cancel (type '1') or
 * return (type '2') an order item; the chosen text is copied onto the item
 * (wl_orders_products.reason), so reasons can be changed or removed freely.
 */
class ReasonModel extends BaseModel
{
    public const TABLE       = 'wl_reasons';
    public const PRIMARY_KEY = 'reason_id';

    public const TYPES = ['1' => 'Cancel', '2' => 'Return'];

    /**
     * Admin list in display order; the total is left in $this->total_rec_found.
     *
     * @return array|false
     */
    public function get_reasons(int $offset, int $per_page, string $keyword = '', string $type = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'reason_type ASC, sort_order ASC, reason_id ASC', $offset, $per_page, static function ($db) use ($keyword, $type, $status) {
            $db->where('status <>', '2');
            $keyword !== '' && $db->like('reason_name', $keyword);
            $type !== '' && $db->where('reason_type', $type);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_reason_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id, 'status <>' => '2']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_reasons_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('status <>', '2')->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * Active reason texts of a type, in display order, for the customer's
     * request form. Falls back to Config\Shop's list when none are active or
     * the table can't be read, so the form always has choices.
     *
     * @param 'cancel'|'return' $kind
     *
     * @return list<string>
     */
    public function active_names(string $kind): array
    {
        $rows = $this->db_ready()
            ? $this->all_rows($this->table(self::TABLE)->select('reason_name')->where(['reason_type' => $kind === 'return' ? '2' : '1', 'status' => '1'])
                ->orderBy('sort_order ASC, reason_id ASC')->get())
            : false;

        if ($rows) {
            return array_column($rows, 'reason_name');
        }
        $shop = config(Shop::class);

        return $kind === 'return' ? $shop->returnReasons : $shop->cancelReasons;
    }

    /**
     * @return bool|null NULL when it could not be checked
     */
    public function name_taken(string $name, string $type, int $except_id = 0): ?bool
    {
        $count = $this->findCount(self::TABLE, ['reason_name' => $name, 'reason_type' => $type, 'status <>' => '2', self::PRIMARY_KEY . ' <>' => $except_id]);

        return $count === false ? null : $count > 0;
    }

    public function next_sort_order(string $type): int
    {
        if (! $this->db_ready()) {
            return 0;
        }
        $row = $this->single_row($this->table(self::TABLE)->select('MAX(sort_order) AS n', false)->where('reason_type', $type)->get());

        return is_array($row) ? (int) $row['n'] + 1 : 0;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status, 'reason_date_updated' => date('Y-m-d H:i:s')]);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }
}
