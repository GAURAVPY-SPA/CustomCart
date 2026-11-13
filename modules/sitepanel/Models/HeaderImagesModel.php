<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Header images (wl_header_images) — Sitepanel > Manage Header Images, the
 * home page slider, and the active slides for the website.
 */
class HeaderImagesModel extends BaseModel
{
    public const TABLE       = 'wl_header_images';
    public const PRIMARY_KEY = 'id';

    /**
     * Newest first; the total is left in $this->total_rec_found.
     *
     * @param string $status '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_header_images(int $offset, int $per_page, string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'id DESC', $offset, $per_page, static function ($db) use ($status) {
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * Active slides for the website (at most $limit, newest first).
     *
     * @return array|false
     */
    public function get_active_slides(int $limit = 10)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)
            ->select('id, header_image, header_url, line_one, line_two, line_three')
            ->where('status', '1')
            ->where('header_image <>', '')
            ->orderBy(self::PRIMARY_KEY, 'DESC')
            ->limit($limit)
            ->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_header_image_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_header_images_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
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
