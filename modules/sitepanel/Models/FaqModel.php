<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * FAQs (wl_faq) — Sitepanel > Manage FAQs, and the active FAQs for the website.
 */
class FaqModel extends BaseModel
{
    public const TABLE       = 'wl_faq';
    public const PRIMARY_KEY = 'faq_id';

    /**
     * FAQs assigned to products (products_id, faq_id).
     */
    public const PRODUCT_TABLE = 'wl_products_faq';

    /**
     * Display order, then newest first; the total is left in $this->total_rec_found.
     *
     * @param string $keyword Part of the question ('' = all)
     * @param string $status  '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_faq(int $offset, int $per_page, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'sort_order ASC, faq_id DESC', $offset, $per_page, static function ($db) use ($keyword, $status) {
            $keyword !== '' && $db->like('faq_question', $keyword);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * Active FAQs for the website, in display order.
     *
     * @return array|false
     */
    public function get_active_faqs()
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)
            ->select('faq_id, faq_question, faq_answer')
            ->where('status', '1')
            ->orderBy('sort_order', 'ASC')
            ->orderBy(self::PRIMARY_KEY, 'DESC')
            ->get());
    }

    /**
     * Active FAQs shown on the website's FAQ page (is_general = '1'), in display order.
     *
     * @return array|false
     */
    public function get_general_faqs()
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)
            ->select('faq_id, faq_question, faq_answer')
            ->where(['status' => '1', 'is_general' => '1'])
            ->orderBy('sort_order', 'ASC')
            ->orderBy(self::PRIMARY_KEY, 'DESC')
            ->get());
    }

    /**
     * Active FAQs assigned to a product (wl_products_faq), in display order,
     * for its product page.
     *
     * @return list<array{faq_id: string, faq_question: string, faq_answer: string}>|false
     */
    public function get_product_faqs(int $product_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE . ' f')
            ->select('f.faq_id, f.faq_question, f.faq_answer')
            ->join(self::PRODUCT_TABLE . ' pf', 'pf.faq_id = f.faq_id')
            ->where(['pf.products_id' => $product_id, 'f.status' => '1'])
            ->orderBy('f.sort_order', 'ASC')
            ->orderBy('f.faq_id', 'DESC')
            ->get());
    }

    /**
     * Ids of the products an FAQ is assigned to.
     *
     * @return list<int>|false
     */
    public function get_product_ids(int $faq_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::PRODUCT_TABLE)->select('products_id')->where('faq_id', $faq_id)->get());

        return $rows === false ? false : array_map('intval', array_column($rows, 'products_id'));
    }

    /**
     * Replace the products an FAQ is assigned to.
     *
     * @param list<int> $product_ids
     */
    public function save_product_ids(int $faq_id, array $product_ids): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->write_failed(function () use ($faq_id, $product_ids) {
            $this->db->transStart();
            $this->table(self::PRODUCT_TABLE)->where('faq_id', $faq_id)->delete();
            if ($product_ids) {
                $this->table(self::PRODUCT_TABLE)->insertBatch(array_map(static fn ($id) => ['products_id' => (int) $id, 'faq_id' => $faq_id], array_values(array_unique($product_ids))));
            }
            $this->db->transComplete();

            return $this->db->transStatus();
        }) === false;
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_faq_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_faqs_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * Whether another FAQ already has this question (case-insensitive, as the collation compares).
     *
     * @return bool|null NULL when it could not be checked
     */
    public function question_exists(string $question, int $except_id = 0): ?bool
    {
        $where = ['faq_question' => $question];
        $except_id > 0 && $where['faq_id !='] = $except_id;
        $count = $this->findCount(self::TABLE, $where);

        return $count === false ? null : $count > 0;
    }

    /**
     * Next display order: after the last FAQ, as in the reference (MAX + 1).
     *
     * @return false|int
     */
    public function next_sort_order()
    {
        if (! $this->db_ready()) {
            return false;
        }
        $row = $this->single_row($this->table(self::TABLE)->selectMax('sort_order', 'max_order')->get());

        return is_array($row) ? (int) $row['max_order'] + 1 : false;
    }

    /**
     * @param array<int, int> $orders faq_id => display order
     */
    public function update_sort_orders(array $orders): bool
    {
        if (! $orders || ! $this->db_ready()) {
            return false;
        }

        return $this->write_failed(function () use ($orders) {
            $this->db->transStart();

            foreach ($orders as $id => $order) {
                $this->table(self::TABLE)->where(self::PRIMARY_KEY, (int) $id)->update(['sort_order' => (int) $order]);
            }
            $this->db->transComplete();

            return $this->db->transStatus();
        }) === false;
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
        if (! $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids)) {
            return false;
        }
        $this->write_failed(fn () => (bool) $this->table(self::PRODUCT_TABLE)->whereIn('faq_id', array_map('intval', $ids))->delete());

        return true;
    }
}
