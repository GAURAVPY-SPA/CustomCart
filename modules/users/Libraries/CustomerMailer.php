<?php

namespace Modules\Users\Libraries;

use App\Libraries\Mailer;
use Throwable;

/**
 * Store customer emails: the welcome email after registering (the
 * reference's template 1, without the password it used to include) and the
 * password reset link (the reference emailed the decrypted password instead).
 *
 * Sent from Admin Settings > contact email as the website's name. When no
 * sender address is set, or the transport fails, nothing is thrown: the
 * method returns FALSE and the reason is logged, so a page never fails
 * because of an email.
 */
class CustomerMailer
{
    protected ?Mailer $mailer = null;

    public function welcome(array $customer): bool
    {
        $site = $this->site_name();
        $body = '<p>Dear ' . html_escape($this->name($customer)) . ',</p>'
            . '<p>Thank you for creating an account with ' . html_escape($site) . '. You can now log in with your email address '
            // Wishlist hidden: there is no wishlist in this project. Was: '... to check out faster, track your orders and keep a wishlist.'
            . '<strong>' . html_escape($customer['user_name']) . '</strong> to check out faster and track your orders.</p>'
            . '<p><a href="' . html_escape(site_url('login')) . '">Log in to your account</a></p>'
            . $this->sign_off($site);

        return $this->send((string) $customer['user_name'], 'Welcome to ' . $site, $body);
    }

    public function password_reset(array $customer, string $link): bool
    {
        $site    = $this->site_name();
        $minutes = config('Shop')->resetMinutes;
        $body    = '<p>Dear ' . html_escape($this->name($customer)) . ',</p>'
            . '<p>We received a request to reset the password of your ' . html_escape($site) . ' account. '
            . 'Use the link below to choose a new password. It can be used once and expires in ' . (int) $minutes . ' minutes.</p>'
            . '<p><a href="' . html_escape($link) . '">Reset my password</a></p>'
            . '<p>If you did not ask for this, you can ignore this email: your password stays the same.</p>'
            . $this->sign_off($site);

        return $this->send((string) $customer['user_name'], 'Reset your password - ' . $site, $body);
    }

    protected function send(string $to, string $subject, string $body): bool
    {
        $from = trim((string) service('siteSettings')->get('contact_email'));
        if ($from === '') {
            log_message('warning', 'CustomerMailer: no contact email in Admin Settings, "' . $subject . '" was not sent.');

            return false;
        }

        try {
            $this->mailer ??= new Mailer();
            if ($this->mailer->send($to, $subject, $body, $from, $this->site_name())) {
                return true;
            }
            log_message('error', 'CustomerMailer: "' . $subject . '" was not sent: ' . $this->mailer->error());
        } catch (Throwable $e) {
            log_message('error', 'CustomerMailer: "' . $subject . '" failed: ' . $e->getMessage());
        }

        return false;
    }

    protected function name(array $customer): string
    {
        $name = trim($customer['first_name'] . ' ' . $customer['last_name']);

        return $name !== '' ? $name : 'Customer';
    }

    protected function site_name(): string
    {
        return (string) service('siteSettings')->get('site_name');
    }

    protected function sign_off(string $site): string
    {
        return '<p>Thanks and Regards,<br>' . html_escape($site) . ' Team</p>';
    }
}
