<?php
/*
| My Account frame (design: html/dashboard.htm + Library/account-left.lbi): the
| inner banner, the account menu (offcanvas below 992px) and, with
| $part = 'end', the closing tags and the Logout form (POST /logout).
|   $part 'start' | 'end'; $customer wl_customers row; $nav_active the menu key; $title the page heading
*/
if ($part === 'end'):
?>
</div>
</div>
</section>
<?php echo form_open('logout', array('id' => 'ktLogoutForm', 'class' => 'd-none')); ?><?php echo form_close(); ?>
<?php
return;
endif;

$nav = array(
	'dashboard' => array('dashboard', 'grid-1x2', 'Dashboard'),
	'edit'      => array('acc-edit', 'person', 'Edit Profile'),
	'address'   => array('acc-address', 'geo-alt', 'My Address'),
	'wishlist'  => array('acc-wishlist', 'heart', 'Wishlist'),
	'coupons'   => array('acc-coupons', 'ticket-perforated', 'Coupons'),
	'orders'    => array('acc-order-history', 'box-seam', 'Orders History'),
);
$coupons   = (new \Modules\Sitepanel\Models\CouponModel())->get_current_coupons(TRUE); /* the coupons My Coupons lists (active, in date) */
$counts    = array('wishlist' => wishlist_count(), 'coupons' => is_array($coupons) ? count($coupons) : 0);
$full_name = trim($customer['first_name'].' '.$customer['last_name']);
$initials  = strtoupper(mb_substr($customer['first_name'], 0, 1).mb_substr((string) $customer['last_name'], 0, 1));
$crumbs    = $nav_active === 'dashboard' ? array('My Account' => NULL) : array('My Account' => site_url('dashboard'), $title => NULL);
$current   = isset($nav[$nav_active]) ? $nav[$nav_active] : $nav['dashboard'];
echo view('inner_hero', array('hero' => array('title' => $nav_active === 'dashboard' ? 'My Account' : $title, 'banner' => 'My Account', 'crumbs' => $crumbs)));
?>
<section class="container-xxl kt-container kt-sec kt-acc">
<div class="row kt-g-acc">
<div class="col-12 col-lg-4 col-xl-3">
<div class="kt-acc-bar kt-shop-card bg-white border d-flex align-items-center d-lg-none">
<span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-<?php echo $current[1]; ?>"></i></span>
<span class="flex-grow-1 kt-min-0"><span class="kt-acc-bar-label d-block fw-bold">My Account</span><b class="kt-acc-bar-page d-block text-dark"><?php echo html_escape($current[2]); ?></b></span>
<button type="button" class="btn btn-kt-outline kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2" data-bs-toggle="offcanvas" data-bs-target="#ktAccNav" aria-controls="ktAccNav"><i class="bi bi-list"></i> MENU</button>
</div>
<div class="offcanvas-lg offcanvas-start kt-acc-off" tabindex="-1" id="ktAccNav" aria-labelledby="ktAccNavTitle">
<div class="offcanvas-header d-lg-none">
<h2 class="kt-shop-title fw-bold text-dark mb-0" id="ktAccNavTitle">My Account</h2>
<button type="button" class="btn btn-kt-close kt-btn-42 rounded-circle d-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas" data-bs-target="#ktAccNav" aria-label="Close"><i class="bi bi-x-lg"></i></button>
</div>
<div class="offcanvas-body">
<nav class="kt-acc-side kt-shop-card bg-white border w-100" aria-label="My account">
<div class="kt-acc-user d-flex align-items-center">
<span class="kt-acc-avatar rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"><?php echo html_escape($initials); ?></span>
<span class="kt-min-0"><b class="kt-acc-name d-block text-dark"><?php echo html_escape($full_name); ?></b><span class="kt-acc-mail d-block text-body-secondary"><?php echo html_escape($customer['user_name']); ?></span></span>
</div>
<ul class="kt-acc-menu list-unstyled d-flex flex-column mb-0">
<?php foreach ($nav as $key => list($uri, $icon, $label)): ?>
<li><a href="<?php echo site_url($uri); ?>" class="kt-acc-link d-flex align-items-center<?php echo $key === $nav_active ? ' active' : ''; ?>"<?php echo $key === $nav_active ? ' aria-current="page"' : ''; ?>><i class="bi bi-<?php echo $icon; ?> rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"></i><span><?php echo $label; ?></span><?php if ( ! empty($counts[$key])): ?><span class="kt-acc-count rounded-pill fw-bold"><?php echo (int) $counts[$key]; ?></span><?php endif; ?></a></li>
<?php endforeach; ?>
<li class="kt-acc-out"><button type="submit" form="ktLogoutForm" class="kt-acc-link is-out d-flex align-items-center w-100 border-0 bg-transparent"><i class="bi bi-box-arrow-right rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"></i><span>Logout</span></button></li>
</ul>
</nav>
</div>
</div>
</div>
<div class="col-12 col-lg-8 col-xl-9">
<?php echo view('Modules\Products\Views\partials\flash'); ?>
