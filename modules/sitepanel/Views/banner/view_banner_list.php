<?php
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Banners</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/banners/add'); ?>"><?php echo sp_icon('plus'); ?> Add banner</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters sp-filters--single" method="get" action="<?php echo site_url('sitepanel/banners'); ?>" role="search" aria-label="Filter banners">
		<div>
			<label class="form-label" for="filter-position">Position</label>
			<select class="form-select" id="filter-position" name="position">
				<option value="">All positions</option>
				<?php foreach ($positions as $name => $size): ?>
				<option value="<?php echo html_escape($name); ?>"<?php echo $name === $position ? ' selected' : ''; ?>><?php echo html_escape($name); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Filter</button>
			<?php if ($position !== ''): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/banners'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Banners could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No banners on this page.' : ($position !== '' ? 'No banners in this position.' : 'No banners yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'banners', 'base' => 'sitepanel/banners', 'page' => $page, 'noun' => 'banners', 'filters' => array('position' => $position), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Banners, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all banners" data-sp-select-all="banners" form="sp-bulk-banners"></th><?php endif; ?>
						<th scope="col">Image</th>
						<th scope="col">Position</th>
						<th scope="col">Banner text</th>
						<th scope="col">Link</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $file = $image_upload->path('banner', $row['banner_image']); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['banner_id']; ?>" aria-label="Select banner <?php echo (int) $row['banner_id']; ?>" data-sp-select="banners" form="sp-bulk-banners"></td><?php endif; ?>
						<td data-label="Image">
							<?php if ($file !== ''): ?>
							<img class="sp-thumb" src="<?php echo html_escape(get_image('banner', $row['banner_image'], 192, 108, 'C')); ?>" alt="" loading="lazy">
							<?php else: ?>
							<span class="sp-muted">Missing file</span>
							<?php endif; ?>
						</td>
						<td data-label="Position"><?php echo html_escape($row['banner_position']); ?></td>
						<td class="sp-cell-text" data-label="Banner text"><?php echo (string) $row['banner_title'] !== '' ? html_escape($row['banner_title']) : '<span class="sp-muted">—</span>'; ?></td>
						<td class="sp-cell-text" data-label="Link">
							<?php if ((string) $row['banner_url'] !== ''): ?>
							<a class="sp-link sp-link--external" href="<?php echo html_escape($row['banner_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($row['banner_url']); ?></a>
							<?php else: ?><span class="sp-muted">—</span><?php endif; ?>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/banners', 'id' => $row['banner_id'], 'page' => $page,
								'label' => 'banner #'.$row['banner_id'], 'active' => $row['status'] === '1',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'banner' : 'banners', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
