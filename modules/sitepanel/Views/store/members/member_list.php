<?php
/* Store customers: keyword search, status filter (members / guests), bulk actions. */
$filtered = $keyword !== '' || $status !== '';
$guests   = $status === '3';
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title"><?php echo $guests ? 'Guest Checkouts' : 'Customers'; ?></h2>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/members'); ?>" role="search" aria-label="Search customers">
		<div>
			<label class="form-label" for="filter-keyword">Email, name or mobile</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($keyword); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-status">Status</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">Members (active and inactive)</option>
				<?php foreach (\Modules\Sitepanel\Controllers\Members::STATUSES as $key => $label): ?>
				<option value="<?php echo $key; ?>"<?php echo $status === (string) $key ? ' selected' : ''; ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/members'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Customers could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No customers on this page.' : ($filtered ? 'No customers match your search.' : 'No customers yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = ( ! $guests && ($activatePrvg || $deactivatePrvg)) || $deletePrvg; ?>
		<?php $bulk = array('key' => 'members', 'base' => 'sitepanel/members', 'page' => $page, 'noun' => 'customers', 'filters' => array('keyword' => $keyword, 'status_filter' => $status), 'can_activate' => $activatePrvg && ! $guests, 'can_deactivate' => $deactivatePrvg && ! $guests, 'can_delete' => $deletePrvg); echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Customers, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all customers" data-sp-select-all="members" form="sp-bulk-members"></th><?php endif; ?>
						<th scope="col">Customer</th>
						<th scope="col">Mobile</th>
						<th scope="col">Orders</th>
						<th scope="col"><?php echo $guests ? 'First order' : 'Registered'; ?></th>
						<th scope="col">Last login</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $name = trim($row['first_name'].' '.$row['last_name']); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['customers_id']; ?>" aria-label="Select <?php echo html_escape($row['user_name']); ?>" data-sp-select="members" form="sp-bulk-members"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Customer">
							<?php echo html_escape($name !== '' ? $name : '—'); ?>
							<span class="d-block small"><a class="sp-link" href="mailto:<?php echo html_escape($row['user_name']); ?>"><?php echo html_escape($row['user_name']); ?></a></span>
						</td>
						<td data-label="Mobile"><?php echo html_escape((string) $row['mobile_number']); ?></td>
						<td data-label="Orders">
							<?php if ((int) $row['order_count'] > 0 && $can_orders): ?>
							<a class="sp-link" href="<?php echo site_url('sitepanel/orders?customer='.(int) $row['customers_id']); ?>"><?php echo (int) $row['order_count']; ?><span class="visually-hidden"> orders of <?php echo html_escape($row['user_name']); ?></span></a>
							<?php else: ?>
							<?php echo (int) $row['order_count']; ?>
							<?php endif; ?>
						</td>
						<td data-label="<?php echo $guests ? 'First order' : 'Registered'; ?>"><?php echo html_escape(sp_datetime($row['account_created_date'])); ?></td>
						<td data-label="Last login"><?php echo $row['current_login'] ? html_escape(sp_datetime($row['current_login'])) : '<span class="sp-muted">Never</span>'; ?></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span>
							<?php elseif ($row['status'] === '3'): ?><span class="sp-badge sp-badge--muted">Guest</span>
							<?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/members', 'id' => $row['customers_id'], 'page' => $page, 'view' => TRUE, 'edit' => FALSE,
								'label' => $row['user_name'], 'active' => $row['status'] === '3' ? NULL : $row['status'] === '1',
							))); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => array('position' => 'bottom') + $bulk)); ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'customer' : 'customers', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
