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

	<?php echo sp_view('includes/status_filter', array('filter_form' => array('url' => $base, 'aria_label' => 'Search states', 'label' => 'State', 'keyword' => $keyword, 'status' => $status))); ?>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">States could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No states on this page.' : ($filtered ? 'No states match your search.' : 'No states added for this country yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php /* States post to state_bulk/<country_id> (not <base>/bulk); the top toolbar has no mt-3. */ ?>
		<?php $bulk = array('key' => 'states', 'base' => 'sitepanel/location', 'action' => 'sitepanel/location/state_bulk/'.(int) $country['country_id'], 'top_margin' => FALSE, 'page' => $page, 'noun' => 'states', 'confirm_noun' => 'states and their cities', 'filters' => array('keyword' => $keyword, 'status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
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
							<?php $id = (int) $row['state_id']; ?>
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/location', 'id' => $id, 'page' => $page, 'label' => $row['state_name'], 'active' => $row['status'] === '1',
								'edit_url' => 'sitepanel/location/state_edit/'.$id, 'status_url' => 'sitepanel/location/state_status/'.$id, 'delete_url' => 'sitepanel/location/state_delete/'.$id,
								'delete_confirm' => 'Delete "'.$row['state_name'].'"'.($cities ? ' and its '.$cities.' cities' : '').'? This cannot be undone.',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'state' : 'states', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
