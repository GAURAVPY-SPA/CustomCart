<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\BaseController;
use Modules\Sitepanel\Libraries\AdminAuth;

/**
 * Sitepanel sign-in and sign-out (public — no AdminController guard).
 *
 *   GET  sitepanel          -> dashboard when signed in, otherwise login
 *   GET  sitepanel/login    -> login form
 *   POST sitepanel/login    -> verify credentials
 *   POST sitepanel/logout   -> end session
 */
class Sitepanel extends BaseController
{
    /**
     * @var list<string>
     */
    protected $helpers = ['form', 'sitepanel'];

    protected AdminAuth $admin_auth;

    protected function init(): void
    {
        $this->admin_auth = service('adminAuth');
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function index(): void
    {
        redirect_now($this->admin_auth->is_logged_in() ? 'sitepanel/dashboard' : 'sitepanel/login');
    }

    public function login(): string
    {
        if ($this->admin_auth->is_logged_in()) {
            redirect_now('sitepanel/dashboard');
        }

        $data = [
            'page_title' => 'Sitepanel Login | ' . $this->site_settings->get('site_title'),
            'error'      => '',
            'notice'     => session()->getFlashdata('sitepanel_notice'),
        ];

        if ($this->request->getMethod() === 'POST') {
            $this->trim_post(['username']);
            $validation = service('validation');
            $validation->setRules([
                'username' => ['label' => 'Username', 'rules' => 'required|max_length[50]'],
                'password' => ['label' => 'Password', 'rules' => 'required|max_length[255]'],
            ]);

            if ($validation->run($this->request->getPost()) === true) {
                $result = $this->admin_auth->attempt(
                    (string) $this->request->getPost('username'),
                    (string) $this->request->getPost('password'),
                );

                if ($result === AdminAuth::LOGIN_OK) {
                    redirect_now('sitepanel/dashboard');
                }

                $messages = [
                    AdminAuth::LOGIN_INVALID   => 'Invalid username or password.',
                    AdminAuth::LOGIN_THROTTLED => 'Too many failed sign-in attempts. Please wait '
                        . (AdminAuth::THROTTLE_WINDOW_SECONDS / 60) . ' minutes and try again.',
                    AdminAuth::LOGIN_UNAVAILABLE => 'Sign-in is temporarily unavailable. Please try again shortly.',
                ];
                $data['error'] = $messages[$result];
            }
        }

        return sp_view('dashboard/admin_login', array_merge($this->view_data, $data));
    }

    public function logout(): void
    {
        // Logout is POST-only so other sites can't sign an admin out via a link/image.
        if ($this->request->getMethod() === 'POST') {
            $this->admin_auth->logout();
            session()->setFlashdata('sitepanel_notice', 'You have been signed out.');
        }

        redirect_now('sitepanel/login');
    }
}
