<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Newsletter subscribers (wl_newsletters) — Sitepanel > Newsletter Management.
 */
class NewsletterModel extends BaseModel
{
    public const TABLE       = 'wl_newsletters';
    public const PRIMARY_KEY = 'subscriber_id';

    /**
     * Newest first, filtered by name/email keyword and status; the total is
     * left in $this->total_rec_found. Argument order (limit, offset) as in
     * the reference model.
     *
     * @param string $status '1', '0' or '' (all)
     *
     * @return array|false
     */
    public function get_newsletter(int $limit = 20, int $offset = 0, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'subscriber_id DESC', $offset, $limit, static function ($db) use ($keyword, $status) {
            if ($keyword !== '') {
                $db->groupStart()
                    ->like('subscriber_name', $keyword)
                    ->orLike('subscriber_email', $keyword)
                    ->groupEnd();
            }
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * @param int|string $subscriber_id
     *
     * @return array|false|null
     */
    public function get_subscriber_by_id($subscriber_id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $subscriber_id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_subscribers_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * @deprecated Use get_subscriber_by_id() (kept for older code).
     *
     * @param int|string $subscriber_id
     *
     * @return array|false|null
     */
    public function get_record_by_id($subscriber_id)
    {
        return $this->get_subscriber_by_id($subscriber_id);
    }

    /**
     * @deprecated Use get_subscribers_by_ids() (kept for older code).
     *
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_records_by_ids(array $ids)
    {
        return $this->get_subscribers_by_ids($ids);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * Add an address to the list (the website's newsletter form), active,
     * unless it is already there.
     *
     * @return bool|null TRUE = added, NULL = already subscribed, FALSE = DB error
     */
    public function subscribe(string $email, string $name = '')
    {
        $found = $this->checkRecords($email);
        if ($found === false) {
            return false;
        }
        if ($found > 0) {
            return null;
        }

        return $this->safe_insert(self::TABLE, [
            'subscriber_name'  => $name !== '' ? mb_substr($name, 0, 32) : null,
            'subscriber_email' => mb_substr($email, 0, 80),
            'status'           => '1',
            'subscribe_date'   => date('Y-m-d H:i:s'),
        ]) !== false;
    }

    /**
     * Subscriptions with this email, other than $except_id (one per address).
     *
     * @param int|string|null $except_id Row being edited
     *
     * @return false|int
     */
    public function checkRecords(string $email, $except_id = null)
    {
        $where = ['subscriber_email' => $email];
        if ($except_id) {
            $where['subscriber_id <>'] = (int) $except_id;
        }

        return $this->findCount(self::TABLE, $where);
    }
}
