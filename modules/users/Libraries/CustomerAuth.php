<?php

namespace Modules\Users\Libraries;

use Modules\Users\Models\CustomerModel;

/**
 * Store customer sign-in (the reference's Auth library), kept apart from the
 * sitepanel sign-in (AdminAuth) under its own session key.
 *
 * As in the reference, the account is checked again on every request that
 * asks for it (current()), so a member deactivated in the sitepanel is
 * signed out at once. Failed sign-ins lock the account for a while
 * (CustomerModel::record_failed_login(); the reference only had the columns).
 * Only store customer accounts (wl_customers.account_type 'user') sign in.
 *
 *   $auth = new CustomerAuth();
 *   $customer = $auth->current();                     // signed-in customer, or NULL
 *   $customer = $auth->require_login();               // or redirect to login?ref=<this page>
 *   $state    = $auth->nav_state();                   // 'guest' | 'user' (account_nav())
 */
class CustomerAuth
{
    /**
     * Session key: ['id' => customers_id, 'name' => ..., 'email' => ...].
     */
    public const SESSION_KEY = 'shop_customer';

    /**
     * The customer's home page (after signing in).
     */
    public const HOME = 'dashboard';

    /**
     * Visitor state of a visitor who is not signed in (nav_state()).
     */
    public const GUEST = 'guest';

    /**
     * Account links (header account menu, footer, Sitemap) for each visitor
     * state (nav_state()): key => label, uri, title, icon (Bootstrap Icons),
     * optional 'fragment' and 'post' (a form button: logout is POST only).
     */
    public const NAV_LINKS = [
        self::GUEST => [
            'register' => ['label' => 'Register', 'uri' => 'register', 'title' => 'Create an Account', 'icon' => 'person-vcard'],
            'login'    => ['label' => 'Login', 'uri' => 'login', 'title' => 'Log In to Your Account', 'icon' => 'box-arrow-in-right'],
        ],
        CustomerModel::TYPE_USER => [
            'account'  => ['label' => 'My Account', 'uri' => 'dashboard', 'title' => 'Go to My Account', 'icon' => 'grid'],
            'orders'   => ['label' => 'Orders History', 'uri' => 'acc-order-history', 'title' => 'View Your Order History', 'icon' => 'bag'],
            'wishlist' => ['label' => 'Wishlist', 'uri' => 'acc-wishlist', 'title' => 'View Your Wishlist', 'icon' => 'heart'],
            'edit'     => ['label' => 'Edit Profile', 'uri' => 'acc-edit', 'title' => 'Edit Your Account Details', 'icon' => 'person'],
            'logout'   => ['label' => 'Logout', 'uri' => 'logout', 'title' => 'Log Out of Your Account', 'icon' => 'box-arrow-right', 'post' => true],
        ],
    ];

    /**
     * Session key of the page to return to after signing in.
     */
    public const REF_KEY = 'shop_login_ref';

    protected CustomerModel $customers;

    /**
     * The signed-in customer of this request (checked once per request).
     */
    protected static ?array $current = null;

    protected static bool $checked = false;

    public function __construct(?CustomerModel $customers = null)
    {
        $this->customers = $customers ?? new CustomerModel();
    }

    /**
     * Check an email and password. On success the customer is signed in.
     *
     * @return array|string The customer row, or the message to show
     */
    public function login(string $email, string $password, string $ip)
    {
        $invalid  = 'The email address or password is incorrect.';
        $customer = $this->customers->get_by_email($email);

        if ($customer === false) {
            return 'Signing in is not available right now. Please try again shortly.';
        }
        if (! is_array($customer) || $customer['status'] === CustomerModel::STATUS_GUEST || $customer['status'] === '2'
            || $customer['password'] === '' || $customer['account_type'] !== CustomerModel::TYPE_USER) {
            // Same work as a real check, so the answer time doesn't tell which emails exist.
            password_verify($password, '$2y$10$usesomesillystringforsaltz7xPvEhyiGjWStVGr5rd6Te9eAzjie');

            return $invalid;
        }
        if ($customer['block_time'] !== null && strtotime($customer['block_time']) > time()) {
            return 'Too many failed sign-ins. Please try again after ' . date('H:i', strtotime($customer['block_time'])) . ' or reset your password.';
        }
        if (! password_verify($password, $customer['password'])) {
            $this->customers->record_failed_login($customer);

            return $invalid;
        }
        if ($customer['status'] !== CustomerModel::STATUS_ACTIVE) {
            return 'Your account is not active. Please contact us for help.';
        }

        if (password_needs_rehash($customer['password'], PASSWORD_DEFAULT)) {
            $this->customers->update_password((int) $customer['customers_id'], $password);
        }
        $this->customers->record_login((int) $customer['customers_id'], $ip);
        $this->start_session($customer);

        return $customer;
    }

    /**
     * Sign a customer in (after registering or login()).
     */
    public function start_session(array $customer): void
    {
        $session = session();
        $session->regenerate(true);
        $session->set(self::SESSION_KEY, [
            'id'    => (int) $customer['customers_id'],
            'name'  => trim($customer['first_name'] . ' ' . $customer['last_name']),
            'email' => $customer['user_name'],
        ]);
        self::$current = null;
        self::$checked = false;
    }

    /**
     * The signed-in, still active customer (full wl_customers row), or NULL.
     * Does not start a session for visitors who have none.
     */
    public function current(): ?array
    {
        return $this->signed_in();
    }

    /**
     * 'user' while a customer is signed in, else NULL.
     */
    public function current_type(): ?string
    {
        return $this->signed_in() === null ? null : CustomerModel::TYPE_USER;
    }

    /**
     * The visitor's state for the account links (NAV_LINKS): 'user' or GUEST.
     */
    public function nav_state(): string
    {
        return $this->current_type() ?? self::GUEST;
    }

    /**
     * The signed-in, still active customer (checked once per request).
     */
    protected function signed_in(): ?array
    {
        if (self::$checked) {
            return self::$current;
        }
        self::$checked = true;

        // No session cookie: nobody is signed in (and no session is started).
        if (! isset($_COOKIE[config('Session')->cookieName]) && session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $data = session(self::SESSION_KEY);
        if (! is_array($data) || empty($data['id'])) {
            return null;
        }

        $customer = $this->customers->get_customer_by_id((int) $data['id']);
        if ($customer === false) {
            // Database unavailable: keep the session, but nothing can be shown for it.
            return null;
        }
        // Inactive, deleted or not a customer account: signed out.
        if (! is_array($customer) || $customer['status'] !== CustomerModel::STATUS_ACTIVE || $customer['account_type'] !== CustomerModel::TYPE_USER) {
            $this->logout();

            return null;
        }

        return self::$current = $customer;
    }

    /**
     * The signed-in customer, or a redirect to the login page that comes back here.
     */
    public function require_login(?string $return_to = null): array
    {
        $account = $this->signed_in();
        if ($account === null) {
            $return_to ??= uri_string() . (($query = (string) service('request')->getUri()->getQuery()) !== '' ? '?' . $query : '');
            session()->set(self::REF_KEY, $return_to);
            session()->setFlashdata('shop_notice', 'Please log in to continue.');
            redirect_now(self::login_path());
        }

        return $account;
    }

    /**
     * Where to go after signing in: the saved page (a local path only), else the dashboard.
     */
    public function take_return_path(): string
    {
        $ref = (string) session(self::REF_KEY);
        session()->remove(self::REF_KEY);

        return self::safe_path($ref) ?? self::home_path();
    }

    /**
     * The customer's dashboard.
     */
    public static function home_path(): string
    {
        return self::HOME;
    }

    /**
     * The login page.
     */
    public static function login_path(): string
    {
        return 'login';
    }

    /**
     * A local, relative path ('checkout', 'products/x?y=1'), or NULL for anything that could lead off the site.
     */
    public static function safe_path(string $path): ?string
    {
        $path = ltrim(trim($path), '/');

        return $path !== '' && preg_match('#^[a-z0-9][a-z0-9/_\-.]*(\?[a-z0-9=&_\-.%+]*)?$#iD', $path) && ! str_contains($path, '..') ? $path : null;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE[config('Session')->cookieName])) {
            session()->remove([self::SESSION_KEY, self::REF_KEY]);
            session()->regenerate(true);
        }
        self::$current = null;
        self::$checked = true;
    }
}
