<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Enquiries (wl_enquiry) — Sitepanel > Manage Enquiry (contact and service
 * enquiries), Manage Product Enquiry, and the Contact Us form.
 *
 * The list and lookups take the enquiry types a screen manages ($types,
 * wl_enquiry.type values); an empty list means every type.
 */
class EnquiryModel extends BaseModel
{
    public const TABLE       = 'wl_enquiry';
    public const PRIMARY_KEY = 'id';

    /**
     * Default $types of the list and lookups (set by the screen using the model).
     *
     * @var list<string>
     */
    public array $types = [];

    /**
     * Newest first; the total is left in $this->total_rec_found.
     *
     * @param array $condition keyword (name, email, company, city, country,
     *                         product/service), from / to (Y-m-d), replied (Y|N); '' = not filtered
     * @param list<string> $types Enquiry types to list ([] = all)
     *
     * @return array|false
     */
    public function get_enquiry(int $offset, int $per_page, array $condition = [], array $types = [])
    {
        $condition += ['keyword' => '', 'from' => '', 'to' => '', 'replied' => ''];
        $types = $types ?: $this->types;

        return $this->paged_rows(self::TABLE, 'receive_date DESC, id DESC', $offset, $per_page, static function ($db) use ($condition, $types) {
            $types && $db->whereIn('type', $types);
            if ($condition['keyword'] !== '') {
                $db->groupStart()
                    ->like("CONCAT_WS(' ', first_name, last_name)", $condition['keyword'])
                    ->orLike('email', $condition['keyword'])
                    ->orLike('company_name', $condition['keyword'])
                    ->orLike('city', $condition['keyword'])
                    ->orLike('country', $condition['keyword'])
                    ->orLike('product_service', $condition['keyword'])
                    ->groupEnd();
            }
            $condition['from'] !== '' && $db->where('receive_date >=', $condition['from'] . ' 00:00:00');
            $condition['to'] !== '' && $db->where('receive_date <=', $condition['to'] . ' 23:59:59');
            $condition['replied'] !== '' && $db->where('reply_status', $condition['replied']);
        });
    }

    /**
     * @param int|string   $id
     * @param list<string> $types Enquiry types it may be ([] = any)
     *
     * @return array|false|null
     */
    public function get_enquiry_by_id($id, array $types = [])
    {
        if (! $this->db_ready()) {
            return false;
        }
        $builder = $this->table(self::TABLE)->where(self::PRIMARY_KEY, (int) $id);
        ($types = $types ?: $this->types) && $builder->whereIn('type', $types);

        return $this->single_row($builder->limit(1)->get());
    }

    /**
     * @param list<int>    $ids
     * @param list<string> $types Enquiry types they may be ([] = any)
     *
     * @return array|false
     */
    public function get_enquiries_by_ids(array $ids, array $types = [])
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }
        $builder = $this->table(self::TABLE)->whereIn(self::PRIMARY_KEY, $ids);
        ($types = $types ?: $this->types) && $builder->whereIn('type', $types);

        return $this->all_rows($builder->get());
    }

    /**
     * Enquiries received from one IP address in the last $minutes (Contact
     * Us rate limit).
     *
     * @return false|int
     */
    public function count_recent_from_ip(string $ip, int $minutes)
    {
        return $this->findCount(self::TABLE, [
            'ip_address'      => $ip,
            'receive_date >=' => date('Y-m-d H:i:s', time() - 60 * $minutes),
        ]);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * @param int|string $id
     */
    public function update_reply_status($id, bool $replied): bool
    {
        return $this->safe_update(self::TABLE, ['reply_status' => $replied ? 'Y' : 'N'], [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * Mark enquiries as replied after an email was sent to them (Send Mail).
     *
     * @param list<int|string> $ids
     */
    public function mark_replied(array $ids): bool
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        $ok = $this->table(self::TABLE)->whereIn(self::PRIMARY_KEY, $ids)->update(['reply_status' => 'Y']);
        if (! $ok) {
            $this->query_failed(false);
        }

        return (bool) $ok;
    }
}
