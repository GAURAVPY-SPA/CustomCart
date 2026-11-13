<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Countries (wl_countries) — Sitepanel > Manage Location.
 */
class CountryModel extends BaseModel
{
    public const TABLE       = 'wl_countries';
    public const PRIMARY_KEY = 'country_id';

    /**
     * By name, with each country's number of states; the total is left in $this->total_rec_found.
     *
     * @param string $status '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_countries(int $offset, int $per_page, string $keyword = '', string $status = '')
    {
        $filter = static function ($db) use ($keyword, $status) {
            $keyword !== '' && $db->like('c.name', $keyword);
            $status !== '' && $db->where('c.status', $status);
        };

        $this->total_rec_found = 0;
        if (! $this->db_ready()) {
            return false;
        }

        $count = $this->table(self::TABLE . ' AS c');
        $filter($count);
        $count = $this->single_row($count->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        $rows = $this->table(self::TABLE . ' AS c')
            ->select('c.*, (SELECT COUNT(*) FROM ' . $this->db->prefixTable(StateModel::TABLE) . ' s WHERE s.country_id = c.country_id) AS state_count', false);
        $filter($rows);

        return $this->all_rows($rows->orderBy('c.name', 'ASC')->limit($per_page, $offset)->get());
    }

    /**
     * Countries for a select box (id => name), by name.
     *
     * @return array|false
     */
    public function get_country_options(bool $active_only = false)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $builder = $this->table(self::TABLE)->select('country_id, name')->orderBy('name', 'ASC');
        $active_only && $builder->where('status', '1');
        $rows = $this->all_rows($builder->get());

        return $rows === false ? false : array_column($rows, 'name', self::PRIMARY_KEY);
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_country_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_countries_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * @return bool|null NULL when it could not be checked
     */
    public function name_exists(string $name, int $except_id = 0): ?bool
    {
        $where = ['name' => $name];
        $except_id > 0 && $where['country_id !='] = $except_id;
        $count = $this->findCount(self::TABLE, $where);

        return $count === false ? null : $count > 0;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    /**
     * Deletes the countries with their states and those states' cities, in
     * one transaction (children deleted here, not left to the foreign key
     * cascade, so nothing is orphaned when foreign_key_checks is off).
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return ! $this->write_failed(function () use ($ids) {
            $this->db->transStart();
            $this->table(CityModel::TABLE)->whereIn('state_id', static fn ($b) => $b->select('state_id')->from(StateModel::TABLE)->whereIn('country_id', $ids))->delete();
            $this->table(StateModel::TABLE)->whereIn('country_id', $ids)->delete();
            $this->table(self::TABLE)->whereIn(self::PRIMARY_KEY, $ids)->delete();
            $this->db->transComplete();

            return $this->db->transStatus();
        });
    }
}
