<?php
/* One order line's cancel / return request and its refund (Returns::view()). */
$requested = array('0' => 'none', '1' => 'requested (open)', '2' => 'accepted', '3' => 'rejected');
$total     = round((float) $row['product_price'] * (int) $row['quantity'], 2);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="req-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="req-title">Request</h2>
		<?php if ($order): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/orders/view/'.(int) $order['order_id']); ?>">Open order <?php echo html_escape($order['invoice_number']); ?></a><?php endif; ?>
	</header>
	<dl class="row mb-0">
		<dt class="col-sm-3">Item</dt><dd class="col-sm-9"><?php echo html_escape($row['product_name']); ?> (<?php echo html_escape($row['product_code']); ?>) · <?php echo (int) $row['quantity']; ?> × <?php echo display_price($row['product_price']); ?> = <?php echo display_price($total); ?></dd>
		<dt class="col-sm-3">Item status</dt><dd class="col-sm-9"><?php echo html_escape($row['product_order_status']); ?></dd>
		<dt class="col-sm-3">Cancellation</dt><dd class="col-sm-9"><?php echo $requested[$row['is_cancel']] ?? $row['is_cancel']; ?></dd>
		<dt class="col-sm-3">Return</dt><dd class="col-sm-9"><?php echo $requested[$row['is_return']] ?? $row['is_return']; ?></dd>
		<dt class="col-sm-3">Reason</dt><dd class="col-sm-9"><?php echo html_escape((string) $row['reason'] !== '' ? $row['reason'] : '—'); ?></dd>
		<dt class="col-sm-3">Comments</dt><dd class="col-sm-9"><?php echo nl2br(html_escape((string) $row['reason_comment'] !== '' ? $row['reason_comment'] : '—')); ?></dd>
		<dt class="col-sm-3">Requested on</dt><dd class="col-sm-9"><?php echo $row['request_date'] ? html_escape(sp_datetime($row['request_date'])) : '—'; ?></dd>
		<?php if ($order): ?><dt class="col-sm-3">Payment</dt><dd class="col-sm-9"><?php echo html_escape($order['payment_status']); ?> · <?php echo html_escape(payment_method_label((string) $order['payment_method'])); ?></dd><?php endif; ?>
	</dl>
	<p class="form-text mb-0 mt-2">Accept or reject an open request on the order's page; accepting gives the item's stock back.</p>
</section>
<section class="sp-panel mb-4" aria-labelledby="refund-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="refund-title">Refund</h2></header>
	<?php if ($editPrvg): ?>
	<?php echo form_open('sitepanel/returns/refund/'.(int) $row['orders_products_id'], array('class' => 'row g-3')); ?>
		<div class="col-12 col-md-4">
			<label class="form-label" for="refund_status">Refund status <span class="text-danger">*</span></label>
			<select class="form-select" id="refund_status" name="refund_status" required>
				<?php foreach ($statuses as $status): ?><option value="<?php echo $status; ?>"<?php echo $row['refund_status'] === $status ? ' selected' : ''; ?>><?php echo $status; ?></option><?php endforeach; ?>
			</select>
		</div>
		<div class="col-12 col-md-4">
			<label class="form-label" for="refund_amount">Refund amount (<?php echo display_symbol(); ?>) <span class="text-danger">*</span></label>
			<input class="form-control" type="text" inputmode="decimal" id="refund_amount" name="refund_amount" maxlength="11" required value="<?php echo html_escape((float) $row['refund_amount'] > 0 ? $row['refund_amount'] : number_format($total, 2, '.', '')); ?>">
			<div class="form-text">Up to the item total, <?php echo display_price($total); ?> (add the item's share of VAT / delivery yourself when they are refunded).</div>
		</div>
		<div class="col-12 col-md-4">
			<label class="form-label" for="refund_note">Note</label>
			<input class="form-control" type="text" id="refund_note" name="refund_note" maxlength="255" value="<?php echo html_escape((string) $row['refund_note']); ?>">
			<div class="form-text">E.g. the payment reference of the refund.</div>
		</div>
		<div class="col-12"><button class="btn sp-btn-primary" type="submit">Save refund</button><?php if ($row['refund_date']): ?> <span class="sp-muted small">Refunded on <?php echo html_escape(sp_datetime($row['refund_date'])); ?></span><?php endif; ?></div>
	<?php echo form_close(); ?>
	<?php else: ?>
		<p class="mb-0"><?php echo html_escape($row['refund_status'] ?: 'No refund recorded'); ?><?php echo (float) $row['refund_amount'] > 0 ? ' · '.display_price($row['refund_amount']) : ''; ?></p>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
