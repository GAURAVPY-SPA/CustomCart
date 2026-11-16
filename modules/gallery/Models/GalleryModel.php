<?php

namespace Modules\Gallery\Models;

use App\Models\BaseModel;

/**
 * Images gallery (wl_gallery) — the website's /images-gallery page and
 * Sitepanel > Manage Images Gallery. Photos are shown in display order
 * (sort_order, then newest first); images are in uploads/gallery/.
 *
 * BaseModel returns FALSE when the database is unavailable or a query
 * fails (logged), NULL when no row matches.
 */
class GalleryModel extends BaseModel
{
    public const TABLE       = 'wl_gallery';
    public const PRIMARY_KEY = 'gallery_id';

    protected const ORDER = 'sort_order ASC, gallery_id DESC';

    /**
     * One Sitepanel list page, in display order; the total is left in
     * $this->total_rec_found.
     *
     * @param string $keyword Part of the title ('' = all)
     * @param string $status  '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_galleries(int $offset = 0, int $limit = 20, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, self::ORDER, $offset, $limit, static function ($db) use ($keyword, $status) {
            $keyword !== '' && $db->like('gallery_title', $keyword);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * Active photos for the website, in display order.
     *
     * @return array|false
     */
    public function get_active_galleries(int $limit = 200)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)
            ->select('gallery_id, gallery_title, gallery_image')
            ->where('status', '1')
            ->where('gallery_image <>', '')
            ->orderBy(self::ORDER)
            ->limit($limit)
            ->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_gallery_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * Rows for a Sitepanel bulk action (AdminController::process_bulk_action()).
     *
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_galleries_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * Display order for a new photo: after the last one (MAX + 1).
     *
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
     * @param array<int, int> $orders gallery_id => display order
     */
    public function update_sort_orders(array $orders): bool
    {
        return $this->update_sort_orders_in(self::TABLE, self::PRIMARY_KEY, $orders);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }
}
