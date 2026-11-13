<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * Category and product pages at the site root, as in the reference (its
 * category and product addresses had no prefix):
 *
 *  - categories: wl_categories.friendly_url and the page_url of their
 *    wl_meta_tags rows lose the 'online-store/' prefix
 *    (online-store/gift-sets/starter-bundles => gift-sets/starter-bundles),
 *    and the rows' entity_type becomes the reference's 'category/index'
 *    (routed to Modules\Category\Controllers\Category::index/<category_id>);
 *  - products: the page_url of their 'products/detail' rows loses the
 *    'products/' prefix (products/volunteer-tee => volunteer-tee);
 *    wl_products.friendly_url already is the bare slug.
 *
 * Rows are changed in place (meta_id, entity_id and the SEO data are kept).
 * Before anything is written, every new address is checked against all
 * other wl_meta_tags rows and the reserved segments; on a clash the
 * migration stops and changes nothing. The store's own pages
 * (/online-store, /products) are not touched. down() restores the prefixes.
 */
class StoreUrlsAtRoot extends Migration
{
    private const CATEGORY_PREFIX = 'online-store/';
    private const PRODUCT_PREFIX  = 'products/';
    private const RESERVED        = ['sitepanel', 'uploads', 'uploaded_files'];

    public function up(): void
    {
        $this->move(
            ['products/category' => 'category/index', 'products/detail' => 'products/detail'],
            static fn (string $type, string $url) => match ($type) {
                'products/category' => str_starts_with($url, self::CATEGORY_PREFIX) ? substr($url, strlen(self::CATEGORY_PREFIX)) : $url,
                'products/detail'   => str_starts_with($url, self::PRODUCT_PREFIX) ? substr($url, strlen(self::PRODUCT_PREFIX)) : $url,
            },
        );
    }

    public function down(): void
    {
        $this->move(
            ['category/index' => 'products/category', 'products/detail' => 'products/detail'],
            static fn (string $type, string $url) => match ($type) {
                'category/index'  => str_starts_with($url, self::CATEGORY_PREFIX) ? $url : self::CATEGORY_PREFIX . $url,
                'products/detail' => str_starts_with($url, self::PRODUCT_PREFIX) ? $url : self::PRODUCT_PREFIX . $url,
            },
        );
    }

    /**
     * @param array<string, string> $types  current entity_type => new entity_type
     * @param callable              $remap  (current entity_type, page_url) => new page_url
     */
    private function move(array $types, callable $remap): void
    {
        $rows  = $this->db->table('wl_meta_tags')->select('meta_id, entity_type, entity_id, page_url')->get()->getResultArray();
        $after = [];
        $moves = [];

        foreach ($rows as $row) {
            $url = $row['page_url'];
            if (isset($types[$row['entity_type']]) && (int) $row['entity_id'] > 0) {
                $url                       = $remap($row['entity_type'], $url);
                $moves[(int) $row['meta_id']] = $row + ['new_url' => $url, 'new_type' => $types[$row['entity_type']]];
                if (in_array(explode('/', $url)[0], self::RESERVED, true)) {
                    throw new RuntimeException('StoreUrlsAtRoot: "' . $url . '" would start with a reserved segment; nothing was changed.');
                }
            }
            if (isset($after[$url])) {
                throw new RuntimeException('StoreUrlsAtRoot: the address "' . $url . '" would be used twice; nothing was changed.');
            }
            $after[$url] = true;
        }

        $this->db->transException(true)->transStart();

        foreach ($moves as $meta_id => $row) {
            $this->db->table('wl_meta_tags')->where('meta_id', $meta_id)->update(['page_url' => $row['new_url'], 'entity_type' => $row['new_type']]);
            if ($row['new_type'] !== 'products/detail') {
                // Category rows: the category's own address follows (wl_products.friendly_url is already the slug).
                $this->db->table('wl_categories')->where('category_id', (int) $row['entity_id'])->update(['friendly_url' => $row['new_url']]);
            }
        }

        $this->db->transComplete();
        $this->db->transException(false);
    }
}
