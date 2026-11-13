<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Banners (wl_banners) — Sitepanel > Manage Banners.
 */
class BannerModel extends BaseModel
{
    public const TABLE       = 'wl_banners';
    public const PRIMARY_KEY = 'banner_id';

    /**
     * Newest first, optionally one position; the total is left in $this->total_rec_found.
     *
     * @param string $position '' = all positions
     *
     * @return array|false
     */
    public function get_banner(int $offset = 0, int $per_page = 20, string $position = '')
    {
        return $this->paged_rows(self::TABLE, 'banner_id DESC', $offset, $per_page, static function ($db) use ($position) {
            $position !== '' && $db->where('banner_position', $position);
        });
    }

    /**
     * Default hero banner of the website's inner pages (a page without an
     * active banner in its own position shows this one).
     */
    public const INNER_PAGES_POSITION = 'Inner Pages Top';

    /**
     * The active banners of some positions for the website, newest first
     * (inner_banner() picks the first of the most specific position).
     *
     * @param list<string> $positions
     *
     * @return array|false
     */
    public function get_active_banners(array $positions)
    {
        $positions = array_values(array_unique(array_filter($positions, static fn ($p) => is_string($p) && $p !== '')));
        if (! $positions) {
            return [];
        }
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)
            ->select('banner_id, banner_position, banner_image, banner_title')
            ->whereIn('banner_position', $positions)
            ->where(['status' => '1', 'banner_image <>' => ''])
            ->orderBy(self::PRIMARY_KEY, 'DESC')
            ->get());
    }

    /**
     * Position of the banners chosen per category (Manage Banners > Category Pages + a category).
     */
    public const CATEGORY_POSITION = 'Category Pages';

    /**
     * The active banner of the first of $category_ids that has one (a
     * category, then its parents), lowest display order first.
     *
     * @param list<int> $category_ids
     *
     * @return array|false|null
     */
    public function get_category_banner(array $category_ids)
    {
        $category_ids = array_values(array_filter(array_map('intval', $category_ids)));
        if (! $category_ids) {
            return null;
        }
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::TABLE)->select('banner_id, category_id, banner_image, banner_title')
            ->where(['banner_position' => self::CATEGORY_POSITION, 'status' => '1', 'banner_image <>' => ''])
            ->whereIn('category_id', $category_ids)->orderBy('sort_order ASC, banner_id DESC')->get());
        if ($rows === false) {
            return false;
        }
        foreach ($category_ids as $id) {
            foreach ($rows as $row) {
                if ((int) $row['category_id'] === $id) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_banner_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_banners_by_ids(array $ids)
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
