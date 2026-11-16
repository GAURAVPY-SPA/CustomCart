<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Models\SettingsModel;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\AdminModel;
use Modules\Sitepanel\Models\AdminPasswordResetModel;
use Modules\Sitepanel\Models\SubadminModel;

/**
 * Sitepanel > Admin Settings: website identity, contact information (incl.
 * social links, analytics/web code, map code, WhatsApp), online store
 * amounts (shipping charge, free delivery threshold, VAT, low-stock threshold), timezone and
 * maintenance mode (tbl_site_settings).
 *
 * Also the signed-in administrator's own email address (Admin account panel).
 * It is not a site setting: it is saved in that administrator's
 * tbl_admin.admin_email, the one address "Forgot password?" sends reset links
 * to. Changing it needs the current password, and ends the reset links
 * already sent to the old address.
 *
 * Settings that are no longer on this page (logo, favicon, social image,
 * footer, SEO) keep their stored values: a save only writes the keys below.
 *
 * Opening needs View on the "settings" section; saving needs Edit
 * (super admins have both).
 */
class Settings extends AdminController
{
    /**
     * Saved trimmed.
     */
    public const TEXT_KEYS = [
        'site_name', 'site_title', 'site_tagline',
        'contact_email', 'contact_phone', 'contact_address', 'office_hours', 'company_license',
        'facebook_link', 'twitter_link', 'linkedin_link', 'youtube_link', 'instagram_link',
        'google_analytics_id', 'whatsapp_no',
        'timezone',
    ];

    /**
     * Saved exactly as entered (code snippets / message text).
     */
    public const RAW_KEYS = ['google_web_code', 'map_code', 'whatsapp_text'];

    /**
     * Online store amounts (key => [label, maximum]), saved with two decimals;
     * {currency} in a label is the store currency symbol (display_symbol()).
     * They drive every cart, checkout and order calculation (SiteSettings::shop_amounts()).
     */
    public const SHOP_KEYS = [
        // Delivery charge and free delivery amount: Sitepanel > Manage Shipping (default delivery method).
        // 'shipping_charges'    => ['Shipping Charges ({currency})', 9999999.99],
        // 'free_delivery_above' => ['Free Delivery Above ({currency})', 9999999.99],
        'gst_percent'         => ['VAT Percentage (%)', 100],
        // Hidden from the form; the saved value (else 5) is still used for low stock.
        // 'low_stock_threshold' => ['Low Stock Threshold', 100000],
    ];

    protected string $menu_highlight = 'settings';

    public function index(): string
    {
        $settings = $this->site_settings->all();
        $errors   = [];

        if ($this->request->getMethod() === 'POST') {
            if (! $this->can(AdminAccess::PRIV_EDIT)) {
                $this->session->setFlashdata('sitepanel_error', "You don't have permission to change settings.");
                redirect_now('sitepanel/settings');
            }

            $errors = $this->save();

            if (empty($errors)) {
                $this->session->setFlashdata('sitepanel_success', 'Settings saved.');
                redirect_now('sitepanel/settings'); // post/redirect/get: refresh can't resubmit
            }
        }

        return $this->render('dashboard/setting_edit_view', [
            'heading_title' => 'Admin Settings',
            'breadcrumbs'   => ['Admin Settings' => null],
            'settings'      => $settings,
            'admin_email'   => (string) $this->admin['admin_email'],
            'errors'        => $errors,
            'can_edit'      => $this->editPrvg,
            'timezones'     => timezone_identifiers_list(),
        ]);
    }

    /**
     * Validate and save. Returns error messages (empty = saved).
     *
     * @return list<string>
     */
    protected function save(): array
    {
        $v = new FormValidation($this->request, $this);

        // Admin account: the signed-in administrator's tbl_admin.admin_email (unique across administrators).
        $admin_id = (int) $this->admin['admin_id'];
        $v->set_rules(
            'admin_email',
            'Admin email',
            ['trim', 'required', 'max_length[191]', 'valid_email',
                ['unique_email', static fn ($value) => (new SubadminModel())->is_taken('admin_email', trim((string) $value), $admin_id) === false]],
            ['unique_email' => 'The %s is already used by another Sitepanel account.'],
        );

        // Website identity
        $v->set_rules('site_name', 'Site name', 'trim|required|max_length[150]');
        $v->set_rules('site_title', 'Website title', 'trim|required|max_length[150]');
        $v->set_rules('site_tagline', 'Tagline', 'trim|max_length[300]');

        // Contact information
        $v->set_rules('contact_email', 'Contact email', 'trim|max_length[191]|valid_email');
        $v->set_rules(
            'contact_phone',
            'Contact phone',
            'trim|max_length[30]|regex_match[/^[0-9+()\-. ]+$/]',
            ['regex_match' => 'The %s field may contain only digits, spaces and + ( ) - .'],
        );
        $v->set_rules('contact_address', 'Contact address', 'trim|max_length[500]');
        $v->set_rules('office_hours', 'Office hours', 'trim|max_length[150]');
        $v->set_rules('company_license', 'Registered entity', 'trim|max_length[150]');

        $social = [
            'facebook_link' => 'Facebook Link', 'twitter_link' => 'Twitter Link', 'linkedin_link' => 'LinkedIn Link',
            'youtube_link'  => 'YouTube Link', 'instagram_link' => 'Instagram Link',
        ];

        foreach ($social as $key => $label) {
            $v->set_rules(
                $key,
                $label,
                ['trim', 'max_length[250]', ['valid_web_url', fn ($value) => $this->is_web_url($value)]],
                ['valid_web_url' => 'The %s field must be a complete web address starting with http:// or https://.'],
            );
        }
        $v->set_rules(
            'google_analytics_id',
            'Google Analytics ID',
            'trim|max_length[50]|regex_match[/^[A-Za-z0-9-]+$/]',
            ['regex_match' => 'The %s field may contain only letters, digits and hyphens (e.g. G-XXXXXXXXXX).'],
        );
        $v->set_rules('google_web_code', 'Google Web Code', 'max_length[10000]');
        // Only a Google Maps embed (iframe snippet or URL); the page prints its own iframe with that URL.
        $v->set_rules(
            'map_code',
            'Contactus Page Map Code',
            ['max_length[10000]', ['valid_map_code', static fn ($value) => trim((string) $value) === '' || map_embed_src($value) !== '']],
            ['valid_map_code' => 'The %s must be a Google Maps embed: an <iframe> snippet or an https://www.google.com/maps… link (no scripts).'],
        );
        // Same length rule as the reference (10–15 characters); text keeps the + and leading zeros.
        $v->set_rules(
            'whatsapp_no',
            'Whatsapp Number',
            'trim|min_length[10]|max_length[15]|regex_match[/^\+?[0-9]+$/]',
            ['regex_match' => 'The %s field may contain only digits, with an optional leading + (e.g. +971501234567).'],
        );
        $v->set_rules('whatsapp_text', 'Whatsapp Text', 'max_length[500]');

        // Online store: 0 or more, up to two decimals (VAT up to 100).
        $symbol = html_entity_decode(display_symbol(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        foreach (self::SHOP_KEYS as $key => [$label, $max]) {
            $v->set_rules(
                $key,
                str_replace('{currency}', $symbol, $label),
                [
                    'trim', 'required',
                    ['valid_amount', static fn ($value) => preg_match('/^[0-9]{1,7}(\.[0-9]{1,2})?$/D', (string) $value) === 1],
                    ['max_amount', static fn ($value) => (float) $value <= $max],
                ],
                [
                    'valid_amount' => 'The %s field must be a number of 0 or more with up to two decimals, e.g. 6.00.',
                    'max_amount'  => 'The %s field may not be more than ' . $max . '.',
                ],
            );
        }

        // General
        $v->set_rules(
            'timezone',
            'Time zone',
            ['required', ['valid_timezone', static fn ($value) => in_array($value, timezone_identifiers_list(), true)]],
            ['valid_timezone' => 'Choose a valid %s.'],
        );

        if ($v->run() === false) {
            return array_values($v->error_array());
        }

        // A new admin email receives password reset links: confirm it with the current password.
        $admin_email   = trim((string) $this->request->getPost('admin_email'));
        $email_changed = $admin_email !== (string) $this->admin['admin_email'];

        if ($email_changed) {
            $password = (string) $this->request->getPost('current_password');
            if ($password === '') {
                return ['Enter your current password to change the admin email.'];
            }
            $valid = $this->admin_auth->check_password($admin_id, $password);
            if ($valid === null) {
                return ['Your password could not be checked. Please try again.'];
            }
            if (! $valid) {
                return ['The current password is incorrect. The admin email was not changed.'];
            }
        }

        // Raw input is stored; every output escapes it.
        $values = [];

        foreach (self::TEXT_KEYS as $key) {
            $values[$key] = trim((string) $this->request->getPost($key));
        }

        foreach (self::RAW_KEYS as $key) {
            $values[$key] = (string) $this->request->getPost($key);
        }
        foreach (array_keys(self::SHOP_KEYS) as $key) {
            $values[$key] = number_format((float) trim((string) $this->request->getPost($key)), 2, '.', '');
        }
        $values['maintenance_mode'] = $this->request->getPost('maintenance_mode') === '1' ? '1' : '0';

        if (! (new SettingsModel())->save($values, $this->admin['admin_id'])) {
            return ['Settings could not be saved. Please try again.'];
        }

        $this->site_settings->refresh();

        if ($email_changed) {
            if (! (new AdminModel())->update_email($admin_id, $admin_email)) {
                return ['The other settings were saved, but the admin email could not be changed. Please try again.'];
            }
            // Reset links already emailed to the old address stop working.
            (new AdminPasswordResetModel())->expire_unused($admin_id);
        }

        return [];
    }

    /**
     * Empty, or an absolute http(s) URL with a host. CodeIgniter's valid_url is
     * not used: it accepts "example.com" without a scheme and other schemes such
     * as ftp://, but these links are stored and used as complete web addresses.
     *
     * @param string|null $value
     */
    protected function is_web_url($value): bool
    {
        $value = (string) $value;
        if ($value === '') {
            return true;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host   = (string) parse_url($value, PHP_URL_HOST);

        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array($scheme, ['http', 'https'], true)
            && $host !== '';
    }
}
