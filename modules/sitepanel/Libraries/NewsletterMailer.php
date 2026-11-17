<?php

namespace Modules\Sitepanel\Libraries;

use App\Libraries\Mailer;

/**
 * Sends one newsletter message per selected subscriber.
 *
 * send_to() is kept protected so delivery can be simulated in tests without
 * invoking the configured transport.
 */
class NewsletterMailer
{
    /**
     * Distinct failure reasons: reason => TRUE.
     */
    protected array $errors = [];

    protected ?Mailer $mailer = null;

    /**
     * @param string $message Sanitized HTML
     */
    public function send_selected(array $rows, string $subject, string $message, string $from_email, string $from_name): array
    {
        $results = ['attempted' => 0, 'sent' => 0, 'failed' => 0, 'invalid' => 0, 'duplicates' => 0];
        $seen    = [];

        foreach ($rows as $row) {
            $email = strtolower(trim((string) $row['subscriber_email']));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $results['invalid']++;

                continue;
            }
            if (isset($seen[$email])) {
                $results['duplicates']++;

                continue;
            }
            $seen[$email] = true;

            $results['attempted']++;
            if ($this->send_to($email, $subject, $message, $from_email, $from_name)) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Distinct failure reasons of the sends so far (safe to show to an administrator).
     *
     * @return list<string>
     */
    public function errors(): array
    {
        return array_keys($this->errors);
    }

    /**
     * Readable name of the active mail method (Mailer, Config\Mail).
     */
    public function method_label(): string
    {
        return $this->mailer()->method_label();
    }

    protected function mailer(): Mailer
    {
        return $this->mailer ??= new Mailer();
    }

    /**
     * @return bool Whether the active transport (Mailer) accepted the message
     */
    protected function send_to(string $email, string $subject, string $message, string $from_email, string $from_name): bool
    {
        if ($this->mailer()->send($email, $subject, $message, $from_email, $from_name)) {
            return true;
        }
        $this->errors[$this->mailer()->error()] = true;

        return false;
    }
}
