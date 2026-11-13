<?php

namespace Modules\Review\Models;

use App\Models\BaseModel;
use Modules\Products\Models\ProductModel;

/**
 * Product reviews (wl_review, rev_type 'P'), as in the reference: anyone may
 * post one; it is shown once approved (status '1') in Sitepanel > Product
 * Reviews. The rating shown is the average of the approved reviews.
 */
class ReviewModel extends BaseModel
{
    public const TABLE       = 'wl_review';
    public const PRIMARY_KEY = 'review_id';

    /**
     * Admin list, newest first, with the product name; the total is left in $this->total_rec_found.
     *
     * @param string $status '1' | '0' | ''
     *
     * @return array|false
     */
    public function get_reviews(int $offset, int $per_page, string $keyword = '', string $status = '', int $product_id = 0)
    {
        $this->total_rec_found = 0;
        if (! $this->db_ready()) {
            return false;
        }

        $build = function () use ($keyword, $status, $product_id) {
            $db = $this->table(self::TABLE . ' r')->join(ProductModel::TABLE . ' p', 'p.products_id = r.prod_id');
            if ($keyword !== '') {
                $db->groupStart()->like('p.product_name', $keyword)->orLike('r.poster_name', $keyword)->orLike('r.email', $keyword)->groupEnd();
            }
            $status !== '' && $db->where('r.status', $status);
            $product_id > 0 && $db->where('r.prod_id', $product_id);

            return $db;
        };

        $count = $this->single_row($build()->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        return $this->all_rows($build()->select('r.*, p.product_name, p.friendly_url')->orderBy('r.review_id', 'DESC')->limit($per_page, $offset)->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_review_by_id($id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table(self::TABLE . ' r')->select('r.*, p.product_name, p.friendly_url')
            ->join(ProductModel::TABLE . ' p', 'p.products_id = r.prod_id')->where('r.review_id', (int) $id)->limit(1)->get());
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_reviews_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * Approved reviews of a product, newest first.
     *
     * @return list<array>|false
     */
    public function get_public_reviews(int $product_id, int $limit = 50)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where(['prod_id' => $product_id, 'status' => '1'])->orderBy('review_id', 'DESC')->limit($limit)->get());
    }

    /**
     * ['average' => 0-5 (1 decimal), 'count' => approved reviews].
     *
     * @return array|false
     */
    public function rating_summary(int $product_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $row = $this->single_row($this->table(self::TABLE)->select('COUNT(*) AS n, AVG(rate) AS a', false)->where(['prod_id' => $product_id, 'status' => '1'])->get());

        return $row === false ? false : ['average' => round((float) $row['a'], 1), 'count' => (int) $row['n']];
    }

    /**
     * Reviews posted from an IP address in the last $minutes (spam limit).
     *
     * @return false|int
     */
    public function count_recent_from_ip(string $ip, int $minutes)
    {
        return $this->findCount(self::TABLE, ['ip_address' => $ip, 'posted_date >=' => date('Y-m-d H:i:s', time() - $minutes * 60)]);
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
}
