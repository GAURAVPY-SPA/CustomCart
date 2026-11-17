<?php
/* News & Events list: title / category search, status filter, display order, bulk actions.
   $page_url = address of the website's News & Events page ('' = not registered). */
$filtered = $keyword !== '' || $status !== '';
$filters  = array('keyword' => $keyword, 'status_filter' => $status);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">News &amp; Events</h2>
		<div class="d-flex flex-wrap gap-2">
			<?php if ($page_url !== ''): ?>
			<a class="btn sp-btn-outline" href="<?php echo html_escape(website_url($page_url)); ?>" target="_blank" rel="noopener"><?php echo sp_icon('external'); ?> View page<span class="visually-hidden"> (opens in a new tab)</span></a>
			<?php endif; ?>
			<?php if ($addPrvg): ?>
			<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/news/add'); ?>"><?php echo sp_icon('plus'); ?> Add news</a>
			<?php endif; ?>
		</div>
	</header>

	<?php echo sp_view('includes/status_filter', array('filter_form' => array('url' => 'sitepanel/news', 'aria_label' => 'Search news', 'label' => 'Title or category', 'keyword' => $keyword, 'status' => $status))); ?>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">News could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No news on this page.' : ($filtered ? 'No news matches your search.' : 'No news yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'news', 'base' => 'sitepanel/news', 'page' => $page, 'noun' => 'news items', 'filters' => $filters, 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<?php if ($editPrvg): ?>
		<?php echo form_open('sitepanel/news/sort', array('id' => 'sp-sort-news')); ?>
			<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
			<?php foreach ($filters as $name => $value): ?>
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo html_escape($value); ?>">
			<?php endforeach; ?>
		<?php echo form_close(); ?>
		<?php endif; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">News in display order</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all news" data-sp-select-all="news" form="sp-bulk-news"></th><?php endif; ?>
						<th scope="col">Image</th>
						<th scope="col">News</th>
						<th scope="col">Published</th>
						<th scope="col">Display order</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $file = $image_upload->path('news', $row['news_image']); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['news_id']; ?>" aria-label="Select news <?php echo (int) $row['news_id']; ?>" data-sp-select="news" form="sp-bulk-news"></td><?php endif; ?>
						<td data-label="Image">
							<?php if ($file !== ''): ?><img class="sp-thumb" src="<?php echo html_escape(get_image('news', $row['news_image'], 192, 108, 'C')); ?>" alt="" loading="lazy"><?php else: ?><span class="sp-muted">No image</span><?php endif; ?>
						</td>
						<td class="sp-cell-text" data-label="News">
							<strong><?php echo html_escape($row['news_title']); ?></strong>
							<?php if ((string) $row['news_category'] !== ''): ?><span class="sp-muted d-block small"><?php echo html_escape($row['news_category']); ?></span><?php endif; ?>
							<?php if ($page_url !== '' && $row['status'] === '1'): ?>
							<div class="small"><a class="sp-link" href="<?php echo html_escape(website_url($page_url.'/'.$row['friendly_url'])); ?>" target="_blank" rel="noopener">View on website<span class="visually-hidden">: <?php echo html_escape($row['news_title']); ?> (opens in a new tab)</span></a></div>
							<?php endif; ?>
						</td>
						<td class="text-nowrap" data-label="Published"><?php echo html_escape(date('j M Y', strtotime($row['publish_date']))); ?></td>
						<td data-label="Display order">
							<?php if ($editPrvg): ?>
							<input class="form-control form-control-sm" type="number" min="0" max="9999" style="width: 6rem" name="ord[<?php echo (int) $row['news_id']; ?>]" value="<?php echo (int) $row['sort_order']; ?>" aria-label="Display order of news <?php echo (int) $row['news_id']; ?>" form="sp-sort-news">
							<?php else: ?>
							<?php echo (int) $row['sort_order']; ?>
							<?php endif; ?>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/news', 'id' => $row['news_id'], 'page' => $page,
								'label' => $row['news_title'], 'active' => $row['status'] === '1',
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
			<button class="btn sp-btn-outline" type="submit" form="sp-sort-news">Update order</button>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'news item' : 'news items', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
