<?php

namespace Modules\News\Models;

use App\Models\BaseModel;
use Throwable;

/**
 * News & Events (wl_news) — the website's /news-and-events page,
 * each item's own page and Sitepanel > Manage News & Events. Items are
 * listed in display order (sort_order ascending: 0 = by date), then newest
 * first; images are in uploads/news/. Each item's page address is its
 * wl_meta_tags row (entity_type News::META_TYPE).
 *
 * BaseModel returns FALSE when the database is unavailable or a query
 * fails (logged), NULL when no row matches.
 */
class NewsModel extends BaseModel
{
    public const TABLE       = 'wl_news';
    public const PRIMARY_KEY = 'news_id';

    protected const ORDER = 'sort_order ASC, publish_date DESC, news_id DESC';

    /**
     * entity_type of the items' wl_meta_tags rows (News::META_TYPE). Set by
     * Sitepanel > Manage News & Events so bulk_delete() removes each item's
     * page address together with the item; '' = the items only.
     */
    public string $meta_type = '';

    /**
     * One Sitepanel list page; the total is left in $this->total_rec_found.
     *
     * @param string $keyword Part of the title or category ('' = all)
     * @param string $status  '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_news(int $offset = 0, int $limit = 20, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, self::ORDER, $offset, $limit, static function ($db) use ($keyword, $status) {
            if ($keyword !== '') {
                $db->groupStart()->like('news_title', $keyword)->orLike('news_category', $keyword)->groupEnd();
            }
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * Active items with their page address (wl_meta_tags.page_url), in list
     * order; $except_id leaves one out (Related News).
     *
     * @return array|false
     */
    public function get_active_news(string $meta_type, int $limit = 500, int $except_id = 0)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $db = $this->table(self::TABLE . ' n')
            ->select('n.news_id, n.news_title, n.news_category, n.publish_date, n.short_description, n.news_image, m.page_url')
            ->join('wl_meta_tags m', 'm.entity_id = n.news_id AND m.entity_type = ' . $this->db->escape($meta_type), 'inner')
            ->where('n.status', '1');
        $except_id > 0 && $db->where('n.news_id <>', $except_id);

        return $this->all_rows($db->orderBy('n.sort_order ASC, n.publish_date DESC, n.news_id DESC')->limit($limit)->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_news_by_id($id)
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
    public function get_news_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * Whether another item uses the URL slug (NULL = could not be checked).
     */
    public function slug_taken(string $slug, int $except_id = 0): ?bool
    {
        $where = ['friendly_url' => $slug];
        $except_id > 0 && $where[self::PRIMARY_KEY . ' <>'] = $except_id;
        $count = $this->findCount(self::TABLE, $where);

        return ($count === false) ? null : $count > 0;
    }

    /**
     * @param array<int, int> $orders news_id => display order
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
     * Delete items. With $meta_type set, their wl_meta_tags rows are deleted
     * in the same transaction: both go, or nothing changes.
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        if ($this->meta_type === '') {
            return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
        }

        $ids = array_values(array_filter(array_map('intval', $ids), static fn ($n) => $n > 0));
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        try {
            $this->db->transException(true)->transStart();
            $this->table('wl_meta_tags')->where('entity_type', $this->meta_type)->whereIn('entity_id', $ids)->delete();
            $this->table(self::TABLE)->whereIn(self::PRIMARY_KEY, $ids)->delete();
            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'NewsModel::bulk_delete(' . implode(',', $ids) . '): ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }

        return true;
    }
}
