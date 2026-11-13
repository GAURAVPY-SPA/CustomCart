<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Outgoing email transport (App\Libraries\Mailer).
 *
 * Every value can be set in .env as mail.<property> (see .env.example), so
 * no credential has to live in the source.
 *
 *   method 'mail'     PHP's native mail() (default, also when empty). On Windows mail()
 *                     relays to the SMTP / smtp_port server set in php.ini; on Linux it
 *                     hands the message to sendmail_path.
 *          'smtp'     CodeIgniter's Email class talking SMTP to smtpHost:smtpPort
 *                     (smtpCrypto '' / 'tls' / 'ssl', optional smtpUser + smtpPass).
 *          'api'      An emailer API over HTTPS: apiProvider 'sendgrid' (SendGrid v3
 *                     Mail Send) with apiKey; apiUrl only to point at another endpoint.
 *          'ci_email' CodeIgniter's Email service with its own configuration
 *                     (Config\Email).
 *
 * One method is used for every email of the site; a failure is reported as
 * such and never retried through another method.
 */
class Mail extends BaseConfig
{
    public string $method = 'mail';
    public string $smtpHost = '';

    /**
     * @var int|string
     */
    public $smtpPort = 587;

    public string $smtpCrypto = 'tls';
    public string $smtpUser   = '';
    public string $smtpPass   = '';

    /**
     * @var int|string
     */
    public $smtpTimeout = 10;

    /**
     * Emailer API (method 'api'). Only 'sendgrid' is supported.
     */
    public string $apiProvider = 'sendgrid';

    /**
     * The provider's API key (set it in .env, never here).
     */
    public string $apiKey = '';

    /**
     * Endpoint override ('' = the provider's own, https://api.sendgrid.com/v3/mail/send).
     */
    public string $apiUrl = '';

    /**
     * @var int|string Seconds
     */
    public $apiTimeout = 15;

    /**
     * TEMPORARY (local / staging): pages report failed sends as sent; the
     * failure is still logged. Keep false on production.
     */
    public bool $showSuccessOnFailure = false;
}
