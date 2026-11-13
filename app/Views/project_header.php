<?php
/*
| Header (design: html/Library/header.lbi): logo, main menu with the product
| mega menu (Manage Categories: site_mega_menu()), search (POST products, q; blank = all products),
| account, wishlist and cart (live counts), Send Inquiry (popup) and the
| Running Marquee (Sitepanel > Manage Running Marquee) above it.
*/
helper('shop');
$logo      = theme_url().'images/customcart-logo.png';
$mega      = site_mega_menu();
$mega_pic  = NULL;
foreach ($mega as $row)
{
	if ($row['image_url'] !== '') { $mega_pic = $row; break; }
}
$customer  = (new \Modules\Users\Libraries\CustomerAuth())->current();
$cart_n    = cart_count();
$wish_n    = wishlist_count();
$marquee   = site_marquee();
$phone     = (string) service('siteSettings')->get('contact_phone');
$email     = (string) service('siteSettings')->get('contact_email');
$nav = array(
	array('label' => 'Home', 'url' => site_url()),
	array('label' => 'Products', 'url' => store_url(), 'mega' => (bool) $mega),
	array('label' => 'About Us', 'url' => site_url('aboutus')),
	array('label' => 'Gallery', 'url' => site_url('images-gallery'), 'children' => array('Images Gallery' => site_url('images-gallery'), 'Video Gallery' => site_url('videos-gallery'))),
	array('label' => 'Contact Us', 'url' => site_url('contactus')),
);
?>
<?php if ($marquee): ?>
<div class="kt-ticker bg-dark text-white overflow-hidden" aria-label="Announcements">
<div class="kt-marquee">
<div class="kt-marquee-track d-flex">
<?php for ($copy = 0; $copy < 2; $copy++): ?>
<?php foreach ($marquee as $item): ?>
<span class="kt-ticker-item flex-shrink-0"<?php echo $copy ? ' aria-hidden="true"' : ''; ?>><?php if ($item['url'] !== ''): ?><a href="<?php echo html_escape($item['url']); ?>"<?php echo $copy ? ' tabindex="-1"' : ''; ?>><?php echo html_escape($item['text']); ?></a><?php else: ?><?php echo html_escape($item['text']); ?><?php endif; ?></span>
<?php endforeach; ?>
<?php endfor; ?>
</div>
</div>
</div>
<?php endif; ?>
<header class="kt-header sticky-top" id="ktHeader">
<div class="kt-header-bar position-relative mx-auto bg-white border rounded-pill d-flex align-items-center">
<button type="button" class="btn btn-kt-icon kt-menu-btn rounded-circle d-flex d-xl-none align-items-center justify-content-center flex-shrink-0" data-bs-toggle="offcanvas" data-bs-target="#ktDrawer" aria-controls="ktDrawer" aria-label="Menu"><i class="bi bi-list"></i></button>
<a href="<?php echo site_url(); ?>" class="kt-logo d-block flex-shrink-0" aria-label="CustomCart home"><img src="<?php echo $logo; ?>" alt="CustomCart logo" class="d-block w-100 h-100 object-fit-contain"></a>

<div class="offcanvas-xl offcanvas-start kt-drawer" tabindex="-1" id="ktDrawer" aria-label="Menu">
<div class="offcanvas-header justify-content-between border-bottom">
<a href="<?php echo site_url(); ?>" class="kt-drawer-logo d-block"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo $logo; ?>" alt="CustomCart" class="d-block w-100 h-100 object-fit-contain"></a>
<button type="button" class="btn btn-kt-close kt-btn-44 rounded-circle d-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas" data-bs-target="#ktDrawer" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
</div>
<div class="offcanvas-body p-0 d-flex flex-column flex-xl-row align-items-xl-center justify-content-xl-center">
<nav class="nav kt-nav flex-column flex-xl-row flex-xl-nowrap justify-content-xl-center" aria-label="Main">
<?php foreach ($nav as $item): ?>
<?php if ( ! empty($item['children'])): ?>
<div class="kt-nav-drop position-relative">
<a href="<?php echo $item['url']; ?>" class="nav-link kt-nav-link text-nowrap" aria-haspopup="true"><span class="kt-nav-label d-flex align-items-center"><?php echo $item['label']; ?><i class="bi bi-chevron-down"></i></span><span class="kt-nav-dot rounded-circle"></span><span class="kt-nav-arrow rounded-circle bg-light align-items-center justify-content-center"><i class="bi bi-chevron-right"></i></span></a>
<div class="kt-drop">
<div class="kt-drop-in bg-white d-flex flex-column">
<?php foreach ($item['children'] as $label => $url): ?>
<a href="<?php echo $url; ?>"><?php echo $label; ?></a>
<?php endforeach; ?>
</div>
</div>
</div>
<?php else: ?>
<a href="<?php echo $item['url']; ?>" class="nav-link kt-nav-link<?php echo ! empty($item['mega']) ? ' has-mega' : ''; ?> text-nowrap"><span class="kt-nav-label d-flex align-items-center"><?php echo $item['label']; ?><?php echo ! empty($item['mega']) ? '<i class="bi bi-chevron-down"></i>' : ''; ?></span><span class="kt-nav-dot rounded-circle"></span><span class="kt-nav-arrow rounded-circle bg-light align-items-center justify-content-center"><i class="bi bi-chevron-right"></i></span></a>
<?php endif; ?>
<?php endforeach; ?>
</nav>

<?php if ($mega): ?>
<div class="kt-mega bg-white" id="ktMega">
<?php foreach (array_slice($mega, 0, 3) as $col): ?>
<div>
<a href="<?php echo html_escape($col['url']); ?>" class="kt-mini-title kt-mega-title d-flex align-items-center gap-2 fw-bold text-primary"><?php echo html_escape(mb_strtoupper($col['category_name'])); ?></a>
<div class="kt-mega-links d-flex flex-column">
<?php foreach ($col['children'] as $child): ?>
<a href="<?php echo html_escape($child['url']); ?>"><?php echo html_escape($child['category_name']); ?></a>
<?php endforeach; ?>
</div>
</div>
<?php endforeach; ?>
<?php if ($mega_pic): ?>
<div class="position-relative d-none d-xl-flex flex-column align-items-center text-center kt-gap-12">
<div class="kt-mega-pic position-relative">
<div class="kt-ring-out-8 position-absolute rounded-circle border border-primary-subtle pe-none"></div>
<div class="position-absolute top-0 start-0 w-100 h-100 rounded-circle overflow-hidden bg-primary-subtle"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($mega_pic['image_url']); ?>" alt="<?php echo html_escape($mega_pic['category_name']); ?>" class="w-100 h-100 object-fit-cover"></div>
</div>
<span class="kt-fs-18 fw-bold text-dark"><?php echo html_escape($mega_pic['category_name']); ?></span>
<a href="<?php echo html_escape($mega_pic['url']); ?>" class="kt-mega-cta fw-bold">EXPLORE →</a>
</div>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="kt-drawer-contact bg-light d-flex d-xl-none flex-column kt-gap-10 flex-shrink-0">
<?php if ($phone !== ''): ?><a href="tel:<?php echo html_escape(phone_digits($phone)); ?>"><i class="bi bi-telephone"></i> <?php echo html_escape($phone); ?></a><?php endif; ?>
<?php if ($email !== ''): ?><a href="mailto:<?php echo html_escape($email); ?>"><i class="bi bi-envelope"></i> <?php echo html_escape($email); ?></a><?php endif; ?>
</div>
</div>
</div>

<div class="kt-head-actions d-flex align-items-center ms-auto">
<button type="button" class="btn btn-kt-icon kt-search-toggle rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" id="ktSearchToggle" aria-label="Search" aria-expanded="false" aria-controls="ktSearch"><i class="bi bi-search"></i><i class="bi bi-x-lg"></i></button>

<a href="<?php echo site_url($customer ? 'dashboard' : 'login'); ?>" class="btn btn-kt-icon rounded-circle d-none d-md-flex align-items-center justify-content-center flex-shrink-0" aria-label="<?php echo $customer ? 'My Account' : 'Login'; ?>" title="<?php echo $customer ? 'My Account' : 'Login / Register'; ?>"><i class="bi bi-person<?php echo $customer ? '-check' : ''; ?>"></i></a>

<a href="<?php echo site_url('acc-wishlist'); ?>" class="btn btn-kt-icon rounded-circle position-relative d-none d-md-flex align-items-center justify-content-center flex-shrink-0" aria-label="Wishlist" title="Wishlist"><i class="bi bi-heart"></i><span class="badge rounded-pill bg-warning kt-cart-count position-absolute d-flex align-items-center justify-content-center js-wish-count"<?php echo $wish_n ? '' : ' hidden'; ?>><?php echo $wish_n; ?></span></a>

<a href="<?php echo site_url('cart'); ?>" class="btn btn-kt-icon rounded-circle position-relative d-flex align-items-center justify-content-center flex-shrink-0" aria-label="Cart"><i class="bi bi-bag"></i><span class="badge rounded-pill bg-warning kt-cart-count position-absolute d-flex align-items-center justify-content-center js-cart-count"><?php echo $cart_n; ?></span></a>

<button type="button" class="btn btn-kt-gold kt-head-inq rounded-pill text-nowrap d-none d-md-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#ktInquiry">Send Inquiry</button>
</div>

<form class="kt-search-pop position-absolute start-0 end-0 bg-white border rounded-pill align-items-center" id="ktSearch" action="<?php echo site_url('products'); ?>" method="post" role="search">
<?php echo csrf_field(); ?>
<i class="bi bi-search text-primary"></i>
<input type="search" name="q" maxlength="80" class="form-control border-0 shadow-none bg-transparent p-0 flex-grow-1" placeholder="Search T-shirts, shirts, jackets..." aria-label="Search products" value="<?php echo html_escape((string) (isset($_COOKIE[config('Session')->cookieName]) ? (session(\Modules\Products\Controllers\Products::LISTING_KEY)['products']['q'] ?? '') : '')); ?>">
<button type="submit" class="btn btn-kt-dark kt-search-go rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" aria-label="Search"><i class="bi bi-arrow-right"></i></button>
</form>
</div>
</header>
