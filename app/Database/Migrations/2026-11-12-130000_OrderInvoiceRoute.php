<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Registers /order-invoice (Cart::invoice(): the printable invoice of an
 * order, guests too) in wl_meta_tags, the URL registry, as StoreSeeder does
 * for the other store pages.
 */
class OrderInvoiceRoute extends Migration
{
    public function up(): void
    {
        $exists = $this->db->table('wl_meta_tags')->where('page_url', 'order-invoice')->countAllResults();
        $exists || $this->db->table('wl_meta_tags')->insert([
            'is_fixed' => 'Y', 'entity_type' => 'cart/invoice', 'entity_id' => 0, 'page_url' => 'order-invoice',
            'meta_title' => 'Invoice | CustomCart', 'meta_description' => '', 'meta_keyword' => '', 'status' => '1',
        ]);
    }

    public function down(): void
    {
        $this->db->table('wl_meta_tags')->where(['page_url' => 'order-invoice', 'entity_type' => 'cart/invoice'])->delete();
    }
}
