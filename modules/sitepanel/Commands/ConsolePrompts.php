<?php

namespace Modules\Sitepanel\Commands;

use CodeIgniter\CLI\CLI;
use Modules\Sitepanel\Libraries\AdminPasswordPolicy;

/**
 * Console input for the sitepanel commands (sitepanel:create-admin,
 * admin:reset-password): plain questions, and passwords read without being
 * shown on screen. Call detect_console() once at the start of run().
 */
trait ConsolePrompts
{
    /**
     * Whether STDIN is a console. Checked once, before anything is read:
     * stream_isatty() on an already-buffered pipe discards the buffer.
     */
    protected bool $interactive = false;

    protected function detect_console(): void
    {
        $this->interactive = function_exists('stream_isatty') && stream_isatty(STDIN);
    }

    protected function ask(string $prompt): string
    {
        fwrite(STDOUT, $prompt);
        $line = fgets(STDIN);

        return $line === false ? '' : trim($line);
    }

    /**
     * Read a line without echoing it when running in an interactive console
     * (PowerShell Read-Host on Windows, stty elsewhere). Piped input is read
     * as-is.
     */ 
    protected function ask_secret(string $prompt): string
    {
        if ($this->interactive && DIRECTORY_SEPARATOR === '\\') {
            fwrite(STDOUT, $prompt);
            $script = '$s = Read-Host -AsSecureString; '
                . '[Runtime.InteropServices.Marshal]::PtrToStringBSTR([Runtime.InteropServices.Marshal]::SecureStringToBSTR($s))';
            $line = shell_exec('powershell -NoProfile -Command "' . $script . '"');

            return $line === null || $line === false ? '' : rtrim($line, "\r\n");
        }

        $can_hide = $this->interactive && DIRECTORY_SEPARATOR === '/';

        if ($can_hide) {
            shell_exec('stty -echo');
        }

        fwrite(STDOUT, $prompt);
        $line = fgets(STDIN);

        if ($can_hide) {
            shell_exec('stty echo');
            fwrite(STDOUT, PHP_EOL);
        }

        return $line === false ? '' : rtrim($line, "\r\n");
    }

    /**
     * Ask for a new password (hidden) until it meets the password rules
     * (AdminPasswordPolicy, Config\Sitepanel) and is confirmed: the
     * rules are shown first, and each failed rule is explained before the
     * next try. NULL after $attempts failed tries (nothing is saved then).
     */
    protected function ask_new_password(AdminPasswordPolicy $policy, int $attempts = 3): ?string
    {
        $this->out('Password requirements:');
        foreach ($policy->requirements() as $rule) {
            $this->out('- ' . $rule);
        }
        $this->out('');

        for ($try = 1; $try <= $attempts; $try++) {
            $password = $this->ask_secret('New password: ');
            $errors   = $policy->errors($password);

            if (! $errors) {
                if ($this->ask_secret('Confirm password: ') === $password) {
                    return $password;
                }
                $errors = ['The passwords do not match.'];
            }

            foreach ($errors as $error) {
                CLI::error('  ' . $error);
            }
            if ($try < $attempts) {
                $this->out('Please try again (' . ($attempts - $try) . ' ' . ($attempts - $try === 1 ? 'try' : 'tries') . ' left).');
            }
        }

        return null;
    }

    protected function out(string $message): void
    {
        CLI::write($message);
    }

    protected function fail(string $message): int
    {
        CLI::error('Error: ' . $message);

        return EXIT_ERROR;
    }
}
