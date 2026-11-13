<?php
/*
 * Test helper for mail-transports.spec.js: sends ONE email through App\Libraries\Mailer
 * with the settings given as JSON (overrides of Config\Mail, keyed as Mailer::DEFAULTS)
 * and prints {"ok": bool, "error": string, "method": string} as JSON.
 *   php mail-send.php <project root> '<settings json>' <to> [<php.ini SMTP host> <smtp_port>]
 * The optional SMTP host / port point PHP mail() at the test's local SMTP sink.
 */
error_reporting(E_ALL & ~E_WARNING); // the test bootstrap redefines ENVIRONMENT
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
chdir($argv[1]);
require $argv[1] . '/vendor/codeigniter4/framework/system/Test/bootstrap.php';
error_reporting(E_ALL);

if (isset($argv[4], $argv[5])) {
    ini_set('SMTP', $argv[4]);
    ini_set('smtp_port', $argv[5]);
}

$settings = json_decode($argv[2], true) ?: [];
$mailer   = new App\Libraries\Mailer($settings);
$ok       = $mailer->send($argv[3], 'PW-TEST transport ' . $mailer->method(), '<p>PW-TEST message via ' . htmlspecialchars($mailer->method()) . '</p>', 'pw-test-sender@example.com', 'PW Test');

echo json_encode(['ok' => $ok, 'error' => $mailer->error(), 'method' => $mailer->method()]);
