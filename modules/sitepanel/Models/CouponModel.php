<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Discount coupons (wl_coupons) — Sitepanel > Manage Discount Coupons, and
 * the reference's cart rules for applying a coupon (get_valid_coupon(),
 * discount_for()), ready for a cart / checkout.
 */
class CouponModel extends BaseModel
{
    public const TABLE       = 'wl_coupons';
    public const PRIMARY_KEY = 'coupon_id';

    /**
     * Characters of a generated coupon code (the reference's random alnum string).
     */
    public const CODE_CHARS  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    public const CODE_LENGTH = 8;

    /**
     * Newest first; deleted ('2') rows are never listed. The total is left in $this->total_rec_found.
     *
     * @param string $keyword Part of the coupon code or title
     * @param string $status  '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_coupons(int $offset, int $per_page, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'coupon_id DESC', $offset, $per_page, static function ($db) use ($keyword, $status) {
            $db->where('status <>', '2');
            if ($keyword !== '') {
                $db->groupStart()->like('coupon_code', $keyword)->orLike('coupon_title', $keyword)->groupEnd();
            }
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_coupon_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id, 'status <>' => '2']);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_coupons_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('status <>', '2')->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * A new random code no other coupon uses.
     *
     * @return false|string
     */
    public function new_coupon_code()
    {
        for ($try = 0; $try < 10; $try++) {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_CHARS[random_int(0, strlen(self::CODE_CHARS) - 1)];
            }
            $count = $this->findCount(self::TABLE, ['coupon_code' => $code]);
            if ($count === false) {
                return false;
            }
            if ($count === 0) {
                return $code;
            }
        }

        return false;
    }

    /**
     * The reference cart's coupon lookup: an active coupon with exactly this
     * (case-sensitive) code, valid on $date.
     *
     * @param string $date Y-m-d (default today)
     *
     * @return array|false|null NULL = no such valid coupon
     */
    public function get_valid_coupon(string $code, ?string $date = null)
    {
        $date ??= date('Y-m-d');

        return $this->find(self::TABLE, [
            'coupon_code'   => $code,
            'status'        => '1',
            'start_date <=' => $date,
            'end_date >='   => $date,
        ]);
    }

    /**
     * Coupons valid today for My Account > Coupons: those for everyone and,
     * for a member, the members-only ones; newest first.
     *
     * @return list<array>|false
     */
    public function get_current_coupons(bool $member)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $date = date('Y-m-d');
        $db   = $this->table(self::TABLE)->where(['status' => '1', 'start_date <=' => $date, 'end_date >=' => $date]);
        $member || $db->where('coupon_for', '0');

        return $this->all_rows($db->orderBy(self::PRIMARY_KEY, 'DESC')->get());
    }

    /**
     * The reference cart's discount: a percentage of the cart total capped at
     * maximum_coupon_discount (when set), or the fixed amount; 0 when the
     * cart total is under the minimum order amount or under the discount.
     */
    public static function discount_for(array $coupon, float $cart_total): float
    {
        if ($cart_total < (float) $coupon['minimum_order_amount']) {
            return 0.0;
        }

        if ($coupon['coupon_type'] === 'p') {
            $discount = $cart_total * (float) $coupon['coupon_discount'] / 100;
            $cap      = (float) $coupon['maximum_coupon_discount'];
            $cap > 0 && $discount = min($discount, $cap);
        } else {
            $discount = (float) $coupon['coupon_discount'];
        }

        return $cart_total >= $discount ? round($discount, 2) : 0.0;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    /**
     * Also removes the coupon's member assignments (foreign key cascade).
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }
}
