<?php

namespace Modules\Sitepanel\Libraries;

/**
 * Sitepanel > Enquiries "Send Mail": one email per selected enquiry address,
 * through the same transport as the newsletter (NewsletterMailer::send_to(),
 * i.e. the shared Mailer library; method chosen in Config\Mail / .env).
 *
 * As in the reference module, each message is addressed to the enquirer
 * ("Dear <name>,") and signed with the site name. An address shared by several
 * selected enquiries is emailed once; every enquiry whose address received
 * the message is reported in 'sent_ids' (to be marked as replied).
 */
class EnquiryMailer extends NewsletterMailer
{
    /**
     * @param array  $rows      wl_enquiry rows (from the database, never from the request)
     * @param string $message   Sanitized HTML
     * @param string $from_name Site name (also used in the signature)
     *
     * @return array attempted, sent, failed, invalid, duplicates, sent_ids
     */
    public function send_enquiries(array $rows, string $subject, string $message, string $from_email, string $from_name): array
    {
        $results  = ['attempted' => 0, 'sent' => 0, 'failed' => 0, 'invalid' => 0, 'duplicates' => 0, 'sent_ids' => []];
        $by_email = [];

        foreach ($rows as $row) {
            $email = strtolower(trim((string) $row['email']));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $results['invalid']++;

                continue;
            }
            if (isset($by_email[$email])) {
                $results['duplicates']++;
                $by_email[$email]['ids'][] = (int) $row['id'];

                continue;
            }
            $by_email[$email] = ['row' => $row, 'ids' => [(int) $row['id']]];
        }

        foreach ($by_email as $email => $entry) {
            $results['attempted']++;
            $body = $this->personalise($entry['row'], $message, $from_name);
            if ($this->send_to($email, $subject, $body, $from_email, $from_name)) {
                $results['sent']++;
                $results['sent_ids'] = array_merge($results['sent_ids'], $entry['ids']);
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Greeting + message + signature, as the reference "Send Reply" mail.
     *
     * @param string $message Sanitized HTML
     */
    public function personalise(array $row, string $message, string $site_name): string
    {
        $name = trim($row['first_name'] . ' ' . $row['last_name']);

        return '<p>Dear ' . html_escape($name !== '' ? $name : 'Sir/Madam') . ',</p>'
            . $message
            . '<p>Thanks and Regards,<br>' . html_escape($site_name) . ' Team</p>';
    }
}
