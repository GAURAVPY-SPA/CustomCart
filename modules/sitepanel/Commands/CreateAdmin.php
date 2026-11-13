<?php

namespace Modules\Sitepanel\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Libraries\AdminPasswordPolicy;
use Modules\Sitepanel\Models\AdminModel;

/**
 * One-time creation of the first sitepanel administrator (CodeIgniter 3:
 * php index.php sitepanel setup create_admin).
 *
 *   php spark sitepanel:create-admin
 *
 * Prompts for name, username, email and password (the password is never
 * passed as an argument, so it doesn't end up in shell history). Refuses to
 * run once any administrator exists. Command line only: it has no web route.
 */
class CreateAdmin extends BaseCommand
{
    use ConsolePrompts;

    protected $group       = 'Sitepanel';
    protected $name        = 'sitepanel:create-admin';
    protected $description = 'Creates the first sitepanel administrator (refuses once one exists).';
    protected $usage       = 'sitepanel:create-admin';

    public function run(array $params)
    {
        $this->detect_console();

        $model    = new AdminModel();
        $existing = $model->count_all();

        if ($existing === false) {
            return $this->fail('Cannot read tbl_admin. Check the database settings in .env and that the table exists (see writable/logs for details).');
        }

        if ($existing > 0) {
            return $this->fail('An administrator already exists. This setup command only creates the first one.');
        }

        $this->out('Create the first sitepanel administrator.');

        $name = $this->ask('Full name: ');
        if ($name === '' || mb_strlen($name) > 100) {
            return $this->fail('Name is required (max 100 characters).');
        }

        $username = $this->ask('Username (3-50 chars: letters, numbers, . _ -): ');
        if (! preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            return $this->fail('Invalid username.');
        }

        $email = $this->ask('Email: ');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191) {
            return $this->fail('Invalid email address.');
        }

        $password = $this->ask_new_password(new AdminPasswordPolicy());
        if ($password === null) {
            return $this->fail('No valid password was entered. Nothing was created.');
        }

        $admin_id = $model->create([
            'admin_type'     => AdminAccess::SUPER_ADMIN, // first admin manages everything
            'admin_name'     => $name,
            'admin_username' => $username,
            'admin_email'    => $email,
            'password_hash'  => password_hash($password, PASSWORD_DEFAULT),
        ]);

        if (! $admin_id) {
            return $this->fail('Could not create the administrator (see writable/logs for details).');
        }

        $this->out('Administrator "' . $username . '" created (id ' . $admin_id . '). Sign in at ' . site_url('sitepanel/login'));

        return EXIT_SUCCESS;
    }
}
