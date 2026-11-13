<?php
/* Video gallery list: title search, status filter, display order, bulk actions. */
use Modules\Video\Models\VideoModel;

$filtered = $keyword !== '' || $status !== '';
$filters  = array('keyword' => $keyword, 'status_filter' => $status);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Video Gallery</h2>
		<div class="d-flex flex-wrap gap-2">
			<a class="btn sp-btn-outline" href="<?php echo html_escape(website_url('videos-gallery')); ?>" target="_blank" rel="noopener"><?php echo sp_icon('external'); ?> View page<span class="visually-hidden"> (opens in a new tab)</span></a>
			<?php if ($addPrvg): ?>
			<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/video/add'); ?>"><?php echo sp_icon('plus'); ?> Add video</a>
			<?php endif; ?>
		</div>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/video'); ?>" role="search" aria-label="Search videos">
		<div>
			<label class="form-label" for="filter-keyword">Title</label>
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
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/video'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Videos could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No videos on this page.' : ($filtered ? 'No videos match your search.' : 'No videos yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'video', 'base' => 'sitepanel/video', 'page' => $page, 'noun' => 'videos', 'filters' => $filters, 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<?php if ($editPrvg): ?>
		<?php echo form_open('sitepanel/video/sort', array('id' => 'sp-sort-video')); ?>
			<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
			<?php foreach ($filters as $name => $value): ?>
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo html_escape($value); ?>">
			<?php endforeach; ?>
		<?php echo form_close(); ?>
		<?php endif; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Videos in display order</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all videos" data-sp-select-all="video" form="sp-bulk-video"></th><?php endif; ?>
						<th scope="col">Thumbnail</th>
						<th scope="col">Title</th>
						<th scope="col">Video link</th>
						<th scope="col">Display order</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php
					$file  = $image_upload->path('video', $row['video_image']);
					$thumb = $file !== '' ? get_image('video', $row['video_image'], 192, 108, 'C') : VideoModel::thumbnail_url((string) $row['video_link']);
					$meta  = implode(' · ', array_filter(array((string) $row['video_category'], (string) $row['video_duration']), 'strlen'));
					?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['video_id']; ?>" aria-label="Select video <?php echo (int) $row['video_id']; ?>" data-sp-select="video" form="sp-bulk-video"></td><?php endif; ?>
						<td data-label="Thumbnail">
							<?php if ($thumb !== ''): ?>
							<img class="sp-thumb" src="<?php echo html_escape($thumb); ?>" alt="" loading="lazy">
							<?php if ($file === ''): ?><span class="sp-muted d-block small">YouTube thumbnail</span><?php endif; ?>
							<?php else: ?>
							<span class="sp-muted">No thumbnail</span>
							<?php endif; ?>
						</td>
						<td class="sp-cell-text" data-label="Title">
							<strong><?php echo html_escape($row['video_title']); ?></strong>
							<?php if ($meta !== ''): ?><span class="sp-muted d-block small"><?php echo html_escape($meta); ?></span><?php endif; ?>
						</td>
						<td class="sp-cell-text" data-label="Video link">
							<a class="sp-link sp-link--external" href="<?php echo html_escape($row['video_link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($row['video_link']); ?></a>
						</td>
						<td data-label="Display order">
							<?php if ($editPrvg): ?>
							<input class="form-control form-control-sm" type="number" min="0" max="9999" style="width: 6rem" name="ord[<?php echo (int) $row['video_id']; ?>]" value="<?php echo (int) $row['sort_order']; ?>" aria-label="Display order of video <?php echo (int) $row['video_id']; ?>" form="sp-sort-video">
							<?php else: ?>
							<?php echo (int) $row['sort_order']; ?>
							<?php endif; ?>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/video', 'id' => $row['video_id'], 'page' => $page,
								'label' => 'video #'.$row['video_id'], 'active' => $row['status'] === '1',
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
			<button class="btn sp-btn-outline" type="submit" form="sp-sort-video">Update order</button>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'video' : 'videos', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
