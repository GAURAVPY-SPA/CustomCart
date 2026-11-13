<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A product can be filed in several categories (wl_product_categories, like
 * wl_product_colors / wl_product_sizes): one row per product × category,
 * unique. wl_products.category_id stays as the product's main category
 * (breadcrumbs, the category shown on its card) and is always one of its rows;
 * category_links stays as the union of its categories and their ancestors.
 *
 * Every existing product is filed in its current category, so no assignment
 * is lost.
 */
class CreateProductCategoriesTable extends Migration
{
    protected array $attributes = ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci'];

    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'  => ['type' => 'INT', 'unsigned' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['product_id', 'category_id'], 'uq_product_category');
        $this->forge->addKey('category_id', false, false, 'idx_product_categories_category');
        $this->forge->addForeignKey('product_id', 'wl_products', 'products_id', 'CASCADE', 'CASCADE', 'fk_product_categories_product');
        $this->forge->addForeignKey('category_id', 'wl_categories', 'category_id', 'CASCADE', 'RESTRICT', 'fk_product_categories_category');
        $this->forge->createTable('wl_product_categories', true, $this->attributes);

        $this->db->query('INSERT IGNORE INTO wl_product_categories (product_id, category_id)
            SELECT products_id, category_id FROM wl_products WHERE category_id IS NOT NULL AND category_id > 0 ORDER BY products_id');
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_product_categories', true);
    }
}
