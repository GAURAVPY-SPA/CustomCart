<?php

namespace App\Libraries;

use CodeIgniter\Email\Email;
use Config\Mail;

/**
 * Outgoing email for the whole site, with one selectable transport.
 *
 * The method and its settings come from Config\Mail (set in .env as
 * mail.*), so no credential is in the source or the UI:
 *
 *   'mail'     PHP's native mail() (default; also when no method is set). On
 *              Windows mail() relays to the SMTP / smtp_port server set in php.ini;
 *              on Linux it hands the message to sendmail_path.
 *   'smtp'     CodeIgniter's Email class talking SMTP to smtpHost:smtpPort
 *              (smtpCrypto '' / 'tls' / 'ssl', optional smtpUser + smtpPass).
 *   'api'      An emailer API over HTTPS (CodeIgniter's HTTP client): apiProvider
 *              'sendgrid' = SendGrid v3 Mail Send with apiKey (apiUrl overrides
 *              the endpoint).
 *   'ci_email' CodeIgniter's Email service with its own configuration
 *              (Config\Email), i.e. how mail was sent before this library existed.
 *
 * Exactly one method sends: there is no fallback, so a failure of the chosen
 * method is reported as a failure and never sent again another way.
 *
 * send() returns TRUE when the transport accepted the message (the SMTP server
 * answered 250 to DATA / the API answered 2xx). PHP mail() is not checked: it
 * always counts as sent (a refusal is only logged as a warning).
 * That is acceptance for delivery, not proof of delivery to the inbox. On
 * failure error() holds a short reason safe to show to an administrator;
 * details go to the log (never the password or API key, the message body or
 * the full recipient address).
 */
class Mailer
{
    public const METHODS = [
        'mail'     => 'PHP mail()',
        'smtp'     => 'SMTP',
        'api'      => 'Emailer API',
        'ci_email' => 'CodeIgniter Email library',
    ];

    /**
     * Emailer API providers (method 'api') => default endpoint.
     */
    public const API_PROVIDERS = [
        'sendgrid' => 'https://api.sendgrid.com/v3/mail/send',
    ];

    public const DEFAULTS = [
        'method'       => 'mail',
        'smtp_host'    => '',
        'smtp_port'    => 587,
        'smtp_crypto'  => 'tls',
        'smtp_user'    => '',
        'smtp_pass'    => '',
        'smtp_timeout' => 10,
        'api_provider' => 'sendgrid',
        'api_key'      => '',
        'api_url'      => '',
        'api_timeout'  => 15,
        // Temporary (local / staging): pages report failed sends as sent; see show_success_on_failure().
        'show_success_on_failure' => false,
    ];

    protected array $settings;
    protected string $error = '';

    /**
     * @param array $settings Overrides of Config\Mail (tests / one-off use), keyed as DEFAULTS
     */
    public function __construct(array $settings = [])
    {
        $config = config(Mail::class);

        $this->settings = array_merge(self::DEFAULTS, [
            'method'                  => $config->method,
            'smtp_host'               => $config->smtpHost,
            'smtp_port'               => $config->smtpPort,
            'smtp_crypto'             => $config->smtpCrypto,
            'smtp_user'               => $config->smtpUser,
            'smtp_pass'               => $config->smtpPass,
            'smtp_timeout'            => $config->smtpTimeout,
            'api_provider'            => $config->apiProvider,
            'api_key'                 => $config->apiKey,
            'api_url'                 => $config->apiUrl,
            'api_timeout'             => $config->apiTimeout,
            'show_success_on_failure' => $config->showSuccessOnFailure,
        ], $settings);
        $this->settings['method'] = strtolower(trim((string) $this->settings['method']));
        if ($this->settings['method'] === '') {
            $this->settings['method'] = 'mail'; // no method chosen: PHP mail()
        }
    }

    /**
     * Active method key ('mail', 'smtp', 'api', 'ci_email'; anything else is refused by send()).
     */
    public function method(): string
    {
        return $this->settings['method'];
    }

    /**
     * Readable name of the active method.
     */
    public function method_label(): string
    {
        return self::METHODS[$this->method()] ?? 'unknown method';
    }

    /**
     * TEMPORARY switch for local / staging servers without a mail server: when TRUE,
     * pages show their success message although sending failed. send() still returns
     * FALSE and every failure is still logged. Off unless .env sets it.
     */
    public function show_success_on_failure(): bool
    {
        return $this->settings['show_success_on_failure'] === true;
    }

    /**
     * Reason of the last failure ('' after a success).
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * Send one HTML email.
     *
     * @param string $html Message body (HTML, already sanitized by the caller)
     *
     * @return bool Whether the transport accepted the message
     */
    public function send($to, $subject, $html, $from_email, $from_name = ''): bool
    {
        $this->error = '';
        $to          = trim((string) $to);
        $from_email  = trim((string) $from_email);
        // Header values: no line breaks (header injection).
        $subject   = trim(preg_replace('/[\r\n]+/', ' ', (string) $subject));
        $from_name = trim(preg_replace('/[\r\n"]+/', ' ', (string) $from_name));

        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return $this->fail('The recipient address is not valid.', 'invalid recipient');
        }
        if (filter_var($from_email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->fail('The sender address is not valid.', 'invalid sender');
        }

        switch ($this->method()) {
            case 'mail':
                return $this->send_native($to, $subject, (string) $html, $from_email, $from_name);

            case 'smtp':
                $problem = $this->smtp_settings_problem();
                if ($problem !== null) {
                    return $this->fail('The SMTP settings are incomplete (' . $problem . '). Check the mail settings in .env (mail.*).', 'bad SMTP settings: ' . $problem);
                }

                return $this->send_ci($to, $subject, (string) $html, $from_email, $from_name, [
                    'protocol'    => 'smtp',
                    'SMTPHost'    => trim((string) $this->settings['smtp_host']),
                    'SMTPPort'    => (int) $this->settings['smtp_port'],
                    'SMTPCrypto'  => strtolower(trim((string) $this->settings['smtp_crypto'])),
                    'SMTPUser'    => (string) $this->settings['smtp_user'],
                    'SMTPPass'    => (string) $this->settings['smtp_pass'],
                    'SMTPTimeout' => max(1, (int) $this->settings['smtp_timeout']),
                    'newline'     => "\r\n",
                    'CRLF'        => "\r\n",
                ], $to);

            case 'api':
                return $this->send_api($to, $subject, (string) $html, $from_email, $from_name);

            case 'ci_email':
                return $this->send_ci($to, $subject, (string) $html, $from_email, $from_name, [], $to);
        }

        return $this->fail('The mail method "' . $this->method() . '" is not supported. Use mail, smtp, api or ci_email in .env (mail.method).', 'unsupported method');
    }

    /**
     * Emailer API: SendGrid v3 Mail Send (POST JSON, success = HTTP 2xx, normally 202).
     */
    protected function send_api(string $to, string $subject, string $html, string $from_email, string $from_name): bool
    {
        $provider = strtolower(trim((string) $this->settings['api_provider']));
        if (! isset(self::API_PROVIDERS[$provider])) {
            return $this->fail('The emailer API provider "' . $provider . '" is not supported. Use sendgrid in .env (mail.apiProvider).', 'unsupported API provider');
        }
        $key = trim((string) $this->settings['api_key']);
        if ($key === '') {
            return $this->fail('The emailer API key is missing. Set mail.apiKey in .env.', 'no API key');
        }
        $url = trim((string) $this->settings['api_url']) !== '' ? trim((string) $this->settings['api_url']) : self::API_PROVIDERS[$provider];
        if (! preg_match('#^https?://#i', $url)) {
            return $this->fail('The emailer API address is not valid. Check mail.apiUrl in .env.', 'invalid API URL');
        }

        $from = ['email' => $from_email] + ($from_name !== '' ? ['name' => $from_name] : []);
        $body = [
            'personalizations' => [['to' => [['email' => $to]]]],
            'from'             => $from,
            'reply_to'         => ['email' => $from_email],
            'subject'          => $subject,
            'content'          => [['type' => 'text/html', 'value' => $html]],
        ];

        try {
            $response = \Config\Services::curlrequest([], null, null, false)->post($url, [ // a fresh client per send
                'headers'     => ['Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'        => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'timeout'     => max(1, (int) $this->settings['api_timeout']),
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            return $this->fail('The emailer API could not be reached. Check the network and mail.apiUrl.', 'request failed: ' . $this->plain($e->getMessage()), $to);
        }

        $status = $response->getStatusCode();
        if ($status >= 200 && $status < 300) {
            return true;
        }

        // SendGrid: {"errors":[{"message":"...","field":"..."}]}
        $json     = json_decode((string) $response->getBody(), true);
        $messages = is_array($json['errors'] ?? null) ? array_filter(array_map(static fn ($e) => is_array($e) ? (string) ($e['message'] ?? '') : '', $json['errors'])) : [];
        $detail   = 'HTTP ' . $status . ($messages ? ': ' . implode(' | ', $messages) : '');
        $reason   = in_array($status, [401, 403], true) ? 'the API key was refused' : ($status === 429 ? 'too many requests, try again later' : ($messages ? rtrim(mb_substr(reset($messages), 0, 200), '. ') : 'HTTP ' . $status));

        return $this->fail('The emailer API did not accept the message: ' . $reason . '.', $detail, $to);
    }

    /**
     * Native mail(): a UTF-8 HTML message, base64 body, encoded subject / sender name.
     */
    protected function send_native(string $to, string $subject, string $html, string $from_email, string $from_name): bool
    {
        $from = $from_email;
        if ($from_name !== '') {
            $from = (preg_match('/[^\x20-\x7E]/', $from_name)
                ? mb_encode_mimeheader($from_name, 'UTF-8', 'B', "\r\n")
                : '"' . addcslashes($from_name, '"\\') . '"') . ' <' . $from_email . '>';
        }
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'From: ' . $from,
            'Reply-To: ' . $from_email,
            'X-Mailer: CustomCart sitepanel',
        ]);
        $body            = chunk_split(base64_encode($html), 76, "\r\n");
        $encoded_subject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
        // Envelope sender (MAIL FROM / Return-Path): sendmail takes -f; Windows mail() uses sendmail_from.
        $windows  = (DIRECTORY_SEPARATOR === '\\');
        $old_from = $windows ? ini_set('sendmail_from', $from_email) : false;

        $warnings = [];
        set_error_handler(static function ($severity, $message) use (&$warnings) {
            $warnings[] = $message;

            return true;
        });

        try {
            $ok = $windows
                ? mail($to, $encoded_subject, $body, $headers)
                : mail($to, $encoded_subject, $body, $headers, '-f' . $from_email);
        } finally {
            restore_error_handler();
            $old_from !== false && ini_set('sendmail_from', $old_from);
        }

        // PHP mail() is not checked: the message counts as sent whatever mail() returned
        // (the default method; pages show their success message). A refusal is only logged.
        if ($ok !== true) {
            $reason = $warnings ? $this->plain(preg_replace('/^mail\(\):\s*/', '', end($warnings))) : 'mail() returned FALSE';
            $at     = strrpos($to, '@');
            log_message('warning', 'Mailer[mail]: mail() did not accept the message to ' . mb_substr($to, 0, 1) . '***' . ($at !== false ? substr($to, $at) : '') . ' (reported as sent): ' . $reason);
        }

        return true;
    }

    /**
     * PHP warning text as plain text (html_errors escapes it) on one line.
     */
    protected function plain($text): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES, 'UTF-8')));
    }

    /**
     * CodeIgniter's Email class; $config = transport settings (empty = its own configuration).
     */
    protected function send_ci(string $to, string $subject, string $html, string $from_email, string $from_name, array $config, string $log_to): bool
    {
        // ci_email: the shared service with CodeIgniter's own configuration (as before);
        // smtp: a separate instance, so its settings never stay on the shared one.
        $email = $config ? new Email(config('Email')) : service('email');
        $email->clear(true);
        $email->initialize(array_merge([
            'mailType' => 'html',
            'charset'  => 'utf-8',
            'wordWrap' => true,
        ], $config));
        $email->setFrom($from_email, $from_name);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($html);

        // PHP warnings of the transport (fsockopen, TLS, mail()) go into this one log line, not separate ones.
        $warnings = [];
        set_error_handler(static function ($severity, $message) use (&$warnings) {
            $warnings[] = $message;

            return true;
        }, E_WARNING | E_NOTICE);

        try {
            $sent = $email->send(false);
        } finally {
            restore_error_handler();
        }
        if ($sent !== true) {
            // Server replies and error lines only (no headers, subject or body).
            $debug  = $this->scrub(trim(html_entity_decode(strip_tags(str_replace(['<br />', '</pre>'], "\n", $email->printDebugger([]))), ENT_QUOTES, 'UTF-8')));
            $detail = $this->scrub(trim(implode(' | ', array_map([$this, 'plain'], $warnings)) . ' | ' . preg_replace('/\s+/', ' ', $debug), ' |'));
            $email->clear(true);

            return $this->fail($this->ci_reason($debug . ' ' . implode(' ', $warnings)), $detail, $log_to);
        }
        $email->clear(true);

        return true;
    }

    /**
     * What is missing or invalid in the SMTP settings.
     */
    protected function smtp_settings_problem(): ?string
    {
        $s = $this->settings;
        if (trim((string) $s['smtp_host']) === '') {
            return 'no SMTP host';
        }
        if (! ctype_digit((string) $s['smtp_port']) || (int) $s['smtp_port'] < 1 || (int) $s['smtp_port'] > 65535) {
            return 'the SMTP port must be a number from 1 to 65535';
        }
        if (! in_array(strtolower(trim((string) $s['smtp_crypto'])), ['', 'tls', 'ssl'], true)) {
            return 'the SMTP encryption must be tls, ssl or empty';
        }
        if (((string) $s['smtp_user'] === '') !== ((string) $s['smtp_pass'] === '')) {
            return 'set both the SMTP username and password, or neither';
        }

        return null;
    }

    /**
     * A short reason from CodeIgniter's debug text.
     */
    protected function ci_reason(string $debug): string
    {
        $label = $this->method_label();
        $where = ($this->method() === 'smtp') ? 'Check the SMTP host, port and encryption.' : 'Check SMTP / smtp_port in php.ini (or sendmail_path on Linux).';
        if (preg_match('/Unable to connect|Failed to connect|Connection refused|actively refused|No connection could be made|failed to respond|timed out|getaddrinfo|php_network|\b1006[01]\b/i', $debug)) {
            return $label . ' could not connect to the mail server. ' . $where;
        }
        // "starttls:" is CodeIgniter's log of the command (the EHLO reply lists "STARTTLS" without a colon).
        if (preg_match('/starttls:|stream_socket_enable_crypto|SSL operation failed|certificate/i', $debug)) {
            return $label . ': the secure connection (TLS/SSL) failed. Check the port and encryption setting.';
        }
        if (preg_match('/Failed to authenticate|authentication|\b53[045]\b/i', $debug)) {
            return $label . ': the mail server refused the login. Check the SMTP username and password.';
        }
        if (preg_match('/Unable to send email using PHP mail/i', $debug)) {
            return $label . ': PHP mail() could not hand the message to the mail server. ' . $where;
        }
        $first = trim((string) strtok($debug, "\n"));

        return $label . ' did not accept the message' . ($first !== '' ? ': ' . rtrim(mb_substr($first, 0, 200), '. ') . '.' : '.');
    }

    /**
     * Remove the SMTP password (plain and base64), the base64 username sent at login
     * and the API key from text that may be logged or shown.
     */
    protected function scrub(string $text): string
    {
        $user    = (string) $this->settings['smtp_user'];
        $pass    = (string) $this->settings['smtp_pass'];
        $key     = trim((string) $this->settings['api_key']);
        $secrets = array_filter([$pass, $pass !== '' ? base64_encode($pass) : '', $user !== '' ? base64_encode($user) : '', $key], 'strlen');

        return $secrets ? str_replace($secrets, '[hidden]', $text) : $text;
    }

    /**
     * Record a failure: $message for the administrator, $detail for the log.
     */
    protected function fail(string $message, $detail, string $to = ''): bool
    {
        $this->error = $message;
        $masked      = '';
        if ($to !== '' && ($at = strrpos($to, '@')) !== false) {
            $masked = ' to ' . mb_substr($to, 0, 1) . '***' . substr($to, $at);
        }
        log_message('error', 'Mailer[' . $this->method() . ']: not sent' . $masked . ': ' . $this->scrub((string) $detail));

        return false;
    }
}
