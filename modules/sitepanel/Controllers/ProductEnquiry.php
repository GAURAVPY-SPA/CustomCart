<?php

namespace Modules\Sitepanel\Controllers;

/**
 * Sitepanel > Other Management > Manage Product Enquiry (wl_enquiry rows of
 * type 3, as in the reference; the product name is in product_service).
 *
 * The same screens and actions as Manage Enquiry (list with search and date
 * range, details, reply status, Send Mail, delete), limited to product
 * enquiries, with its own sub-admin grant ("product_enquiry"). The
 * reference reached this list as enquiry/products?type=3.
 */
class ProductEnquiry extends Enquiry
{
    public const MAIL_FLOW_KEY = 'product_enquiry_mail_flow';

    protected string $base           = 'sitepanel/product_enquiry';
    protected string $item           = 'Product enquiry';
    protected string $menu_highlight = 'product_enquiry';
    protected array $scope           = ['3'];
    protected string $title          = 'Manage Product Enquiry';
}
