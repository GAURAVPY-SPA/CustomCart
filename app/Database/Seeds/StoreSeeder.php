<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Online store set-up (php spark db:seed StoreSeeder):
 *
 *  1. the store's sitepanel menu / permission rows (tbl_admin_sections;
 *     privileges 1=View, 2=Add, 3=Edit, 4=Activate, 5=Deactivate, 6=Delete)
 *     in the reference sitepanel's groups, labels and order ($menu);
 *  2. the store's page addresses in wl_meta_tags, the URL registry
 *     (entity_type '<module>/<method>', as php spark make:module-route
 *     registers them) — the addresses the site header already links to;
 *  3. the delivery methods of the design (cart.htm: island-wide delivery
 *     6.00, free from 150; payment.htm: collect at the CustomCart store),
 *     only when wl_shipping is empty.
 *
 * Safe to run again: existing rows are left exactly as they are; an address
 * already used by another page is reported and skipped.
 */
class StoreSeeder extends Seeder
{
    /**
     * The sitepanel's store menu (tbl_admin_sections,
     * active rows): heading => [display order, [controller => [title, order, privileges, status]]].
     * Headings keep the reference's order (Product Management, Members
     * Management, Manage Orders, Manage Entities); the reference's Materials
     * row is inactive (hidden), as there. Product reviews have no row: as in
     * the reference (products/review) they belong to Manage Products.
     * Manage Product Enquiry and Manage Discount Coupons stay under Other
     * Management, where the reference has them too.
     */
    protected array $menu = [
        'Product Management' => [2, [
            'category'      => ['Manage Categories', 1, '1,2,3,4,5,6', '1'],
            'products'      => ['Manage Products', 2, '1,2,3,4,5,6', '1'],
            'products_bulk' => ['Manage Bulk Upload Product', 3, '1,2', '1'],
        ]],
        'Members Management' => [3, [
            'members' => ['Manage Members', 2, '1,3,4,5,6', '1'],
        ]],
        'Manage Orders' => [4, [
            'orders'         => ['Manage Orders', 1, '1,3,6', '1'],
            'order_products' => ['Manage Orders Products', 2, '1,3', '1'],
            'shipping'       => ['Manage Shipping', 3, '1,2,3,4,5,6', '1'],
            'reasons'        => ['Manage Reasons', 4, '1,2,3,4,5,6', '1'],
        ]],
        'Manage Entities' => [6, [
            'brand'    => ['Manage Brands', 1, '1,2,3,4,5,6', '1'],
            'color'    => ['Manage Colors', 2, '1,2,3,4,5,6', '1'],
            'size'     => ['Manage Size', 3, '1,2,3,4,5,6', '1'],
            'material' => ['Manage Materials', 4, '1,2,3,4,5,6', '0'],
        ]],
    ];

    /**
     * Rows of an earlier version of this seeder, removed: the single "Store
     * Management" heading, and the Product Reviews row (now part of Manage Products).
     */
    protected const OLD_HEADING  = 'Store Management';
    protected const OLD_SECTIONS = ['reviews'];

    /**
     * Store pages: entity_type => [address, SEO title, SEO description].
     */
    protected array $pages = [
        'products/index'          => ['online-store', 'Online Store | CustomCart', 'Shop industrial workwear and FR uniforms from CustomCart.'],
        'products/listing'        => ['products', 'All Products | CustomCart', 'Browse every CustomCart product by category, price and availability.'],
        'cart/index'              => ['cart', 'Your Cart | CustomCart', ''],
        'cart/add'                => ['cart/add', 'Add to Cart | CustomCart', ''],
        'cart/shipping'           => ['cart/shipping', 'Delivery Option | CustomCart', ''],
        'cart/checkout'           => ['checkout', 'Checkout | CustomCart', ''],
        'cart/delivery'           => ['delivery-information', 'Delivery Information | CustomCart', ''],
        'cart/payment'            => ['payment', 'Payment | CustomCart', ''],
        'cart/confirmation'       => ['order-confirmation', 'Order Confirmation | CustomCart', ''],
        'cart/invoice'            => ['order-invoice', 'Invoice | CustomCart', ''],
        // Wishlist hidden: there is no wishlist in this project. Was: '... track orders, manage your wishlist and update your details.' / '... order tracking and a wishlist.'
        'users/login'             => ['login', 'Customer Login | CustomCart', 'Log in to your CustomCart account to track orders and update your details.'],
        'users/register'          => ['register', 'Create an Account | CustomCart', 'Create a CustomCart account for faster checkout and order tracking.'],
        'users/logout'            => ['logout', 'Logout | CustomCart', ''],
        'users/forgot_password'   => ['forgot-password', 'Forgot Password | CustomCart', ''],
        'users/reset_password'    => ['reset-password', 'Reset Password | CustomCart', ''],
        'members/dashboard'       => ['dashboard', 'My Account | CustomCart', ''],
        'members/orders'          => ['acc-order-history', 'Order History | My Account', ''],
        'members/order'           => ['acc-order-invoice', 'Order Details | My Account', ''],
        'members/invoice_print'   => ['invoice-print', 'Invoice | CustomCart', ''],
        'members/wishlist'        => ['acc-wishlist', 'Wishlist | My Account', ''],
        'members/edit_account'    => ['acc-edit', 'Edit Account | My Account', ''],
        'members/change_password' => ['change-password', 'Change Password | My Account', ''],
    ];

    public function run(): void
    {
        $this->sections();
        $this->pages();
        $this->shipping();
    }

    /**
     * Bring the store's menu rows in line with $menu: headings created when
     * missing, each store section moved under its heading with the
     * reference's title and order (its id, and so the sub-admin grants
     * pointing at it, is kept). Rows of other controllers are never touched.
     */
    protected function sections(): void
    {
        $sections = $this->db->table('tbl_admin_sections');

        foreach ($this->menu as $title => [$heading_order, $items]) {
            $heading = $this->db->table('tbl_admin_sections')->select('id')->where(['parent_id' => 0, 'section_title' => $title])->get()->getRowArray();
            if ($heading) {
                $this->db->table('tbl_admin_sections')->where('id', $heading['id'])->update(['disp_order' => $heading_order, 'status' => '1']);
            } else {
                $this->db->table('tbl_admin_sections')->insert([
                    'parent_id' => 0, 'section_title' => $title, 'section_controller' => null,
                    'disp_order' => $heading_order, 'actual_privilege' => null, 'status' => '1',
                ]);
                $heading = ['id' => $this->db->insertID()];
            }

            foreach ($items as $controller => [$label, $order, $privileges, $status]) {
                $row  = $this->db->table('tbl_admin_sections')->select('id')->where('section_controller', $controller)->orderBy('id')->get()->getRowArray();
                $data = ['parent_id' => (int) $heading['id'], 'section_title' => $label, 'disp_order' => $order, 'actual_privilege' => $privileges, 'status' => $status];
                $row
                    ? $this->db->table('tbl_admin_sections')->where('id', $row['id'])->update($data)
                    : $this->db->table('tbl_admin_sections')->insert($data + ['section_controller' => $controller]);
            }
        }

        // Rows of the earlier store menu.
        $old = $sections->select('id')->whereIn('section_controller', self::OLD_SECTIONS)->get()->getResultArray();
        if ($old) {
            $ids = array_column($old, 'id');
            $this->db->table('tbl_admin_allowed_sections')->whereIn('sec_id', $ids)->delete();
            $this->db->table('tbl_admin_sections')->whereIn('id', $ids)->delete();
        }
        $heading = $this->db->table('tbl_admin_sections')->select('id')->where(['parent_id' => 0, 'section_title' => self::OLD_HEADING])->get()->getRowArray();
        if ($heading && $this->db->table('tbl_admin_sections')->where('parent_id', $heading['id'])->countAllResults() === 0) {
            $this->db->table('tbl_admin_sections')->where('id', $heading['id'])->delete();
        }
    }

    protected function pages(): void
    {
        foreach ($this->pages as $type => [$url, $title, $description]) {
            $at = $this->db->table('wl_meta_tags')->where('page_url', $url)->get()->getRowArray();
            if ($at) {
                $at['entity_type'] !== $type && $this->report('Address "' . $url . '" is used by ' . ($at['entity_type'] ?: 'an SEO-only row') . '; ' . $type . ' was not registered.');

                continue;
            }
            $this->db->table('wl_meta_tags')->insert([
                'is_fixed' => 'Y', 'entity_type' => $type, 'entity_id' => 0, 'page_url' => $url,
                'meta_title' => $title, 'meta_description' => $description, 'meta_keyword' => '', 'status' => '1',
            ]);
        }
    }

    protected function shipping(): void
    {
        if ($this->db->table('wl_shipping')->countAllResults() > 0) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table('wl_shipping')->insertBatch([
            ['shipping_type' => 'Standard Delivery (3–5 working days)', 'shipment_rate' => '6.00', 'free_above' => '150.00', 'is_default' => '1', 'sort_order' => 1, 'status' => '1', 'added_date' => $now],
            ['shipping_type' => 'Self Collection at CustomCart Store', 'shipment_rate' => '0.00', 'free_above' => '0.00', 'is_default' => '0', 'sort_order' => 2, 'status' => '1', 'added_date' => $now],
        ]);
    }

    protected function report(string $message): void
    {
        is_cli() ? CLI::write($message, 'yellow') : log_message('warning', 'StoreSeeder: ' . $message);
    }
}
