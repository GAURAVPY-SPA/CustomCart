<?php
/*
| Totals of a placed order (design: the "Order Total" card of
| order-confirmation.htm / invoice.htm). $order (wl_order row), $items
| (its lines), $title ('Order Total').
*/
$units   = array_sum(array_map('intval', array_column($items, 'quantity')));
$percent = rtrim(rtrim(number_format((float) $order['vat_applied_cent'], 2), '0'), '.');
$paid    = $order['payment_status'] === 'Paid';
?>
<div class="kt-shop-card kt-sum bg-white border">
<div class="kt-sum-title fw-bold text-dark"><?php echo isset($title) ? $title : 'Order Total'; ?></div>
<dl class="kt-sum-list mb-0">
<div class="kt-sum-row"><dt>Total Product Price <span class="kt-sum-note"><?php echo $units; ?> item<?php echo $units === 1 ? '' : 's'; ?></span></dt><dd><?php echo display_price($order['total_amount']); ?></dd></div>
<?php if ((float) $order['coupon_discount_amount'] > 0): ?>
<div class="kt-sum-row is-save"><dt>Coupon Discount <span class="kt-sum-note">(<?php echo html_escape($order['coupon_code']); ?>)</span></dt><dd>- <?php echo display_price($order['coupon_discount_amount']); ?></dd></div>
<?php endif; ?>
<div class="kt-sum-row kt-sum-sub"><dt>Subtotal</dt><dd><?php echo display_price((float) $order['total_amount'] - (float) $order['coupon_discount_amount']); ?></dd></div>
<div class="kt-sum-row"><dt>Shipping &amp; Delivery Charges</dt><dd><?php echo (float) $order['shipping_amount'] > 0 ? display_price($order['shipping_amount']) : 'FREE'; ?></dd></div>
<?php if ((float) $order['vat_amount'] > 0): ?>
<div class="kt-sum-row"><dt>VAT (<?php echo $percent; ?>%)</dt><dd><?php echo display_price($order['vat_amount']); ?></dd></div>
<?php endif; ?>
</dl>
<div class="kt-sum-total d-flex align-items-center justify-content-between"><span><?php echo $paid ? 'Total Paid' : 'Total Payable'; ?></span><b><?php echo display_price(\Modules\Cart\Models\OrderModel::payable($order)); ?></b></div>
</div>
