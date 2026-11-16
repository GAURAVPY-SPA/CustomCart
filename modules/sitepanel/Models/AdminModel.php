<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Data access for sitepanel administrators (table tbl_admin).
 *
 * Read methods return FALSE when the database is unavailable or the query
 * fails (the error is logged), NULL when no matching row exists.
 */
class AdminModel extends BaseModel
{
    public const TABLE = 'tbl_admin';

    /**
     * Login lookup: includes the password hash, status and previous sign-in.
     *
     * @return array|false|null
     */
    public function find_for_login(string $username)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $query = $this->table(self::TABLE)
            ->select('admin_id, admin_type, admin_name, admin_username, admin_email, password_hash, admin_password, status, last_login_at, last_login_ip, locked_until')
            ->where('admin_username', $username)
            ->limit(1)
            ->get();

        return $this->single_row($query);
    }

    /**
     * Active administrator by id, without the password hash.
     *
     * @return array|false|null
     */
    public function find_active_by_id(int $admin_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $query = $this->table(self::TABLE)
            ->select('admin_id, admin_type, admin_name, admin_username, admin_email, last_login_at')
            ->where('admin_id', $admin_id)
            ->where('status', 1)
            ->limit(1)
            ->get();

        return $this->single_row($query);
    }

    public function record_login(int $admin_id, string $ip_address): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        return (bool) $this->table(self::TABLE)
            ->where('admin_id', $admin_id)
            ->update([
                'last_login_at'   => date('Y-m-d H:i:s'),
                'last_login_ip'   => $ip_address,
                'failed_attempts' => 0,
                'locked_until'    => null,
            ]);
    }

    /**
     * Count a wrong password; the account is locked once $max_failures is
     * reached. A failure after an expired lock starts the count again.
     * One UPDATE, so concurrent attempts cannot under-count.
     */
    public function record_failed_login(int $admin_id, int $max_failures, int $lock_seconds): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        // MySQL applies SET assignments left to right, so locked_until sees the new count.
        $ok = $this->db->query(
            'UPDATE ' . self::TABLE . '
			    SET failed_attempts = IF(locked_until IS NOT NULL AND locked_until <= ?, 1, failed_attempts + 1),
			        locked_until    = IF(failed_attempts >= ?, ?, NULL)
			  WHERE admin_id = ?',
            [$now, $max_failures, date('Y-m-d H:i:s', time() + $lock_seconds), $admin_id],
        );

        if (! $ok) {
            $this->query_failed(false);
        }

        return (bool) $ok;
    }

    /**
     * Save a password as its password_hash() value. Any legacy plain-text
     * password (admin_password) is cleared, so only the hash is ever checked
     * again. $unlock also clears a sign-in lockout (admin:reset-password).
     */
    public function update_password_hash(int $admin_id, string $password_hash, bool $unlock = false): bool
    {
        if (! $this->db_ready() || ! str_starts_with($password_hash, '$') || password_get_info($password_hash)['algo'] === null) {
            return false;
        }

        $data = [
            'password_hash'  => $password_hash,
            'admin_password' => null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        if ($unlock) {
            $data += ['failed_attempts' => 0, 'locked_until' => null];
        }

        return (bool) $this->table(self::TABLE)
            ->where('admin_id', $admin_id)
            ->update($data);
    }

    /**
     * Save an administrator's email address (tbl_admin.admin_email, unique):
     * the address shown in the Sitepanel and used by "Forgot password?".
     * FALSE when it could not be saved (e.g. another account took it meanwhile).
     */
    public function update_email(int $admin_id, string $email): bool
    {
        return $this->safe_update(self::TABLE, [
            'admin_email' => $email,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], ['admin_id' => $admin_id]);
    }

    /**
     * Administrator by email address (any status; status 1 = active), without
     * the password, for the login page's "Forgot password?" (admin_email is unique).
     *
     * @return array|false|null
     */
    public function find_by_email(string $email)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $query = $this->table(self::TABLE)
            ->select('admin_id, admin_name, admin_username, admin_email, status')
            ->where('admin_email', $email)
            ->limit(1)
            ->get();

        return $this->single_row($query);
    }

    /**
     * Administrator by username or email (any status), without the password,
     * for php spark admin:reset-password.
     *
     * @return array|false|null
     */
    public function find_by_username_or_email(string $login)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $query = $this->table(self::TABLE)
            ->select('admin_id, admin_type, admin_name, admin_username, admin_email, status, locked_until')
            ->groupStart()
            ->where('admin_username', $login)
            ->orWhere('admin_email', $login)
            ->groupEnd()
            ->limit(2)
            ->get();

        $rows = $this->all_rows($query);

        // A value that is one admin's username and another's email is ambiguous: refuse it.
        return is_array($rows) ? (count($rows) === 1 ? $rows[0] : null) : false;
    }

    /**
     * Stored password of an administrator (Change Password check).
     *
     * @return array|false|null
     */
    public function find_password(int $admin_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $query = $this->table(self::TABLE)
            ->select('password_hash, admin_password')
            ->where('admin_id', $admin_id)
            ->limit(1)
            ->get();

        return $this->single_row($query);
    }

    /**
     * @return false|int
     */
    public function count_all()
    {
        if (! $this->db_ready()) {
            return false;
        }

        $row = $this->single_row($this->table(self::TABLE)->select('COUNT(*) AS total', false)->get());

        return is_array($row) ? (int) $row['total'] : false;
    }

    /**
     * @param array $data admin_type, admin_name, admin_username, admin_email, password_hash
     *
     * @return false|int New admin_id
     */
    public function create(array $data)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $ok  = $this->table(self::TABLE)->insert([
            'admin_type'     => (int) $data['admin_type'],
            'admin_name'     => $data['admin_name'],
            'admin_username' => $data['admin_username'],
            'admin_email'    => $data['admin_email'],
            'password_hash'  => $data['password_hash'],
            'status'         => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        if (! $ok) {
            $error = $this->db->error();
            log_message('error', 'AdminModel insert failed: ' . $error['code'] . ' ' . $error['message']);

            return false;
        }

        return (int) $this->db->insertID();
    }
}
