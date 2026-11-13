<?php

namespace App\Controllers;

use App\Exceptions\HaltException;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Base controller for the public website pages.
 *
 * Closes the website with the
 * maintenance page while Admin Settings > Maintenance mode is on (the
 * sitepanel stays available so it can be switched off again).
 *
 * Page views include the shared layout themselves, as in CodeIgniter 3:
 * echo view('top_application') ... echo view('bottom_application'), from
 * app/Views. The <head> SEO tags come from seo_head() (wl_meta_tags); a
 * controller passes the page's own defaults as $page_meta.
 */
abstract class PublicController extends BaseController
{
    protected function boot(): void
    {
        if ($this->site_settings->maintenance_mode() && ! $this->sitepanel_request()) {
            // Unmatched URL: this is the 404 override, where CodeIgniter 4 does not
            // send a thrown response, so render_not_found() returns the page instead.
            if (service('router')->getMatchedRoute() === null) {
                $this->maintenance = true;

                return;
            }

            throw new HaltException($this->response->setBody($this->maintenance_page()));
        }
    }

    /**
     * Maintenance mode is on and this request is answered with the maintenance page.
     */
    protected bool $maintenance = false;

    /**
     * The maintenance page (503, Retry-After), as in CodeIgniter 3.
     */
    protected function maintenance_page(): string
    {
        $this->response->setStatusCode(503)->setHeader('Retry-After', '3600');

        return $this->render('maintenance_mode', [
            'page_meta' => [
                'lookup'    => false,
                'title'     => $this->site_settings->get('site_title'),
                'canonical' => base_url(),
            ],
        ]);
    }

    /**
     * Render a page view with the data shared by every public page.
     */
    protected function render(string $view, array $data = []): string
    {
        return view($view, $data + $this->view_data);
    }

    /**
     * Stop with the designed 404 page (CodeIgniter 3 show_404()).
     */
    protected function not_found_page(): never
    {
        throw PageNotFoundException::forPageNotFound();
    }

    /**
     * Whether the URL is under sitepanel/ (the 404 override also handles
     * those; as in CodeIgniter 3 they keep the plain 404 template and are
     * never closed by maintenance mode).
     */
    protected function sitepanel_request(): bool
    {
        $path = trim(service('request')->getPath(), '/');

        return $path === 'sitepanel' || str_starts_with($path, 'sitepanel/');
    }

    /**
     * The designed 404 page (with the site header and footer), rendered by
     * the 404 override (Pages::not_found) for every unknown URL; sitepanel
     * URLs get the plain errors/html/error_404 template.
     */
    protected function render_not_found(): string
    {
        if ($this->maintenance) {
            return $this->maintenance_page();
        }

        $this->response->setStatusCode(404);

        if ($this->sitepanel_request()) {
            return view('errors/html/error_404', [], ['saveData' => false]);
        }

        return $this->render('page_not_found', [
            'page_meta' => [
                'lookup'      => false,
                'title'       => 'Page Not Found | ' . $this->site_settings->get('site_name'),
                'description' => "Sorry, the page you are looking for doesn't exist. It may have been moved, or the link may be incorrect.",
                'canonical'   => current_url(),
            ],
        ]);
    }
}
