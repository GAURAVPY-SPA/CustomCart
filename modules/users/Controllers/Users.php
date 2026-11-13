<?php

namespace Modules\Users\Controllers;

use App\Controllers\PublicController;
use App\Libraries\Captcha;
use App\Libraries\FormValidation;
use Modules\Cart\Libraries\DeliveryAddress;
use Modules\Users\Libraries\CustomerAuth;
use Modules\Users\Libraries\CustomerMailer;
use Modules\Users\Models\CustomerModel;

/**
 * Store customer sign-in pages (the reference's users module), built from
 * html/login.htm. Each address is a wl_meta_tags row (StoreSeeder):
 *
 *   /login             login            email + password (CustomerAuth); ?ref=<page> to come back to
 *   /register          register         new member account (a guest checkout email becomes the member)
 *   /logout            logout           POST only
 *   /forgot-password   forgot_password  emails a one-time reset link (always the same answer)
 *   /reset-password    reset_password   ?token=<emailed token>: choose a new password
 *
 * Changes from the reference: passwords are hashed (it stored them
 * reversibly encrypted and emailed them back), a forgotten password is reset
 * through an expiring link, repeated failed sign-ins lock the account for a
 * while, and "Remember me" no longer stores the email and password in
 * cookies.
 *
 * Designs: html/login.htm (with the Forgot Password popup), register.htm.
 */
class Users extends PublicController
{
    /**
     * Password reset requests accepted per session per hour.
     */
    public const RESETS_PER_HOUR = 5;

    /**
     * Password rules: min / max length; at least one letter and one number.
     */
    public const PASSWORD_MIN = 8;
    public const PASSWORD_MAX = 72;

    /**
     * Verification code of the registration form (Config\Captcha::$forms).
     */
    public const REGISTER_CAPTCHA = 'register';

    protected $helpers = ['shop'];

    protected CustomerAuth $auth;
    protected CustomerModel $customers;

    protected function init(): void
    {
        $this->auth      = new CustomerAuth();
        $this->customers = new CustomerModel();

        // Sign-in pages carry a CSRF token and personal data: never cached.
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function login(): string
    {
        if (($signed_in = $this->auth->current_type()) !== null) {
            redirect_now(CustomerAuth::home_path());
        }

        $ref = CustomerAuth::safe_path((string) ($this->request->getGet('ref') ?? $this->request->getPost('ref')));
        $ref !== null && session()->set(CustomerAuth::REF_KEY, $ref);

        $errors = [];
        $email  = '';

        if ($this->request->getMethod() === 'POST') {
            $email = trim((string) $this->request->getPost('email'));
            $v     = new FormValidation($this->request, $this);
            $v->set_rules('email', 'Email Address', 'trim|required|valid_email|max_length[120]');
            $v->set_rules('password', 'Password', 'required|max_length[' . self::PASSWORD_MAX . ']');

            if (! $v->run()) {
                $errors = array_values($v->error_array());
            } else {
                $result = $this->auth->login($email, (string) $this->request->getPost('password'), $this->request->getIPAddress());
                if (is_array($result)) {
                    session()->setFlashdata('shop_success', 'Welcome back, ' . $result['first_name'] . '!');
                    redirect_now($this->auth->take_return_path());
                }
                $errors = [$result];
            }
        }

        return $this->render('Modules\Users\Views\login', [
            'page_meta'  => ['title' => 'Login | CustomCart', 'canonical' => site_url('login')],
            'body_class' => 'page-inner page-cms',
            'errors'     => $errors,
            'email'      => $email,
            'ref'        => (string) session(CustomerAuth::REF_KEY),
        ]);
    }

    public function register(): string
    {
        if (($signed_in = $this->auth->current_type()) !== null) {
            redirect_now(CustomerAuth::home_path());
        }

        $fields = ['full_name', 'email', 'mobile_number'];
        $values = array_fill_keys($fields, '') + ['newsletter' => '', 'terms' => ''];
        $errors = [];

        if ($this->request->getMethod() === 'POST') {
            foreach (array_keys($values) as $field) {
                $values[$field] = trim((string) $this->request->getPost($field));
            }

            // Honeypot: a hidden field people never fill in.
            if (trim((string) $this->request->getPost('website')) !== '') {
                redirect_now('register');
            }
            [$values['first_name'], $values['last_name']] = array_pad(preg_split('/\s+/u', $values['full_name'], 2), 2, '');
            $values['mobile_number'] = DeliveryAddress::digits($values['mobile_number']);

            $errors = $this->validate_registration($values);
            if (! $errors) {
                $id = $this->customers->register([
                    'account_type'  => CustomerModel::TYPE_USER,
                    'user_name'     => $values['email'],
                    'password'      => (string) $this->request->getPost('password'),
                    'first_name'    => $values['first_name'],
                    'last_name'     => $values['last_name'],
                    'mobile_number' => $values['mobile_number'],
                    'newsletter'    => $values['newsletter'] === '1',
                ], $this->request->getIPAddress());

                $customer = $id !== false ? $this->customers->get_customer_by_id($id) : null;
                if (! is_array($customer)) {
                    $errors = ['Your account could not be created right now. Please try again shortly.'];
                } else {
                    $this->auth->start_session($customer);
                    (new CustomerMailer())->welcome($customer);
                    if ($values['newsletter'] === '1') {
                        $this->customers->subscribe_newsletter($customer['user_name'], $values['first_name']);
                    }
                    session()->setFlashdata('shop_success', 'Welcome, ' . $customer['first_name'] . '! Your account has been created.');
                    redirect_now($this->auth->take_return_path());
                }
            }
        }

        $captcha = new Captcha();

        return $this->render('Modules\Users\Views\register', [
            'page_meta'    => ['title' => 'Create an Account | CustomCart', 'canonical' => site_url('register')],
            'body_class'   => 'page-inner page-cms',
            'errors'       => $errors,
            'values'       => $values,
            'page_scripts' => [Captcha::page_script()],
        ] + $captcha->view_data(self::REGISTER_CAPTCHA, 'regCaptcha', ['label' => 'Captcha', 'error' => (string) ($errors[$captcha->field()] ?? '')]));
    }

    /**
     * @return array<int|string, string> field => message (shown under the
     *                                   field); a message not about one field under an integer key
     */
    protected function validate_registration(array $values): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules('full_name', 'Full Name', "trim|required|min_length[3]|max_length[100]|regex_match[/^[\\p{L}][\\p{L} .'\\-]*$/u]", ['regex_match' => 'The {field} may contain only letters, spaces and . \' -']);
        $v->set_rules('email', 'Email Address', 'trim|required|valid_email|max_length[120]');
        // Saudi-only check removed:
        // $v->set_rules('mobile_number', 'Mobile Number', ['required', ['saudi_mobile', static fn ($value) => preg_match(config(\Config\Shop::class)->mobilePattern, DeliveryAddress::digits((string) $value)) === 1]], ['saudi_mobile' => 'Please enter a Saudi mobile number, e.g. +966 5X XXX XXXX.']);
        $v->set_rules('mobile_number', 'Mobile Number', ['required', ['valid_mobile', static fn ($value) => DeliveryAddress::valid_mobile((string) $value)]], ['valid_mobile' => DeliveryAddress::mobile_message('')]);
        $this->password_rules($v, 'password', 'Password');
        $v->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]', ['matches' => 'The passwords do not match.']);
        $v->set_rules('terms', 'Terms & Conditions', 'required', ['required' => 'Please accept the Terms & Conditions and Privacy Policy.']);
        // The verification code (used once: a wrong or expired code needs the new code shown).
        (new Captcha())->add_rule($v, self::REGISTER_CAPTCHA, 'Captcha');

        if (! $v->run()) {
            return $v->error_array();
        }

        $taken = self::account_taken_errors($this->customers, $values['email'], $values['mobile_number']);
        isset($taken['mobile']) && $taken = ['mobile_number' => $taken['mobile']];

        return $taken;
    }

    /**
     * Uniqueness of a new account: its email and
     * mobile number may not belong to another account of either type.
     * Keyed by the field the message is about ('email' / 'mobile'); a
     * failure to check is under key 0.
     *
     * @return array<int|string, string>
     */
    public static function account_taken_errors(CustomerModel $customers, string $email, string $mobile): array
    {
        $email_taken  = $customers->member_email_taken($email);
        $mobile_taken = $customers->member_mobile_taken($mobile);
        if ($email_taken === null || $mobile_taken === null) {
            return ['Your account could not be created right now. Please try again shortly.'];
        }
        if ($email_taken) {
            return ['email' => 'An account with this email address already exists. Please log in or reset your password.'];
        }

        return $mobile_taken ? ['mobile' => 'An account with this mobile number already exists. Please log in, or use another mobile number.'] : [];
    }

    /**
     * Password rules shared by registration, reset and Change Password.
     */
    public static function password_rules(FormValidation $v, string $field, string $label): void
    {
        $v->set_rules($field, $label, [
            'required', 'min_length[' . self::PASSWORD_MIN . ']', 'max_length[' . self::PASSWORD_MAX . ']',
            // Callables run before 'required' (FormValidation): an empty password gets the required message.
            ['letter_and_number', static fn ($value) => (string) $value === '' || (preg_match('/\pL/u', (string) $value) === 1 && preg_match('/[0-9]/', (string) $value) === 1)],
        ], ['letter_and_number' => 'The {field} must contain at least one letter and one number.']);
    }

    public function logout(): void
    {
        if ($this->request->getMethod() !== 'POST') {
            redirect_now('');
        }

        $this->auth->logout();
        session()->setFlashdata('shop_success', 'You have been logged out.');
        redirect_now(CustomerAuth::login_path());
    }

    public function forgot_password(): string
    {
        $errors = [];
        $sent   = false;
        $email  = '';

        if ($this->request->getMethod() === 'POST') {
            $email = trim((string) $this->request->getPost('email'));
            $v     = new FormValidation($this->request, $this);
            $v->set_rules('email', 'Email Address', 'trim|required|valid_email|max_length[120]');

            if (! $v->run()) {
                $errors = array_values($v->error_array());
            } elseif (! $this->reset_allowed()) {
                $errors = ['We have received several reset requests from you. Please try again later.'];
            } else {
                $customer = $this->customers->get_by_email($email);
                if (is_array($customer) && $customer['status'] === CustomerModel::STATUS_ACTIVE && $customer['password'] !== '') {
                    $token = $this->customers->create_reset_token((int) $customer['customers_id']);
                    $token !== false && (new CustomerMailer())->password_reset($customer, site_url('reset-password') . '?token=' . $token);
                }
                // The same answer whether or not the email has an account.
                $sent = true;
            }
        }

        return $this->render('Modules\Users\Views\forgot_password', [
            'page_meta'  => ['title' => 'Forgot Password | CustomCart', 'canonical' => site_url('forgot-password')],
            'body_class' => 'page-inner page-cms',
            'errors'     => $errors,
            'sent'       => $sent,
            'email'      => $sent ? '' : $email,
        ]);
    }

    /**
     * Count a reset request; FALSE once RESETS_PER_HOUR were made this hour.
     */
    protected function reset_allowed(): bool
    {
        $since    = time() - 3600;
        $requests = array_values(array_filter((array) session('shop_reset_requests'), static fn ($time) => (int) $time > $since));
        if (count($requests) >= self::RESETS_PER_HOUR) {
            return false;
        }
        $requests[] = time();
        session()->set('shop_reset_requests', $requests);

        return true;
    }

    public function reset_password(): string
    {
        $token    = (string) ($this->request->getGet('token') ?? $this->request->getPost('token'));
        $customer = $this->customers->get_by_reset_token($token);
        $errors   = [];

        if (is_array($customer) && $this->request->getMethod() === 'POST') {
            $v = new FormValidation($this->request, $this);
            self::password_rules($v, 'password', 'New Password');
            $v->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]', ['matches' => 'The passwords do not match.']);

            if (! $v->run()) {
                $errors = array_values($v->error_array());
            } elseif ($this->customers->update_password((int) $customer['customers_id'], (string) $this->request->getPost('password'))) {
                session()->setFlashdata('shop_success', 'Your password has been changed. Please log in with your new password.');
                redirect_now(CustomerAuth::login_path());
            } else {
                $errors = ['Your password could not be changed right now. Please try again shortly.'];
            }
        }

        return $this->render('Modules\Users\Views\reset_password', [
            'page_meta'  => ['title' => 'Reset Password | CustomCart', 'lookup' => false, 'canonical' => site_url('reset-password')],
            'body_class' => 'page-inner page-cms',
            'errors'     => $errors,
            'valid'      => is_array($customer),
            'unavailable' => $customer === false,
            'token'      => $token,
        ]);
    }
}
