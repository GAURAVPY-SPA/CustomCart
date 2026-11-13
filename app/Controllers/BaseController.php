<?php

namespace App\Controllers;

use App\Libraries\SiteSettings;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Base controller for the whole application (the CodeIgniter 3 MY_Controller).
 *
 * Sends the security headers, loads the website settings (one query;
 * defaults if the database is unavailable) and applies the saved timezone.
 * No session work, so a page that doesn't need one never sets a session
 * cookie.
 *
 * CodeIgniter 4 creates the controller before it has a request, so set-up
 * code that the CodeIgniter 3 controllers ran in their constructors goes in
 * init(), which runs at the end of initController().
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    protected SiteSettings $site_settings;

    /**
     * Data shared with every view this controller renders.
     */
    protected array $view_data = [];

    /**
     * @param CLIRequest|IncomingRequest $request
     *
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // No framing (clickjacking), no MIME sniffing, no full URLs leaked to other sites.
        $this->response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->response->setHeader('X-Content-Type-Options', 'nosniff');
        $this->response->setHeader('Referrer-Policy', 'same-origin');

        $this->site_settings = service('siteSettings');

        // Saved timezone (validated on save) replaces the Config\App default.
        $timezone = $this->site_settings->get('timezone');
        if (in_array($timezone, timezone_identifiers_list(), true)) {
            date_default_timezone_set($timezone);
        }

        $this->view_data['site_name']          = $this->site_settings->get('site_name');
        $this->view_data['site_title']         = $this->site_settings->get('site_title');
        $this->view_data['site_logo']          = $this->site_settings->logo_path();
        $this->view_data['site_favicon']       = $this->site_settings->favicon_path();
        $this->view_data['copyright']          = $this->site_settings->copyright();
        $this->view_data['footer_description'] = $this->site_settings->get('footer_description');

        $this->boot();
        $this->init();
    }

    /**
     * Set-up of an intermediate base class (AdminController: sign-in and
     * permission checks). Runs before init(), so a module controller's
     * set-up never runs for a request its base class refuses.
     */
    protected function boot(): void
    {
    }

    /**
     * Controller set-up that needs the request, session or services
     * (constructor code in CodeIgniter 3). Runs at the end of initController().
     */
    protected function init(): void
    {
    }

    /**
     * Trim POST fields in the request before validation, as CodeIgniter 3's
     * 'trim' validation rule did (it rewrote $_POST): validation, set_value()
     * and later getPost() calls all see the trimmed values. Arrays are trimmed
     * per string element. (CodeIgniter 4 validation never changes the data.)
     *
     * @param list<string> $fields
     */
    protected function trim_post(array $fields): void
    {
        $post = $this->request->getPost();
        $post = is_array($post) ? $post : [];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $post)) {
                continue;
            }
            if (is_string($post[$field])) {
                $post[$field] = trim($post[$field]);
            } elseif (is_array($post[$field])) {
                array_walk_recursive($post[$field], static function (&$value): void {
                    $value = is_string($value) ? trim($value) : $value;
                });
            }
        }

        $this->request->setGlobal('post', $post);
    }
}
