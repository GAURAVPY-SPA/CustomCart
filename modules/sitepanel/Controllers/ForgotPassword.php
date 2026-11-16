<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\BaseController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Config\Sitepanel as SitepanelConfig;
use Modules\Sitepanel\Libraries\AdminAuth;
use Modules\Sitepanel\Libraries\AdminPasswordPolicy;
use Modules\Sitepanel\Libraries\AdminResetMailer;
use Modules\Sitepanel\Models\AdminModel;
use Modules\Sitepanel\Models\AdminPasswordResetModel;

/**
 * Sitepanel "Forgot password?" (public — no AdminController guard).
 *
 *   GET  sitepanel/forgot_password          -> email form
 *   POST sitepanel/forgot_password          -> email a reset link to an active admin with that address
 *   GET  sitepanel/reset_password?token=... -> check the link, then redirect to the clean URL below
 *   GET  sitepanel/reset_password           -> new password form (link kept in the session)
 *   POST sitepanel/reset_password           -> save the new password, end the link
 *
 * The forgot form says whether the address belongs to an administrator: only
 * an active admin's tbl_admin.admin_email gets a token and an email; an unknown
 * or inactive address gets an error and nothing is created or sent (the
 * per-session request limit slows down probing of addresses). A link is a 64-hex
 * random token; only its sha256 is stored (AdminPasswordResetModel), it
 * expires after Config\Sitepanel::$passwordResetMinutes and works once.
 * After it is opened, the token leaves the address bar: only its hash is kept,
 * in the session, so it is not left in the browser history or sent on.
 *
 * The new password follows AdminPasswordPolicy and is saved with
 * AdminModel::update_password_hash() (password_hash(), legacy plain text
 * cleared, sign-in lockout lifted), as Change Password and
 * php spark admin:reset-password do. Sign-in itself (AdminAuth) is unchanged.
 * CSRF: the global csrf filter covers both POST forms.
 */
class ForgotPassword extends BaseController
{
    /**
     * Session key: sha256 of the reset link being used.
     */
    protected const SESSION_RESET = 'sitepanel_reset_token_hash';

    /**
     * Session key: times of this session's reset requests (per-hour limit).
     */
    protected const SESSION_REQUESTS = 'sitepanel_reset_requests';

    /**
     * Results of send_link().
     */
    protected const SENT        = 'sent';
    protected const NOT_FOUND   = 'not_found';
    protected const INACTIVE    = 'inactive';
    protected const LIMITED     = 'limited';
    protected const NOT_SENT    = 'not_sent';
    protected const UNAVAILABLE = 'unavailable';

    /**
     * Message shown for each result but SENT.
     */
    protected const MESSAGES = [
        self::NOT_FOUND   => 'This email address is not registered with any Sitepanel account.',
        self::INACTIVE    => 'The Sitepanel account with this email address is inactive. Please contact a super administrator.',
        self::LIMITED     => 'Too many password reset emails have been sent to this account. Please wait an hour and try again.',
        self::NOT_SENT    => 'The password reset email could not be sent. Please try again later or contact the site administrator.',
        self::UNAVAILABLE => 'Password reset is temporarily unavailable. Please try again shortly.',
    ];

    /**
     * @var list<string>
     */
    protected $helpers = ['form', 'sitepanel'];

    protected AdminAuth $admin_auth;
    protected SitepanelConfig $config;
    protected AdminPasswordResetModel $resets;

    protected function init(): void
    {
        $this->admin_auth = service('adminAuth');
        $this->config     = config(SitepanelConfig::class);
        $this->resets     = new AdminPasswordResetModel();
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        // The reset page's URL carries the token: never send it to another page.
        $this->response->setHeader('Referrer-Policy', 'no-referrer');
    }

    public function index(): string
    {
        if ($this->admin_auth->is_logged_in()) {
            redirect_now('sitepanel/dashboard');
        }

        $errors = [];
        $email  = '';

        if ($this->request->getMethod() === 'POST') {
            $this->trim_post(['email']);
            $email = (string) $this->request->getPost('email');

            $v = new FormValidation($this->request, $this);
            $v->set_rules('email', 'Email address', 'required|valid_email|max_length[191]');

            if (! $v->run()) {
                $errors = array_values($v->error_array());
            } elseif (! $this->request_allowed()) {
                $errors = ['Too many password reset requests. Please wait an hour and try again.'];
            } else {
                $result = $this->send_link($email);

                if ($result === self::SENT) {
                    session()->setFlashdata('sitepanel_notice', 'Password reset instructions have been sent to ' . $email . '. '
                        . 'The link expires in ' . $this->minutes() . ' minutes. Please also check your spam folder.');
                    redirect_now('sitepanel/forgot_password'); // post/redirect/get
                }

                $errors = [self::MESSAGES[$result]];
            }
        }

        return sp_view('dashboard/admin_forgot_password', array_merge($this->view_data, [
            'page_title' => 'Forgot Password | ' . $this->site_settings->get('site_title'),
            'errors'     => $errors,
            'notice'     => session()->getFlashdata('sitepanel_notice'),
            'email'      => $email,
        ]));
    }

    public function reset(): string
    {
        if ($this->admin_auth->is_logged_in()) {
            redirect_now('sitepanel/dashboard');
        }

        // Opened from the email: check the link, keep only its hash, drop the token from the URL.
        $token = $this->request->getGet('token');
        if ($token !== null) {
            $hash = AdminPasswordResetModel::hash_token(is_string($token) ? $token : '');
            if ($hash !== null && is_array($this->resets->find_valid($hash))) {
                session()->set(self::SESSION_RESET, $hash);
            } else {
                session()->remove(self::SESSION_RESET);
            }
            redirect_now('sitepanel/reset_password');
        }

        $hash  = (string) session(self::SESSION_RESET);
        $reset = $hash !== '' ? $this->resets->find_valid($hash) : null;
        $errors = [];

        if (is_array($reset) && $this->request->getMethod() === 'POST') {
            $result = $this->save_password($reset);

            if ($result === true) {
                session()->remove(self::SESSION_RESET);
                session()->regenerate(true);
                session()->setFlashdata('sitepanel_notice', 'Your password has been reset. Please sign in with your new password.');
                redirect_now('sitepanel/login');
            }
            if ($result === null) {
                $reset = null; // used or expired meanwhile
            } else {
                $errors = $result;
            }
        }

        if (! is_array($reset)) {
            session()->remove(self::SESSION_RESET);
        }

        return sp_view('dashboard/admin_reset_password', array_merge($this->view_data, [
            'page_title'  => 'Reset Password | ' . $this->site_settings->get('site_title'),
            'valid'       => is_array($reset),
            'unavailable' => $reset === false,
            'errors'      => $errors,
            'policy'      => new AdminPasswordPolicy(),
        ]));
    }

    /**
     * Email a new reset link when $email is an active administrator's address
     * (tbl_admin.admin_email) and that administrator hasn't had too many links
     * this hour. No token is created and nothing is sent otherwise.
     *
     * @return string One of the result constants (SENT, NOT_FOUND, ...)
     */
    protected function send_link(string $email): string
    {
        $admin = (new AdminModel())->find_by_email($email);
        if ($admin === false) {
            return self::UNAVAILABLE;
        }
        if (! is_array($admin)) {
            return self::NOT_FOUND;
        }
        if ((int) $admin['status'] !== 1) {
            return self::INACTIVE;
        }

        $recent = $this->resets->count_recent((int) $admin['admin_id'], 3600);
        if ($recent === false) {
            return self::UNAVAILABLE;
        }
        if ($recent >= max(1, $this->config->passwordResetEmailsPerHour)) {
            return self::LIMITED;
        }

        $token = $this->resets->create_token((int) $admin['admin_id'], $this->minutes(), $this->request->getIPAddress());
        if ($token === false) {
            return self::UNAVAILABLE;
        }

        if (! (new AdminResetMailer())->password_reset($admin, site_url('sitepanel/reset_password') . '?token=' . $token, $this->minutes())) {
            return self::NOT_SENT; // the reason is logged by AdminResetMailer (never the token)
        }

        return self::SENT;
    }

    /**
     * Validate and save the new password.
     *
     * @return list<string>|bool|null TRUE = saved; NULL = the link is no longer valid; list = errors
     */
    protected function save_password(array $reset)
    {
        // Passwords are not trimmed: they are saved exactly as typed.
        $v = new FormValidation($this->request, $this);
        // Length and characters: AdminPasswordPolicy (Config\Sitepanel), checked below.
        $v->set_rules('new_password', 'New password', 'required');
        $v->set_rules('confirm_password', 'Confirm new password', 'required|matches[new_password]', ['matches' => 'The new passwords do not match.']);

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $password = (string) $this->request->getPost('new_password');
        $errors   = (new AdminPasswordPolicy())->errors($password, 'The new password');
        if ($errors) {
            return $errors;
        }

        $saved = $this->resets->use_token((int) $reset['id'], (int) $reset['admin_id'], password_hash($password, PASSWORD_DEFAULT));

        return $saved === false ? ['Your password could not be changed right now. Please try again shortly.'] : $saved;
    }

    /**
     * Count a reset request of this session; FALSE once the hourly limit is reached.
     */
    protected function request_allowed(): bool
    {
        $since    = time() - 3600;
        $requests = array_values(array_filter((array) session(self::SESSION_REQUESTS), static fn ($time) => (int) $time > $since));
        if (count($requests) >= max(1, $this->config->passwordResetRequestsPerHour)) {
            return false;
        }
        $requests[] = time();
        session()->set(self::SESSION_REQUESTS, $requests);

        return true;
    }

    protected function minutes(): int
    {
        return max(1, $this->config->passwordResetMinutes);
    }
}
