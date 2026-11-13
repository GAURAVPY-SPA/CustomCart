<?php

namespace Modules\Sitepanel\Commands;

use CodeIgniter\CLI\BaseCommand;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Libraries\AdminPasswordPolicy;
use Modules\Sitepanel\Models\AdminModel;

/**
 * Reset a sitepanel administrator's forgotten password from the command line:
 *
 *   php spark admin:reset-password [<username or email>]
 *
 * The account is chosen by its username or email (asked for when not
 * given); its details are shown and the reset must be confirmed. The
 * password rules are shown (AdminPasswordPolicy, Config\Sitepanel); the
 * new password is typed twice and never shown on screen (it is never an
 * argument either, so it doesn't end up in shell history), and a password
 * that breaks a rule is explained and can be typed again. It is saved as
 * its password_hash() value (AdminModel::update_password_hash()): any legacy
 * plain-text password is cleared and a sign-in lockout is lifted. Nothing
 * else of the account changes, and neither the old nor the new password or
 * hash is printed or logged. Command line only: it has no web route, so only
 * someone with shell access to the server can use it.
 *
 * Manual alternative (database): docs/SITEPANEL_ADMIN_PASSWORDS.md.
 */
class ResetPassword extends BaseCommand
{
    use ConsolePrompts;

    protected $group       = 'Sitepanel';
    protected $name        = 'admin:reset-password';
    protected $description = 'Resets a sitepanel administrator\'s password (asked for twice, never shown).';
    protected $usage       = 'admin:reset-password [<username or email>]';
    protected $arguments   = [
        'username or email' => 'The administrator\'s username or email address (asked for when left out).',
    ];

    public function run(array $params)
    {
        $this->detect_console();

        $login = trim((string) ($params[0] ?? ''));
        if ($login === '') {
            $login = $this->ask('Username or email of the administrator: ');
        }
        if ($login === '' || mb_strlen($login) > 191) {
            return $this->fail('A username or email address is required.');
        }

        $model = new AdminModel();
        $admin = $model->find_by_username_or_email($login);
        if ($admin === false) {
            return $this->fail('Cannot read tbl_admin. Check the database settings in .env and that the table exists (see writable/logs for details).');
        }
        if ($admin === null) {
            return $this->fail('No single administrator has the username or email "' . $login . '".');
        }

        $this->out('Administrator: ' . $admin['admin_name'] . ' (username "' . $admin['admin_username'] . '", ' . $admin['admin_email'] . ', id ' . (int) $admin['admin_id'] . ', '
            . ((int) $admin['admin_type'] === AdminAccess::SUPER_ADMIN ? 'super admin' : 'sub-admin') . ')');
        if ((int) $admin['status'] !== 1) {
            $this->out('Note: this account is inactive. It can sign in only after it is activated again (Sitepanel > Manage Subadmins).');
        }
        if ($admin['locked_until'] !== null && strtotime((string) $admin['locked_until']) > time()) {
            $this->out('Note: sign-in is locked after too many wrong passwords; the reset lifts the lock.');
        }

        if (! in_array(strtolower($this->ask('Reset the password of this administrator? [y/N]: ')), ['y', 'yes'], true)) {
            $this->out('Nothing was changed.');

            return EXIT_SUCCESS;
        }

        $this->out('');
        $password = $this->ask_new_password(new AdminPasswordPolicy());
        if ($password === null) {
            return $this->fail('No valid password was entered. Nothing was changed.');
        }

        if (! $model->update_password_hash((int) $admin['admin_id'], password_hash($password, PASSWORD_DEFAULT), true)) {
            return $this->fail('The password could not be saved (see writable/logs for details). Nothing was changed.');
        }

        $this->out('');
        $this->out('Password successfully updated for "' . $admin['admin_username'] . '". Sign in at ' . site_url('sitepanel/login'));

        return EXIT_SUCCESS;
    }
}
