<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use Modules\Review\Models\ReviewModel;

/**
 * Sitepanel > Product Management > Manage Products > Product Reviews (wl_review).
 *
 * As in the reference (sitepanel/products/review), reviews are part of Manage
 * Products: they have no menu item of their own, are opened from the
 * products list, and use the Manage Products section's permissions
 * (section() = 'products'). Reviews posted on the
 * product pages wait as Inactive until activated here; only active ones are
 * shown and counted in the product rating. Search by product, name or email,
 * filter by status or product (?product=<id>).
 *
 *   index          store/reviews/review_list (keyword, status, product)
 *   view/<id>      store/reviews/review_detail
 *   status/<id>    POST status=1 activates, status=0 deactivates
 *   delete/<id>    POST
 *   bulk           POST, activate / deactivate / delete the selected rows
 */
class Reviews extends AdminController
{
    protected string $base           = 'sitepanel/reviews';
    protected string $item           = 'Testimonial';
    protected string $menu_highlight = 'reviews'; // left menu: Manage Product Testimonials

    protected ReviewModel $review_model;

    /**
     * Permissions of Manage Products (the reference's products/review).
     */
    protected function section(): string
    {
        return 'products';
    }

    protected function init(): void
    {
        $this->review_model = new ReviewModel();
    }

    public function index(): string
    {
        $keyword    = $this->keyword_param();
        $status     = $this->status_param((string) $this->request->getGet('status'));
        $product_id = max(0, (int) $this->request->getGet('product'));
        $page       = $this->current_page();
        $res        = $this->review_model->get_reviews(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status, $product_id);

        $data['heading_title'] = 'Manage Product Testimonials';
        $data['breadcrumbs']   = ['Product Testimonials' => null];
        $data['res']           = $res;
        $data['total']         = $this->review_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;
        $data['product_id']    = $product_id;

        return $this->render('store/reviews/review_list', $data);
    }

    /**
     * @param int|string $id
     */
    public function view($id = 0): string
    {
        $res = $this->record_or_back($this->review_model->get_review_by_id($id));

        $data['heading_title'] = 'Product Testimonial';
        $data['breadcrumbs']   = ['Product Testimonials' => site_url($this->base), 'Testimonial #' . (int) $res['review_id'] => null];
        $data['res']           = $res;

        return $this->render('store/reviews/review_detail', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(ReviewModel::TABLE, ReviewModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record(ReviewModel::TABLE, ReviewModel::PRIMARY_KEY, $id);
    }

    public function bulk(): void
    {
        $query = array_filter([
            'keyword' => $this->keyword_param((string) $this->request->getPost('keyword')),
            'status'  => $this->status_param((string) $this->request->getPost('status_filter')),
            'product' => max(0, (int) $this->request->getPost('product')) ?: '',
        ], 'strlen');

        $this->process_bulk_action($this->review_model, 'get_reviews_by_ids', 'Review', $query);
    }
}
