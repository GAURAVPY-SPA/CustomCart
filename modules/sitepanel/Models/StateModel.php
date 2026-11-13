<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * States (wl_states) — Sitepanel > Manage Location > a country's states.
 *
 * Set $country_id before the bulk methods: only that country's states are
 * found, so a bulk action can't reach another country's rows.
 */
class StateModel extends BaseModel
{
    public const TABLE       = 'wl_states';
    public const PRIMARY_KEY = 'state_id';

    public int $country_id = 0;

    /**
     * One country's states by name, with each state's number of cities;
     * the total is left in $this->total_rec_found.
     *
     * @param string $status '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_states(int $country_id, int $offset, int $per_page, string $keyword = '', string $status = '')
    {
        $filter = static function ($db) use ($country_id, $keyword, $status) {
            $db->where('s.country_id', $country_id);
            $keyword !== '' && $db->like('s.state_name', $keyword);
            $status !== '' && $db->where('s.status', $status);
        };

        $this->total_rec_found = 0;
        if (! $this->db_ready()) {
            return false;
        }

        $count = $this->table(self::TABLE . ' AS s');
        $filter($count);
        $count = $this->single_row($count->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        $rows = $this->table(self::TABLE . ' AS s')
            ->select('s.*, (SELECT COUNT(*) FROM ' . $this->db->prefixTable(CityModel::TABLE) . ' c WHERE c.state_id = s.state_id) AS city_count', false);
        $filter($rows);

        return $this->all_rows($rows->orderBy('s.state_name', 'ASC')->limit($per_page, $offset)->get());
    }

    /**
     * One country's states for a select box (id => name), by name.
     *
     * @return array|false
     */
    public function get_state_options(int $country_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::TABLE)->select('state_id, state_name')
            ->where('country_id', $country_id)->orderBy('state_name', 'ASC')->get());

        return $rows === false ? false : array_column($rows, 'state_name', self::PRIMARY_KEY);
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_state_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_states_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('country_id', $this->country_id)->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * @return bool|null NULL when it could not be checked
     */
    public function name_exists(int $country_id, string $name, int $except_id = 0): ?bool
    {
        $where = ['country_id' => $country_id, 'state_name' => $name];
        $except_id > 0 && $where['state_id !='] = $except_id;
        $count = $this->findCount(self::TABLE, $where);

        return $count === false ? null : $count > 0;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $ids && $this->db_ready()
            && ! $this->write_failed(fn () => (bool) $this->table(self::TABLE)->where('country_id', $this->country_id)->whereIn(self::PRIMARY_KEY, $ids)->update(['status' => $status]));
    }

    /**
     * Deletes the states of $this->country_id and their cities, in one
     * transaction. Children are deleted here, not left to the foreign key
     * cascade, so nothing is orphaned when the server runs with
     * foreign_key_checks off.
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }
        $ids = array_map('intval', (array) array_column((array) $this->get_states_by_ids($ids), self::PRIMARY_KEY));

        return $ids && ! $this->write_failed(function () use ($ids) {
            $this->db->transStart();
            $this->table(CityModel::TABLE)->whereIn('state_id', $ids)->delete();
            $this->table(self::TABLE)->whereIn(self::PRIMARY_KEY, $ids)->delete();
            $this->db->transComplete();

            return $this->db->transStatus();
        });
    }
}
