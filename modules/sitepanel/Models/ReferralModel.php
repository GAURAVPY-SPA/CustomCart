<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Refer a Friend messages (wl_referrals): sent from the website's Refer a
 * Friend form (Pages::refer_friend), listed in Sitepanel > Manage Refer a Friend.
 */
class ReferralModel extends BaseModel
{
    public const TABLE       = 'wl_referrals';
    public const PRIMARY_KEY = 'referral_id';

    /**
     * Newest first, with the referred product's name; the total is left in $this->total_rec_found.
     *
     * @param string $keyword Part of a name or email ('' = all)
     *
     * @return array|false
     */
    public function get_referrals(int $offset, int $per_page, string $keyword = '')
    {
        $rows = $this->paged_rows(self::TABLE, 'referral_id DESC', $offset, $per_page, static function ($db) use ($keyword) {
            if ($keyword !== '') {
                $db->groupStart()->like('sender_name', $keyword)->orLike('sender_email', $keyword)
                    ->orLike('friend_name', $keyword)->orLike('friend_email', $keyword)->groupEnd();
            }
        });
        if (! $rows) {
            return $rows;
        }

        $ids   = array_values(array_unique(array_filter(array_map('intval', array_column($rows, 'products_id')))));
        $names = [];
        if ($ids) {
            $products = $this->all_rows($this->table('wl_products')->select('products_id, product_name')->whereIn('products_id', $ids)->get());
            $names    = is_array($products) ? array_column($products, 'product_name', 'products_id') : [];
        }
        foreach ($rows as &$row) {
            $row['product_name'] = $names[(int) $row['products_id']] ?? null;
        }

        return $rows;
    }

    /**
     * @return array|false|null
     */
    public function get_referral($id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table(self::TABLE . ' r')->select('r.*, p.product_name')
            ->join('wl_products p', 'p.products_id = r.products_id', 'left')
            ->where('r.referral_id', (int) $id)->get(1));
    }

    /**
     * @return array|false
     */
    public function get_referrals_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * Messages sent from an IP address in the last $minutes (spam limit).
     *
     * @return false|int
     */
    public function count_recent_from_ip(string $ip, int $minutes)
    {
        return $this->findCount(self::TABLE, ['ip_address' => $ip, 'referral_date >=' => date('Y-m-d H:i:s', time() - $minutes * 60)]);
    }

    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }
}
