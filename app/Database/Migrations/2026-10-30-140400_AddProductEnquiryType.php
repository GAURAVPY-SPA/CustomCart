<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Other Management > Manage Product Enquiry: wl_enquiry.type '3'
 * (product enquiry, as in the reference). Existing values stay as they are.
 */
class AddProductEnquiryType extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('wl_enquiry', [
            'type' => ['type' => 'ENUM', 'constraint' => ['1', '2', '3'], 'default' => '1', 'null' => false, 'comment' => '1=Contact us,2=Service,3=Product'],
        ]);
    }

    public function down(): void
    {
        // Product enquiries would not fit the old column: they are removed first.
        $this->db->table('wl_enquiry')->where('type', '3')->delete();
        $this->forge->modifyColumn('wl_enquiry', [
            'type' => ['type' => 'ENUM', 'constraint' => ['1', '2'], 'default' => '1', 'null' => false, 'comment' => '1=Contact us,2=Service'],
        ]);
    }
}
