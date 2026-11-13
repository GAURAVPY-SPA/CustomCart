<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Cities (wl_cities) — Sitepanel > Manage Location > a state's cities.
 *
 * Set $state_id before the bulk methods: only that state's cities are
 * found, so a bulk action can't reach another state's rows.
 */
class CityModel extends BaseModel
{
    public const TABLE       = 'wl_cities';
    public const PRIMARY_KEY = 'city_id';

    public int $state_id = 0;

    /**
     * One state's cities by name; the total is left in $this->total_rec_found.
     *
     * @param string $status '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_cities(int $state_id, int $offset, int $per_page, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'city_name ASC', $offset, $per_page, static function ($db) use ($state_id, $keyword, $status) {
            $db->where('state_id', $state_id);
            $keyword !== '' && $db->like('city_name', $keyword);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * The website's address form lists, by name: active countries that have
     * at least one active state => their active states => those states'
     * active cities. A state without cities has an empty list.
     *
     * @return array<string, array<string, list<string>>>|false
     */
    public function get_location_tree()
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(StateModel::TABLE . ' AS s')
            ->select('co.name AS country_name, s.state_name, ci.city_name')
            ->join(CountryModel::TABLE . ' AS co', 'co.country_id = s.country_id')
            ->join(self::TABLE . ' AS ci', "ci.state_id = s.state_id AND ci.status = '1'", 'left')
            ->where(['co.status' => '1', 's.status' => '1'])
            ->orderBy('co.name', 'ASC')
            ->orderBy('s.state_name', 'ASC')
            ->orderBy('ci.city_name', 'ASC')
            ->get());
        if ($rows === false) {
            return false;
        }

        $tree = [];
        foreach ($rows as $row) {
            $tree[$row['country_name']][$row['state_name']] ??= [];
            $row['city_name'] !== null && $tree[$row['country_name']][$row['state_name']][] = $row['city_name'];
        }

        return $tree;
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_city_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_cities_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('state_id', $this->state_id)->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * @return bool|null NULL when it could not be checked
     */
    public function name_exists(int $state_id, string $name, int $except_id = 0): ?bool
    {
        $where = ['state_id' => $state_id, 'city_name' => $name];
        $except_id > 0 && $where['city_id !='] = $except_id;
        $count = $this->findCount(self::TABLE, $where);

        return $count === false ? null : $count > 0;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $ids && $this->db_ready()
            && ! $this->write_failed(fn () => (bool) $this->table(self::TABLE)->where('state_id', $this->state_id)->whereIn(self::PRIMARY_KEY, $ids)->update(['status' => $status]));
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $ids && $this->db_ready()
            && ! $this->write_failed(fn () => $this->table(self::TABLE)->where('state_id', $this->state_id)->whereIn(self::PRIMARY_KEY, $ids)->delete() !== false);
    }
}
