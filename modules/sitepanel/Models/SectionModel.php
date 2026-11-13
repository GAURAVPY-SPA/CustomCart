<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Sitepanel sections (tbl_admin_sections) and sub-admin grants
 * (tbl_admin_allowed_sections).
 */
class SectionModel extends BaseModel
{
    public const SECTIONS = 'tbl_admin_sections';
    public const ALLOWED  = 'tbl_admin_allowed_sections';

    /**
     * Active page sections under active headings, in menu order.
     *
     * @param int|null $subadmin_id    Limit to sections granted to this sub-admin
     *                                 (plus $always_allowed); NULL = all sections.
     * @param array    $always_allowed section_controller values every admin may open
     *
     * @return array|false Rows: heading, heading_id, id, section_title, section_controller
     */
    public function menu_sections(?int $subadmin_id = null, array $always_allowed = [])
    {
        if (! $this->db_ready()) {
            return false;
        }

        $builder = $this->table(self::SECTIONS . ' AS s')
            ->select('h.section_title AS heading, h.id AS heading_id, s.id, s.section_title, s.section_controller')
            ->join(self::SECTIONS . ' AS h', 'h.id = s.parent_id')
            ->where('h.parent_id', 0)
            ->where('h.status', '1')
            ->where('s.status', '1')
            // A page row needs a controller to link to and a title to show.
            ->where("s.section_controller <> ''", null, false)
            ->where("s.section_title <> ''", null, false)
            ->orderBy('h.disp_order', 'ASC')
            ->orderBy('h.id', 'ASC')
            ->orderBy('s.disp_order', 'ASC')
            ->orderBy('s.id', 'ASC');

        if ($subadmin_id !== null) {
            $builder->join(self::ALLOWED . ' AS a', 'a.sec_id = s.id AND a.subadmin_id = ' . $subadmin_id, 'left');
            $builder->groupStart()
                ->where('a.id IS NOT NULL', null, false);
            if ($always_allowed) {
                $builder->orWhereIn('s.section_controller', $always_allowed);
            }
            $builder->groupEnd();
        }

        return $this->all_rows($builder->get());
    }

    /**
     * Permission codes granted to a sub-admin for the active section mapped to
     * a controller.
     *
     * @param string $controller section_controller value
     *
     * @return array|false|null int codes; NULL when not granted / not a registered section
     */
    public function granted_permissions(int $subadmin_id, string $controller)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $row = $this->single_row($this->table(self::SECTIONS . ' AS s')
            ->select('a.permission')
            ->join(self::ALLOWED . ' AS a', 'a.sec_id = s.id')
            ->where('s.section_controller', $controller)
            ->where('s.status', '1')
            ->where('a.subadmin_id', $subadmin_id)
            ->limit(1)
            ->get());

        if (! is_array($row)) {
            return $row; // FALSE (error) or NULL (no grant)
        }

        $codes = array_filter(array_map('intval', explode(',', (string) $row['permission'])));

        return array_values(array_unique($codes));
    }
}
