<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Registers /cart/shipping (Cart::shipping(): the delivery option chosen in
 * the Order Summary) in wl_meta_tags, the URL registry, as StoreSeeder does
 * for the other store pages.
 */
class CartShippingRoute extends Migration
{
    public function up(): void
    {
        $exists = $this->db->table('wl_meta_tags')->where('page_url', 'cart/shipping')->countAllResults();
        $exists || $this->db->table('wl_meta_tags')->insert([
            'is_fixed' => 'Y', 'entity_type' => 'cart/shipping', 'entity_id' => 0, 'page_url' => 'cart/shipping',
            'meta_title' => 'Delivery Option | CustomCart', 'meta_description' => '', 'meta_keyword' => '', 'status' => '1',
        ]);
    }

    public function down(): void
    {
        $this->db->table('wl_meta_tags')->where(['page_url' => 'cart/shipping', 'entity_type' => 'cart/shipping'])->delete();
    }
}
