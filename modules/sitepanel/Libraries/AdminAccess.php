<?php

namespace Modules\Sitepanel\Libraries;

use Modules\Sitepanel\Models\SectionModel;

/**
 * Sitepanel authorization (service('adminAccess'); same model as the
 * reference project):
 *
 *  - admin_type 1 (super admin): every section, every privilege.
 *  - admin_type 2+ (sub-admin): only sections granted in
 *    tbl_admin_allowed_sections, with the privilege codes stored there.
 *  - ALWAYS_ALLOWED controllers (the dashboard) are open to every admin with
 *    View rights, so a sub-admin always has a landing page.
 *  - A controller with no row in tbl_admin_sections is super-admin only.
 */
class AdminAccess
{
    public const SUPER_ADMIN     = 1;
    public const PRIV_VIEW       = 1;
    public const PRIV_ADD        = 2;
    public const PRIV_EDIT       = 3;
    public const PRIV_ACTIVATE   = 4;
    public const PRIV_DEACTIVATE = 5;
    public const PRIV_DELETE     = 6;

    /**
     * Matched against tbl_admin_sections.section_controller.
     */
    public const ALWAYS_ALLOWED = ['dashboard', 'change_password'];

    /**
     * Heading shown as the sidebar's expandable group (tbl_admin_sections.section_title).
     */
    public const OTHER_MANAGEMENT = 'Other Management';

    protected ?SectionModel $model = null;

    protected function model(): SectionModel
    {
        return $this->model ??= new SectionModel();
    }

    public function is_super_admin(array $admin): bool
    {
        return (int) $admin['admin_type'] === self::SUPER_ADMIN;
    }

    /**
     * Privilege codes the admin holds on a controller.
     *
     * @param string $controller Lower-case controller name (tbl_admin_sections.section_controller)
     *
     * @return array|false Empty array = no access; FALSE = could not be determined
     */
    public function permissions_for(array $admin, string $controller)
    {
        if ($this->is_super_admin($admin)) {
            return [self::PRIV_VIEW, self::PRIV_ADD, self::PRIV_EDIT,
                self::PRIV_ACTIVATE, self::PRIV_DEACTIVATE, self::PRIV_DELETE];
        }

        if (in_array($controller, self::ALWAYS_ALLOWED, true)) {
            return [self::PRIV_VIEW];
        }

        $codes = $this->model()->granted_permissions((int) $admin['admin_id'], $controller);

        if ($codes === false) {
            return false;
        }

        // A grant without View doesn't open the page.
        return (is_array($codes) && in_array(self::PRIV_VIEW, $codes, true)) ? $codes : [];
    }

    /**
     * Sidebar sections the admin may see, grouped by heading.
     *
     * @return array|false heading => list of [title, controller]
     */
    public function menu(array $admin)
    {
        $rows = $this->model()->menu_sections(
            $this->is_super_admin($admin) ? null : (int) $admin['admin_id'],
            self::ALWAYS_ALLOWED,
        );

        if ($rows === false) {
            return false;
        }

        $menu = [];
        $seen = [];

        foreach ($rows as $row) {
            // Duplicate rows for one controller: list it once (first in menu order).
            if (isset($seen[$row['section_controller']])) {
                continue;
            }
            $seen[$row['section_controller']] = true;

            $menu[$row['heading']][] = [
                'title'      => $row['section_title'],
                'controller' => $row['section_controller'],
            ];
        }

        return $menu;
    }
}
