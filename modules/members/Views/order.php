<?php
/*
| Order details / invoice (design: html/invoice.htm), see Members::order().
| Above the invoice: each item's status with Cancel / Return (popups:
| item-request?type=cancel|return) and its refund status; Print opens the
| printable invoice (invoice-print).
| $order, $items, $requests [orders_products_id => [cancel, return]].
*/
$request_label = array('1' => 'requested', '2' => 'accepted', '3' => 'rejected');
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Invoice', 'banner' => 'My Account', 'crumbs' => array('My Account' => site_url('dashboard'), 'Orders History' => site_url('acc-order-history'), 'Invoice' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-inv-page">
<?php echo view('Modules\Products\Views\partials\flash'); ?>
<div class="kt-inv-bar kt-no-print d-flex flex-wrap align-items-center justify-content-between">
<a href="<?php echo site_url('acc-order-history'); ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-left"></i> BACK TO ORDERS</a>
<a href="<?php echo html_escape(order_url('invoice-print', $order)); ?>" target="_blank" rel="noopener" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-printer"></i> PRINT INVOICE</a>
</div>
<div class="kt-shop-card bg-white border kt-no-print mb-4">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Items, Returns &amp; Refunds</h2>
<a href="<?php echo site_url('track-order'); ?>?order=<?php echo rawurlencode($order['invoice_number']); ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2" data-kt-pop="<?php echo site_url('track-order'); ?>?frame=1" data-kt-order="<?php echo html_escape($order['invoice_number']); ?>"><i class="bi bi-geo"></i> TRACK ORDER</a>
</div>
<ul class="kt-ord-prods list-unstyled mb-0">
<?php foreach ($items as $item):
	$iid     = (int) $item['orders_products_id'];
	$variant = implode(' / ', array_filter(array($item['product_color'], $item['product_size'])));
	$notes   = array();
	isset($request_label[$item['is_cancel']]) && $notes[] = 'Cancellation '.$request_label[$item['is_cancel']];
	isset($request_label[$item['is_return']]) && $notes[] = 'Return '.$request_label[$item['is_return']];
	(string) $item['refund_status'] !== '' && $notes[] = 'Refund '.strtolower($item['refund_status']).((float) $item['refund_amount'] > 0 ? ': '.display_price($item['refund_amount']) : '');
	$base = site_url('item-request').'?order='.\Modules\Cart\Models\OrderModel::reference($order['invoice_number']).'&item='.$iid;
?>
<li class="kt-ord-item flex-wrap"><span class="kt-ord-thumb rounded-3 overflow-hidden bg-primary-subtle"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape(product_image_url($item['product_image'], 120, 120)); ?>" alt="<?php echo html_escape($item['product_name']); ?>" class="w-100 h-100 object-fit-cover"></span><span class="kt-ord-info kt-min-0 flex-grow-1"><b class="kt-ord-name d-block text-dark"><?php echo html_escape($item['product_name']); ?></b><span class="kt-ord-var d-block text-body-secondary">Qty <?php echo (int) $item['quantity']; ?><?php echo $variant !== '' ? ' &middot; '.html_escape($variant) : ''; ?> &middot; <?php echo html_escape(\Modules\Members\Controllers\Members::STATUS_LABELS[$item['product_order_status']] ?? $item['product_order_status']); ?></span><?php if ($notes): ?><span class="kt-ord-var d-block fw-bold text-dark"><?php echo html_escape(implode(' · ', $notes)); ?></span><?php endif; ?></span>
<span class="d-flex gap-2">
<?php if ($requests[$iid]['cancel']): ?><a href="<?php echo html_escape($base); ?>&amp;type=cancel" class="btn btn-kt-outline kt-ord-btn rounded-pill" data-kt-pop="<?php echo html_escape($base); ?>&amp;type=cancel">CANCEL ITEM</a><?php endif; ?>
<?php if ($requests[$iid]['return']): ?><a href="<?php echo html_escape($base); ?>&amp;type=return" class="btn btn-kt-outline kt-ord-btn rounded-pill" data-kt-pop="<?php echo html_escape($base); ?>&amp;type=return">RETURN ITEM</a><?php endif; ?>
</span></li>
<?php endforeach; ?>
</ul>
</div>
<?php echo view('Modules\Members\Views\partials\invoice_doc', array('order' => $order, 'items' => $items, 'settings' => service('siteSettings'))); ?>
</section>
<?php echo view('bottom_application'); ?>
