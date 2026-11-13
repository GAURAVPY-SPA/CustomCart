<?php

namespace Modules\Sitepanel\Libraries;

use App\Exceptions\HaltException;
use CodeIgniter\Session\Session;
use Config\Session as SessionConfig;
use Modules\Sitepanel\Models\AdminModel;

/**
 * Sitepanel authentication: login, session guard and logout
 * (service('adminAuth')).
 *
 * Only the admin id is kept in the session; the account is re-read on every
 * protected request, so deactivating or deleting an admin takes effect
 * immediately.
 */
class AdminAuth
{
    public const SESSION_KEY        = 'sitepanel_admin_id';
    public const PREVIOUS_LOGIN_KEY = 'sitepanel_previous_login';
    public const LAST_ACTIVITY_KEY  = 'sitepanel_last_activity';
    public const LOGIN_OK           = 'ok';
    public const LOGIN_INVALID      = 'invalid';
    public const LOGIN_THROTTLED    = 'throttled';
    public const LOGIN_UNAVAILABLE  = 'unavailable';

    /**
     * Brute-force lockout (tbl_admin.failed_attempts / locked_until; no
     * login-history table): after MAX_FAILURES_PER_USERNAME wrong passwords
     * the account is locked for THROTTLE_WINDOW_SECONDS and sign-in is refused
     * without checking the password. A successful sign-in resets the count.
     */
    public const THROTTLE_WINDOW_SECONDS   = 900;
    public const MAX_FAILURES_PER_USERNAME = 5;

    /**
     * Hash of a random throwaway string. Verified against when the username
     * doesn't exist, so response time doesn't reveal valid usernames.
     */
    public const DUMMY_HASH = '$2y$10$R2TGDwyYIrU9Hm0t4bndlOCZG.uYxg5gcXujinK7Tn.K2IBfEzSV2';

    protected ?AdminModel $model = null;

    /**
     * Administrator loaded by require_login() in this request.
     */
    protected ?array $admin = null;

    protected function model(): AdminModel
    {
        return $this->model ??= new AdminModel();
    }

    protected function session(): Session
    {
        return session();
    }

    public function is_logged_in(): bool
    {
        return (int) $this->session()->get(self::SESSION_KEY) > 0;
    }

    /**
     * Verify credentials and start an authenticated session.
     *
     * @return string One of the LOGIN_* constants
     */
    public function attempt(string $username, string $password): string
    {
        $ip = service('request')->getIPAddress();

        $admin = $this->model()->find_for_login($username);

        if ($admin === false) {
            return self::LOGIN_UNAVAILABLE;
        }

        if ($admin && $admin['locked_until'] !== null && strtotime($admin['locked_until']) > time()) {
            return self::LOGIN_THROTTLED;
        }

        $plain = $this->plaintext_password($admin);
        $hash  = $admin ? $admin['password_hash'] : self::DUMMY_HASH;
        $valid = ($plain !== null)
            ? hash_equals($plain, $password)
            : password_verify($password, (string) $hash);

        if (! $admin || ! $valid || (int) $admin['status'] !== 1) {
            if ($admin && ! $valid) {
                $this->model()->record_failed_login((int) $admin['admin_id'], self::MAX_FAILURES_PER_USERNAME, self::THROTTLE_WINDOW_SECONDS);
            }

            return self::LOGIN_INVALID;
        }

        // A legacy plain-text password that just matched is replaced by its hash (the password itself
        // stays the same); a hash made with older settings is renewed. Failing to save never blocks the sign-in.
        if ($plain !== null || password_needs_rehash((string) $hash, PASSWORD_DEFAULT)) {
            if (! $this->model()->update_password_hash((int) $admin['admin_id'], password_hash($password, PASSWORD_DEFAULT))) {
                log_message('error', 'AdminAuth: the password of admin ' . (int) $admin['admin_id'] . ' could not be saved as a hash; it is converted at the next sign-in.');
            }
        }

        // New session id on privilege change (prevents session fixation).
        $this->session()->regenerate(true);
        $this->session()->set([
            self::SESSION_KEY       => (int) $admin['admin_id'],
            self::LAST_ACTIVITY_KEY => time(),
            // Kept before record_login() overwrites it, for the dashboard.
            self::PREVIOUS_LOGIN_KEY => [
                'at' => $admin['last_login_at'],
                'ip' => $admin['last_login_ip'],
            ],
        ]);

        $this->model()->record_login((int) $admin['admin_id'], $ip);

        return self::LOGIN_OK;
    }

    /**
     * Whether $password is the administrator's current password (the hash,
     * or a legacy plain-text password not converted yet), for Change Password.
     *
     * @return bool|null NULL = could not be checked (database unavailable)
     */
    public function check_password(int $admin_id, string $password): ?bool
    {
        $stored = $this->model()->find_password($admin_id);

        if (! is_array($stored)) {
            return null;
        }

        $plain = $this->plaintext_password($stored);

        return ($plain !== null)
            ? hash_equals($plain, $password)
            : ((string) $stored['password_hash'] !== '' && password_verify($password, (string) $stored['password_hash']));
    }

    /**
     * Legacy plain-text admin password (tbl_admin.admin_password), kept only
     * so accounts saved that way can still sign in: when it is non-empty it
     * is compared as plain text (constant time) and, on a successful sign-in,
     * replaced by its password_hash() value (attempt()). Every password saved
     * now (sign-in, Change Password, Manage Subadmins, sitepanel:create-admin,
     * admin:reset-password) is a hash, and admin_password is cleared; when it
     * is empty, or the column does not exist, password_hash is verified.
     */
    protected function plaintext_password(?array $admin): ?string
    {
        if (! $admin || ! isset($admin['admin_password'])) {
            return null;
        }

        return $admin['admin_password'] !== '' ? (string) $admin['admin_password'] : null;
    }

    /**
     * Guard for protected pages. Returns the signed-in, active administrator
     * or ends the request with a redirect to the login page. The account is
     * read once per request (the route filter and the controller share it).
     */
    public function require_login(): array
    {
        if ($this->admin !== null) {
            return $this->admin;
        }

        $admin_id = (int) $this->session()->get(self::SESSION_KEY);

        if ($admin_id < 1) {
            redirect_now('sitepanel/login');
        }

        // Server-side idle timeout, as in CodeIgniter 3: the session's own
        // expiry depends on cookie lifetime and probabilistic garbage
        // collection, so a replayed cookie could outlive it without this check.
        $last_activity = (int) $this->session()->get(self::LAST_ACTIVITY_KEY);
        if ($last_activity < 1 || time() - $last_activity > config(SessionConfig::class)->expiration) {
            $this->logout();
            $this->session()->setFlashdata('sitepanel_notice', 'Your session has expired. Please sign in again.');
            redirect_now('sitepanel/login');
        }
        $this->session()->set(self::LAST_ACTIVITY_KEY, time());

        $admin = $this->model()->find_active_by_id($admin_id);

        if ($admin === false) {
            throw HaltException::error('The sitepanel is temporarily unavailable. Please try again shortly.', 503, 'Service unavailable');
        }

        if ($admin === null) {
            // Account deleted or deactivated since sign-in.
            $this->logout();
            redirect_now('sitepanel/login');
        }

        return $this->admin = $admin;
    }

    /**
     * Sign-in before the current one, captured at login.
     *
     * @return array ['at' => string|null, 'ip' => string|null]
     */
    public function previous_login(): array
    {
        $previous = $this->session()->get(self::PREVIOUS_LOGIN_KEY);

        return is_array($previous) ? $previous : ['at' => null, 'ip' => null];
    }

    /**
     * Wipe all session data and move to a new session id; the old session is
     * destroyed server-side. The new (empty) session can still carry a flash
     * notice for the login page.
     */
    public function logout(): void
    {
        // Start (load) the session first: clearing $_SESSION before the
        // session is started would be undone when it starts.
        $session     = $this->session();
        $this->admin = null;
        $_SESSION    = [];
        $session->regenerate(true);
    }
}
