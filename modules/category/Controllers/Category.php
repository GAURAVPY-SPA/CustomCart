<?php

namespace Modules\Category\Controllers;

use Modules\Category\Models\CategoryModel;
use Modules\Products\Controllers\Products;

/**
 * Category pages (Category::index):
 *
 *   /<category>                          a top-level category
 *   /<category>/<sub>                    a sub category
 *   /<category>/<sub>/<sub-sub>          a sub sub category (CategoryModel::MAX_DEPTH)
 *
 * Every category page is a wl_meta_tags row (entity_type category/index,
 * entity_id = category_id, page_url = the category's friendly_url), so the
 * whole requested path is matched at once by app/Config/ModuleRoutes.php.
 * index() then checks that the category is public, that the path really is
 * its ancestors' slugs then its own (a stale or edited row can't serve a
 * wrong hierarchy) and that it is within MAX_DEPTH levels; anything else is
 * the 404 page. As in the reference, a category with sub-categories lists
 * them (design: sub-category.htm), one without lists its products and those
 * of its sub-categories (listing.htm) — rendered by the store's catalog
 * pages (Products::category_page()).
 */
class Category extends Products
{
    /**
     * @param int|string $category_id The entity_id of the category's wl_meta_tags row
     */
    public function index($category_id = 0): string
    {
        if (preg_match('/^[1-9][0-9]*$/D', (string) $category_id) !== 1) {
            $this->not_found_page();
        }

        $category = $this->category_model->get_public_category((int) $category_id);
        if ($category === false) {
            $this->unavailable('Category::index could not load category ' . $category_id);
        }
        if ($category === null) {
            $this->not_found_page();
        }

        $requested = trim((string) preg_replace('#/+#', '/', $this->request->getPath()), '/');
        $expected  = $this->category_model->expected_url((int) $category['category_id']);
        $depth     = $this->category_model->depth((int) $category['category_id']);
        if ($expected === false || $depth === false) {
            $this->unavailable('Category::index could not check the address of category ' . $category_id);
        }
        if ($requested !== $category['friendly_url'] || $requested !== $expected || $depth > CategoryModel::MAX_DEPTH) {
            log_message('warning', 'Category::index: "' . $requested . '" is not the address of category ' . $category_id . ' ("' . $expected . '", level ' . $depth . ').');
            $this->not_found_page();
        }

        return $this->category_page($category);
    }
}
