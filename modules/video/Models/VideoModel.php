<?php

namespace Modules\Video\Models;

use App\Models\BaseModel;

/**
 * Video gallery (wl_video) — the website's /videos-gallery page and
 * Sitepanel > Manage Video Gallery. Videos are shown in display order
 * (sort_order, then newest first); thumbnails are in uploads/video/.
 *
 * A video is a YouTube or Vimeo address, kept as entered (video_link) and
 * turned into the player address with embed_url(). A YouTube video without
 * an uploaded thumbnail shows YouTube's own (thumbnail_url()).
 *
 * BaseModel returns FALSE when the database is unavailable or a query
 * fails (logged), NULL when no row matches.
 */
class VideoModel extends BaseModel
{
    public const TABLE       = 'wl_video';
    public const PRIMARY_KEY = 'video_id';

    protected const ORDER = 'sort_order ASC, video_id DESC';

    /**
     * One Sitepanel list page, in display order; the total is left in
     * $this->total_rec_found.
     *
     * @param string $keyword Part of the title ('' = all)
     * @param string $status  '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_videos(int $offset = 0, int $limit = 20, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, self::ORDER, $offset, $limit, static function ($db) use ($keyword, $status) {
            $keyword !== '' && $db->like('video_title', $keyword);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * Active videos for the website, in display order.
     *
     * @return array|false
     */
    public function get_active_videos(int $limit = 200)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)
            ->select('video_id, video_title, video_link, video_category, video_duration, video_image')
            ->where('status', '1')
            ->orderBy(self::ORDER)
            ->limit($limit)
            ->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_video_by_id($id)
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
    public function get_videos_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * Display order for a new video: after the last one (MAX + 1).
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
     * @param array<int, int> $orders video_id => display order
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

    /**
     * The video of a YouTube or Vimeo address: ['youtube'|'vimeo', id], or
     * NULL for any other address. Accepted: youtube.com/watch?v=, youtu.be/,
     * youtube.com/embed/, /shorts/, /live/, youtube-nocookie.com/embed/,
     * vimeo.com/<id>, player.vimeo.com/video/<id>.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function parse_link(string $link): ?array
    {
        $parts = parse_url(trim($link));
        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }
        $host = preg_replace('/^(www\.|m\.)/', '', strtolower($parts['host']));
        $path = (string) ($parts['path'] ?? '');

        if (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $id = $path === '/watch' ? (string) ($query['v'] ?? '')
                : (preg_match('#^/(?:embed|shorts|live)/([^/?]+)#', $path, $m) ? $m[1] : '');
        } elseif ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            return preg_match('#^/(?:video/)?([0-9]{6,12})/?$#', $path, $m) ? ['vimeo', $m[1]] : null;
        } else {
            return null;
        }

        return preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) ? ['youtube', $id] : null;
    }

    /**
     * Player address of a video link (the design's data-video), '' when it isn't a YouTube or Vimeo video.
     */
    public static function embed_url(string $link): string
    {
        $video = self::parse_link($link);
        if ($video === null) {
            return '';
        }

        return $video[0] === 'youtube'
            ? 'https://www.youtube.com/embed/' . $video[1]
            : 'https://player.vimeo.com/video/' . $video[1];
    }

    /**
     * YouTube's thumbnail of a video link, '' for other links.
     */
    public static function thumbnail_url(string $link): string
    {
        $video = self::parse_link($link);

        return $video !== null && $video[0] === 'youtube' ? 'https://i.ytimg.com/vi/' . $video[1] . '/hqdefault.jpg' : '';
    }
}
