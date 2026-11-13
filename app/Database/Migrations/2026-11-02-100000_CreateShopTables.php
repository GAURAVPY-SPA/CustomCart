<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Online store (reference: catalog, cart, order and customer
 * tables). Table and column names are the reference's; columns the reference
 * code never used are left out (xls_type, *_strtotime, the exclusive-deal and
 * legacy wl_product_attributes columns, SEO columns of wl_categories — SEO
 * lives in wl_meta_tags), and every table is InnoDB with foreign keys.
 *
 * Catalog
 *  - wl_categories: nested by parent_id (any depth); friendly_url is the full
 *    address (online-store/<parent>/<child>), as the reference built it.
 *  - wl_products: category_links = the category and its ancestors (comma
 *    list), so a category page lists the products of its sub-categories.
 *    product_quantity = total available stock (sum of wl_product_stock).
 *  - wl_product_stock: one row per colour × size (0 = none), as the
 *    reference's stock matrix: price, discounted price, available quantity
 *    (product_quantity), sold quantity (used_quantity) and the low-stock
 *    threshold (inventory).
 *  - wl_products_media: product photos; color_id ties a photo to a colour.
 *
 * Customers: wl_customers (status 3 = guest checkout), passwords hashed with
 * password_hash() (the reference stored them reversibly encrypted), password
 * reset by a hashed, expiring token.
 *
 * Orders: wl_order / wl_orders_products keep a copy of everything shown on an
 * invoice. Stock is taken when an order is placed and given back once
 * (is_stock_back) when an item or the order is cancelled.
 */
class CreateShopTables extends Migration
{
    protected array $attributes = ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci'];

    public function up(): void
    {
        $status = static fn (string $default = '1') => ['type' => 'ENUM', 'constraint' => ['0', '1', '2'], 'default' => $default, 'comment' => '1=Active,0=Inactive,2=Deleted'];
        $flag   = static fn () => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '0'];
        $money  = static fn () => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'];

        // ---------------------------------------------------------------- catalog
        $this->forge->addField([
            'category_id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'parent_id'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'category_name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'friendly_url'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'category_image'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'category_alt'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'category_description' => ['type' => 'TEXT', 'null' => true],
            'is_home'              => $flag(),
            'is_menu'              => $flag(),
            'sort_order'           => ['type' => 'INT', 'default' => 0],
            'status'               => $status(),
            'date_added'           => ['type' => 'DATETIME'],
            'date_modified'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('category_id');
        $this->forge->addUniqueKey('friendly_url', 'uq_category_url');
        $this->forge->addKey(['parent_id', 'status', 'sort_order'], false, false, 'idx_category_parent');
        $this->forge->createTable('wl_categories', true, $this->attributes);

        foreach (['brand' => 'wl_brands', 'material' => 'wl_materials'] as $name => $table) {
            $this->forge->addField([
                $name . '_id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                $name . '_name'         => ['type' => 'VARCHAR', 'constraint' => 100],
                $name . '_image'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'sort_order'            => ['type' => 'INT', 'default' => 0],
                'status'                => $status(),
                $name . '_date_added'   => ['type' => 'DATETIME'],
                $name . '_date_updated' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey($name . '_id');
            $this->forge->addKey($name . '_name', false, false, 'idx_' . $name . '_name');
            $this->forge->createTable($table, true, $this->attributes);
        }

        $this->forge->addField([
            'color_id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'color_name'         => ['type' => 'VARCHAR', 'constraint' => 32],
            'color_code'         => ['type' => 'VARCHAR', 'constraint' => 7, 'comment' => '#rrggbb'],
            'sort_order'         => ['type' => 'INT', 'default' => 0],
            'status'             => $status(),
            'color_date_added'   => ['type' => 'DATETIME'],
            'color_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('color_id');
        $this->forge->addKey('color_name', false, false, 'idx_color_name');
        $this->forge->createTable('wl_colors', true, $this->attributes);

        $this->forge->addField([
            'size_id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'size_name'         => ['type' => 'VARCHAR', 'constraint' => 32],
            'sort_order'        => ['type' => 'INT', 'default' => 0],
            'status'            => $status(),
            'size_date_added'   => ['type' => 'DATETIME'],
            'size_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('size_id');
        $this->forge->addKey('size_name', false, false, 'idx_size_name');
        $this->forge->createTable('wl_sizes', true, $this->attributes);

        $this->forge->addField([
            'products_id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id'              => ['type' => 'INT', 'unsigned' => true],
            'category_links'           => ['type' => 'VARCHAR', 'constraint' => 255, 'comment' => 'category_id and its ancestors, comma separated'],
            'brand_id'                 => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'material_id'              => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'product_name'             => ['type' => 'VARCHAR', 'constraint' => 200],
            'friendly_url'             => ['type' => 'VARCHAR', 'constraint' => 200],
            'product_code'             => ['type' => 'VARCHAR', 'constraint' => 64],
            'product_alt'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'product_price'            => $money(),
            'product_discounted_price' => $money(),
            'product_discount_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00'],
            // The lowest selling price over the product's variants and its regular
            // price (listing cards, price filter and sort); kept up to date by
            // ProductModel::refresh_summary(). The reference overwrote
            // product_price with it instead.
            'display_price'            => $money(),
            'display_regular_price'    => $money(),
            'products_description'     => ['type' => 'MEDIUMTEXT', 'null' => true],
            'products_specification'   => ['type' => 'MEDIUMTEXT', 'null' => true],
            'product_weight'           => ['type' => 'VARCHAR', 'constraint' => 55, 'null' => true],
            'product_quantity'         => ['type' => 'INT', 'default' => 0, 'comment' => 'Available stock, sum of wl_product_stock'],
            'is_new'                   => $flag(),
            'is_hot'                   => $flag(),
            'is_featured'              => $flag(),
            'is_popular'               => $flag(),
            'products_viewed'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status'                   => $status(),
            'product_added_date'       => ['type' => 'DATETIME'],
            'product_updated_date'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('products_id');
        $this->forge->addUniqueKey('friendly_url', 'uq_product_url');
        $this->forge->addUniqueKey('product_code', 'uq_product_code');
        $this->forge->addKey(['status', 'category_id'], false, false, 'idx_product_status_category');
        $this->forge->addForeignKey('category_id', 'wl_categories', 'category_id', 'CASCADE', 'RESTRICT', 'fk_products_category');
        $this->forge->addForeignKey('brand_id', 'wl_brands', 'brand_id', 'CASCADE', 'SET NULL', 'fk_products_brand');
        $this->forge->addForeignKey('material_id', 'wl_materials', 'material_id', 'CASCADE', 'SET NULL', 'fk_products_material');
        $this->forge->createTable('wl_products', true, $this->attributes);

        foreach (['color' => 'wl_product_colors', 'size' => 'wl_product_sizes'] as $name => $table) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'product_id'   => ['type' => 'INT', 'unsigned' => true],
                $name . '_id'  => ['type' => 'INT', 'unsigned' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['product_id', $name . '_id'], 'uq_product_' . $name);
            $this->forge->addForeignKey('product_id', 'wl_products', 'products_id', 'CASCADE', 'CASCADE', 'fk_product_' . $name . 's_product');
            $this->forge->addForeignKey($name . '_id', 'wl_' . $name . 's', $name . '_id', 'CASCADE', 'RESTRICT', 'fk_product_' . $name . 's_' . $name);
            $this->forge->createTable($table, true, $this->attributes);
        }

        $this->forge->addField([
            'stock_id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'               => ['type' => 'INT', 'unsigned' => true],
            'product_color'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'color_id, 0 = none'],
            'product_size'             => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'size_id, 0 = none'],
            'product_price'            => $money(),
            'product_discounted_price' => $money(),
            'product_discount_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00'],
            'product_quantity'         => ['type' => 'INT', 'default' => 0, 'comment' => 'Available'],
            'used_quantity'            => ['type' => 'INT', 'default' => 0, 'comment' => 'Sold'],
            'inventory'                => ['type' => 'INT', 'default' => 0, 'comment' => 'Low-stock threshold'],
        ]);
        $this->forge->addPrimaryKey('stock_id');
        $this->forge->addUniqueKey(['product_id', 'product_color', 'product_size'], 'uq_stock_variant');
        $this->forge->addForeignKey('product_id', 'wl_products', 'products_id', 'CASCADE', 'CASCADE', 'fk_stock_product');
        $this->forge->createTable('wl_product_stock', true, $this->attributes);

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'products_id'      => ['type' => 'INT', 'unsigned' => true],
            'color_id'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => '0 = every colour'],
            'media_type'       => ['type' => 'ENUM', 'constraint' => ['photo'], 'default' => 'photo'],
            'media'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_default'       => ['type' => 'ENUM', 'constraint' => ['Y', 'N'], 'default' => 'N'],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'media_date_added' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['products_id', 'sort_order'], false, false, 'idx_media_product');
        $this->forge->addForeignKey('products_id', 'wl_products', 'products_id', 'CASCADE', 'CASCADE', 'fk_media_product');
        $this->forge->createTable('wl_products_media', true, $this->attributes);

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'         => ['type' => 'INT', 'unsigned' => true],
            'related_id'         => ['type' => 'INT', 'unsigned' => true],
            'sort_order'         => ['type' => 'INT', 'default' => 0],
            'related_date_added' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['product_id', 'related_id'], 'uq_related');
        $this->forge->addForeignKey('product_id', 'wl_products', 'products_id', 'CASCADE', 'CASCADE', 'fk_related_product');
        $this->forge->addForeignKey('related_id', 'wl_products', 'products_id', 'CASCADE', 'CASCADE', 'fk_related_related');
        $this->forge->createTable('wl_products_related', true, $this->attributes);

        // ---------------------------------------------------------------- customers
        $this->forge->addField([
            'customers_id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_name'            => ['type' => 'VARCHAR', 'constraint' => 120, 'comment' => 'Email, used to sign in'],
            'password'             => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => '', 'comment' => 'password_hash(); empty for guests'],
            'first_name'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'last_name'            => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => ''],
            'mobile_number'        => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'address'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'city'                 => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'state'                => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'country'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'zipcode'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status'               => ['type' => 'ENUM', 'constraint' => ['0', '1', '2', '3'], 'default' => '1', 'comment' => '1=Active,0=Inactive,2=Deleted,3=Guest'],
            'newsletter'           => $flag(),
            'account_created_date' => ['type' => 'DATETIME'],
            'current_login'        => ['type' => 'DATETIME', 'null' => true],
            'last_login_date'      => ['type' => 'DATETIME', 'null' => true],
            'ip_address'           => ['type' => 'VARCHAR', 'constraint' => 45, 'default' => ''],
            'number_of_login_try'  => ['type' => 'INT', 'default' => 0],
            'block_time'           => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Sign-in refused until then'],
            'reset_token'          => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'comment' => 'sha256 of the emailed token'],
            'reset_expires'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('customers_id');
        $this->forge->addUniqueKey('user_name', 'uq_customer_email');
        $this->forge->addKey('status', false, false, 'idx_customer_status');
        $this->forge->createTable('wl_customers', true, $this->attributes);

        $this->forge->addField([
            'address_id'     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id'    => ['type' => 'INT', 'unsigned' => true],
            'first_name'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'last_name'      => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => ''],
            'address'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'mobile'         => ['type' => 'VARCHAR', 'constraint' => 32],
            'zipcode'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'city'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'state'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'country'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'address_type'   => ['type' => 'ENUM', 'constraint' => ['Bill', 'Ship'], 'default' => 'Ship'],
            'default_status' => ['type' => 'ENUM', 'constraint' => ['Y', 'N'], 'default' => 'N'],
            'reciv_date'     => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('address_id');
        $this->forge->addKey(['customer_id', 'address_type', 'default_status'], false, false, 'idx_address_customer');
        $this->forge->addForeignKey('customer_id', 'wl_customers', 'customers_id', 'CASCADE', 'CASCADE', 'fk_address_customer');
        $this->forge->createTable('wl_customers_address_book', true, $this->attributes);

        $this->forge->addField([
            'wishlists_id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id'          => ['type' => 'INT', 'unsigned' => true],
            'product_id'           => ['type' => 'INT', 'unsigned' => true],
            'wishlists_date_added' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('wishlists_id');
        $this->forge->addUniqueKey(['customer_id', 'product_id'], 'uq_wishlist');
        $this->forge->addForeignKey('customer_id', 'wl_customers', 'customers_id', 'CASCADE', 'CASCADE', 'fk_wishlist_customer');
        $this->forge->addForeignKey('product_id', 'wl_products', 'products_id', 'CASCADE', 'CASCADE', 'fk_wishlist_product');
        $this->forge->createTable('wl_wishlists', true, $this->attributes);

        $this->forge->addField([
            'review_id'   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'rev_type'    => ['type' => 'ENUM', 'constraint' => ['P'], 'default' => 'P', 'comment' => 'P=Product'],
            'prod_id'     => ['type' => 'INT', 'unsigned' => true],
            'mem_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'poster_name' => ['type' => 'VARCHAR', 'constraint' => 60],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'rate'        => ['type' => 'TINYINT', 'unsigned' => true],
            'comment'     => ['type' => 'TEXT'],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'default' => ''],
            'posted_date' => ['type' => 'DATETIME'],
            'status'      => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '0', 'comment' => '1=Approved,0=Pending'],
        ]);
        $this->forge->addPrimaryKey('review_id');
        $this->forge->addKey(['prod_id', 'status'], false, false, 'idx_review_product');
        $this->forge->addForeignKey('prod_id', 'wl_products', 'products_id', 'CASCADE', 'CASCADE', 'fk_review_product');
        $this->forge->createTable('wl_review', true, $this->attributes);

        // ---------------------------------------------------------------- shipping & orders
        $this->forge->addField([
            'shipping_id'   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'shipping_type' => ['type' => 'VARCHAR', 'constraint' => 100],
            'shipment_rate' => $money(),
            'free_above'    => $money() + ['comment' => 'Free when the cart reaches this amount, 0 = never'],
            'is_default'    => $flag(),
            'sort_order'    => ['type' => 'INT', 'default' => 0],
            'status'        => $status(),
            'added_date'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('shipping_id');
        $this->forge->createTable('wl_shipping', true, $this->attributes);

        $order_statuses = ['Pending', 'Ready For Dispatch', 'Dispatched', 'Delivered', 'Canceled', 'Rejected', 'Return', 'Closed', 'Deleted'];
        $this->forge->addField([
            'order_id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'customers_id'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'customer_type'          => ['type' => 'ENUM', 'constraint' => ['Member', 'Guest'], 'default' => 'Member'],
            'invoice_number'         => ['type' => 'VARCHAR', 'constraint' => 30],
            'first_name'             => ['type' => 'VARCHAR', 'constraint' => 50],
            'last_name'              => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => ''],
            'email'                  => ['type' => 'VARCHAR', 'constraint' => 120],
            'mobile_number'          => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => ''],
            'shipping_name'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'shipping_address'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'shipping_mobile'        => ['type' => 'VARCHAR', 'constraint' => 32],
            'shipping_zipcode'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'shipping_country'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'shipping_state'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'shipping_city'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'delivery_notes'         => ['type' => 'TEXT', 'null' => true],
            'shipping_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'shipping_method'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'discount_coupon_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'coupon_code'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'coupon_discount_amount' => $money(),
            'shipping_amount'        => $money(),
            'total_amount'           => $money() + ['comment' => 'Items, GST included, before discount and shipping'],
            'vat_amount'             => $money() + ['comment' => 'GST included in the payable amount'],
            'vat_applied_cent'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00'],
            'price_without_tax'      => $money(),
            'grand_total'            => $money() + ['comment' => 'total_amount - coupon_discount_amount + shipping_amount'],
            'currency_code'          => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'SGD'],
            'currency_symbol'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'S$'],
            'currency_value'         => ['type' => 'DECIMAL', 'constraint' => '14,6', 'default' => '1.000000'],
            'order_status'           => ['type' => 'ENUM', 'constraint' => $order_statuses, 'default' => 'Pending'],
            'order_received_date'    => ['type' => 'DATETIME'],
            'order_delivery_date'    => ['type' => 'DATETIME', 'null' => true],
            'payment_method'         => ['type' => 'VARCHAR', 'constraint' => 30],
            'payment_status'         => ['type' => 'ENUM', 'constraint' => ['Paid', 'Unpaid'], 'default' => 'Unpaid'],
            'payment_date'           => ['type' => 'DATETIME', 'null' => true],
            'courier_company_name'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'bill_number'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'Tracking number'],
            'bill_url'               => ['type' => 'VARCHAR', 'constraint' => 455, 'null' => true, 'comment' => 'Tracking link'],
            'admin_comment'          => ['type' => 'TEXT', 'null' => true],
            'ip_address'             => ['type' => 'VARCHAR', 'constraint' => 45, 'default' => ''],
        ]);
        $this->forge->addPrimaryKey('order_id');
        $this->forge->addUniqueKey('invoice_number', 'uq_order_invoice');
        $this->forge->addKey(['customers_id', 'order_status'], false, false, 'idx_order_customer');
        $this->forge->addKey(['order_status', 'order_received_date'], false, false, 'idx_order_status_date');
        $this->forge->createTable('wl_order', true, $this->attributes);

        $item_statuses = ['Pending', 'Ready For Dispatch', 'Dispatched', 'Delivered', 'Cancelled', 'Rejected', 'Returned', 'Closed'];
        $request       = static fn () => ['type' => 'ENUM', 'constraint' => ['0', '1', '2', '3'], 'default' => '0', 'comment' => '0=None,1=Requested,2=Accepted,3=Rejected'];
        $this->forge->addField([
            'orders_products_id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'orders_id'                  => ['type' => 'INT', 'unsigned' => true],
            'sub_order_id'               => ['type' => 'VARCHAR', 'constraint' => 40],
            'products_id'                => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'stock_id'                   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'product_name'               => ['type' => 'VARCHAR', 'constraint' => 200],
            'product_code'               => ['type' => 'VARCHAR', 'constraint' => 64],
            'product_brand'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'product_color'              => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'product_color_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'product_color_code'         => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true],
            'product_size'               => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'product_size_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'product_image'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'File in uploads/products'],
            'product_price'              => $money(),
            'quantity'                   => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'coupon_discount_percentage' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => '0.00'],
            'coupon_discount_amounts'    => $money(),
            'product_order_status'       => ['type' => 'ENUM', 'constraint' => $item_statuses, 'default' => 'Pending'],
            'is_cancel'                  => $request(),
            'is_return'                  => $request(),
            'reason'                     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'reason_comment'             => ['type' => 'TEXT', 'null' => true],
            'request_date'               => ['type' => 'DATETIME', 'null' => true],
            'is_stock_back'              => $flag() + ['comment' => '1 = stock given back'],
        ]);
        $this->forge->addPrimaryKey('orders_products_id');
        $this->forge->addKey('orders_id', false, false, 'idx_item_order');
        $this->forge->addKey('products_id', false, false, 'idx_item_product');
        $this->forge->addForeignKey('orders_id', 'wl_order', 'order_id', 'CASCADE', 'CASCADE', 'fk_item_order');
        $this->forge->createTable('wl_orders_products', true, $this->attributes);
    }

    public function down(): void
    {
        foreach (['wl_orders_products', 'wl_order', 'wl_shipping', 'wl_review', 'wl_wishlists', 'wl_customers_address_book', 'wl_customers',
            'wl_products_related', 'wl_products_media', 'wl_product_stock', 'wl_product_sizes', 'wl_product_colors', 'wl_products',
            'wl_sizes', 'wl_colors', 'wl_materials', 'wl_brands', 'wl_categories'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
