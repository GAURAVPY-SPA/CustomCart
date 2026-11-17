<?php

namespace Modules\Users\Models;

use App\Models\BaseModel;
use Config\Shop;

/**
 * Store customers (wl_customers). user_name is the email and is unique: a
 * guest checkout (status '3', no password) keeps one row per email, which
 * becomes a member account when that email registers, as in the reference.
 * Passwords are password_hash() values (the reference stored them reversibly
 * encrypted and emailed them back on "forgot password"); a reset uses a
 * random token of which only the sha256 is stored, valid for
 * Config\Shop::$resetMinutes.
 */
class CustomerModel extends BaseModel
{
    public const TABLE       = 'wl_customers';
    public const PRIMARY_KEY = 'customers_id';

    public const STATUS_ACTIVE   = '1';
    public const STATUS_INACTIVE = '0';
    public const STATUS_GUEST    = '3';

    /**
     * account_type: store customer accounts ('user'; a guest checkout row is a
     * 'user' row until its email registers). CustomerAuth signs in only these.
     */
    public const TYPE_USER      = 'user';
    public const TYPES          = [self::TYPE_USER];

    /**
     * Admin list of members (not guests), newest first, with each one's order
     * count; the total is left in $this->total_rec_found.
     *
     * @param string $status '1' | '0' | '3' (guests) | '' (members)
     * @param string $type   account_type listed (Manage Members: store customers)
     *
     * @return array|false
     */
    public function get_customers(int $offset, int $per_page, string $keyword = '', string $status = '', string $type = self::TYPE_USER)
    {
        $this->total_rec_found = 0;
        if (! $this->db_ready()) {
            return false;
        }

        $build = function () use ($keyword, $status, $type) {
            $db = $this->table(self::TABLE . ' c');
            $db->where('c.account_type', $type);
            $status !== '' ? $db->where('c.status', $status) : $db->whereIn('c.status', ['0', '1']);
            if ($keyword !== '') {
                $db->groupStart()->like('c.user_name', $keyword)->orLike("CONCAT(c.first_name, ' ', c.last_name)", $keyword, 'both', null, true)->orLike('c.mobile_number', $keyword)->groupEnd();
            }

            return $db;
        };

        $count = $this->single_row($build()->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        return $this->all_rows($build()
            ->select("c.*, (SELECT COUNT(*) FROM wl_order o WHERE o.customers_id = c.customers_id AND o.order_status <> 'Deleted') AS order_count", false)
            ->orderBy('c.customers_id', 'DESC')->limit($per_page, $offset)->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_customer_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id, 'status <>' => '2']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_customers_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('status <>', '2')->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * Any row with this email (guest, member or deleted).
     *
     * @return array|false|null
     */
    public function get_by_email(string $email)
    {
        return $this->find(self::TABLE, ['user_name' => strtolower(trim($email))]);
    }

    /**
     * Whether a member account (not a guest row) uses the email.
     *
     * @return bool|null NULL when it could not be checked
     */
    public function member_email_taken(string $email, int $except_id = 0): ?bool
    {
        $count = $this->findCount(self::TABLE, ['user_name' => strtolower(trim($email)), 'status <>' => self::STATUS_GUEST, self::PRIMARY_KEY . ' <>' => $except_id]);

        return $count === false ? null : $count > 0;
    }

    /**
     * Whether an account (a member, not a guest or deleted row) uses
     * the mobile number, compared on its digits ("+65 9123-4567" = "6591234567").
     *
     * @return bool|null NULL when it could not be checked
     */
    public function member_mobile_taken(string $mobile, int $except_id = 0): ?bool
    {
        $digits = preg_replace('/[^0-9]/', '', $mobile);
        if ($digits === '') {
            return false;
        }
        if (! $this->db_ready()) {
            return null;
        }
        $row = $this->single_row($this->table(self::TABLE)
            ->select('COUNT(*) AS n', false)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_INACTIVE])
            ->where(self::PRIMARY_KEY . ' <>', $except_id)
            ->where("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(mobile_number, ''), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') = " . $this->db->escape($digits), null, false)
            ->get());

        return $row === false ? null : (int) $row['n'] > 0;
    }

    /**
     * Register a member: a new row, or the guest row of the same email upgraded.
     *
     * @param array $data user_name, password (plain), first_name, last_name, mobile_number, newsletter,
     *                    account_type (TYPE_USER)
     *
     * @return false|int The customer id
     */
    public function register(array $data, string $ip)
    {
        $email = strtolower(trim($data['user_name']));
        $type  = in_array($data['account_type'] ?? '', self::TYPES, true) ? $data['account_type'] : self::TYPE_USER;
        $row   = [
            'user_name'            => $email,
            'account_type'         => $type,
            'password'             => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name'           => $data['first_name'],
            'last_name'            => $data['last_name'] ?? '',
            'mobile_number'        => $data['mobile_number'] ?? null,
            'newsletter'           => ! empty($data['newsletter']) ? '1' : '0',
            'status'               => self::STATUS_ACTIVE,
            'account_created_date' => date('Y-m-d H:i:s'),
            'ip_address'           => $ip,
            'number_of_login_try'  => 0,
            'block_time'           => null,
        ];

        $existing = $this->get_by_email($email);
        if ($existing === false) {
            return false;
        }
        if (is_array($existing)) {
            if ($existing['status'] !== self::STATUS_GUEST) {
                return false;
            }

            return $this->safe_update(self::TABLE, $row, [self::PRIMARY_KEY => (int) $existing['customers_id']]) ? (int) $existing['customers_id'] : false;
        }

        return $this->safe_insert(self::TABLE, $row);
    }

    /**
     * The customer row of a guest checkout: the existing row of the email
     * (any status but deleted), else a new guest row.
     *
     * @return array|false|null NULL when the email belongs to a deleted account
     */
    public function guest_for_checkout(string $email, string $first_name, string $last_name, string $mobile, string $ip)
    {
        $email    = strtolower(trim($email));
        $existing = $this->get_by_email($email);
        if ($existing === false || is_array($existing)) {
            return is_array($existing) && $existing['status'] === '2' ? null : $existing;
        }

        $id = $this->safe_insert(self::TABLE, [
            'user_name'            => $email,
            'password'             => '',
            'first_name'           => $first_name,
            'last_name'            => $last_name,
            'mobile_number'        => $mobile,
            'status'               => self::STATUS_GUEST,
            'account_created_date' => date('Y-m-d H:i:s'),
            'ip_address'           => $ip,
        ]);

        return $id === false ? false : $this->find(self::TABLE, [self::PRIMARY_KEY => $id]);
    }

    /**
     * Record a successful sign-in (the reference's update_last_login) and clear failed tries.
     */
    public function record_login(int $id, string $ip): void
    {
        if ($this->db_ready()) {
            $this->write_failed(fn () => (bool) $this->table(self::TABLE)
                ->set('last_login_date', 'current_login', false)
                ->set(['current_login' => date('Y-m-d H:i:s'), 'ip_address' => $ip, 'number_of_login_try' => 0, 'block_time' => null])
                ->where(self::PRIMARY_KEY, $id)->update());
        }
    }

    /**
     * Count a failed sign-in; the account is locked for Config\Shop::$lockMinutes
     * after Config\Shop::$maxLoginTries failures in a row.
     */
    public function record_failed_login(array $customer): void
    {
        $shop  = config(Shop::class);
        $tries = (int) $customer['number_of_login_try'] + 1;
        $data  = ['number_of_login_try' => $tries];
        if ($tries >= $shop->maxLoginTries) {
            $data = ['number_of_login_try' => 0, 'block_time' => date('Y-m-d H:i:s', time() + $shop->lockMinutes * 60)];
        }
        $this->safe_update(self::TABLE, $data, [self::PRIMARY_KEY => (int) $customer['customers_id']]);
    }

    /**
     * Start a password reset: a new random token (returned, to be emailed);
     * only its sha256 is stored.
     *
     * @return false|string
     */
    public function create_reset_token(int $id)
    {
        $token = bin2hex(random_bytes(32));
        $saved = $this->safe_update(self::TABLE, [
            'reset_token'   => hash('sha256', $token),
            'reset_expires' => date('Y-m-d H:i:s', time() + config(Shop::class)->resetMinutes * 60),
        ], [self::PRIMARY_KEY => $id]);

        return $saved ? $token : false;
    }

    /**
     * The active member whose unexpired reset token this is.
     *
     * @return array|false|null
     */
    public function get_by_reset_token(string $token)
    {
        if (! preg_match('/^[0-9a-f]{64}$/D', $token)) {
            return null;
        }

        return $this->find(self::TABLE, [
            'reset_token'     => hash('sha256', $token),
            'reset_expires >' => date('Y-m-d H:i:s'),
            'status'          => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Set a new password (also ends any reset and lock).
     */
    public function update_password(int $id, string $password): bool
    {
        return $this->safe_update(self::TABLE, [
            'password'            => password_hash($password, PASSWORD_DEFAULT),
            'reset_token'         => null,
            'reset_expires'       => null,
            'number_of_login_try' => 0,
            'block_time'          => null,
        ], [self::PRIMARY_KEY => $id]);
    }

    /**
     * Update a member's own details (Edit Account).
     *
     * @param array $data first_name, last_name, mobile_number, address, city, state, country, zipcode, newsletter
     */
    public function update_profile(int $id, array $data): bool
    {
        $allowed = ['first_name', 'last_name', 'mobile_number', 'address', 'city', 'state', 'country', 'zipcode', 'newsletter'];

        return $this->safe_update(self::TABLE, array_intersect_key($data, array_flip($allowed)), [self::PRIMARY_KEY => $id]);
    }

    /**
     * Ids among $ids that have orders (they can't be deleted: deactivate them instead).
     *
     * @param list<int> $ids
     *
     * @return list<int>|false
     */
    public function ids_with_orders(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return [];
        }
        $rows = $this->all_rows($this->table('wl_order')->distinct()->select('customers_id')->whereIn('customers_id', $ids)->get());

        return $rows === false ? false : array_map('intval', array_column($rows, 'customers_id'));
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    /**
     * Also removes their addresses and wishlists (foreign key cascade).
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }
}
