<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Running Marquee (wl_marquee) — Sitepanel > Manage Running Marquee, and the
 * active items shown above the website header.
 */
class MarqueeModel extends BaseModel
{
    public const TABLE       = 'wl_marquee';
    public const PRIMARY_KEY = 'marquee_id';

    /**
     * Display order, then newest first; the total is left in $this->total_rec_found.
     *
     * @return array|false
     */
    public function get_items(int $offset, int $per_page, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'sort_order ASC, marquee_id DESC', $offset, $per_page, static function ($db) use ($keyword, $status) {
            $keyword !== '' && $db->like('marquee_text', $keyword);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * @return array|false
     */
    public function get_active()
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->select('marquee_text, marquee_url')->where('status', '1')
            ->orderBy('sort_order', 'ASC')->orderBy(self::PRIMARY_KEY, 'DESC')->get());
    }

    /**
     * @return array|false|null
     */
    public function get_item($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @return array|false
     */
    public function get_items_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * @return false|int
     */
    public function next_sort_order()
    {
        if (! $this->db_ready()) {
            return false;
        }
        $row = $this->single_row($this->table(self::TABLE)->selectMax('sort_order', 'max_order')->get());

        return is_array($row) ? (int) $row['max_order'] + 1 : false;
    }

    /**
     * @param array<int, int> $orders marquee_id => display order
     */
    public function update_sort_orders(array $orders): bool
    {
        return $this->update_sort_orders_in(self::TABLE, self::PRIMARY_KEY, $orders);
    }

    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }
}
