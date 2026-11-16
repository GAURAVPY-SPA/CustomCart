<?php

namespace Modules\Sitepanel\Libraries;

use App\Libraries\Mailer;
use Throwable;

/**
 * Email with a Sitepanel password reset link (login page > "Forgot password?").
 *
 * Sent through the site's one transport (App\Libraries\Mailer, Config\Mail /
 * .env mail.*) from Admin Settings > contact email as the website's name, like
 * the store's customer emails (Modules\Users\Libraries\CustomerMailer). When no
 * sender address is set or the transport fails, nothing is thrown: send()
 * returns FALSE and the reason is logged — never the link or its token.
 */
class AdminResetMailer
{
    protected ?Mailer $mailer = null;

    /**
     * @param array  $admin   admin_name, admin_username, admin_email
     * @param string $link    Reset link (contains the token)
     * @param int    $minutes Link lifetime
     */
    public function password_reset(array $admin, string $link, int $minutes): bool
    {
        $site = (string) service('siteSettings')->get('site_name');
        $name = trim((string) $admin['admin_name']) !== '' ? (string) $admin['admin_name'] : (string) $admin['admin_username'];

        $body = '<p>Dear ' . html_escape($name) . ',</p>'
            . '<p>We received a request to reset the password of your ' . html_escape($site) . ' Sitepanel account '
            . '(username <strong>' . html_escape((string) $admin['admin_username']) . '</strong>).</p>'
            . '<p>Use the link below to choose a new password. It can be used once and expires in ' . $minutes . ' minutes.</p>'
            . '<p><a href="' . html_escape($link) . '">Reset my Sitepanel password</a></p>'
            . '<p>If the link does not open, copy this address into your browser:<br>' . html_escape($link) . '</p>'
            . '<p>If you did not ask for this, you can ignore this email: your password stays the same.</p>'
            . '<p>Thanks and Regards,<br>' . html_escape($site) . ' Team</p>';

        return $this->send((string) $admin['admin_email'], 'Reset your Sitepanel password - ' . $site, $body, $site);
    }

    protected function send(string $to, string $subject, string $body, string $from_name): bool
    {
        $from = trim((string) service('siteSettings')->get('contact_email'));
        if ($from === '') {
            log_message('warning', 'AdminResetMailer: no contact email in Admin Settings, the password reset email was not sent.');

            return false;
        }

        try {
            $this->mailer ??= new Mailer();
            if ($this->mailer->send($to, $subject, $body, $from, $from_name)) {
                return true;
            }
            log_message('error', 'AdminResetMailer: the password reset email was not sent: ' . $this->mailer->error());
        } catch (Throwable $e) {
            log_message('error', 'AdminResetMailer: the password reset email failed: ' . $e->getMessage());
        }

        return false;
    }
}
