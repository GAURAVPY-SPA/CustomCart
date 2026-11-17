<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Libraries\AdminPasswordPolicy;
use Modules\Sitepanel\Models\AdminModel;
use Modules\Sitepanel\Models\AdminPasswordResetModel;

/**
 * Sitepanel > Change Password: the signed-in administrator changes their own
 * password. The current password is verified first (AdminAuth::check_password());
 * the new one is stored as its password_hash() value in tbl_admin.password_hash
 * (any legacy plain-text admin_password is cleared), and unused password reset
 * links of the administrator expire. A forgotten password is
 * reset from the login page's "Forgot password?" link (ForgotPassword) or with
 * php spark admin:reset-password (docs/SITEPANEL_ADMIN_PASSWORDS.md).
 *
 * Open to every administrator (AdminAccess::ALWAYS_ALLOWED).
 *
 *   GET  sitepanel/change_password -> form
 *   POST sitepanel/change_password -> verify current password, save the new one
 */
class ChangePassword extends AdminController
{
    protected string $menu_highlight = 'change_password';

    public function index(): string
    {
        $errors = [];

        if ($this->request->getMethod() === 'POST') {
            $errors = $this->save();

            if (empty($errors)) {
                $this->session->setFlashdata('sitepanel_success', 'Password changed successfully.');
                redirect_now('sitepanel/change_password'); // post/redirect/get: refresh can't resubmit
            }
        }

        return $this->render('dashboard/change_password_view', [
            'heading_title' => 'Change Password',
            'breadcrumbs'   => ['Change Password' => null],
            'errors'        => $errors,
            'policy'        => new AdminPasswordPolicy(),
        ]);
    }

    /**
     * Validate and save. Returns error messages (empty = saved).
     * Passwords are not trimmed: they are saved exactly as typed.
     *
     * @return list<string>
     */
    protected function save(): array
    {
        $v = new FormValidation($this->request, $this);

        $v->set_rules('current_password', 'Current password', 'required|max_length[255]');
        // Length and characters: AdminPasswordPolicy (Config\Sitepanel), checked below.
        $v->set_rules('new_password', 'New password', 'required');
        $v->set_rules(
            'confirm_password',
            'Confirm new password',
            'required|matches[new_password]',
            ['matches' => 'The new passwords do not match.'],
        );

        if ($v->run() === false) {
            return array_values($v->error_array());
        }

        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');

        $policy_errors = (new AdminPasswordPolicy())->errors($new, 'The new password');
        if ($policy_errors) {
            return $policy_errors;
        }

        $valid = $this->admin_auth->check_password((int) $this->admin['admin_id'], $current);
        if ($valid === null) {
            return ['Your password could not be checked. Please try again.'];
        }
        if (! $valid) {
            return ['The current password is incorrect.'];
        }

        $admin_id = (int) $this->admin['admin_id'];
        if (! (new AdminModel())->update_password_hash($admin_id, password_hash($new, PASSWORD_DEFAULT))) {
            return ['Your password could not be changed. Please try again.'];
        }
        // Password reset links already emailed ("Forgot password?") stop working.
        (new AdminPasswordResetModel())->expire_unused($admin_id);

        return [];
    }
}
