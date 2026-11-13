<?php
/* One country's states: name search, status filter, link to each state's cities, bulk actions. */
$filtered = $keyword !== '' || $status !== '';
$base     = 'sitepanel/location/states/'.(int) $country['country_id'];
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">States of <?php echo html_escape($country['name']); ?></h2>
		<div class="d-flex flex-wrap gap-2">
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/location'); ?>">All countries</a>
			<?php if ($addPrvg): ?>
			<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/location/state_add/'.(int) $country['country_id']); ?>"><?php echo sp_icon('plus'); ?> Add state</a>
			<?php endif; ?>
		</div>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url($base); ?>" role="search" aria-label="Search states">
		<div>
			<label class="form-label" for="filter-keyword">State</label>
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
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url($base); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">States could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No states on this page.' : ($filtered ? 'No states match your search.' : 'No states added for this country yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php /* The shared bulk form posts to <base>/bulk; states use state_bulk/<country_id>. */ ?>
		<?php if ($bulk_enabled): ?>
		<div class="sp-bulk-toolbar">
			<?php echo form_open('sitepanel/location/state_bulk/'.(int) $country['country_id'], array('id' => 'sp-bulk-states', 'class' => 'sp-row-actions', 'data-sp-bulk-form' => 'states', 'data-sp-bulk-noun' => 'states')); ?>
				<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
				<input type="hidden" name="keyword" value="<?php echo html_escape($keyword); ?>">
				<input type="hidden" name="status_filter" value="<?php echo html_escape($status); ?>">
				<?php if ($activatePrvg): ?><button class="btn sp-btn-outline" type="submit" name="bulk_action" value="activate">Active</button><?php endif; ?>
				<?php if ($deactivatePrvg): ?><button class="btn sp-btn-outline" type="submit" name="bulk_action" value="deactivate">Deactive</button><?php endif; ?>
				<?php if ($deletePrvg): ?><button class="btn sp-btn-outline sp-btn-danger" type="submit" name="bulk_action" value="delete" data-sp-confirm="Delete the selected states and their cities? This cannot be undone.">Delete</button><?php endif; ?>
			<?php echo form_close(); ?>
		</div>
		<?php endif; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">States of <?php echo html_escape($country['name']); ?>, by name</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all states" data-sp-select-all="states" form="sp-bulk-states"></th><?php endif; ?>
						<th scope="col">State</th>
						<th scope="col">Cities</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $label = html_escape($row['state_name']); $cities = (int) $row['city_count']; ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['state_id']; ?>" aria-label="Select <?php echo $label; ?>" data-sp-select="states" form="sp-bulk-states"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="State"><?php echo $label; ?></td>
						<td data-label="Cities"><a class="sp-link" href="<?php echo site_url('sitepanel/location/cities/'.(int) $row['state_id']); ?>">Manage cities<span class="visually-hidden"> of <?php echo $label; ?></span> <span class="sp-link-count"><?php echo $cities; ?></span></a></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<div class="sp-row-actions">
								<?php if ($editPrvg): ?>
								<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/location/state_edit/'.(int) $row['state_id']); ?>">Edit<span class="visually-hidden"> <?php echo $label; ?></span></a>
								<?php endif; ?>
								<?php $active = $row['status'] === '1'; ?>
								<?php if ($active ? $deactivatePrvg : $activatePrvg): ?>
								<?php echo form_open('sitepanel/location/state_status/'.(int) $row['state_id']); ?>
									<input type="hidden" name="status" value="<?php echo $active ? '0' : '1'; ?>">
									<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
									<button class="btn sp-btn-outline" type="submit"><?php echo $active ? 'Deactivate' : 'Activate'; ?><span class="visually-hidden"> <?php echo $label; ?></span></button>
								<?php echo form_close(); ?>
								<?php endif; ?>
								<?php if ($deletePrvg): ?>
								<?php echo form_open('sitepanel/location/state_delete/'.(int) $row['state_id'], array('data-sp-confirm' => 'Delete "'.$row['state_name'].'"'.($cities ? ' and its '.$cities.' cities' : '').'? This cannot be undone.')); ?>
									<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
									<button class="btn sp-btn-outline sp-btn-danger" type="submit">Delete<span class="visually-hidden"> <?php echo $label; ?></span></button>
								<?php echo form_close(); ?>
								<?php endif; ?>
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
				<?php if ($activatePrvg): ?><button form="sp-bulk-states" class="btn sp-btn-outline" type="submit" name="bulk_action" value="activate">Active</button><?php endif; ?>
				<?php if ($deactivatePrvg): ?><button form="sp-bulk-states" class="btn sp-btn-outline" type="submit" name="bulk_action" value="deactivate">Deactive</button><?php endif; ?>
				<?php if ($deletePrvg): ?><button form="sp-bulk-states" class="btn sp-btn-outline sp-btn-danger" type="submit" name="bulk_action" value="delete" data-sp-confirm="Delete the selected states and their cities? This cannot be undone.">Delete</button><?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'state' : 'states', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
