<?php
/* Delivery methods: status filter, default method, bulk actions. */
echo sp_view('includes/header'); ?>
<?php /* Note hidden:
<div class="alert alert-info" role="status">The online store charges the <strong>default active</strong> delivery method (or the first active one): its rate, free when the cart subtotal reaches its <strong>Free above</strong> amount. With no active method, delivery is free. VAT is set in <a href="<?php echo site_url('sitepanel/settings'); ?>">Admin Settings</a>.</div>
*/ ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Delivery Methods</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/shipping/add'); ?>"><?php echo sp_icon('plus'); ?> Add delivery method</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters sp-filters--single" method="get" action="<?php echo site_url('sitepanel/shipping'); ?>" aria-label="Filter delivery methods">
		<div>
			<label class="form-label" for="filter-status">Status</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">All</option>
				<option value="1"<?php echo $status === '1' ? ' selected' : ''; ?>>Active</option>
				<option value="0"<?php echo $status === '0' ? ' selected' : ''; ?>>Inactive</option>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Filter</button>
			<?php if ($status !== ''): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/shipping'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Delivery methods could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No delivery methods on this page.' : ($status !== '' ? 'No delivery methods match the filter.' : 'No delivery methods yet. Customers can\'t check out until one is active.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'shipping', 'base' => 'sitepanel/shipping', 'page' => $page, 'noun' => 'delivery methods', 'filters' => array('status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Delivery methods in display order</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all delivery methods" data-sp-select-all="shipping" form="sp-bulk-shipping"></th><?php endif; ?>
						<th scope="col">Delivery method</th>
						<th scope="col">Rate</th>
						<th scope="col">Free from</th>
						<th scope="col">Delivery days</th>
						<th scope="col">Order</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['shipping_id']; ?>" aria-label="Select <?php echo html_escape($row['shipping_type']); ?>" data-sp-select="shipping" form="sp-bulk-shipping"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Delivery method">
							<?php echo html_escape($row['shipping_type']); ?>
							<?php if ($row['is_default'] === '1'): ?><span class="d-block"><span class="sp-badge sp-badge--ok">Default</span></span><?php endif; ?>
						</td>
						<td data-label="Rate"><?php echo (float) $row['shipment_rate'] > 0 ? display_price($row['shipment_rate']) : 'Free'; ?></td>
						<td data-label="Free from"><?php echo (float) $row['free_above'] > 0 ? display_price($row['free_above']) : '<span class="sp-muted">—</span>'; ?></td>
						<td class="text-nowrap" data-label="Delivery days"><?php $dmin = (int) $row['delivery_min_days']; $dmax = max($dmin, (int) $row['delivery_max_days']); echo $dmin === $dmax ? $dmin : $dmin.'–'.$dmax; ?> days</td>
						<td data-label="Order"><?php echo (int) $row['sort_order']; ?></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/shipping', 'id' => $row['shipping_id'], 'page' => $page,
								'label' => $row['shipping_type'], 'active' => $row['status'] === '1',
							))); ?>
							<?php if ($editPrvg && $row['is_default'] !== '1'): ?>
							<?php echo form_open('sitepanel/shipping/make_default/'.(int) $row['shipping_id'], array('class' => 'sp-row-actions mt-1')); ?>
								<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
								<button class="btn sp-btn-outline" type="submit">Make default<span class="visually-hidden"> <?php echo html_escape($row['shipping_type']); ?></span></button>
							<?php echo form_close(); ?>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => array('position' => 'bottom') + $bulk)); ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'delivery method' : 'delivery methods', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
