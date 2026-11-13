<?php

namespace App\Controllers;

use App\Libraries\Captcha as CaptchaLibrary;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * GET captcha/<form>: a new verification code for one of the public forms
 * (Config\Captcha::$forms), as a PNG that is never cached. Each request
 * replaces the form's code in the session (App\Libraries\Captcha), so the
 * code of a refreshed image no longer works. Unknown forms get a plain 404.
 *
 * A plain Controller (not BaseController), as Uploads: no settings query or
 * maintenance page for an image request.
 */
class Captcha extends Controller
{
    public function image(string $form = ''): ResponseInterface
    {
        $png = (new CaptchaLibrary())->image($form);
        if ($png === '') {
            return $this->response->setStatusCode(404)->setContentType('text/plain')->setBody('Not Found');
        }

        return $this->response
            ->setContentType('image/png')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setBody($png);
    }
}
