<?php

namespace Modules\Testimonials\Models;

use App\Models\BaseModel;

/**
 * Testimonials (wl_testimonial) — Sitepanel > Manage Testimonials and the
 * public /testimonials page.
 */
class TestimonialModel extends BaseModel
{
    public const TABLE       = 'wl_testimonial';
    public const PRIMARY_KEY = 'testimonial_id';

    /**
     * Newest first; the total is left in $this->total_rec_found.
     * Argument order (limit, offset) as in the reference model.
     * $filter: keyword (name, email, company / role, comment), status ('1'/'0'),
     * source ('public' = sent from the website form, 'admin' = added here).
     *
     * @return array|false
     */
    public function get_testimonial(int $limit = 20, int $offset = 0, array $filter = [])
    {
        $keyword = (string) ($filter['keyword'] ?? '');
        $status  = (string) ($filter['status'] ?? '');
        $source  = (string) ($filter['source'] ?? '');

        return $this->paged_rows(self::TABLE, 'testimonial_id DESC', $offset, $limit, static function ($db) use ($keyword, $status, $source) {
            if ($keyword !== '') {
                $db->groupStart()
                    ->like('poster_name', $keyword)
                    ->orLike('email', $keyword)
                    ->orLike('poster_role', $keyword)
                    ->orLike('testimonial_description', $keyword)
                    ->groupEnd();
            }
            $status !== '' && $db->where('status', $status);
            if ($source === 'public') {
                $db->where('submitted_ip IS NOT NULL', null, false)->where('submitted_ip !=', '');
            } elseif ($source === 'admin') {
                $db->groupStart()->where('submitted_ip IS NULL', null, false)->orWhere('submitted_ip', '')->groupEnd();
            }
        });
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_testimonial_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_testimonials_by_ids(array $ids)
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

    /**
     * Active testimonials for the public page, newest first.
     *
     * @return array|false
     */
    public function get_public_testimonials(int $limit = 100)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->select('testimonial_id, poster_name, poster_role, email, photo, testimonial_description, posted_date')
            ->where('status', '1')
            ->orderBy('posted_date DESC, testimonial_id DESC')
            ->limit($limit)
            ->get());
    }

    /**
     * Public submissions received from one IP address in the last $minutes.
     *
     * @return false|int
     */
    public function count_recent_from_ip(string $ip, int $minutes)
    {
        return $this->findCount(self::TABLE, [
            'submitted_ip'   => $ip,
            'posted_date >=' => date('Y-m-d H:i:s', time() - 60 * $minutes),
        ]);
    }

    /**
     * "Jane Doe" -> "jane-doe", or "jane-doe-2", "jane-doe-3"... when taken.
     *
     * @return string|null NULL when uniqueness could not be checked
     */
    public function unique_slug($name): ?string
    {
        // convert_accented_characters(): text helper (autoloaded).
        $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(convert_accented_characters((string) $name))), '-');
        $base = trim(substr($base, 0, 90), '-');
        $base = ($base === '') ? 'testimonial' : $base;

        for ($n = 1; $n < 1000; $n++) {
            $slug  = ($n === 1) ? $base : $base . '-' . $n;
            $taken = $this->checkurl($slug);
            if ($taken === null) {
                return null;
            }
            if (! $taken) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Whether a page URL (friendly_url) is already used.
     *
     * @return bool|null NULL when it could not be checked
     */
    public function checkurl(string $friendly_url): ?bool
    {
        $count = $this->findCount(self::TABLE, ['friendly_url' => $friendly_url]);

        return ($count === false) ? null : $count > 0;
    }
}
