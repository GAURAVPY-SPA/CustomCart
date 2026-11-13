<?php 
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Meta Tags</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/meta/add'); ?>"><?php echo sp_icon('plus'); ?> Add Meta Tag</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/meta'); ?>" role="search" aria-label="Search meta tags">
		<div>
			<label class="form-label" for="filter-keyword">Page URL</label>
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
			<?php if ($keyword !== '' || $status !== ''): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/meta'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Meta tags could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No meta tags on this page.' : (($keyword !== '' || $status !== '') ? 'No meta tags match this search.' : 'No meta tags yet.'); ?></p>
	<?php else: ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Meta tags, newest first</caption>
				<thead>
					<tr>
						<th scope="col">Page URL</th>
						<th scope="col">Meta Details</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<td class="sp-cell-text" data-label="Page URL">
							<?php echo html_escape(website_url($row['page_url'])); ?>
						</td>
						<td class="sp-cell-text" data-label="Meta Details">
							<p><strong>Title:</strong> <?php echo html_escape($row['meta_title']); ?></p>
							<p><strong>Keywords:</strong> <?php echo html_escape((string) $row['meta_keyword']); ?></p>
							<p class="mb-0"><strong>Description:</strong> <?php echo html_escape($row['meta_description']); ?></p>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/meta', 'id' => $row['meta_id'], 'page' => $page,
								'label' => $row['page_url'], 'active' => $row['status'] === '1','delete' => FALSE, // Delete button hidden on Meta list
							))); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'meta tag' : 'meta tags', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
