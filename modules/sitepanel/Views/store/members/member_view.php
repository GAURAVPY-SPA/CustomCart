<?php
/* One customer ($res): profile, default delivery address ($address), recent orders ($orders). Customer input: always escaped. */
echo sp_view('includes/header');
$status_label = array('1' => 'Active', '0' => 'Inactive', '3' => 'Guest checkout');
$fields = array(
	'Name'           => trim($res['first_name'].' '.$res['last_name']),
	'Email'          => $res['user_name'],
	'Mobile'         => $res['mobile_number'],
	'Address'        => trim(implode(', ', array_filter(array($res['address'], $res['city'], $res['state'], $res['zipcode'], $res['country'])))),
	'Newsletter'     => $res['newsletter'] === '1' ? 'Subscribed' : 'Not subscribed',
	'Registered'     => sp_datetime($res['account_created_date']),
	'Last login'     => sp_datetime($res['current_login']),
	'Previous login' => sp_datetime($res['last_login_date']),
	'Last IP'        => $res['ip_address'],
);
?>
<section class="sp-panel mb-4" aria-labelledby="customer-info">
	<header class="sp-panel-head">
		<h2 class="sp-panel-title" id="customer-info">Customer #<?php echo (int) $res['customers_id']; ?></h2>
		<span class="sp-badge <?php echo $res['status'] === '1' ? 'sp-badge--ok' : 'sp-badge--muted'; ?>"><?php echo $status_label[$res['status']] ?? $res['status']; ?></span>
	</header>
	<dl class="sp-details">
		<?php foreach ($fields as $label => $text): ?>
		<?php if ((string) $text === '') { continue; } ?>
		<div><dt><?php echo $label; ?></dt><dd>
			<?php if ($label === 'Email'): ?>
			<a class="sp-link" href="mailto:<?php echo html_escape($text); ?>"><?php echo html_escape($text); ?></a>
			<?php else: ?>
			<?php echo html_escape($text); ?>
			<?php endif; ?>
		</dd></div>
		<?php endforeach; ?>
	</dl>
</section>

<section class="sp-panel mb-4" aria-labelledby="customer-address">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="customer-address">Delivery address</h2></header>
	<?php if ($address === FALSE): ?>
		<p class="sp-empty mb-0">The address could not be loaded.</p>
	<?php elseif ( ! $address): ?>
		<p class="sp-empty mb-0">No saved delivery address. It is saved when the customer places an order while signed in.</p>
	<?php else: ?>
		<p class="sp-text mb-0">
			<?php echo html_escape(trim($address['first_name'].' '.$address['last_name'])); ?><br>
			<?php echo html_escape($address['address']); ?><br>
			<?php echo html_escape(trim(implode(', ', array_filter(array($address['city'], $address['state'], $address['zipcode']))))); ?><br>
			<?php echo html_escape($address['country']); ?><br>
			Mobile: <?php echo html_escape($address['mobile']); ?>
		</p>
	<?php endif; ?>
</section>

<section class="sp-panel mb-4" aria-labelledby="customer-orders">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="customer-orders">Orders<?php echo $orders ? ' ('.(int) $order_total.')' : ''; ?></h2>
		<?php if ($order_total > count((array) $orders) && $can_orders): ?>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/orders?customer='.(int) $res['customers_id']); ?>">All orders</a>
		<?php endif; ?>
	</header>
	<?php if ($orders === FALSE): ?>
		<p class="sp-empty mb-0">Orders could not be loaded.</p>
	<?php elseif ( ! $orders): ?>
		<p class="sp-empty mb-0">No orders yet.</p>
	<?php else: ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Latest orders of this customer</caption>
				<thead><tr><th scope="col">Invoice</th><th scope="col">Date</th><th scope="col">Total</th><th scope="col">Payment</th><th scope="col">Status</th></tr></thead>
				<tbody>
					<?php foreach ($orders as $order): ?>
					<tr>
						<td data-label="Invoice">
							<?php if ($can_orders): ?><a class="sp-link" href="<?php echo site_url('sitepanel/orders/view/'.(int) $order['order_id']); ?>"><?php echo html_escape($order['invoice_number']); ?></a>
							<?php else: ?><?php echo html_escape($order['invoice_number']); ?><?php endif; ?>
						</td>
						<td data-label="Date"><?php echo html_escape(sp_datetime($order['order_received_date'])); ?></td>
						<td data-label="Total"><?php echo display_price(\Modules\Cart\Models\OrderModel::payable($order)); ?></td>
						<td data-label="Payment"><span class="sp-badge <?php echo $order['payment_status'] === 'Paid' ? 'sp-badge--ok' : 'sp-badge--muted'; ?>"><?php echo html_escape($order['payment_status']); ?></span></td>
						<td data-label="Status"><?php echo html_escape($order['order_status']); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<div class="sp-form-actions mb-4">
	<?php if ($res['status'] !== '3' && ($res['status'] === '1' ? $deactivatePrvg : $activatePrvg)): ?>
	<?php echo form_open('sitepanel/members/status/'.(int) $res['customers_id']); ?>
		<input type="hidden" name="status" value="<?php echo $res['status'] === '1' ? '0' : '1'; ?>">
		<button class="btn sp-btn-primary" type="submit"><?php echo $res['status'] === '1' ? 'Deactivate account' : 'Activate account'; ?></button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<?php if ($deletePrvg && ! $order_total): ?>
	<?php echo form_open('sitepanel/members/delete/'.(int) $res['customers_id'], array('data-sp-confirm' => 'Delete this customer? This cannot be undone.')); ?>
		<button class="btn sp-btn-outline sp-btn-danger" type="submit">Delete customer</button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/members'); ?>">Back to customers</a>
</div>
<?php echo sp_view('includes/footer'); ?>
