<?php
/*
| My Account dashboard (design: html/dashboard.htm), see Members::dashboard().
| $customer, $order_count, $wishlist_count, $coupon_count, $orders (latest).
*/
$cards = array(
	array('acc-order-history', 'is-orders', 'box-seam', (int) $order_count, 'Orders', 'Order History', 'Track, view and reorder your past purchases.', 'View Orders'),
	array('acc-wishlist', 'is-wish', 'heart', (int) $wishlist_count, 'Saved', 'Wishlist', 'Products you have saved for later.', 'View Wishlist'),
	array('acc-coupons', 'is-cpn', 'ticket-perforated', (int) $coupon_count, 'Available', 'Manage Coupons', 'Exclusive offers ready to use at checkout.', 'View Coupons'),
);
echo view('top_application');
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'start', 'customer' => $customer, 'nav_active' => 'dashboard', 'title' => 'Dashboard'));
?>
<div class="kt-dash-hello d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0">
<div class="kt-eyebrow d-inline-flex align-items-center fw-bold">MY DASHBOARD</div>
<h2 class="kt-dash-title fw-bold">Welcome back, <?php echo html_escape($customer['first_name']); ?>!</h2>
<p class="kt-dash-text mb-0">View your orders, saved products and coupons from here.</p>
</div>
<span class="kt-dash-since rounded-pill fw-bold"><i class="bi bi-calendar-check"></i> Member since <?php echo date('M Y', strtotime((string) $customer['account_created_date'])); ?></span>
</div>
<div class="row row-cols-1 row-cols-md-3 kt-g-dc">
<?php foreach ($cards as list($uri, $css, $icon, $num, $unit, $title, $text, $cta)): ?>
<div class="col">
<a href="<?php echo site_url($uri); ?>" class="kt-dc <?php echo $css; ?> h-100 d-flex flex-column position-relative overflow-hidden">
<span class="kt-dc-top d-flex align-items-start justify-content-between">
<span class="kt-dc-ico d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-<?php echo $icon; ?>"></i></span>
<span class="kt-dc-stat text-end"><b class="kt-dc-num d-block"><?php echo $num; ?></b><span class="kt-dc-label fw-bold d-block"><?php echo $unit; ?></span></span>
</span>
<b class="kt-dc-title d-block"><?php echo $title; ?></b>
<span class="kt-dc-text d-block"><?php echo $text; ?></span>
<span class="kt-dc-foot d-flex align-items-center justify-content-between mt-auto">
<span class="kt-dc-cta fw-bold"><?php echo $cta; ?></span>
<span class="kt-dc-arrow rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-right"></i></span>
</span>
</a>
</div>
<?php endforeach; ?>
</div>
<div class="kt-shop-card kt-ord-wrap bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0"><h2 class="kt-shop-title fw-bold text-dark mb-0">Recent Orders</h2></div>
<a href="<?php echo site_url('acc-order-history'); ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2">VIEW ALL <i class="bi bi-arrow-right"></i></a>
</div>
<?php if ($orders): ?>
<div class="kt-ord-list d-flex flex-column">
<?php foreach ($orders as $order): ?>
<?php echo view('Modules\Members\Views\partials\order_card', array('order' => $order)); ?>
<?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-body-secondary mb-0">You have not placed any orders yet. <a href="<?php echo store_url(); ?>" class="kt-link fw-bold">Start shopping</a></p>
<?php endif; ?>
</div>
<?php
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'end'));
echo view('bottom_application');
