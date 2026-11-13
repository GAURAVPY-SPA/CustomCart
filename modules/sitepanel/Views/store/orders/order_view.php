<?php
/*
| One order ($res) with its lines ($items): customer, delivery, lines with
| their cancel / return requests, totals, and the forms that change the order
| (Edit privilege). Customer input is always escaped.
*/
use Modules\Cart\Models\OrderModel;
use Modules\Sitepanel\Controllers\Orders;

$payable  = OrderModel::payable($res);
$is_guest = $res['customer_type'] === 'Guest';
$badge    = function ($status) {
	return in_array($status, array('Delivered', 'Closed', 'Paid'), TRUE) ? 'sp-badge--ok'
		: (in_array($status, array('Canceled', 'Cancelled', 'Rejected', 'Return', 'Returned'), TRUE) ? 'sp-badge--error' : 'sp-badge--muted');
};
$requests = array('1' => 'Requested', '2' => 'Accepted', '3' => 'Rejected');
// Parts of this page not used by this project: hidden here only (their routes,
// controller actions and data stay). Set to TRUE to show one again.
$show_item_status = FALSE; // the items' own "Set" status control (Items table, Actions column)
$show_courier     = TRUE;  // the "Courier details" form (order tracking: courier, tracking number and link)
$show_note        = FALSE; // the "Internal note" form / note
// "Email the customer" box of a form (notify=1, ticked by default); each gets its own id.
$notify   = function ($checked = TRUE) {
	static $n = 0;
	$n++;
	return '<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="notify" value="1" id="notify-'.$n.'"'.($checked ? ' checked' : '').'>'
		.'<label class="form-check-label" for="notify-'.$n.'">Email the customer</label></div>';
};
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<section class="sp-panel mb-4" aria-labelledby="order-info">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="order-info">Order <?php echo html_escape($res['invoice_number']); ?></h2>
		<div class="sp-row-actions">
			<span class="sp-badge <?php echo $badge($res['order_status']); ?>"><?php echo html_escape($res['order_status']); ?></span>
			<span class="sp-badge <?php echo $badge($res['payment_status']); ?>"><?php echo html_escape($res['payment_status']); ?></span>
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/orders/invoice/'.(int) $res['order_id']); ?>" target="_blank" rel="noopener">Print invoice<span class="visually-hidden"> (opens in a new tab)</span></a>
		</div>
	</header>
	<div class="row g-4">
		<div class="col-12 col-lg-4">
			<h3 class="h6">Customer</h3>
			<p class="sp-text mb-0">
				<?php echo html_escape(trim($res['first_name'].' '.$res['last_name'])); ?>
				<?php if ($is_guest): ?> <span class="sp-badge sp-badge--muted">Guest</span><?php endif; ?><br>
				<a class="sp-link" href="mailto:<?php echo html_escape($res['email']); ?>"><?php echo html_escape($res['email']); ?></a><br>
				<?php echo html_escape($res['mobile_number']); ?>
				<?php if ((int) $res['customers_id'] > 0 && ! $is_guest): ?><br><a class="sp-link" href="<?php echo site_url('sitepanel/members/view/'.(int) $res['customers_id']); ?>">Customer account</a><?php endif; ?>
			</p>
		</div>
		<div class="col-12 col-lg-4">
			<h3 class="h6">Delivery address</h3>
			<p class="sp-text mb-0">
				<?php echo html_escape($res['shipping_name']); ?><br>
				<?php echo html_escape($res['shipping_address']); ?><br>
				<?php echo html_escape(trim(implode(', ', array_filter(array($res['shipping_city'], $res['shipping_state'], $res['shipping_zipcode']))))); ?><br>
				<?php echo html_escape($res['shipping_country']); ?><br>
				Mobile: <?php echo html_escape($res['shipping_mobile']); ?>
			</p>
		</div>
		<div class="col-12 col-lg-4">
			<h3 class="h6">Order</h3>
			<dl class="sp-details mb-0">
				<div><dt>Placed</dt><dd><?php echo html_escape(sp_datetime($res['order_received_date'])); ?></dd></div>
				<div><dt>Delivery</dt><dd><?php echo html_escape($res['shipping_method']); ?></dd></div>
				<div><dt>Payment</dt><dd><?php echo html_escape(payment_method_label((string) $res['payment_method'])); ?><?php echo $res['payment_date'] ? ' &middot; paid '.html_escape(sp_datetime($res['payment_date'])) : ''; ?></dd></div>
				<?php if ($res['order_delivery_date']): ?><div><dt>Delivered</dt><dd><?php echo html_escape(sp_datetime($res['order_delivery_date'])); ?></dd></div><?php endif; ?>
				<?php if ((string) $res['bill_number'] !== ''): ?>
				<div><dt>Courier</dt><dd><?php echo html_escape((string) $res['courier_company_name']); ?> &middot; <?php echo html_escape($res['bill_number']); ?>
					<?php if (preg_match('#^https?://#i', (string) $res['bill_url'])): ?> &middot; <a class="sp-link sp-link--external" href="<?php echo html_escape($res['bill_url']); ?>" target="_blank" rel="noopener noreferrer">Track<span class="visually-hidden"> (opens in a new tab)</span></a><?php endif; ?></dd></div>
				<?php endif; ?>
			</dl>
		</div>
		<?php if ((string) $res['delivery_notes'] !== ''): ?>
		<div class="col-12">
			<h3 class="h6">Delivery notes from the customer</h3>
			<p class="sp-text mb-0"><?php echo nl2br(html_escape($res['delivery_notes'])); ?></p>
		</div>
		<?php endif; ?>
	</div>
</section>

<section class="sp-panel mb-4" aria-labelledby="order-items">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="order-items">Items</h2></header>
	<?php if ($items === FALSE): ?>
		<p class="sp-empty mb-0">The items could not be loaded. Please try again shortly.</p>
	<?php else: ?>
	<div class="sp-table-wrap">
		<table class="sp-table sp-table--stack">
			<caption class="visually-hidden">Items of order <?php echo html_escape($res['invoice_number']); ?></caption>
			<thead>
				<tr><th scope="col">Product</th><th scope="col">Price</th><th scope="col">Qty</th><th scope="col">Total</th><th scope="col">Status</th><?php if ($editPrvg && $show_item_status): ?><th scope="col"><span class="visually-hidden">Actions</span></th><?php endif; ?></tr>
			</thead>
			<tbody>
				<?php foreach ($items as $item): ?>
				<?php $label = html_escape($item['product_name']); ?>
				<tr>
					<td class="sp-cell-text" data-label="Product">
						<div class="d-flex gap-2 align-items-start">
							<img class="sp-thumb" src="<?php echo html_escape(product_image_url($item['product_image'], 192, 108)); ?>" alt="" width="56" height="42" loading="lazy">
							<div>
								<?php echo $label; ?>
								<span class="sp-muted d-block small"><?php echo html_escape($item['product_code']); ?><?php echo (string) $item['product_brand'] !== '' ? ' &middot; '.html_escape($item['product_brand']) : ''; ?></span>
								<?php if ((string) $item['product_color'] !== '' || (string) $item['product_size'] !== ''): ?>
								<span class="d-block small">
									<?php if ((string) $item['product_color'] !== ''): ?>
									<span class="d-inline-block align-middle rounded-circle border" style="width:12px;height:12px;background:<?php echo preg_match('/^#[0-9a-f]{6}$/i', (string) $item['product_color_code']) ? $item['product_color_code'] : 'transparent'; ?>" aria-hidden="true"></span>
									<?php echo html_escape($item['product_color']); ?>
									<?php endif; ?>
									<?php echo (string) $item['product_size'] !== '' ? ((string) $item['product_color'] !== '' ? ' &middot; ' : '').'Size '.html_escape($item['product_size']) : ''; ?>
								</span>
								<?php endif; ?>
								<?php foreach (array('is_cancel' => 'Cancel', 'is_return' => 'Return') as $flag => $word): ?>
								<?php if ($item[$flag] !== '0'): ?>
								<div class="small mt-1">
									<strong><?php echo $word; ?> request: <?php echo $requests[$item[$flag]]; ?></strong>
									<?php echo $item['request_date'] ? '<span class="sp-muted"> &middot; '.html_escape(sp_datetime($item['request_date'])).'</span>' : ''; ?>
									<?php if ((string) $item['reason'] !== ''): ?><span class="d-block">Reason: <?php echo html_escape($item['reason']); ?></span><?php endif; ?>
									<?php if ((string) $item['reason_comment'] !== ''): ?><span class="d-block">“<?php echo nl2br(html_escape($item['reason_comment'])); ?>”</span><?php endif; ?>
									<?php if ($item[$flag] === '1' && $editPrvg): ?>
									<?php $type = $flag === 'is_cancel' ? 'cancel' : 'return'; ?>
									<?php echo form_open('sitepanel/orders/item/'.(int) $item['orders_products_id'], array('class' => 'sp-row-actions mt-1')); ?>
										<button class="btn sp-btn-primary" type="submit" name="action" value="accept_<?php echo $type; ?>">Accept<span class="visually-hidden"> <?php echo $type; ?> of <?php echo $label; ?></span></button>
										<button class="btn sp-btn-outline" type="submit" name="action" value="reject_<?php echo $type; ?>">Reject<span class="visually-hidden"> <?php echo $type; ?> of <?php echo $label; ?></span></button>
										<?php echo $notify(); ?>
									<?php echo form_close(); ?>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
					</td>
					<td data-label="Price"><?php echo display_price($item['product_price']); ?></td>
					<td data-label="Qty"><?php echo (int) $item['quantity']; ?></td>
					<td data-label="Total"><?php echo display_price((float) $item['product_price'] * (int) $item['quantity']); ?></td>
					<td data-label="Status">
						<span class="sp-badge <?php echo $badge($item['product_order_status']); ?>"><?php echo html_escape($item['product_order_status']); ?></span>
						<?php if ($item['is_stock_back'] === '1'): ?><span class="sp-muted d-block small">Stock given back</span><?php endif; ?>
					</td>
					<?php if ($editPrvg && $show_item_status): ?>
					<td data-label="Actions">
						<?php echo form_open('sitepanel/orders/item/'.(int) $item['orders_products_id'], array('class' => 'sp-row-actions')); ?>
							<input type="hidden" name="action" value="status">
							<label class="visually-hidden" for="item-status-<?php echo (int) $item['orders_products_id']; ?>">Status of <?php echo $label; ?></label>
							<select class="form-select form-select-sm" id="item-status-<?php echo (int) $item['orders_products_id']; ?>" name="item_status">
								<?php foreach (Orders::ITEM_STATUSES as $status): ?>
								<option value="<?php echo html_escape($status); ?>"<?php echo $item['product_order_status'] === $status ? ' selected' : ''; ?>><?php echo html_escape($status); ?></option>
								<?php endforeach; ?>
							</select>
							<button class="btn sp-btn-outline" type="submit" data-sp-confirm="Change the status of this item? Cancelled, Rejected and Returned give its stock back.">Set<span class="visually-hidden"> status of <?php echo $label; ?></span></button>
						<?php echo form_close(); ?>
					</td>
					<?php endif; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<dl class="sp-details mt-3 ms-auto" style="max-width:420px">
		<div><dt>Subtotal</dt><dd><?php echo display_price($res['total_amount']); ?></dd></div>
		<?php if ((float) $res['coupon_discount_amount'] > 0): ?>
		<div><dt>Coupon<?php echo (string) $res['coupon_code'] !== '' ? ' ('.html_escape($res['coupon_code']).')' : ''; ?></dt><dd>&minus;<?php echo display_price($res['coupon_discount_amount']); ?></dd></div>
		<?php endif; ?>
		<div><dt>Delivery</dt><dd><?php echo (float) $res['shipping_amount'] > 0 ? display_price($res['shipping_amount']) : 'Free'; ?></dd></div>
		<div><dt><strong>Grand total</strong></dt><dd><strong><?php echo display_price($payable); ?></strong></dd></div>
		<?php if ((float) $res['vat_amount'] > 0): ?>
		<div><dt>Includes GST (<?php echo rtrim(rtrim(number_format((float) $res['vat_applied_cent'], 2), '0'), '.'); ?>%)</dt><dd><?php echo display_price($res['vat_amount']); ?></dd></div>
		<?php endif; ?>
	</dl>
	<?php if ($items && array_filter(array_column($items, 'product_order_status'), function ($s) { return in_array($s, OrderModel::LINE_ENDED, TRUE); })): ?>
	<p class="sp-muted small mb-0 text-end">The totals are those of the order as placed; cancelled, rejected or returned items are not deducted.</p>
	<?php endif; ?>
</section>

<?php if ($editPrvg): ?>
<div class="row g-4 mb-4">
	<div class="col-12 col-xl-6">
		<section class="sp-panel h-100" aria-labelledby="order-status">
			<header class="sp-panel-head"><h2 class="sp-panel-title" id="order-status">Order status</h2></header>
			<?php echo form_open('sitepanel/orders/status/'.(int) $res['order_id']); ?>
				<label class="form-label" for="order_status">Status</label>
				<select class="form-select" id="order_status" name="order_status">
					<?php foreach (array_keys(OrderModel::STATUS_FLOW) as $status): ?>
					<option value="<?php echo html_escape($status); ?>"<?php echo $res['order_status'] === $status ? ' selected' : ''; ?>><?php echo html_escape($status); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="form-text">Items follow the order, except those cancelled, rejected or returned on their own. Canceled and Rejected give the stock back.</div>
				<?php echo $notify(); ?>
				<div class="sp-form-actions mt-3"><button class="btn sp-btn-primary" type="submit">Update status</button></div>
			<?php echo form_close(); ?>
		</section>
	</div>
	<div class="col-12 col-xl-6">
		<section class="sp-panel h-100" aria-labelledby="order-payment">
			<header class="sp-panel-head"><h2 class="sp-panel-title" id="order-payment">Payment</h2></header>
			<p class="sp-text">
				<?php echo html_escape(payment_method_label((string) $res['payment_method'])); ?>: <strong><?php echo display_price($payable); ?></strong>,
				currently <strong><?php echo html_escape($res['payment_status']); ?></strong>.
				<?php if ($res['payment_status'] !== 'Paid' && $res['payment_method'] === 'paynow'): ?>The customer pays by PayNow / bank transfer with <?php echo html_escape($res['invoice_number']); ?> as the reference.<?php endif; ?>
			</p>
			<?php echo form_open('sitepanel/orders/payment/'.(int) $res['order_id']); ?>
				<input type="hidden" name="payment_status" value="<?php echo $res['payment_status'] === 'Paid' ? 'Unpaid' : 'Paid'; ?>">
				<?php if ($res['payment_status'] !== 'Paid'): ?><?php echo $notify(); ?><?php endif; ?>
				<div class="sp-form-actions mt-3"><button class="btn sp-btn-<?php echo $res['payment_status'] === 'Paid' ? 'outline' : 'primary'; ?>" type="submit"><?php echo $res['payment_status'] === 'Paid' ? 'Mark as unpaid' : 'Mark as paid'; ?></button></div>
			<?php echo form_close(); ?>
		</section>
	</div>
	<?php if ($show_courier): ?>
	<div class="col-12 col-xl-6">
		<section class="sp-panel h-100" id="courier" aria-labelledby="order-courier">
			<header class="sp-panel-head"><h2 class="sp-panel-title" id="order-courier">Courier details</h2></header>
			<?php echo form_open('sitepanel/orders/courier/'.(int) $res['order_id'], array('novalidate' => 'novalidate')); ?>
				<div class="row g-3">
					<div class="col-12 col-md-6">
						<label class="form-label" for="courier_company_name">Courier company <span class="text-danger">*</span></label>
						<input class="form-control" type="text" id="courier_company_name" name="courier_company_name" maxlength="150" required value="<?php echo set_value('courier_company_name', (string) $res['courier_company_name']); ?>">
					</div>
					<div class="col-12 col-md-6">
						<label class="form-label" for="bill_number">Tracking number <span class="text-danger">*</span></label>
						<input class="form-control" type="text" id="bill_number" name="bill_number" maxlength="50" required value="<?php echo set_value('bill_number', (string) $res['bill_number']); ?>">
					</div>
					<div class="col-12">
						<label class="form-label" for="bill_url">Tracking link</label>
						<input class="form-control" type="url" id="bill_url" name="bill_url" maxlength="455" placeholder="https://" value="<?php echo set_value('bill_url', (string) $res['bill_url']); ?>">
					</div>
				</div>
				<?php $dispatch = in_array($res['order_status'], Orders::COURIER_DISPATCHES, TRUE); ?>
				<?php if ($dispatch): ?>
				<div class="form-text">Saving marks the order Dispatched.</div>
				<?php echo $notify(); ?>
				<?php endif; ?>
				<div class="sp-form-actions mt-3"><button class="btn sp-btn-primary" type="submit"><?php echo $dispatch ? 'Save and dispatch' : 'Save courier details'; ?></button></div>
			<?php echo form_close(); ?>
		</section>
	</div>
	<?php endif; ?>
	<?php if ($show_note): ?>
	<div class="col-12 col-xl-6">
		<section class="sp-panel h-100" id="note" aria-labelledby="order-note">
			<header class="sp-panel-head"><h2 class="sp-panel-title" id="order-note">Internal note</h2></header>
			<?php echo form_open('sitepanel/orders/comment/'.(int) $res['order_id']); ?>
				<label class="form-label visually-hidden" for="admin_comment">Internal note</label>
				<textarea class="form-control" id="admin_comment" name="admin_comment" rows="5" maxlength="2000"><?php echo set_value('admin_comment', (string) $res['admin_comment']); ?></textarea>
				<div class="form-text">For administrators only; never shown to the customer.</div>
				<div class="sp-form-actions mt-3"><button class="btn sp-btn-primary" type="submit">Save note</button></div>
			<?php echo form_close(); ?>
		</section>
	</div>
	<?php endif; ?>
</div>
<?php elseif ($show_note && (string) $res['admin_comment'] !== ''): ?>
<section class="sp-panel mb-4" aria-labelledby="order-note">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="order-note">Internal note</h2></header>
	<p class="sp-text mb-0"><?php echo nl2br(html_escape($res['admin_comment'])); ?></p>
</section>
<?php endif; ?>

<div class="sp-form-actions mb-4">
	<?php if ($deletePrvg): ?>
	<?php echo form_open('sitepanel/orders/delete/'.(int) $res['order_id'], array('data-sp-confirm' => 'Delete order '.$res['invoice_number'].'? If it has not been dispatched, its stock is given back.')); ?>
		<button class="btn sp-btn-outline sp-btn-danger" type="submit">Delete order</button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/orders'); ?>">Back to orders</a>
</div>
<?php echo sp_view('includes/footer'); ?>
