<?php
/* Product reviews: product / name / email search, status filter (Active = 1, Inactive = 0), ?product filter. */
$filtered = $keyword !== '' || $status !== '' || $product_id > 0;
$stars    = function ($rate) { $rate = max(0, min(5, (int) $rate)); return str_repeat('★', $rate).str_repeat('☆', 5 - $rate); };
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Product testimonials</h2>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/reviews'); ?>" role="search" aria-label="Search reviews">
		<?php if ($product_id > 0): ?><input type="hidden" name="product" value="<?php echo $product_id; ?>"><?php endif; ?>
		<div>
			<label class="form-label" for="filter-keyword">Product, name or email</label>
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
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/reviews'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Reviews could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No reviews on this page.' : ($filtered ? 'No reviews match your search.' : 'No reviews yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'reviews', 'base' => 'sitepanel/reviews', 'page' => $page, 'noun' => 'reviews', 'filters' => array('keyword' => $keyword, 'status_filter' => $status, 'product' => $product_id ?: ''), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Product testimonials, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all reviews" data-sp-select-all="reviews" form="sp-bulk-reviews"></th><?php endif; ?>
						<th scope="col">Product</th>
						<th scope="col">Reviewer</th>
						<th scope="col">Rating</th>
						<th scope="col">Review</th>
						<th scope="col">Posted</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['review_id']; ?>" aria-label="Select review by <?php echo html_escape($row['poster_name']); ?>" data-sp-select="reviews" form="sp-bulk-reviews"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Product"><a class="sp-link" href="<?php echo site_url('sitepanel/reviews?product='.(int) $row['prod_id']); ?>"><?php echo html_escape($row['product_name']); ?></a></td>
						<td class="sp-cell-text" data-label="Reviewer"><?php echo html_escape($row['poster_name']); ?><span class="sp-muted d-block small"><?php echo html_escape($row['email']); ?></span></td>
						<td data-label="Rating"><span aria-hidden="true"><?php echo $stars($row['rate']); ?></span><span class="visually-hidden"><?php echo (int) $row['rate']; ?> out of 5</span></td>
						<td class="sp-cell-text" data-label="Review"><?php echo html_escape(mb_strimwidth((string) $row['comment'], 0, 120, '…')); ?></td>
						<td data-label="Posted"><?php echo html_escape(sp_datetime($row['posted_date'])); ?></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/reviews', 'id' => $row['review_id'], 'page' => $page, 'view' => TRUE, 'edit' => FALSE,
								'label' => 'review by '.$row['poster_name'], 'active' => $row['status'] === '1',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'review' : 'reviews', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
