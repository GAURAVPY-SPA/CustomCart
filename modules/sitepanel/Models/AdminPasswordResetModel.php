<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;
use Throwable;

/**
 * Sitepanel password reset links (table tbl_admin_password_resets).
 *
 * The token sent by email is 32 random bytes (random_bytes(), 64 hex
 * characters); only its sha256 is stored, so the table alone can't be used
 * to reset a password. A link is valid while it is unused, unexpired and its
 * administrator is active. Using it (use_token()) marks it used and sets the
 * new password in one transaction, so it works exactly once even when it is
 * submitted twice at the same moment.
 *
 * Times are PHP date() values (the site timezone), as in the rest of the
 * project; MySQL's NOW() is not used, so both sides always agree.
 *
 * Read methods return FALSE when the database is unavailable or the query
 * fails (the error is logged), NULL when no matching row exists. Tokens and
 * their hashes are never logged.
 */
class AdminPasswordResetModel extends BaseModel
{
    public const TABLE = 'tbl_admin_password_resets';

    /**
     * Used/expired rows are kept this long (for the per-hour limit), then removed.
     */
    protected const KEEP_SECONDS = 86400;

    /**
     * sha256 of a token, or NULL when it isn't 64 hex characters (never queried).
     */
    public static function hash_token(string $token): ?string
    {
        return preg_match('/^[0-9a-f]{64}$/D', $token) ? hash('sha256', $token) : null;
    }

    /**
     * Reset links created for an administrator during the last $seconds.
     *
     * @return false|int
     */
    public function count_recent(int $admin_id, int $seconds)
    {
        return $this->findCount(self::TABLE, [
            'admin_id'     => $admin_id,
            'created_at >' => date('Y-m-d H:i:s', time() - $seconds),
        ]);
    }

    /**
     * Start a reset: ends the administrator's earlier unused links and stores a
     * new one. Returns the token to be emailed (its hash is what is stored).
     *
     * @return false|string
     */
    public function create_token(int $admin_id, int $minutes, string $ip_address)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->purge_old();

        // Only the newest link works.
        $this->expire_unused($admin_id);

        $token = bin2hex(random_bytes(32));
        $saved = $this->safe_insert(self::TABLE, [
            'admin_id'   => $admin_id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + max(1, $minutes) * 60),
            'request_ip' => mb_substr($ip_address, 0, 45),
            'created_at' => $now,
        ]);

        return $saved ? $token : false;
    }

    /**
     * End the administrator's unused links (a newer link was requested, or the
     * account's email address changed: links sent to the old address stop working).
     */
    public function expire_unused(int $admin_id): bool
    {
        $now = date('Y-m-d H:i:s');

        return $this->safe_update(self::TABLE, ['expires_at' => $now], [
            'admin_id'     => $admin_id,
            'used_at'      => null,
            'expires_at >' => $now,
        ]);
    }

    /**
     * The valid (unused, unexpired) link with this token hash and its active
     * administrator: id, admin_id, expires_at, admin_name, admin_username, admin_email.
     *
     * @return array|false|null
     */
    public function find_valid(string $token_hash)
    {
        if (! preg_match('/^[0-9a-f]{64}$/D', $token_hash)) {
            return null;
        }
        if (! $this->db_ready()) {
            return false;
        }

        $query = $this->table(self::TABLE . ' r')
            ->select('r.id, r.admin_id, r.expires_at, a.admin_name, a.admin_username, a.admin_email')
            ->join(AdminModel::TABLE . ' a', 'a.admin_id = r.admin_id')
            ->where('r.token_hash', $token_hash)
            ->where('r.used_at', null)
            ->where('r.expires_at >', date('Y-m-d H:i:s'))
            ->where('a.status', 1)
            ->limit(1)
            ->get();

        return $this->single_row($query);
    }

    /**
     * Use a link: mark it used, save the new password hash (which also lifts a
     * sign-in lockout) and end the administrator's other open links — all or
     * nothing.
     *
     * @return bool|null TRUE = password changed; NULL = the link is no longer
     *                   valid (used or expired meanwhile); FALSE = database error
     */
    public function use_token(int $reset_id, int $admin_id, string $password_hash): ?bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        try {
            $this->db->transBegin();

            // Claim the link; a second (concurrent) use finds used_at already set.
            $claimed = $this->table(self::TABLE)
                ->where('id', $reset_id)
                ->where('admin_id', $admin_id)
                ->where('used_at', null)
                ->where('expires_at >', $now)
                ->update(['used_at' => $now]);

            if (! $claimed || $this->db->affectedRows() !== 1) {
                $this->db->transRollback();

                return $claimed ? null : $this->failed();
            }

            if (! (new AdminModel())->update_password_hash($admin_id, $password_hash, true)) {
                $this->db->transRollback();

                return $this->failed();
            }

            $this->table(self::TABLE)
                ->where('admin_id', $admin_id)
                ->where('used_at', null)
                ->where('expires_at >', $now)
                ->update(['expires_at' => $now]);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();

                return $this->failed();
            }

            $this->db->transCommit();

            return true;
        } catch (Throwable $e) {
            try {
                $this->db->transRollback();
            } catch (Throwable) {
            }
            log_message('error', $this->name() . ': password reset could not be saved: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Remove links that ended more than KEEP_SECONDS ago.
     */
    protected function purge_old(): void
    {
        $before = date('Y-m-d H:i:s', time() - self::KEEP_SECONDS);
        $this->safe_delete(self::TABLE, ['expires_at <' => $before]);
    }

    protected function failed(): bool
    {
        $error = $this->db->error();
        log_message('error', $this->name() . ': password reset could not be saved: ' . $error['code'] . ' ' . $error['message']);

        return false;
    }
}
