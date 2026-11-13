<?php

namespace Modules\Members\Models;

use App\Models\BaseModel;

/**
 * Customer delivery addresses (wl_customers_address_book, address_type
 * 'Ship'): the address book of My Account > My Address and the checkout
 * Address step (design: address.htm, checkout.htm). One address is the
 * default (default_status 'Y'); it is selected first at checkout. Every
 * address is looked up with the customer's id, so a customer only ever
 * reaches their own.
 */
class AddressModel extends BaseModel
{
    public const TABLE       = 'wl_customers_address_book';
    public const PRIMARY_KEY = 'address_id';

    public const FIELDS = ['first_name', 'last_name', 'address', 'landmark', 'mobile', 'alt_mobile', 'zipcode', 'city', 'state', 'country'];

    /**
     * Most addresses one customer can keep.
     */
    public const MAX_ADDRESSES = 10;

    /**
     * The customer's addresses, default first, then newest.
     *
     * @return list<array>|false
     */
    public function get_addresses(int $customer_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where(['customer_id' => $customer_id, 'address_type' => 'Ship'])
            ->orderBy("default_status = 'Y'", 'DESC', false)->orderBy('address_id', 'DESC')->get());
    }

    /**
     * One of the customer's addresses.
     *
     * @return array|false|null
     */
    public function get_address(int $customer_id, int $address_id)
    {
        return $this->find(self::TABLE, ['customer_id' => $customer_id, 'address_type' => 'Ship', self::PRIMARY_KEY => $address_id]);
    }

    /**
     * @return array|false|null
     */
    public function get_default_address(int $customer_id)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table(self::TABLE)->where(['customer_id' => $customer_id, 'address_type' => 'Ship'])
            ->orderBy("default_status = 'Y'", 'DESC', false)->orderBy('address_id', 'DESC')->limit(1)->get());
    }

    /**
     * Add an address (the first one becomes the default), or update one of the
     * customer's ($address_id > 0).
     *
     * @param array $data FIELDS
     *
     * @return false|int The address id
     */
    public function save_address(int $customer_id, array $data, int $address_id = 0, bool $make_default = false)
    {
        $values = array_intersect_key($data, array_flip(self::FIELDS));

        if ($address_id > 0) {
            if (! $this->safe_update(self::TABLE, $values, ['customer_id' => $customer_id, self::PRIMARY_KEY => $address_id])) {
                return false;
            }
        } else {
            $count = $this->findCount(self::TABLE, ['customer_id' => $customer_id, 'address_type' => 'Ship']);
            if ($count === false) {
                return false;
            }
            $make_default = $make_default || $count === 0;
            $address_id   = (int) $this->safe_insert(self::TABLE, $values + [
                'customer_id' => $customer_id, 'address_type' => 'Ship', 'default_status' => 'N', 'reciv_date' => date('Y-m-d H:i:s'),
            ]);
            if ($address_id < 1) {
                return false;
            }
        }

        return $make_default && ! $this->set_default($customer_id, $address_id) ? false : $address_id;
    }

    /**
     * Make one of the customer's addresses the default.
     */
    public function set_default(int $customer_id, int $address_id): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->write_failed(function () use ($customer_id, $address_id) {
            $this->db->transStart();
            $this->table(self::TABLE)->where('customer_id', $customer_id)->update(['default_status' => 'N']);
            $this->table(self::TABLE)->where(['customer_id' => $customer_id, self::PRIMARY_KEY => $address_id])->update(['default_status' => 'Y']);
            $this->db->transComplete();

            return $this->db->transStatus();
        }) === false;
    }

    /**
     * Delete one of the customer's addresses; the newest remaining one becomes
     * the default when the default was deleted.
     */
    public function delete_address(int $customer_id, int $address_id): bool
    {
        $address = $this->get_address($customer_id, $address_id);
        if (! is_array($address)) {
            return $address === null;
        }
        if (! $this->safe_delete(self::TABLE, ['customer_id' => $customer_id, self::PRIMARY_KEY => $address_id])) {
            return false;
        }
        if ($address['default_status'] === 'Y') {
            $next = $this->get_default_address($customer_id);
            is_array($next) && $this->set_default($customer_id, (int) $next[self::PRIMARY_KEY]);
        }

        return true;
    }

    /**
     * Create or replace the customer's default Ship address (an order placed
     * while signed in with a new address keeps it).
     *
     * @param array $data FIELDS
     */
    public function save_default_address(int $customer_id, array $data): bool
    {
        $current = $this->get_default_address($customer_id);
        if ($current === false) {
            return false;
        }

        return $this->save_address($customer_id, $data, is_array($current) ? (int) $current['address_id'] : 0, true) !== false;
    }
}
