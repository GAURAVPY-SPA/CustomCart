<?php
/* Cancel / return reasons: name search, type + status filters, bulk actions. */
$types    = \Modules\Cart\Models\ReasonModel::TYPES;
$filtered = $keyword !== '' || $type !== '' || $status !== '';
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Reasons</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/reasons/add'.($type !== '' ? '?type='.$type : '')); ?>"><?php echo sp_icon('plus'); ?> Add reason</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/reasons'); ?>" role="search" aria-label="Search reasons">
		<div>
			<label class="form-label" for="filter-keyword">Reason</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($keyword); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-type">Type</label>
			<select class="form-select" id="filter-type" name="type">
				<option value="">All</option>
				<?php foreach ($types as $key => $label): ?>
				<option value="<?php echo $key; ?>"<?php echo $type === (string) $key ? ' selected' : ''; ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label class="form-label" for="filter-status">Status</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">All</option>
				<option value="1"<?php echo $status === '1' ? ' selected' : ''; ?>>Active</option>
				<option value="0"<?php echo $status === '0' ? ' selected' : ''; ?>>Inactive</option>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/reasons'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Reasons could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No reasons on this page.' : ($filtered ? 'No reasons match your search.' : 'No reasons yet. Customers then see the default list.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'reasons', 'base' => 'sitepanel/reasons', 'page' => $page, 'noun' => 'reasons', 'filters' => array('keyword' => $keyword, 'type' => $type, 'status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Cancel and return reasons in display order</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all reasons" data-sp-select-all="reasons" form="sp-bulk-reasons"></th><?php endif; ?>
						<th scope="col">Reason</th>
						<th scope="col">Type</th>
						<th scope="col">Order</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['reason_id']; ?>" aria-label="Select reason <?php echo html_escape($row['reason_name']); ?>" data-sp-select="reasons" form="sp-bulk-reasons"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Reason"><?php echo html_escape($row['reason_name']); ?></td>
						<td data-label="Type"><?php echo html_escape($types[$row['reason_type']] ?? $row['reason_type']); ?></td>
						<td data-label="Order"><?php echo (int) $row['sort_order']; ?></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/reasons', 'id' => $row['reason_id'], 'page' => $page,
								'label' => 'reason '.$row['reason_name'], 'active' => $row['status'] === '1',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'reason' : 'reasons', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
