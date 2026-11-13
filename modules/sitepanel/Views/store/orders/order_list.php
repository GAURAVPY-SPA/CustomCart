<?php
/* Orders: summary cards, search + filters, bulk Mark paid / Mark unpaid / Delete. */
use Modules\Cart\Models\OrderModel;
use Modules\Sitepanel\Controllers\Orders;

$filtered = $filters['keyword'] !== '' || $filters['order_status'] !== '' || $filters['payment_status'] !== '' || $filters['from_date'] !== '' || $filters['to_date'] !== '' || $filters['customer_id'] > 0;
$query    = array_filter(array(
	'keyword' => $filters['keyword'], 'order_status' => $filters['order_status'], 'payment_status' => $filters['payment_status'],
	'from_date' => $filters['from_date'], 'to_date' => $filters['to_date'], 'customer' => $filters['customer_id'] ?: '',
), 'strlen');
$bulk_enabled = $editPrvg || $deletePrvg;
$status_badge = function ($status) {
	return in_array($status, array('Delivered', 'Closed'), TRUE) ? 'sp-badge--ok' : (in_array($status, array('Canceled', 'Rejected', 'Return'), TRUE) ? 'sp-badge--error' : 'sp-badge--muted');
};
echo sp_view('includes/header'); ?>
<?php if (is_array($stats)): ?>
<section class="sp-stats mb-4" aria-label="Orders summary">
	<?php foreach (array(
		array('icon' => 'cart', 'label' => 'Orders', 'value' => $stats['total'], 'meta' => 'All orders', 'tone' => 'neutral', 'url' => site_url('sitepanel/orders')),
		array('icon' => 'inbox', 'label' => 'Pending', 'value' => $stats['by_status']['Pending'] ?? 0, 'meta' => 'Waiting to be prepared', 'tone' => 'warn', 'url' => site_url('sitepanel/orders?order_status=Pending')),
		array('icon' => 'section', 'label' => 'Unpaid', 'value' => $stats['unpaid'], 'meta' => 'Not cancelled, awaiting payment', 'tone' => 'error', 'url' => site_url('sitepanel/orders?payment_status=Unpaid')),
		array('icon' => 'dashboard', 'label' => 'Paid', 'value' => display_price($stats['paid_amount']), 'meta' => 'Total of paid orders', 'tone' => 'ok', 'url' => site_url('sitepanel/orders?payment_status=Paid')),
	) as $card): ?>
	<?php echo sp_view('includes/stat_card', array('card' => $card)); ?>
	<?php endforeach; ?>
</section>
<?php endif; ?>

<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title"><?php echo $customer ? 'Orders of '.html_escape(trim($customer['first_name'].' '.$customer['last_name']) ?: $customer['user_name']) : 'Orders'; ?></h2>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/orders'); ?>" role="search" aria-label="Search orders">
		<?php if ($filters['customer_id'] > 0): ?><input type="hidden" name="customer" value="<?php echo (int) $filters['customer_id']; ?>"><?php endif; ?>
		<div>
			<label class="form-label" for="filter-keyword">Invoice, name, email or mobile</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($filters['keyword']); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-order-status">Order status</label>
			<select class="form-select" id="filter-order-status" name="order_status">
				<option value="">All</option>
				<?php foreach (Orders::FILTER_STATUSES as $status): ?>
				<option value="<?php echo html_escape($status); ?>"<?php echo $filters['order_status'] === $status ? ' selected' : ''; ?>><?php echo html_escape($status); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label class="form-label" for="filter-payment">Payment</label>
			<select class="form-select" id="filter-payment" name="payment_status">
				<option value="">All</option>
				<?php foreach (Orders::PAYMENT_STATUSES as $status): ?>
				<option value="<?php echo $status; ?>"<?php echo $filters['payment_status'] === $status ? ' selected' : ''; ?>><?php echo $status; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label class="form-label" for="filter-from">From</label>
			<input class="form-control" type="date" id="filter-from" name="from_date" value="<?php echo html_escape($filters['from_date']); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-to">To</label>
			<input class="form-control" type="date" id="filter-to" name="to_date" value="<?php echo html_escape($filters['to_date']); ?>">
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/orders'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Orders could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No orders on this page.' : ($filtered ? 'No orders match your search.' : 'No orders yet.'); ?></p>
	<?php else: ?>
		<?php if ($bulk_enabled): ?>
		<div class="sp-bulk-toolbar">
			<?php echo form_open('sitepanel/orders/bulk', array('id' => 'sp-bulk-orders', 'class' => 'sp-row-actions', 'data-sp-bulk-form' => 'orders', 'data-sp-bulk-noun' => 'orders')); ?>
				<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
				<?php foreach ($query as $name => $value): ?>
				<input type="hidden" name="<?php echo html_escape($name); ?>" value="<?php echo html_escape($value); ?>">
				<?php endforeach; ?>
				<?php if ($editPrvg): ?>
				<button class="btn sp-btn-outline" type="submit" name="bulk_action" value="paid">Mark paid</button>
				<button class="btn sp-btn-outline" type="submit" name="bulk_action" value="unpaid">Mark unpaid</button>
				<?php endif; ?>
				<?php if ($deletePrvg): ?>
				<button class="btn sp-btn-outline sp-btn-danger" type="submit" name="bulk_action" value="delete" data-sp-confirm="Delete the selected orders? Orders not yet dispatched give their stock back.">Delete</button>
				<?php endif; ?>
			<?php echo form_close(); ?>
		</div>
		<?php endif; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Orders, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all orders" data-sp-select-all="orders" form="sp-bulk-orders"></th><?php endif; ?>
						<th scope="col">Invoice</th>
						<th scope="col">Customer</th>
						<th scope="col">Total</th>
						<th scope="col">Payment</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['order_id']; ?>" aria-label="Select order <?php echo html_escape($row['invoice_number']); ?>" data-sp-select="orders" form="sp-bulk-orders"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Invoice">
							<a class="sp-link" href="<?php echo site_url('sitepanel/orders/view/'.(int) $row['order_id']); ?>"><strong><?php echo html_escape($row['invoice_number']); ?></strong></a>
							<span class="sp-muted d-block small"><?php echo html_escape(sp_datetime($row['order_received_date'])); ?></span>
						</td>
						<td class="sp-cell-text" data-label="Customer">
							<?php echo html_escape(trim($row['first_name'].' '.$row['last_name'])); ?>
							<?php if ($row['customer_type'] === 'Guest'): ?> <span class="sp-badge sp-badge--muted">Guest</span><?php endif; ?>
							<span class="d-block small"><?php echo html_escape($row['email']); ?></span>
						</td>
						<td data-label="Total"><?php echo display_price(OrderModel::payable($row)); ?></td>
						<td data-label="Payment">
							<span class="sp-badge <?php echo $row['payment_status'] === 'Paid' ? 'sp-badge--ok' : 'sp-badge--muted'; ?>"><?php echo html_escape($row['payment_status']); ?></span>
							<span class="sp-muted d-block sp-badge sp-badge--muted"><?php echo html_escape(payment_method_label((string) $row['payment_method'])); ?></span>
						</td>
						<td data-label="Status"><span class="sp-badge <?php echo $status_badge($row['order_status']); ?>"><?php echo html_escape($row['order_status']); ?></span></td>
						<td data-label="Actions">
							<div class="sp-row-actions">
								<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/orders/view/'.(int) $row['order_id']); ?>">View<span class="visually-hidden"> order <?php echo html_escape($row['invoice_number']); ?></span></a>
								<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/orders/invoice/'.(int) $row['order_id']); ?>" target="_blank" rel="noopener">Invoice<span class="visually-hidden"> <?php echo html_escape($row['invoice_number']); ?> (opens in a new tab)</span></a>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ($bulk_enabled): /* the same actions under the table, sent with the bulk form above (form attribute) */ ?>
		<div class="sp-bulk-toolbar mt-3 sp-bulk-toolbar--bottom">
			<div class="sp-row-actions">
				<?php if ($editPrvg): ?>
				<button form="sp-bulk-orders" class="btn sp-btn-outline" type="submit" name="bulk_action" value="paid">Mark paid</button>
				<button form="sp-bulk-orders" class="btn sp-btn-outline" type="submit" name="bulk_action" value="unpaid">Mark unpaid</button>
				<?php endif; ?>
				<?php if ($deletePrvg): ?>
				<button form="sp-bulk-orders" class="btn sp-btn-outline sp-btn-danger" type="submit" name="bulk_action" value="delete" data-sp-confirm="Delete the selected orders? Orders not yet dispatched give their stock back.">Delete</button>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'order' : 'orders', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
