<?php
/* Order lines with a cancel / return request or a refund (Returns::index()). */
$requested = array('1' => 'requested', '2' => 'accepted', '3' => 'rejected');
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Returns &amp; Refunds</h2>
	</header>
	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/returns'); ?>" role="search" aria-label="Search returns and refunds">
		<div>
			<label class="form-label" for="filter-keyword">Product, code, order or email</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($filters['keyword']); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-request">Request</label>
			<select class="form-select" id="filter-request" name="request">
				<option value="">All</option>
				<option value="cancel"<?php echo $filters['request'] === 'cancel' ? ' selected' : ''; ?>>Open cancel requests</option>
				<option value="return"<?php echo $filters['request'] === 'return' ? ' selected' : ''; ?>>Open return requests</option>
			</select>
		</div>
		<div>
			<label class="form-label" for="filter-refund">Refund</label>
			<select class="form-select" id="filter-refund" name="refund">
				<option value="">All</option>
				<?php foreach (\Modules\Sitepanel\Controllers\Returns::REFUND_STATUSES as $status): ?>
				<option value="<?php echo $status; ?>"<?php echo $filters['refund'] === $status ? ' selected' : ''; ?>><?php echo $status; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filters['keyword'] !== '' || $filters['request'] !== 'any' || $filters['refund'] !== ''): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/returns'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>
	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Returns could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0">No cancel / return requests or refunds yet.</p>
	<?php else: ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Order items with requests or refunds, newest first</caption>
				<thead><tr><th scope="col">Item</th><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Request</th><th scope="col">Refund</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<td class="sp-cell-text" data-label="Item"><?php echo html_escape($row['product_name']); ?><span class="sp-muted d-block small"><?php echo (int) $row['quantity']; ?> × <?php echo display_price($row['product_price']); ?></span></td>
						<td data-label="Order"><?php echo html_escape($row['invoice_number']); ?><span class="sp-muted d-block small"><?php echo html_escape($row['order_status']); ?> · <?php echo html_escape($row['payment_status']); ?></span></td>
						<td class="sp-cell-text" data-label="Customer"><?php echo html_escape(trim($row['first_name'].' '.$row['last_name'])); ?><span class="sp-muted d-block small"><?php echo html_escape($row['email']); ?></span></td>
						<td data-label="Request">
							<?php if (isset($requested[$row['is_cancel']])): ?><span class="d-block small<?php echo $row['is_cancel'] === '1' ? ' text-danger' : ''; ?>">Cancel <?php echo $requested[$row['is_cancel']]; ?></span><?php endif; ?>
							<?php if (isset($requested[$row['is_return']])): ?><span class="d-block small<?php echo $row['is_return'] === '1' ? ' text-danger' : ''; ?>">Return <?php echo $requested[$row['is_return']]; ?></span><?php endif; ?>
							<?php if ((string) $row['reason'] !== ''): ?><span class="sp-muted d-block small"><?php echo html_escape($row['reason']); ?></span><?php endif; ?>
						</td>
						<td data-label="Refund"><?php echo $row['refund_status'] !== '' ? '<span class="sp-badge'.($row['refund_status'] === 'Refunded' ? ' sp-badge--ok' : '').'">'.html_escape($row['refund_status']).'</span> '.((float) $row['refund_amount'] > 0 ? display_price($row['refund_amount']) : '') : '<span class="sp-muted">—</span>'; ?></td>
						<td data-label="Actions"><div class="sp-row-actions"><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/returns/view/'.(int) $row['orders_products_id']); ?>">Open<span class="visually-hidden"> <?php echo html_escape($row['product_name']); ?></span></a></div></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'item' : 'items', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
