<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Expected delivery per delivery method (Sitepanel > Manage Shipping):
 * wl_shipping.delivery_min_days / delivery_max_days (existing methods: 3-5,
 * the rule used until now), copied onto each order when it is placed
 * (wl_order, NULL for older orders: they keep showing 3-5 days). See
 * expected_delivery() in the shop helper.
 */
class DeliveryDays extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('wl_shipping', [
            'delivery_min_days' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 3, 'after' => 'free_above', 'comment' => 'Expected delivery: from (days after the order)'],
            'delivery_max_days' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 5, 'after' => 'delivery_min_days', 'comment' => 'Expected delivery: to (days after the order)'],
        ]);
        $this->forge->addColumn('wl_order', [
            'delivery_min_days' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'after' => 'shipping_amount', 'comment' => 'From the delivery method when placed'],
            'delivery_max_days' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'after' => 'delivery_min_days', 'comment' => 'From the delivery method when placed'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('wl_order', ['delivery_min_days', 'delivery_max_days']);
        $this->forge->dropColumn('wl_shipping', ['delivery_min_days', 'delivery_max_days']);
    }
}
