<?php

namespace Modules\Sitepanel\Libraries;

use Modules\Sitepanel\Config\Sitepanel;

/**
 * The one place that checks a new Sitepanel administrator password against
 * the admin password settings of Config\Sitepanel (length, letter, number,
 * special character) and
 * describes those rules — for the Change Password and Manage Subadmins forms
 * and the sitepanel:create-admin / admin:reset-password commands. Hashing
 * stays where it is (password_hash() before AdminModel saves it).
 *
 * Passwords are never trimmed, logged or included in a message.
 */
class AdminPasswordPolicy
{
    protected Sitepanel $config;

    public function __construct(?Sitepanel $config = null)
    {
        $this->config = $config ?? config(Sitepanel::class);
    }

    public function min_length(): int
    {
        return max(1, $this->config->passwordMinLength);
    }

    public function max_length(): int
    {
        return max($this->min_length(), $this->config->passwordMaxLength);
    }

    /**
     * What $password is missing, one message per failed rule (empty = it meets them all).
     *
     * @param string $label Field name used in the messages, e.g. 'New password'
     *
     * @return list<string>
     */
    public function errors(string $password, string $label = 'The password'): array
    {
        $length = mb_strlen($password);
        $errors = [];

        if ($length < $this->min_length()) {
            $errors[] = $label . ' must be at least ' . $this->min_length() . ' characters long.';
        } elseif ($length > $this->max_length()) {
            $errors[] = $label . ' must be at most ' . $this->max_length() . ' characters long.';
        }
        if (trim($password) === '') {
            $errors[] = $label . ' cannot be only spaces.';
        }
        if ($this->config->passwordRequireLetter && ! preg_match('/[A-Za-z]/', $password)) {
            $errors[] = $label . ' must contain at least one letter (A-Z or a-z).';
        }
        if ($this->config->passwordRequireNumber && ! preg_match('/[0-9]/', $password)) {
            $errors[] = $label . ' must contain at least one number (0-9).';
        }
        if ($this->config->passwordRequireSpecialCharacter && ! preg_match('/[^\p{L}\p{N}\s]/u', $password)) {
            $errors[] = $label . ' must contain at least one special character, e.g. @ # $ ! % &.';
        }

        return $errors;
    }

    /**
     * The rules as a list, e.g. for the command line.
     *
     * @return list<string>
     */
    public function requirements(): array
    {
        $rules = ['Minimum length: ' . $this->min_length(), 'Maximum length: ' . $this->max_length()];
        $this->config->passwordRequireLetter && $rules[]           = 'At least 1 alphabetic character (A-Z or a-z)';
        $this->config->passwordRequireNumber && $rules[]           = 'At least 1 number (0-9)';
        $this->config->passwordRequireSpecialCharacter && $rules[] = 'At least 1 special character (e.g. @ # $ ! % &)';

        return $rules;
    }

    /**
     * The rules in one sentence, for a form's help text:
     * "8 to 255 characters, with at least one letter, one number and one special character (e.g. @ # $ ! % &)."
     */
    public function summary(): string
    {
        $needs = [];
        $this->config->passwordRequireLetter && $needs[]           = 'one letter';
        $this->config->passwordRequireNumber && $needs[]           = 'one number';
        $this->config->passwordRequireSpecialCharacter && $needs[] = 'one special character (e.g. @ # $ ! % &)';

        $text = $this->min_length() . ' to ' . $this->max_length() . ' characters';
        if ($needs) {
            $last  = array_pop($needs);
            $text .= ', with at least ' . ($needs ? implode(', ', $needs) . ' and ' : '') . $last;
        }

        return $text . '.';
    }
}
