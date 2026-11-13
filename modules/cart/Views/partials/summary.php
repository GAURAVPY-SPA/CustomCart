<?php
/*
| Cart / order summary card (design: the .kt-sum card of cart.htm, checkout.htm
| and payment.htm). $summary (ShopCart::summary()); $items TRUE: the Order
| Summary with its products and Edit Cart (checkout steps), else the Cart
| Summary; $actions: HTML of the buttons under the total (cart page);
| $choose_shipping TRUE: the delivery option dropdown (cart and checkout pages, not payment).
*/
$items   = ! empty($items);
$count   = (int) $summary['count'];
$saving  = round($summary['product_discount'] + $summary['discount'], 2);
$percent = rtrim(rtrim(number_format((float) $summary['gst_percent'], 2), '0'), '.');
$methods = ! empty($choose_shipping) && isset($summary['shipping_methods']) ? $summary['shipping_methods'] : array(); /* active delivery options (Manage Shipping): cart and checkout pages */
?>
<div class="kt-shop-card kt-sum bg-white border">
<?php if ($items): ?>
<div class="kt-sum-head d-flex align-items-center justify-content-between">
<div class="kt-sum-title fw-bold text-dark mb-0">Order Summary</div>
<a href="<?php echo site_url('cart'); ?>" class="kt-shop-link fw-bold">EDIT CART</a>
</div>
<div class="kt-os-list">
<?php foreach ($summary['lines'] as $line): $variant = implode(' / ', array_filter(array($line['color_name'], $line['size_name']))); ?>
<div class="kt-os-item d-flex align-items-center">
<span class="kt-os-img position-relative flex-shrink-0"><span class="d-block w-100 h-100 rounded-3 overflow-hidden bg-primary-subtle"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape(product_image_url($line['image'], 160, 160)); ?>" alt="<?php echo html_escape($line['name']); ?>" class="w-100 h-100 object-fit-cover"></span><span class="kt-os-qty rounded-circle fw-bold d-flex align-items-center justify-content-center"><?php echo (int) $line['qty']; ?></span></span>
<div class="flex-grow-1 kt-min-0">
<div class="kt-os-name fw-bold text-dark"><?php echo html_escape($line['name']); ?></div>
<div class="kt-os-var text-body-secondary"><?php echo $variant !== '' ? html_escape($variant).' &middot; ' : ''; ?>Qty <?php echo (int) $line['qty']; ?></div>
<div class="kt-os-price"><span class="fw-bold text-dark"><?php echo display_price($line['price']); ?></span><?php if ($line['regular'] > $line['price']): ?> <s class="kt-os-old"><?php echo display_price($line['regular']); ?></s><?php endif; ?></div>
</div>
<b class="kt-os-total text-dark text-nowrap"><?php echo display_price($line['line_total']); ?></b>
</div>
<?php endforeach; ?>
</div>
<div class="kt-sum-title kt-sum-title-sub fw-bold text-dark">Price Details</div>
<?php else: ?>
<div class="kt-sum-title fw-bold text-dark">Cart Summary</div>
<?php endif; ?>
<dl class="kt-sum-list mb-0">
<div class="kt-sum-row"><dt>Total Product Price <span class="kt-sum-note"><?php echo $count; ?> item<?php echo $count === 1 ? '' : 's'; ?></span></dt><dd><?php echo display_price($summary['regular_total']); ?></dd></div>
<div class="kt-sum-row is-save<?php echo $summary['product_discount'] > 0 ? '' : ' is-off'; ?>"><dt>Product Discount</dt><dd><?php echo $summary['product_discount'] > 0 ? '- '.display_price($summary['product_discount']) : display_price(0); ?></dd></div>
<div class="kt-sum-row is-save<?php echo $summary['discount'] > 0 ? '' : ' is-off'; ?>"><dt>Coupon Discount <span class="kt-sum-note"><?php echo html_escape((string) $summary['coupon_code']); ?></span></dt><dd><?php echo $summary['discount'] > 0 ? '- '.display_price($summary['discount']) : 'Not applied'; ?></dd></div>
<div class="kt-sum-row kt-sum-sub"><dt>Subtotal</dt><dd><?php echo display_price($summary['subtotal'] - $summary['discount']); ?></dd></div>
<div class="kt-sum-row"><dt>Shipping &amp; Delivery Charges<?php if ( ! $methods && (string) ($summary['shipping_method'] ?? '') !== ''): /* no dropdown here (payment page): the delivery option chosen earlier */ ?> (<span class="kt-sum-note"><?php echo html_escape($summary['shipping_method']); ?></span>)<?php endif; ?></dt><dd><?php echo $summary['shipping_amount'] > 0 ? display_price($summary['shipping_amount']) : 'FREE'; ?></dd></div>
<?php if ($methods): /* the delivery option: no name, so it is never sent with the address / payment form around this card */ ?>
</dl>
<div class="kt-sum-ship mb-2">
<label for="ktShipOption<?php echo $items ? 'Os' : 'Cs'; ?>" class="visually-hidden">Delivery option</label>
<select id="ktShipOption<?php echo $items ? 'Os' : 'Cs'; ?>" class="form-select form-select-sm kt-input kt-select rounded-pill" data-kt-shipping="<?php echo site_url('cart/shipping'); ?>" data-kt-items="<?php echo $items ? '1' : '0'; ?>"<?php echo count($methods) < 2 ? ' disabled' : ''; ?>>
<?php foreach ($methods as $id => $m): ?>
<option value="<?php echo (int) $id; ?>"<?php echo (int) $id === (int) $summary['shipping_id'] ? ' selected' : ''; ?>><?php echo html_escape($m['name']); ?> &ndash; <?php echo $m['charge'] > 0 ? display_price($m['charge']) : 'FREE'; ?><?php echo $m['charge'] > 0 && $m['free_above'] > 0 ? ' (free over '.display_price($m['free_above']).')' : ''; ?></option>
<?php endforeach; ?>
</select>
</div>
<dl class="kt-sum-list mb-0">
<?php endif; ?>
<?php if ($summary['gst_percent'] > 0): ?>
<div class="kt-sum-row"><dt>VAT (<?php echo $percent; ?>%)</dt><dd><?php echo display_price($summary['gst_amount']); ?></dd></div>
<?php endif; ?>
</dl>
<div class="kt-sum-total d-flex align-items-center justify-content-between"><span>Total Payable</span><b><?php echo display_price($summary['grand_total']); ?></b></div>
<?php if ($saving > 0): ?><div class="kt-sum-saving fw-bold">You save <?php echo display_price($saving); ?> on this order</div><?php endif; ?>
<?php if ($summary['free_delivery_gap'] > 0): ?><p class="text-body-secondary small mb-0 mt-2">Add <?php echo display_price($summary['free_delivery_gap']); ?> more for free delivery.</p><?php endif; ?>
<?php echo ! empty($actions) ? $actions : ''; ?>
<div class="kt-sum-secure d-flex align-items-center justify-content-center gap-2 text-body-secondary"><i class="bi bi-shield-lock text-primary"></i> Safe and secure payments</div>
</div>
