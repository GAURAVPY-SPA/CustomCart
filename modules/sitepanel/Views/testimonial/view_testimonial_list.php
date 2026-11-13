<?php
/* Testimonial list: keyword search, status and source filters, bulk actions. */
$filtered = $keyword !== '' || $status !== '' || $source !== '';
$filters  = array('keyword' => $keyword, 'status_filter' => $status, 'source' => $source);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Testimonials</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/testimonial/add'); ?>"><?php echo sp_icon('plus'); ?> Add testimonial</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/testimonial'); ?>" role="search" aria-label="Search testimonials">
		<div>
			<label class="form-label" for="filter-keyword">Search</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($keyword); ?>" placeholder="Name, email, company, comment">
		</div>
		<div>
			<label class="form-label" for="filter-status">Status</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">All</option>
				<option value="1"<?php echo $status === '1' ? ' selected' : ''; ?>>Active</option>
				<option value="0"<?php echo $status === '0' ? ' selected' : ''; ?>>Inactive</option>
			</select>
		</div>
		<div>
			<label class="form-label" for="filter-source">Source</label>
			<select class="form-select" id="filter-source" name="source">
				<option value="">All</option>
				<option value="public"<?php echo $source === 'public' ? ' selected' : ''; ?>>Public submission</option>
				<option value="admin"<?php echo $source === 'admin' ? ' selected' : ''; ?>>Added by admin</option>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/testimonial'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Testimonials could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No testimonials on this page.' : ($filtered ? 'No testimonials match your search.' : 'No testimonials yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'testimonials', 'base' => 'sitepanel/testimonial', 'page' => $page, 'noun' => 'testimonials', 'filters' => $filters, 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Testimonials, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all testimonials" data-sp-select-all="testimonials" form="sp-bulk-testimonials"></th><?php endif; ?>
						<th scope="col">Photo</th>
						<th scope="col">Name</th>
						<th scope="col">Comment</th>
						<th scope="col">Status</th>
						<th scope="col">Posted</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $file = $image_upload->path('testimonial', $row['photo']); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['testimonial_id']; ?>" aria-label="Select testimonial by <?php echo html_escape($row['poster_name']); ?>" data-sp-select="testimonials" form="sp-bulk-testimonials"></td><?php endif; ?>
						<td data-label="Photo">
							<?php if ($file !== ''): ?>
							<img class="sp-thumb sp-thumb--round" src="<?php echo html_escape(get_image('testimonial', $row['photo'], 96, 96, 'C')); ?>" alt="" loading="lazy">
							<?php else: ?>
							<?php /* No photo (or the file is missing): the same placeholder as /testimonials. */ ?>
							<img class="sp-thumb sp-thumb--round" src="<?php echo theme_url().'images/avatar-placeholder.svg'; ?>" alt="" title="No photo" loading="lazy">
							<?php endif; ?>
						</td>
						<td data-label="Name">
							<?php echo html_escape($row['poster_name']); ?>
							<?php if ((string) $row['poster_role'] !== ''): ?><span class="sp-muted d-block small"><?php echo html_escape($row['poster_role']); ?></span><?php endif; ?>
							<span class="sp-muted d-block small"><?php echo html_escape($row['email']); ?></span>
							<?php if ((string) $row['submitted_ip'] !== ''): ?><span class="sp-badge sp-badge--muted mt-1">Public submission</span><?php endif; ?>
						</td>
						<td class="sp-cell-text" data-label="Comment"><?php echo html_escape(character_limiter($row['testimonial_description'], 140)); ?></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td class="text-nowrap" data-label="Posted"><?php echo html_escape(sp_datetime($row['posted_date'])); ?></td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/testimonial', 'id' => $row['testimonial_id'], 'page' => $page,
								'label' => 'testimonial by '.$row['poster_name'], 'active' => $row['status'] === '1',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'testimonial' : 'testimonials', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
