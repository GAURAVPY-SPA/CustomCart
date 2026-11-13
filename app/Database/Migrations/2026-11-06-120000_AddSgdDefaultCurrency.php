<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Makes Singapore dollars the store's default currency in wl_currencies
 * (read by display_price() / site_currency()). The table came over from the
 * CodeIgniter 3 reference with SAR as its only active default and no SGD row;
 * this site sells and records orders in SGD. Other rows are kept as they are,
 * only their default flag is cleared (one default currency, as in the reference).
 */
class AddSgdDefaultCurrency extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('wl_currencies')) {
            return;
        }

        $table = $this->db->table('wl_currencies');
        $now   = date('Y-m-d H:i:s');

        if ($table->where('code', 'SGD')->countAllResults() === 0) {
            $table->insert([
                'title'         => 'Singapore Dollar',
                'code'          => 'SGD',
                'symbol_left'   => 'S$',
                'symbol_right'  => '',
                'curr_image'    => '',
                'decimal_place' => '2',
                'value'         => 1,
                'is_default'    => 'N',
                'status'        => 1,
                'date_modified' => $now,
            ]);
        }

        $table->where('code !=', 'SGD')->where('is_default', 'Y')->update(['is_default' => 'N', 'date_modified' => $now]);
        $table->where('code', 'SGD')->update(['is_default' => 'Y', 'status' => 1, 'date_modified' => $now]);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('wl_currencies')) {
            return;
        }

        // Back to the imported state: SAR the default, no SGD row.
        $table = $this->db->table('wl_currencies');
        $table->where('code', 'SGD')->delete();
        $table->where('code', 'SAR')->update(['is_default' => 'Y', 'date_modified' => date('Y-m-d H:i:s')]);
    }
}
