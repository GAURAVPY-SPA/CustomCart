<?php
/* Header images (home page slider): status filter, bulk actions. */
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Header Images</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/header_images/add'); ?>"><?php echo sp_icon('plus'); ?> Add header image</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters sp-filters--single" method="get" action="<?php echo site_url('sitepanel/header_images'); ?>" role="search" aria-label="Filter header images">
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
			<?php if ($status !== ''): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/header_images'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Header images could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No header images on this page.' : ($status !== '' ? 'No header images with this status.' : 'No header images yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'header-images', 'base' => 'sitepanel/header_images', 'page' => $page, 'noun' => 'header images', 'filters' => array('status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Header images, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all header images" data-sp-select-all="header-images" form="sp-bulk-header-images"></th><?php endif; ?>
						<th scope="col">Image</th>
						<th scope="col">Caption</th>
						<th scope="col">Link</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $file = $image_upload->path('header_images', $row['header_image']); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['id']; ?>" aria-label="Select header image <?php echo (int) $row['id']; ?>" data-sp-select="header-images" form="sp-bulk-header-images"></td><?php endif; ?>
						<td data-label="Image">
							<?php if ($file !== ''): ?>
							<a href="<?php echo base_url($file); ?>" target="_blank" rel="noopener noreferrer"><img class="sp-thumb" src="<?php echo html_escape(get_image('header_images', $row['header_image'], 192, 108, 'C')); ?>" alt="Header image <?php echo (int) $row['id']; ?> (opens the full image)" loading="lazy"></a>
							<?php else: ?>
							<span class="sp-muted">Missing file</span>
							<?php endif; ?>
						</td>
						<td class="sp-cell-text" data-label="Caption">
							<?php $lines = array_filter(array((string) $row['line_one'], (string) $row['line_two'], (string) $row['line_three']), 'strlen'); ?>
							<?php if ($lines): ?>
							<?php foreach ($lines as $line): ?><span class="d-block"><?php echo html_escape($line); ?></span><?php endforeach; ?>
							<?php else: ?><span class="sp-muted">—</span><?php endif; ?>
						</td>
						<td class="sp-cell-text" data-label="Link">
							<?php if ((string) $row['header_url'] !== ''): ?>
							<a class="sp-link sp-link--external" href="<?php echo html_escape($row['header_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($row['header_url']); ?></a>
							<?php else: ?><span class="sp-muted">—</span><?php endif; ?>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/header_images', 'id' => $row['id'], 'page' => $page,
								'label' => 'header image #'.$row['id'], 'active' => $row['status'] === '1',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'header image' : 'header images', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
