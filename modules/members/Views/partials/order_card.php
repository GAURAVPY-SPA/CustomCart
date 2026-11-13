<?php
/*
| One order in My Account (design: the .kt-ord cards of dashboard.htm /
| order-history.htm). $order: OrderModel::get_customer_orders() row with
| 'items' and 'payable'. data-status feeds the history's filter chips.
*/
$status  = (string) $order['order_status'];
$group   = in_array($status, array('Delivered', 'Closed'), TRUE) ? 'delivered' : (in_array($status, array('Canceled', 'Rejected'), TRUE) ? 'cancelled' : ($status === 'Return' ? 'returns' : 'progress'));
$class   = array('Pending' => 'is-confirmed', 'Ready For Dispatch' => 'is-confirmed', 'Dispatched' => 'is-shipped', 'Delivered' => 'is-delivered', 'Closed' => 'is-delivered', 'Canceled' => 'is-cancelled', 'Rejected' => 'is-cancelled', 'Return' => 'is-return');
$icon    = array('is-confirmed' => 'check2-circle', 'is-shipped' => 'truck', 'is-delivered' => 'box-seam', 'is-cancelled' => 'x-circle', 'is-return' => 'arrow-return-left');
$label   = \Modules\Members\Controllers\Members::STATUS_LABELS[$status] ?? $status;
$css     = $class[$status] ?? 'is-confirmed';
$units   = array_sum(array_map('intval', array_column($order['items'], 'quantity')));
$paid    = $order['payment_status'] === 'Paid';
$refund  = in_array('Refunded', array_column($order['items'], 'refund_status'), TRUE);
$placed  = strtotime((string) $order['order_received_date']);
$pending = count(array_filter($order['items'], static fn ($i) => $i['is_cancel'] === '1' || $i['is_return'] === '1'));
?>
<article class="kt-ord" data-status="<?php echo $group; ?>">
<div class="kt-ord-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-ord-id kt-min-0">
<div class="kt-ord-id-top d-flex flex-wrap align-items-center"><b class="kt-ord-no text-dark">#<?php echo html_escape($order['invoice_number']); ?></b><span class="kt-status <?php echo $css; ?> rounded-pill fw-bold"><i class="bi bi-<?php echo $icon[$css]; ?>"></i> <?php echo html_escape($label); ?></span></div>
<span class="kt-ord-sub d-flex align-items-center gap-2"><i class="bi bi-calendar3"></i> Placed on <?php echo date('j M Y', $placed); ?> <span class="kt-ord-dot" aria-hidden="true"></span> <?php echo $units; ?> item<?php echo $units === 1 ? '' : 's'; ?></span>
</div>
<div class="kt-ord-amt">
<b class="kt-ord-total d-block text-dark"><span class="visually-hidden">Order total: </span><?php echo display_price($order['payable']); ?></b>
<?php if ($refund): ?>
<span class="kt-ord-pay is-refunded d-inline-flex align-items-center gap-1"><i class="bi bi-arrow-counterclockwise"></i> Refunded</span>
<?php else: ?>
<span class="kt-ord-pay d-inline-flex align-items-center gap-1"><i class="bi bi-<?php echo $paid ? 'check-circle-fill' : 'hourglass-split'; ?>"></i> <?php echo $paid ? 'Paid' : 'Payment pending'; ?></span>
<?php endif; ?>
</div>
</div>
<div class="kt-ord-body">
<ul class="kt-ord-prods list-unstyled mb-0">
<?php foreach ($order['items'] as $item): $variant = implode(' / ', array_filter(array($item['product_color'], $item['product_size']))); ?>
<li class="kt-ord-item"><span class="kt-ord-thumb rounded-3 overflow-hidden bg-primary-subtle"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape(product_image_url($item['product_image'], 120, 120)); ?>" alt="<?php echo html_escape($item['product_name']); ?>" class="w-100 h-100 object-fit-cover"></span><span class="kt-ord-info kt-min-0"><b class="kt-ord-name d-block text-dark"><?php echo html_escape($item['product_name']); ?></b><span class="kt-ord-var d-block text-body-secondary">SKU: <?php echo html_escape($item['product_code']); ?><?php echo $variant !== '' ? ' &middot; '.html_escape($variant) : ''; ?></span></span><span class="kt-ord-q rounded-pill fw-bold">Qty <?php echo (int) $item['quantity']; ?></span></li>
<?php endforeach; ?>
</ul>
<?php if ($pending): ?>
<div class="kt-ord-note is-warn d-flex align-items-center gap-2"><i class="bi bi-hourglass-split"></i> <?php echo $pending; ?> cancel / return request<?php echo $pending === 1 ? '' : 's'; ?> under review</div>
<?php elseif ($group === 'progress'): ?>
<div class="kt-ord-note is-info d-flex align-items-center gap-2"><i class="bi bi-truck"></i> Expected delivery: <?php echo html_escape(expected_delivery($order)); ?></div>
<?php endif; ?>
</div>
<div class="kt-ord-acts d-flex flex-wrap justify-content-end">
<a href="<?php echo html_escape(order_url('acc-order-invoice', $order)); ?>" class="btn btn-kt-outline kt-ord-btn rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-receipt"></i> VIEW DETAILS</a>
<a href="<?php echo site_url('track-order'); ?>?order=<?php echo rawurlencode($order['invoice_number']); ?>" class="btn btn-kt-dark kt-ord-btn rounded-pill d-inline-flex align-items-center gap-2" data-kt-pop="<?php echo site_url('track-order'); ?>?frame=1" data-kt-order="<?php echo html_escape($order['invoice_number']); ?>"><i class="bi bi-geo"></i> TRACK ORDER</a>
</div>
</article>
