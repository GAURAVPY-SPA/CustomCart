<?php

namespace Modules\Sitepanel\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Protected sitepanel routes: only a signed-in, active administrator gets
 * through (otherwise a redirect to the login page, or 503 when the account
 * cannot be read). Section permissions are checked by AdminController, which
 * knows the controller being opened.
 */
class SitepanelAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        service('adminAuth')->require_login();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
