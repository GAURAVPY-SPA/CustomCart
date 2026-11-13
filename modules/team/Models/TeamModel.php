<?php

namespace Modules\Team\Models;

use App\Models\BaseModel;

/**
 * Team members and partners (wl_team) — Sitepanel > Manage Team & Partners,
 * the website's /our-team and /partners pages and the homepage partner logos.
 * team_type: TYPE_TEAM (photo, name, designation) or TYPE_PARTNER (logo, name).
 */
class TeamModel extends BaseModel
{
    public const TABLE       = 'wl_team';
    public const PRIMARY_KEY = 'team_id';

    public const TYPE_TEAM    = 'team';
    public const TYPE_PARTNER = 'partner';

    /**
     * team_type => label in the sitepanel.
     */
    public const TYPES = [self::TYPE_TEAM => 'Team member', self::TYPE_PARTNER => 'Partner'];

    protected const ORDER = 'sort_order ASC, team_id DESC';

    /**
     * Sitepanel list; the total is left in $this->total_rec_found.
     *
     * @param string $type   A TYPES key or '' (all)
     * @param string $status '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_team(int $offset, int $per_page, string $keyword = '', string $status = '', string $type = '')
    {
        return $this->paged_rows(self::TABLE, self::ORDER, $offset, $per_page, static function ($db) use ($keyword, $status, $type) {
            if ($keyword !== '') {
                $db->groupStart()->like('name', $keyword)->orLike('designation', $keyword)->groupEnd();
            }
            $status !== '' && $db->where('status', $status);
            $type !== '' && $db->where('team_type', $type);
        });
    }

    /**
     * Active members or partners for the website, in display order.
     *
     * @param string $type  TYPE_TEAM | TYPE_PARTNER
     * @param int    $limit 0 = all
     *
     * @return array|false
     */
    public function get_active(string $type, int $limit = 0)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $db = $this->table(self::TABLE)
            ->select('team_id, name, designation, photo, biography')
            ->where(['status' => '1', 'team_type' => $type])
            ->orderBy(self::ORDER, '', false);
        $limit > 0 && $db->limit($limit);

        return $this->all_rows($db->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_member_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_members_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * A free friendly_url (unique column) built from $base: base, base-2, base-3 ...
     *
     * @return false|string
     */
    public function free_slug(string $base, int $except_id = 0)
    {
        $base = $base !== '' ? $base : 'member';
        for ($n = 1; $n < 500; $n++) {
            $slug  = $n === 1 ? $base : mb_substr($base, 0, 140) . '-' . $n;
            $where = ['friendly_url' => $slug];
            $except_id > 0 && $where['team_id !='] = $except_id;
            $count = $this->findCount(self::TABLE, $where);
            if ($count === false) {
                return false;
            }
            if ($count === 0) {
                return $slug;
            }
        }

        return false;
    }

    /**
     * Next display order: after the last row (MAX + 1).
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
     * @param array<int, int> $orders team_id => display order
     */
    public function update_sort_orders(array $orders): bool
    {
        if (! $orders || ! $this->db_ready()) {
            return false;
        }

        return $this->write_failed(function () use ($orders) {
            $this->db->transStart();
            foreach ($orders as $id => $order) {
                $this->table(self::TABLE)->where(self::PRIMARY_KEY, (int) $id)->update(['sort_order' => (int) $order]);
            }
            $this->db->transComplete();

            return $this->db->transStatus();
        }) === false;
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
