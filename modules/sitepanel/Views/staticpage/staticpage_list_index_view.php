<?php
/* Static pages list: title search and View (status filter / column, bulk and row actions hidden below). */
$filtered = $keyword !== '' || $status !== '';
echo sp_view('includes/header');
?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Static pages</h2>
		<?php /*if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/staticpages/add'); ?>"><?php echo sp_icon('plus'); ?> Add page</a>
		<?php endif; */ ?>
	</header>

	<form class="sp-filters sp-filters--single" method="get" action="<?php echo site_url('sitepanel/staticpages'); ?>" role="search" aria-label="Search static pages">
		<div>
			<label class="form-label" for="filter-keyword">Page title</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="50" value="<?php echo html_escape($keyword); ?>">
		</div>
		<?php /* Status filter hidden (kept for later use):
		<div>
			<label class="form-label" for="filter-status">Status</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">All</option>
				<option value="1"<?php echo $status === '1' ? ' selected' : ''; ?>>Active</option>
				<option value="0"<?php echo $status === '0' ? ' selected' : ''; ?>>Inactive</option>
			</select>
		</div>
		*/ ?>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/staticpages'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Pages could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No pages on this page of the list.' : ($filtered ? 'No pages match this search.' : 'No static pages yet.'); ?></p>
	<?php else: ?>
		<?php /* Bulk Active / Deactive / Delete hidden (rows show View only); restore: use this line instead of FALSE and add the includes/bulk_actions toolbars (top and bottom, as in location/view_country_list.php).
		$bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; */ ?>
		<?php $bulk_enabled = FALSE; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Static pages, by title</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all pages" data-sp-select-all="pages" form="sp-bulk-pages"></th><?php endif; ?>
						<th scope="col">Sl.</th>
						<th scope="col">Page title</th>
						<th scope="col">Page address</th>
						<?php /* Layout column hidden (kept for later use):
						<th scope="col">Layout</th>
						*/ ?>
						<?php /* Status column hidden (kept for later use):
						<th scope="col">Status</th>
						*/ ?>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $i => $row): ?>
					<?php $active = $row['status'] === '1'; ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['page_id']; ?>" aria-label="Select <?php echo html_escape($row['page_name']); ?>" data-sp-select="pages" form="sp-bulk-pages"></td><?php endif; ?>
						<td data-label="Sl."><?php echo ($page - 1) * $per_page + $i + 1; ?></td>
						<td class="sp-cell-text" data-label="Page title"><?php echo html_escape($row['page_name']); ?></td>
						<td class="sp-cell-text" data-label="Page address">
							<?php if ($active): ?>
							<a class="sp-link sp-link--external" href="<?php echo html_escape(website_url($row['friendly_url'])); ?>" target="_blank" rel="noopener">/<?php echo html_escape($row['friendly_url']); ?><span class="visually-hidden"> (opens in a new tab)</span></a>
							<?php else: ?>
							<span class="sp-muted">/<?php echo html_escape($row['friendly_url']); ?></span>
							<?php endif; ?>
						</td>
						<?php /* Layout column hidden (kept for later use):
						<td data-label="Layout">
							<?php $view = \Modules\Pages\Models\PagesModel::layout_of($row['layout']); ?>
							<?php echo $view === \Modules\Pages\Models\PagesModel::TEXT_LAYOUT ? 'Page text' : html_escape('Designed: '.(\Modules\Pages\Controllers\Pages::DESIGNED_VIEWS[$view] ?? $view)); ?>
						</td>
						*/ ?>
						<?php /* Status column hidden (kept for later use):
						<td data-label="Status">
							<?php if ($active): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						*/ ?>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/staticpages', 'id' => $row['page_id'], 'page' => $page, 'view' => TRUE,
								// View only: Edit is on the View page; Activate / Deactivate and Delete hidden (were 'active' => $active, delete on).
								'label' => $row['page_name'], 'edit' => TRUE, 'active' => NULL, 'delete' => FALSE,
							))); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'page' : 'pages', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
