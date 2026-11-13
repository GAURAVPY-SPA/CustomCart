<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Links of the public header and footer (design: html/Library/header.lbi
 * and footer.lbi).
 *
 * Every entry of the design is listed with the URI its page will have.
 * project_header / project_footer show an entry only while a GET route
 * exists for that URI (site_menu_url()), so a link appears by itself once
 * its page is built. 'uri' => null: the target is not a page of its own.
 * The product menu's categories come from Manage Categories, not from here.
 *
 * Link: label, uri, title (tooltip); optional 'fragment' (#part of the
 * page) and 'children' (header dropdown / nested flyout).
 */
class SiteMenu extends BaseConfig
{
    /**
     * Top bar links, left of the social icons.
     */
    public array $topbar = [
        ['label' => 'Contact Us', 'uri' => 'contactus', 'title' => 'Contact Us'],
    ];

    /**
     * Main menu (after the Home link). A parent without a page of its own
     * links to its first shown child.
     */
    public array $main = [
        ['label' => 'Products', 'uri' => 'online-store', 'title' => 'View Our Products'],
        ['label' => 'About Us', 'uri' => 'aboutus', 'title' => 'About Us', 'children' => [
            ['label' => 'About CustomCart', 'uri' => 'aboutus', 'title' => 'About CustomCart'],
            ['label' => 'Mission & Vision', 'uri' => 'aboutus', 'fragment' => 'mission-vision', 'title' => 'Our Mission & Vision'],
            ['label' => 'Our Team', 'uri' => 'our-team', 'title' => 'Meet Our Team'],
        ]],
        ['label' => 'Gallery', 'uri' => null, 'title' => 'Gallery', 'children' => [
            ['label' => 'Images Gallery', 'uri' => 'images-gallery', 'title' => 'View Our Photos'],
            ['label' => 'Video Gallery', 'uri' => 'videos-gallery', 'title' => 'Watch Our Videos'],
        ]],
        ['label' => 'Contact Us', 'uri' => 'contactus', 'title' => 'Contact Us'],
    ];

    /**
     * Header actions: cart, account menu, Send Inquiry button.
     */
    public array $cart = ['label' => 'Shopping cart', 'uri' => 'cart', 'title' => 'Open Shopping Cart'];

    public array $register = [
        ['label' => 'Register', 'uri' => 'register', 'title' => 'Create an Account', 'icon' => 'person-vcard'],
        ['divider' => true],
        ['label' => 'Login', 'uri' => 'login', 'title' => 'Log In to Your Account', 'icon' => 'box-arrow-in-right'],
        ['label' => 'My Account', 'uri' => 'dashboard', 'title' => 'Go to My Account', 'icon' => 'grid'],
        ['label' => 'Order History', 'uri' => 'acc-order-history', 'title' => 'View Your Order History', 'icon' => 'bag'],
        ['label' => 'Wishlist', 'uri' => 'acc-wishlist', 'title' => 'View Your Wishlist', 'icon' => 'heart'],
        ['label' => 'Edit Profile', 'uri' => 'acc-edit', 'title' => 'Edit Your Account Details', 'icon' => 'person'],
        ['label' => 'Change Password', 'uri' => 'change-password', 'title' => 'Change Your Password', 'icon' => 'lock'],
    ];

    public array $inquiry = ['label' => 'Send Inquiry', 'uri' => 'send-inquiry', 'title' => 'Send Us an Inquiry'];

    /**
     * Footer link columns: each column is a list of headed groups.
     */
    public array $footer = [
        [
            ['heading' => 'Products', 'links' => [
                ['label' => 'View All', 'uri' => 'online-store', 'title' => 'View All Products'],
            ]],
        ],
        [
            ['heading' => 'Company', 'links' => [
                ['label' => 'About Us', 'uri' => 'aboutus', 'title' => 'About Us'],
                ['label' => 'Vision', 'uri' => 'aboutus', 'fragment' => 'mission-vision', 'title' => 'Our Vision'],
                ['label' => 'Mission', 'uri' => 'aboutus', 'fragment' => 'mission-vision', 'title' => 'Our Mission'],
                ['label' => 'Corporate Orders', 'uri' => 'industries', 'title' => 'Corporate & Bulk Orders'],
                ['label' => 'News & Events', 'uri' => 'news-and-events', 'title' => 'Read News & Events'],
                ['label' => 'Images Gallery', 'uri' => 'images-gallery', 'title' => 'View Our Photos'],
                ['label' => 'Video Gallery', 'uri' => 'videos-gallery', 'title' => 'Watch Our Videos'],
                ['label' => 'Certificates', 'uri' => 'certificates', 'title' => 'View Our Certificates'],
                ['label' => 'Brochure', 'uri' => 'brochure', 'title' => 'Download Our Brochure'],
                ['label' => 'Teams', 'uri' => 'our-team', 'title' => 'Meet Our Team'],
                ['label' => 'Partners', 'uri' => 'partners', 'title' => 'Our Partners'],
            ]],
        ],
        [
            ['heading' => 'Help', 'links' => [
                ['label' => 'My Account', 'uri' => 'dashboard', 'title' => 'Go to My Account'],
                ['label' => 'Track Order', 'uri' => 'track-order', 'title' => 'Track Your Order'],
                ['label' => 'Returns', 'uri' => 'returns', 'title' => 'Returns'],
                ['label' => 'Refunds', 'uri' => 'refund-policy', 'title' => 'Refunds'],
                ['label' => 'FAQ', 'uri' => 'faq', 'title' => 'Frequently Asked Questions'],
                ['label' => 'Testimonials', 'uri' => 'testimonials', 'title' => 'Read Testimonials'],
                ['label' => 'Refer a Friend', 'uri' => 'refer-a-friend', 'title' => 'Refer a Friend'],
                ['label' => 'Send Inquiry', 'uri' => 'send-inquiry', 'title' => 'Send Us an Inquiry'],
            ]],
        ],
    ];

    /**
     * Footer bottom bar.
     */
    public array $legal = [
        ['label' => 'Privacy Policy', 'uri' => 'privacy-policy', 'title' => 'Read Our Privacy Policy'],
        ['label' => 'Terms & Conditions', 'uri' => 'terms-conditions', 'title' => 'Read Terms & Conditions'],
        ['label' => 'Sitemap', 'uri' => 'sitemap', 'title' => 'View Sitemap'],
    ];

    /**
     * Social icons (top bar and footer): Admin Settings key => [Bootstrap
     * Icons name, network]. An icon shows only when its link is set.
     */
    public array $social = [
        'facebook_link'  => ['facebook', 'Facebook'],
        'instagram_link' => ['instagram', 'Instagram'],
        'youtube_link'   => ['youtube', 'YouTube'],
        'twitter_link'   => ['twitter-x', 'X (Twitter)'],
        'linkedin_link'  => ['linkedin', 'LinkedIn'],
    ];
}
