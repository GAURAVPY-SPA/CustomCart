<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;
use Throwable;

/**
 * Sub-admins (tbl_admin rows with admin_type > 1) and their section grants
 * (tbl_admin_allowed_sections) — Sitepanel > Manage Subadmins.
 *
 * Every lookup and change here is limited to sub-admins, so the super
 * admin can never be listed, edited, deactivated or deleted through it.
 */
class SubadminModel extends BaseModel
{
    public const SUBADMIN_TYPE = 2;

    public const TABLE       = 'tbl_admin';
    public const PRIMARY_KEY = 'admin_id';

    protected const SECTIONS = 'tbl_admin_sections';
    protected const ALLOWED  = 'tbl_admin_allowed_sections';

    /**
     * Newest first, with their number of granted sections; the total is left
     * in $this->total_rec_found.
     *
     * @param string $keyword Part of the name, username or email
     * @param string $status  '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_subadmins(int $offset, int $per_page, string $keyword = '', string $status = '')
    {
        $filter = static function ($db) use ($keyword, $status) {
            $db->where('a.admin_type >', 1);
            if ($keyword !== '') {
                $db->groupStart()->like('a.admin_name', $keyword)->orLike('a.admin_username', $keyword)->orLike('a.admin_email', $keyword)->groupEnd();
            }
            $status !== '' && $db->where('a.status', (int) $status);
        };

        $this->total_rec_found = 0;
        if (! $this->db_ready()) {
            return false;
        }

        $count = $this->table(self::TABLE . ' AS a');
        $filter($count);
        $count = $this->single_row($count->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        $rows = $this->table(self::TABLE . ' AS a')->select(
            'a.admin_id, a.admin_name, a.admin_username, a.admin_email, a.phone, a.status, a.last_login_at, a.created_at, '
            . '(SELECT COUNT(*) FROM ' . $this->db->prefixTable(self::ALLOWED) . ' g WHERE g.subadmin_id = a.admin_id) AS section_count',
            false
        );
        $filter($rows);

        return $this->all_rows($rows->orderBy('a.admin_id', 'DESC')->limit($per_page, $offset)->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_subadmin_by_id($id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table(self::TABLE)
            ->select('admin_id, admin_type, admin_name, admin_username, admin_email, phone, status, last_login_at, created_at')
            ->where(self::PRIMARY_KEY, (int) $id)
            ->where('admin_type >', 1)
            ->limit(1)
            ->get());
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_subadmins_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->select('admin_id')->where('admin_type >', 1)->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * Whether another administrator (any type) already uses this value.
     *
     * @param string $column admin_username | admin_email
     *
     * @return bool|null NULL when it could not be checked
     */
    public function is_taken(string $column, string $value, int $except_id = 0): ?bool
    {
        $where = [$column => $value];
        $except_id > 0 && $where[self::PRIMARY_KEY . ' !='] = $except_id;
        $count = $this->findCount(self::TABLE, $where);

        return $count === false ? null : $count > 0;
    }

    /**
     * Sections that can be granted, grouped by heading (in menu order):
     * active sections with a controller, except $excluded.
     *
     * @param list<string> $excluded section_controller values
     *
     * @return array|false heading => list of [id, parent_id, section_title, section_controller, privileges (list<int>)]
     */
    public function grantable_sections(array $excluded)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $builder = $this->table(self::SECTIONS . ' AS s')
            ->select('h.section_title AS heading, s.id, s.parent_id, s.section_title, s.section_controller, s.actual_privilege')
            ->join(self::SECTIONS . ' AS h', 'h.id = s.parent_id')
            ->where(['h.parent_id' => 0, 'h.status' => '1', 's.status' => '1'])
            ->where("s.section_controller <> ''", null, false)
            ->orderBy('h.disp_order', 'ASC')->orderBy('h.id', 'ASC')
            ->orderBy('s.disp_order', 'ASC')->orderBy('s.id', 'ASC');
        $excluded && $builder->whereNotIn('s.section_controller', $excluded);

        $rows = $this->all_rows($builder->get());
        if ($rows === false) {
            return false;
        }

        $groups = [];

        foreach ($rows as $row) {
            $codes             = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $row['actual_privilege'])))));
            $row['privileges'] = $codes ?: [1];
            $groups[$row['heading']][] = $row;
        }

        return $groups;
    }

    /**
     * A sub-admin's grants: section id => privilege codes.
     *
     * @return array|false
     */
    public function get_grants(int $admin_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::ALLOWED)->select('sec_id, permission')->where('subadmin_id', $admin_id)->get());
        if ($rows === false) {
            return false;
        }

        $grants = [];

        foreach ($rows as $row) {
            $grants[(int) $row['sec_id']] = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $row['permission'])))));
        }

        return $grants;
    }

    /**
     * Add or update a sub-admin and replace their grants, in one transaction.
     *
     * @param array      $data   admin_name, admin_username, admin_email, phone, and password_hash when set
     * @param array      $grants section id => [parent_id, list<int> codes]
     * @param int|null   $id     NULL = add
     *
     * @return false|int The sub-admin's id
     */
    public function save(array $data, array $grants, ?int $id = null)
    {
        if (! $this->db_ready()) {
            return false;
        }

        try {
            $this->db->transException(true)->transStart();
            $now = date('Y-m-d H:i:s');

            if ($id === null) {
                $this->table(self::TABLE)->insert($data + [
                    'admin_type' => self::SUBADMIN_TYPE, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $id = (int) $this->db->insertID();
            } else {
                $this->table(self::TABLE)->where(self::PRIMARY_KEY, $id)->where('admin_type >', 1)->update($data + ['updated_at' => $now]);
                $this->table(self::ALLOWED)->where('subadmin_id', $id)->delete();
            }

            foreach ($grants as $sec_id => [$parent_id, $codes]) {
                $this->table(self::ALLOWED)->insert([
                    'subadmin_id'   => $id,
                    'sec_parent_id' => (int) $parent_id,
                    'sec_id'        => (int) $sec_id,
                    'permission'    => implode(',', $codes),
                ]);
            }

            $this->db->transComplete();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', $this->name() . ' save failed: ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }

        return $id;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $ids && $this->db_ready()
            && ! $this->write_failed(fn () => (bool) $this->table(self::TABLE)->where('admin_type >', 1)->whereIn(self::PRIMARY_KEY, $ids)
                ->update(['status' => (int) $status, 'updated_at' => date('Y-m-d H:i:s')]));
    }

    /**
     * Their grants go with them (foreign key cascade).
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $ids && $this->db_ready()
            && ! $this->write_failed(fn () => $this->table(self::TABLE)->where('admin_type >', 1)->whereIn(self::PRIMARY_KEY, $ids)->delete() !== false);
    }
}
