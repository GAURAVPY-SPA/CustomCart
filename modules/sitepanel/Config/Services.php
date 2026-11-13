<?php

namespace Modules\Sitepanel\Config;

use CodeIgniter\Config\BaseService;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Libraries\AdminAuth;
use Modules\Sitepanel\Libraries\SitepanelTheme;

/**
 * Sitepanel services (discovered by CodeIgniter 4's module discovery).
 * Shared instances: one of each per request.
 */
class Services extends BaseService
{
    /**
     * Sign-in, session guard and sign-out.
     */
    public static function adminAuth(bool $getShared = true): AdminAuth
    {
        if ($getShared) {
            return static::getSharedInstance('adminAuth');
        }

        return new AdminAuth();
    }

    /**
     * Section permissions and the sidebar menu.
     */
    public static function adminAccess(bool $getShared = true): AdminAccess
    {
        if ($getShared) {
            return static::getSharedInstance('adminAccess');
        }

        return new AdminAccess();
    }

    /**
     * Sitepanel theme colours (CSS custom properties).
     */
    public static function sitepanelTheme(bool $getShared = true): SitepanelTheme
    {
        if ($getShared) {
            return static::getSharedInstance('sitepanelTheme');
        }

        return new SitepanelTheme();
    }
}
