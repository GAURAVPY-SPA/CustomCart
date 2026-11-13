<?php
/*
| Track Order (design: html/track-order.htm), see Members::track_order(). A popup
| page ($is_frame) or a page of its own. Without an order: the lookup form
| (order number + email). $order, $items, $invoice, $email, $error, $customer.
*/
$steps = array('Order Placed' => 'bag-check', 'Order Confirmed' => 'check2-circle', 'Shipped' => 'truck', 'Out for Delivery' => 'geo-alt', 'Delivered' => 'house-check');
if ($order)
{
	$status  = (string) $order['order_status'];
	$reached = array('Pending' => 1, 'Ready For Dispatch' => 2, 'Dispatched' => 3, 'Delivered' => 5, 'Closed' => 5)[$status] ?? 1;
	$stopped = in_array($status, array('Canceled', 'Rejected', 'Return'), TRUE);
	$placed  = strtotime((string) $order['order_received_date']);
	$expect  = $status === 'Delivered' || $status === 'Closed'
		? 'Delivered'.($order['order_delivery_date'] ? ' on '.date('j M Y', strtotime((string) $order['order_delivery_date'])) : '')
		: expected_delivery($order); /* delivery days of the order's delivery method (Manage Shipping) */
}
if (empty($is_frame))
{
	echo view('top_application');
	echo view('inner_hero', array('hero' => array('title' => 'Track Order', 'banner' => 'My Account', 'crumbs' => array('Track Order' => NULL))));
	echo '<section class="container-xxl kt-container kt-sec"><div class="kt-shop-card bg-white border mx-auto" style="max-width:640px">';
}
else
{
	echo view('top_application', array('is_frame' => TRUE));
	echo '<main class="kt-frame kt-pop-page">';
}
?>
<div class="kt-form-modal-head d-flex align-items-center justify-content-between">
<div class="d-flex align-items-center kt-gap-12">
<span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-truck"></i></span>
<h1 class="kt-shop-title fw-bold text-dark mb-0">Track Order<?php if ($order): ?> <span class="kt-shop-count">#<?php echo html_escape($order['invoice_number']); ?></span><?php endif; ?></h1>
</div>
<?php if ( ! empty($is_frame)): ?><button type="button" class="btn btn-kt-close kt-btn-42 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" data-kt-close aria-label="Close"><i class="bi bi-x-lg"></i></button><?php endif; ?>
</div>
<div class="kt-form-modal-body">
<?php if ($order): ?>
<section>
<ul class="kt-trk-info list-unstyled d-flex flex-wrap">
<li>Courier: <b class="text-dark"><?php echo html_escape($order['courier_company_name'] ?: 'Assigned when shipped'); ?></b></li>
<li>Tracking No.: <b class="text-dark"><?php echo $order['bill_url'] ? '<a href="'.html_escape($order['bill_url']).'" target="_blank" rel="noopener">'.html_escape($order['bill_number'] ?: 'Track').'</a>' : html_escape($order['bill_number'] ?: 'Assigned when shipped'); ?></b></li>
<li class="w-100">Delivery: <b class="text-dark"><?php echo html_escape($stopped ? \Modules\Members\Controllers\Members::STATUS_LABELS[$status] ?? $status : $expect); ?></b></li>
</ul>
<?php if ($stopped): ?>
<p class="kt-ord-note is-warn d-flex align-items-center gap-2 mb-0"><i class="bi bi-info-circle"></i> This order is <?php echo html_escape(strtolower(\Modules\Members\Controllers\Members::STATUS_LABELS[$status] ?? $status)); ?>.</p>
<?php else: ?>
<ol class="kt-trk">
<?php $n = 0; foreach ($steps as $name => $icon): $n++; $class = $n < $reached || ($n === $reached && $reached === 5) ? 'is-done' : ($n === $reached ? 'is-current' : ''); ?>
<li<?php echo $class ? ' class="'.$class.'"' : ''; ?>><span class="kt-trk-dot rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-<?php echo $icon; ?>"></i></span><b class="kt-trk-name d-block text-dark"><?php echo $name; ?></b><span class="kt-trk-text d-block"><?php echo $n === 1 ? date('j M Y, g:i A', $placed) : ($n <= $reached ? 'Done' : ($n === 5 ? 'Expected '.html_escape($expect) : 'Pending')); ?></span></li>
<?php endforeach; ?>
</ol>
<?php endif; ?>
</section>
<?php else: ?>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?php echo html_escape($error); ?></div><?php endif; ?>
<?php echo form_open(site_url('track-order').( ! empty($is_frame) ? '?frame=1' : ''), array('class' => 'kt-validate', 'novalidate' => 'novalidate')); ?>
<p class="kt-shop-text text-body-secondary">Enter your order number and the email address used for the order.</p>
<div class="row kt-g-ctf">
<div class="col-12 col-md-6"><label for="ktTrkOrder" class="kt-ct-label fw-bold">Order Number <span class="text-danger">*</span></label><input type="text" id="ktTrkOrder" name="order" required maxlength="30" placeholder="e.g. KT10001" class="form-control kt-input rounded-pill" value="<?php echo html_escape($invoice); ?>"></div>
<div class="col-12 col-md-6"><label for="ktTrkEmail" class="kt-ct-label fw-bold">Email Address <span class="text-danger">*</span></label><input type="email" id="ktTrkEmail" name="email" required maxlength="120" placeholder="you@example.com" class="form-control kt-input rounded-pill" value="<?php echo html_escape($email); ?>"></div>
</div>
<button type="submit" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2 mt-3"><i class="bi bi-search"></i> TRACK ORDER</button>
<?php echo form_close(); ?>
<?php endif; ?>
</div>
<?php
if (empty($is_frame))
{
	echo '</div></section>';
	echo view('bottom_application');
}
else
{
	echo '</main>';
	echo view('bottom_application', array('is_frame' => TRUE));
}
