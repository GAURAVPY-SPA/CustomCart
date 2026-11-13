<?php
/* Countries: name search, status filter, link to each country's states. */
$filtered = $keyword !== '' || $status !== '';
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Countries</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/location/add'); ?>"><?php echo sp_icon('plus'); ?> Add country</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/location'); ?>" role="search" aria-label="Search countries">
		<div>
			<label class="form-label" for="filter-keyword">Country</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($keyword); ?>">
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
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/location'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Countries could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No countries on this page.' : ($filtered ? 'No countries match your search.' : 'No countries yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'countries', 'base' => 'sitepanel/location', 'page' => $page, 'noun' => 'countries and their states and cities', 'filters' => array('keyword' => $keyword, 'status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Countries by name</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all countries" data-sp-select-all="countries" form="sp-bulk-countries"></th><?php endif; ?>
						<th scope="col">Country</th>
						<th scope="col">ISO codes</th>
						<th scope="col">States</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['country_id']; ?>" aria-label="Select <?php echo html_escape($row['name']); ?>" data-sp-select="countries" form="sp-bulk-countries"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Country"><?php echo html_escape($row['name']); ?></td>
						<td data-label="ISO codes"><?php echo $row['iso_code_2'] !== '' ? html_escape($row['iso_code_2'].' / '.$row['iso_code_3']) : '<span class="sp-muted">—</span>'; ?></td>
						<td data-label="States"><a class="sp-link" href="<?php echo site_url('sitepanel/location/states/'.(int) $row['country_id']); ?>">Manage states<span class="visually-hidden"> of <?php echo html_escape($row['name']); ?></span> <span class="sp-link-count"><?php echo (int) $row['state_count']; ?></span></a></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/location', 'id' => $row['country_id'], 'page' => $page,
								'label' => $row['name'].((int) $row['state_count'] ? ' and its '.(int) $row['state_count'].' states (with their cities)' : ''), 'active' => $row['status'] === '1',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'country' : 'countries', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
