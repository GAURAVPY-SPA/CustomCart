<?php
/* One state's cities: name search, status filter, bulk actions. */
$filtered = $keyword !== '' || $status !== '';
$base     = 'sitepanel/location/cities/'.(int) $state['state_id'];
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Cities of <?php echo html_escape($state['state_name'].', '.$country['name']); ?></h2>
		<div class="d-flex flex-wrap gap-2">
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/location/states/'.(int) $country['country_id']); ?>">All states of <?php echo html_escape($country['name']); ?></a>
			<?php if ($addPrvg): ?>
			<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/location/city_add/'.(int) $state['state_id']); ?>"><?php echo sp_icon('plus'); ?> Add city</a>
			<?php endif; ?>
		</div>
	</header>

	<?php echo sp_view('includes/status_filter', array('filter_form' => array('url' => $base, 'aria_label' => 'Search cities', 'label' => 'City', 'keyword' => $keyword, 'status' => $status))); ?>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Cities could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No cities on this page.' : ($filtered ? 'No cities match your search.' : 'No cities added for this state yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php /* Cities post to city_bulk/<state_id> (not <base>/bulk); the top toolbar has no mt-3. */ ?>
		<?php $bulk = array('key' => 'cities', 'base' => 'sitepanel/location', 'action' => 'sitepanel/location/city_bulk/'.(int) $state['state_id'], 'top_margin' => FALSE, 'page' => $page, 'noun' => 'cities', 'filters' => array('keyword' => $keyword, 'status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Cities of <?php echo html_escape($state['state_name']); ?>, by name</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all cities" data-sp-select-all="cities" form="sp-bulk-cities"></th><?php endif; ?>
						<th scope="col">City</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $label = html_escape($row['city_name']); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['city_id']; ?>" aria-label="Select <?php echo $label; ?>" data-sp-select="cities" form="sp-bulk-cities"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="City"><?php echo $label; ?></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php $id = (int) $row['city_id']; ?>
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/location', 'id' => $id, 'page' => $page, 'label' => $row['city_name'], 'active' => $row['status'] === '1',
								'edit_url' => 'sitepanel/location/city_edit/'.$id, 'status_url' => 'sitepanel/location/city_status/'.$id, 'delete_url' => 'sitepanel/location/city_delete/'.$id,
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'city' : 'cities', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
