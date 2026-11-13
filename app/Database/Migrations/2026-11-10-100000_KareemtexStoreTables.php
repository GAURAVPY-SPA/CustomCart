<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CustomCart Phase 2 tables and columns (quotation: product FAQs, certificates
 * and brochures, industries, running marquee, category banners, refer a
 * friend, returns / refunds, Saudi delivery addresses):
 *
 *   wl_products_faq     FAQs assigned to products (Manage FAQs)
 *   wl_faq.is_general   shown on the website's FAQ page (else product pages only)
 *   wl_certificates     Manage Certificates & Brochures (cert_type)
 *   wl_industries       Manage Industries
 *   wl_marquee          Manage Running Marquee
 *   wl_banners          category_id + sort_order (Manage Banners, category wise)
 *   wl_referrals        Refer a Friend messages
 *   wl_orders_products  refund_status, refund_amount, refund_date, refund_note
 *   wl_order / wl_customers_address_book  alternate mobile and landmark
 *
 * Only adds tables and columns; down() removes exactly these.
 */
class KareemtexStoreTables extends Migration
{
    protected array $attributes = ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci'];

    public function up(): void
    {
        $this->forge->addField([
            'products_id' => ['type' => 'INT', 'unsigned' => true],
            'faq_id'      => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['products_id', 'faq_id']);
        $this->forge->addKey('faq_id');
        $this->forge->createTable('wl_products_faq', true, $this->attributes);

        if (! $this->db->fieldExists('is_general', 'wl_faq')) {
            $this->forge->addColumn('wl_faq', [
                'is_general' => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'after' => 'faq_answer', 'comment' => '1 = shown on the FAQ page'],
            ]);
        }

        $this->forge->addField([
            'certificate_id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'cert_type'      => ['type' => 'ENUM', 'constraint' => ['certificate', 'brochure'], 'default' => 'certificate'],
            'title'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cert_image'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'cert_file'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
            'status'         => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1'],
            'date_added'     => ['type' => 'DATETIME'],
            'date_updated'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('certificate_id');
        $this->forge->addKey(['cert_type', 'status', 'sort_order']);
        $this->forge->createTable('wl_certificates', true, $this->attributes);

        $this->forge->addField([
            'industry_id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'industry_name'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'friendly_url'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'industry_image'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'short_description' => ['type' => 'TEXT', 'null' => true],
            'highlights'        => ['type' => 'TEXT', 'null' => true, 'comment' => 'one point per line'],
            'sort_order'        => ['type' => 'INT', 'default' => 0],
            'status'            => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1'],
            'date_added'        => ['type' => 'DATETIME'],
            'date_updated'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('industry_id');
        $this->forge->addUniqueKey('friendly_url', 'uq_industries_url');
        $this->forge->createTable('wl_industries', true, $this->attributes);

        $this->forge->addField([
            'marquee_id'   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'marquee_text' => ['type' => 'VARCHAR', 'constraint' => 255],
            'marquee_url'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'   => ['type' => 'INT', 'default' => 0],
            'status'       => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1'],
            'date_added'   => ['type' => 'DATETIME'],
            'date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('marquee_id');
        $this->forge->createTable('wl_marquee', true, $this->attributes);

        if (! $this->db->fieldExists('category_id', 'wl_banners')) {
            $this->forge->addColumn('wl_banners', [
                'category_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'banner_position', 'comment' => 'wl_categories (position Category Pages)'],
                'sort_order'  => ['type' => 'INT', 'default' => 0, 'after' => 'banner_title'],
            ]);
        }

        $this->forge->addField([
            'referral_id'   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sender_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'sender_email'  => ['type' => 'VARCHAR', 'constraint' => 120],
            'friend_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'friend_email'  => ['type' => 'VARCHAR', 'constraint' => 120],
            'message'       => ['type' => 'TEXT', 'null' => true],
            'page_url'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'products_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'customers_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45],
            'referral_date' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('referral_id');
        $this->forge->addKey('referral_date');
        $this->forge->createTable('wl_referrals', true, $this->attributes);

        if (! $this->db->fieldExists('refund_status', 'wl_orders_products')) {
            $this->forge->addColumn('wl_orders_products', [
                'refund_status' => ['type' => 'ENUM', 'constraint' => ['', 'Pending', 'Refunded', 'Rejected'], 'default' => '', 'after' => 'is_stock_back'],
                'refund_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00', 'after' => 'refund_status'],
                'refund_date'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'refund_amount'],
                'refund_note'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'refund_date'],
            ]);
        }

        if (! $this->db->fieldExists('shipping_landmark', 'wl_order')) {
            $this->forge->addColumn('wl_order', [
                'shipping_alt_mobile' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'after' => 'shipping_mobile'],
                'shipping_landmark'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'shipping_address'],
            ]);
        }

        if (! $this->db->fieldExists('landmark', 'wl_customers_address_book')) {
            $this->forge->addColumn('wl_customers_address_book', [
                'alt_mobile' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'after' => 'mobile'],
                'landmark'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'address'],
            ]);
        }
    }

    public function down(): void
    {
        foreach (['wl_products_faq', 'wl_certificates', 'wl_industries', 'wl_marquee', 'wl_referrals'] as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->fieldExists('is_general', 'wl_faq') && $this->forge->dropColumn('wl_faq', 'is_general');
        $this->db->fieldExists('category_id', 'wl_banners') && $this->forge->dropColumn('wl_banners', ['category_id', 'sort_order']);
        $this->db->fieldExists('refund_status', 'wl_orders_products') && $this->forge->dropColumn('wl_orders_products', ['refund_status', 'refund_amount', 'refund_date', 'refund_note']);
        $this->db->fieldExists('shipping_landmark', 'wl_order') && $this->forge->dropColumn('wl_order', ['shipping_alt_mobile', 'shipping_landmark']);
        $this->db->fieldExists('landmark', 'wl_customers_address_book') && $this->forge->dropColumn('wl_customers_address_book', ['alt_mobile', 'landmark']);
    }
}
