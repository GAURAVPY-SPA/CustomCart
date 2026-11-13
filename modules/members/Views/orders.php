<?php
/*
| Orders History (design: html/order-history.htm), see Members::orders().
| $orders (with items), $total, $keyword, $status, $pager. The status chips
| filter the shown orders (customcart.js: input[name="ktOrderFilter"]).
*/
$groups = array('all' => 'All Orders', 'progress' => 'In Progress', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returns' => 'Returns');
echo view('top_application');
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'start', 'customer' => $customer, 'nav_active' => 'orders', 'title' => 'Orders History'));
?>
<div class="kt-shop-card kt-ord-wrap bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Orders History <span class="kt-shop-count"><?php echo (int) $total; ?> order<?php echo $total == 1 ? '' : 's'; ?></span></h2>
<p class="kt-acc-sub text-body-secondary mb-0">Track, cancel or return items from your orders.</p>
</div>
<form action="<?php echo site_url('acc-order-history'); ?>" method="get" class="kt-coupon-form d-flex gap-2" role="search">
<input type="search" name="q" maxlength="30" class="form-control kt-input rounded-pill" placeholder="Order number, e.g. CC10001" aria-label="Search by order number" value="<?php echo html_escape($keyword); ?>">
<button type="submit" class="btn btn-kt-dark rounded-pill flex-shrink-0" aria-label="Search"><i class="bi bi-search"></i></button>
</form>
</div>
<?php if ($orders): ?>
<div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Filter orders">
<?php foreach ($groups as $value => $label): ?>
<input type="radio" class="btn-check" name="ktOrderFilter" id="ktOf-<?php echo $value; ?>" value="<?php echo $value; ?>" autocomplete="off"<?php echo $value === 'all' ? ' checked' : ''; ?>><label class="kt-fchip d-inline-flex align-items-center" for="ktOf-<?php echo $value; ?>"><?php echo $label; ?></label>
<?php endforeach; ?>
</div>
<div class="kt-ord-list d-flex flex-column">
<?php foreach ($orders as $order): ?>
<?php echo view('Modules\Members\Views\partials\order_card', array('order' => $order)); ?>
<?php endforeach; ?>
</div>
<p class="text-body-secondary text-center d-none mb-0 mt-3" id="ktOrdEmpty">No orders with this status on this page.</p>
<?php echo view('Modules\Products\Views\partials\pager', array('pager' => $pager)); ?>
<?php else: ?>
<p class="text-body-secondary mb-0"><?php echo $keyword !== '' ? 'No order matches this number.' : 'You have not placed any orders yet.'; ?> <a href="<?php echo store_url(); ?>" class="kt-link fw-bold">Start shopping</a></p>
<?php endif; ?>
</div>
<?php
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'end'));
echo view('bottom_application');
