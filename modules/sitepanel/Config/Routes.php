<?php

/**
 * Sitepanel routes (discovered by CodeIgniter 4's module discovery).
 *
 * CodeIgniter 3 reached every sitepanel action through HMVC auto-routing:
 * sitepanel/<controller>/<method>/<id>. Auto-routing is off in this
 * application, so each of those URLs is declared here and keeps its exact
 * path. An action listed with an id also answers without one (CodeIgniter 3
 * passed the default $id = 0), and accepts GET and POST like before —
 * state-changing actions refuse anything but POST themselves.
 *
 * Every route except sign-in/sign-out runs the sitepanelauth filter (signed-in
 * active administrator); AdminController checks the section permissions.
 *
 * @var CodeIgniter\Router\RouteCollection $routes
 */
$routes->group('sitepanel', ['namespace' => 'Modules\Sitepanel\Controllers'], static function ($routes): void {
    // Public: sign-in and sign-out.
    $routes->get('/', 'Sitepanel::index');
    $routes->match(['GET', 'POST'], 'login', 'Sitepanel::login');
    $routes->match(['GET', 'POST'], 'logout', 'Sitepanel::logout');

    // URL segment => [controller class, [action => takes an id]]
    $sections = [
        'dashboard' => ['Dashboard', ['index' => false, 'clear_cache' => false]],
        'settings'  => ['Settings', ['index' => false]],
        'theme'     => ['Theme', ['index' => false]],

        'change_password' => ['ChangePassword', ['index' => false]],

        'testimonial' => ['Testimonial', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'team'        => ['Team', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'sort' => false]],
        'certificates' => ['Certificates', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'sort' => false]],
        'marquee'     => ['Marquee', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'sort' => false]],
        'referrals'   => ['Referrals', ['index' => false, 'view' => true, 'delete' => true, 'bulk' => false]],

        'news'    => ['News', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'sort' => false, 'upload_image' => false]],

        'enquiry' => ['Enquiry', ['index' => false, 'view' => true, 'reply_status' => true, 'delete' => true, 'bulk' => false,
            'send_mail' => true, 'preview_mail' => false, 'compose_mail' => false, 'deliver_mail' => false, 'upload_image' => false]],

        // Other Management
        'product_enquiry' => ['ProductEnquiry', ['index' => false, 'view' => true, 'reply_status' => true, 'delete' => true, 'bulk' => false,
            'send_mail' => true, 'preview_mail' => false, 'compose_mail' => false, 'deliver_mail' => false, 'upload_image' => false]],
        'coupons'       => ['Coupons', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'header_images' => ['HeaderImages', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'faq'           => ['Faq', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'sort' => false]],
        'gallery'       => ['Gallery', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'sort' => false]],
        'video'         => ['Video', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'sort' => false]],
        'subadmin'      => ['Subadmin', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'location'      => ['Location', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false,
            'states' => true, 'state_add' => true, 'state_edit' => true, 'state_status' => true, 'state_delete' => true, 'state_bulk' => true,
            'cities' => true, 'city_add' => true, 'city_edit' => true, 'city_status' => true, 'city_delete' => true, 'city_bulk' => true]],
        'meta'          => ['Meta', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true]],
        'banners'       => ['Banners', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'staticpages'   => ['Staticpages', ['index' => false, 'view' => true, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'upload_image' => false]],
        'newsletter'    => ['Newsletter', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false,
            'preview_email' => false, 'compose_email' => false, 'send_email' => false, 'upload_image' => false]],

        // Online store: Product Management, Members Management, Manage Orders, Manage Entities
        // index/<parent_id> and add/<parent_id>, as the reference's sitepanel/category/index/<id>.
        'category' => ['Category', ['index' => true, 'add' => true, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'sort' => false]],
        'products' => ['Products', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false,
            'stock' => true, 'media' => true, 'related' => true, 'upload_image' => false]],
        'color'    => ['Color', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'size'     => ['Size', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'brand'    => ['Brand', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'material' => ['Material', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'reviews'  => ['Reviews', ['index' => false, 'view' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'shipping' => ['Shipping', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false, 'make_default' => true]],
        'orders'   => ['Orders', ['index' => false, 'view' => true, 'status' => true, 'payment' => true, 'courier' => true, 'comment' => true,
            'item' => true, 'invoice' => true, 'delete' => true, 'bulk' => false]],
        'members'  => ['Members', ['index' => false, 'view' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'products_bulk'  => ['ProductsBulk', ['index' => false, 'template' => false, 'ids' => true]],
        'order_products' => ['OrderProducts', ['index' => false, 'bulk' => false]],
        'reasons'        => ['Reasons', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
        'returns'        => ['Returns', ['index' => false, 'view' => true, 'refund' => true]],
    ];

    $routes->group('', ['filter' => 'sitepanelauth'], static function ($routes) use ($sections): void {
        foreach ($sections as $segment => [$class, $actions]) {
            $routes->match(['GET', 'POST'], $segment, $class . '::index');

            foreach ($actions as $action => $takes_id) {
                $routes->match(['GET', 'POST'], $segment . '/' . $action, $class . '::' . $action);
                if ($takes_id) {
                    $routes->match(['GET', 'POST'], $segment . '/' . $action . '/(:segment)', $class . '::' . $action . '/$1');
                }
            }
        }
    });
});
