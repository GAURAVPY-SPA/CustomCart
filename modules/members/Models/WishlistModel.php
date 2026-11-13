<?php

namespace Modules\Members\Models;

use App\Models\BaseModel;
use Modules\Products\Models\ProductModel;

/**
 * Customer wishlists (wl_wishlists), as in the reference: signed-in
 * customers only, one entry per product, removed with the product.
 */
class WishlistModel extends BaseModel
{
    public const TABLE       = 'wl_wishlists';
    public const PRIMARY_KEY = 'wishlists_id';

    /**
     * The customer's wishlist, newest first: public products (with the
     * listing fields of ProductModel) plus wishlists_id and wishlists_date_added.
     *
     * @return list<array>|false
     */
    public function get_wishlist(int $customer_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $rows = $this->all_rows($this->table(self::TABLE)->where('customer_id', $customer_id)->orderBy('wishlists_id', 'DESC')->get());
        if (! $rows) {
            return $rows;
        }

        $products = (new ProductModel())->get_public_by_ids(array_map('intval', array_column($rows, 'product_id')));
        if ($products === false) {
            return false;
        }

        $list = [];

        foreach ($rows as $row) {
            if (isset($products[(int) $row['product_id']])) {
                $list[] = $products[(int) $row['product_id']] + ['wishlists_id' => $row['wishlists_id'], 'wishlists_date_added' => $row['wishlists_date_added']];
            }
        }

        return $list;
    }

    /**
     * Product ids on the customer's wishlist (heart buttons).
     *
     * @return list<int>|false
     */
    public function product_ids(int $customer_id)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $rows = $this->all_rows($this->table(self::TABLE)->select('product_id')->where('customer_id', $customer_id)->get());

        return $rows === false ? false : array_map('intval', array_column($rows, 'product_id'));
    }

    /**
     * Add a product (no-op when it is already there).
     */
    public function add(int $customer_id, int $product_id): bool
    {
        $count = $this->findCount(self::TABLE, ['customer_id' => $customer_id, 'product_id' => $product_id]);
        if ($count === false) {
            return false;
        }

        return $count > 0 || $this->safe_insert(self::TABLE, [
            'customer_id' => $customer_id, 'product_id' => $product_id, 'wishlists_date_added' => date('Y-m-d H:i:s'),
        ]) !== false;
    }

    public function remove(int $customer_id, int $product_id): bool
    {
        return $this->safe_delete(self::TABLE, ['customer_id' => $customer_id, 'product_id' => $product_id]);
    }

    /**
     * @return false|int
     */
    public function count_items(int $customer_id)
    {
        return $this->findCount(self::TABLE, ['customer_id' => $customer_id]);
    }
}
