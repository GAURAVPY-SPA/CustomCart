<?php
/* Order items of every order: search, item status / request / date filters, bulk status change. */
$filtered = implode('', $filters) !== '';
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Order Items</h2>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/order_products'); ?>" role="search" aria-label="Search order items">
		<div>
			<label class="form-label" for="filter-keyword">Product, code, order or email</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($filters['keyword']); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-status">Item status</label>
			<select class="form-select" id="filter-status" name="item_status">
				<option value="">All</option>
				<?php foreach ($statuses as $status): ?>
				<option value="<?php echo html_escape($status); ?>"<?php echo $filters['item_status'] === $status ? ' selected' : ''; ?>><?php echo html_escape($status); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label class="form-label" for="filter-request">Requests</label>
			<select class="form-select" id="filter-request" name="request">
				<option value="">All</option>
				<option value="cancel"<?php echo $filters['request'] === 'cancel' ? ' selected' : ''; ?>>Open cancel requests</option>
				<option value="return"<?php echo $filters['request'] === 'return' ? ' selected' : ''; ?>>Open return requests</option>
			</select>
		</div>
		<div>
			<label class="form-label" for="filter-from">Ordered from</label>
			<input class="form-control" type="date" id="filter-from" name="from_date" value="<?php echo html_escape($filters['from_date']); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-to">to</label>
			<input class="form-control" type="date" id="filter-to" name="to_date" value="<?php echo html_escape($filters['to_date']); ?>">
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/order_products'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Order items could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No order items on this page.' : ($filtered ? 'No order items match your search.' : 'No orders yet.'); ?></p>
	<?php else: ?>
		<?php if ($editPrvg): ?>
		<?php echo form_open('sitepanel/order_products/bulk', array('id' => 'sp-bulk-order_products', 'class' => 'sp-filters mb-3', 'aria-label' => 'Change the status of the selected items')); ?>
			<input type="hidden" name="bulk_action" value="status">
			<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
			<?php foreach ($filters as $name => $value): ?>
			<input type="hidden" name="<?php echo html_escape($name); ?>" value="<?php echo html_escape($value); ?>">
			<?php endforeach; ?>
			<div>
				<label class="form-label" for="bulk-item-status">Set selected items to</label>
				<select class="form-select" id="bulk-item-status" name="item_status" required>
					<option value="">Choose a status</option>
					<?php foreach ($statuses as $status): ?>
					<option value="<?php echo html_escape($status); ?>"><?php echo html_escape($status); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="sp-filters-actions">
				<button class="btn sp-btn-outline" type="submit">Apply to selected</button>
			</div>
			<p class="form-text mb-0 w-100">Cancelled, Rejected and Returned give the item's stock back. Use the order's page to answer cancel / return requests.</p>
		<?php echo form_close(); ?>
		<?php endif; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Order items, newest first</caption>
				<thead>
					<tr>
						<?php if ($editPrvg): ?><th scope="col"><input type="checkbox" aria-label="Select all items" data-sp-select-all="order_products" form="sp-bulk-order_products"></th><?php endif; ?>
						<th scope="col">Item</th>
						<th scope="col">Order</th>
						<th scope="col">Customer</th>
						<th scope="col">Qty × price</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $variant = trim(implode(' / ', array_filter(array($row['product_color'], $row['product_size'])))); ?>
					<tr>
						<?php if ($editPrvg): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['orders_products_id']; ?>" aria-label="Select <?php echo html_escape($row['sub_order_id']); ?>" data-sp-select="order_products" form="sp-bulk-order_products"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Item">
							<?php echo html_escape($row['product_name']); ?>
							<span class="sp-muted d-block small"><?php echo html_escape($row['product_code']); ?><?php echo $variant !== '' ? ' · '.html_escape($variant) : ''; ?></span>
							<span class="sp-muted d-block small"><?php echo html_escape($row['sub_order_id']); ?></span>
						</td>
						<td data-label="Order">
							<?php echo html_escape($row['invoice_number']); ?>
							<span class="sp-muted d-block small"><?php echo html_escape(sp_datetime($row['order_received_date'])); ?></span>
							<span class="sp-muted d-block small"><?php echo html_escape($row['order_status']); ?> · <?php echo html_escape($row['payment_status']); ?></span>
						</td>
						<td class="sp-cell-text" data-label="Customer">
							<?php echo html_escape(trim($row['first_name'].' '.$row['last_name'])); ?><?php if ($row['customer_type'] === 'Guest'): ?> <span class="sp-badge sp-badge--muted">Guest</span><?php endif; ?>
							<span class="sp-muted d-block small"><?php echo html_escape($row['email']); ?></span>
						</td>
						<td data-label="Qty × price"><?php echo (int) $row['quantity']; ?> × <?php echo display_price($row['product_price']); ?></td>
						<td data-label="Status">
							<span class="sp-badge<?php echo in_array($row['product_order_status'], array('Delivered', 'Closed'), TRUE) ? ' sp-badge--ok' : (in_array($row['product_order_status'], \Modules\Cart\Models\OrderModel::LINE_ENDED, TRUE) ? ' sp-badge--muted' : ''); ?>"><?php echo html_escape($row['product_order_status']); ?></span>
							<?php if ($row['is_cancel'] === '1'): ?><span class="d-block small text-danger">Cancel requested</span><?php endif; ?>
							<?php if ($row['is_return'] === '1'): ?><span class="d-block small text-danger">Return requested</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php if ($can_orders): ?>
							<div class="sp-row-actions"><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/orders/view/'.(int) $row['orders_id']); ?>">Open order<span class="visually-hidden"> <?php echo html_escape($row['invoice_number']); ?></span></a></div>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'item' : 'items', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
