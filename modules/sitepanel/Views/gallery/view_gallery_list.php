<?php
/* Images gallery list: title search, status filter, display order, bulk actions. */
$filtered = $keyword !== '' || $status !== '';
$filters  = array('keyword' => $keyword, 'status_filter' => $status);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Images Gallery</h2>
		<div class="d-flex flex-wrap gap-2">
			<a class="btn sp-btn-outline" href="<?php echo html_escape(website_url('images-gallery')); ?>" target="_blank" rel="noopener"><?php echo sp_icon('external'); ?> View page<span class="visually-hidden"> (opens in a new tab)</span></a>
			<?php if ($addPrvg): ?>
			<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/gallery/add'); ?>"><?php echo sp_icon('plus'); ?> Add photo</a>
			<?php endif; ?>
		</div>
	</header>

	<?php echo sp_view('includes/status_filter', array('filter_form' => array('url' => 'sitepanel/gallery', 'aria_label' => 'Search photos', 'label' => 'Title', 'keyword' => $keyword, 'status' => $status))); ?>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Photos could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No photos on this page.' : ($filtered ? 'No photos match your search.' : 'No photos yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'gallery', 'base' => 'sitepanel/gallery', 'page' => $page, 'noun' => 'photos', 'filters' => $filters, 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<?php if ($editPrvg): ?>
		<?php echo form_open('sitepanel/gallery/sort', array('id' => 'sp-sort-gallery')); ?>
			<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
			<?php foreach ($filters as $name => $value): ?>
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo html_escape($value); ?>">
			<?php endforeach; ?>
		<?php echo form_close(); ?>
		<?php endif; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Photos in display order</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all photos" data-sp-select-all="gallery" form="sp-bulk-gallery"></th><?php endif; ?>
						<th scope="col">Image</th>
						<th scope="col">Title</th>
						<th scope="col">Display order</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $file = $image_upload->path('gallery', $row['gallery_image']); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['gallery_id']; ?>" aria-label="Select photo <?php echo (int) $row['gallery_id']; ?>" data-sp-select="gallery" form="sp-bulk-gallery"></td><?php endif; ?>
						<td data-label="Image">
							<?php if ($file !== ''): ?>
							<a href="<?php echo base_url($file); ?>" target="_blank" rel="noopener noreferrer"><img class="sp-thumb" src="<?php echo html_escape(get_image('gallery', $row['gallery_image'], 192, 108, 'C')); ?>" alt="<?php echo html_escape($row['gallery_title']); ?> (opens the full image)" loading="lazy"></a>
							<?php else: ?>
							<span class="sp-muted">Missing file</span>
							<?php endif; ?>
						</td>
						<td class="sp-cell-text" data-label="Title"><?php echo html_escape($row['gallery_title']); ?></td>
						<td data-label="Display order">
							<?php if ($editPrvg): ?>
							<input class="form-control form-control-sm" type="number" min="0" max="9999" style="width: 6rem" name="ord[<?php echo (int) $row['gallery_id']; ?>]" value="<?php echo (int) $row['sort_order']; ?>" aria-label="Display order of photo <?php echo (int) $row['gallery_id']; ?>" form="sp-sort-gallery">
							<?php else: ?>
							<?php echo (int) $row['sort_order']; ?>
							<?php endif; ?>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/gallery', 'id' => $row['gallery_id'], 'page' => $page,
								'label' => 'photo #'.$row['gallery_id'], 'active' => $row['status'] === '1',
							))); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => array('position' => 'bottom') + $bulk)); ?>
		<?php if ($editPrvg): ?>
		<div class="sp-form-actions mt-3">
			<button class="btn sp-btn-outline" type="submit" form="sp-sort-gallery">Update order</button>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'photo' : 'photos', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
