<?php
/*
 * Colours / sizes / brands / materials (StoreLookup). $kind, $base, $item,
 * $plural, $pk, $name_key, $has_image; $usage = id => products using it.
 */
$filtered = $keyword !== '' || $status !== '';
$key      = $kind.'s';
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title"><?php echo html_escape(ucfirst($plural)); ?></h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url($base.'/add'); ?>"><?php echo sp_icon('plus'); ?> Add <?php echo html_escape(strtolower($item)); ?></a>
		<?php endif; ?>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url($base); ?>" role="search" aria-label="Search <?php echo html_escape($plural); ?>">
		<div>
			<label class="form-label" for="filter-keyword"><?php echo html_escape($item); ?> name</label>
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
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url($base); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0"><?php echo html_escape(ucfirst($plural)); ?> could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'Nothing on this page.' : ($filtered ? 'Nothing matches your search.' : 'No '.html_escape($plural).' yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => $key, 'base' => $base, 'page' => $page, 'noun' => $plural, 'filters' => array('keyword' => $keyword, 'status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden"><?php echo html_escape(ucfirst($plural)); ?> in display order</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all <?php echo html_escape($plural); ?>" data-sp-select-all="<?php echo $key; ?>" form="sp-bulk-<?php echo $key; ?>"></th><?php endif; ?>
						<?php if ($kind === 'color'): ?><th scope="col">Swatch</th><?php endif; ?>
						<?php if ($has_image): ?><th scope="col">Logo</th><?php endif; ?>
						<th scope="col"><?php echo html_escape($item); ?></th>
						<th scope="col">Products</th>
						<th scope="col">Order</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $id = (int) $row[$pk]; ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo $id; ?>" aria-label="Select <?php echo html_escape($row[$name_key]); ?>" data-sp-select="<?php echo $key; ?>" form="sp-bulk-<?php echo $key; ?>"></td><?php endif; ?>
						<?php if ($kind === 'color'): ?>
						<td data-label="Swatch"><span class="d-inline-block rounded-circle border align-middle" style="width:24px;height:24px;background:<?php echo html_escape($row['color_code']); ?>" aria-hidden="true"></span> <code><?php echo html_escape($row['color_code']); ?></code></td>
						<?php endif; ?>
						<?php if ($has_image): ?>
						<?php $image = $image_upload->path($kind, $row[$kind.'_image']); ?>
						<td data-label="Logo"><?php if ($image !== ''): ?><img class="sp-thumb" src="<?php echo html_escape(get_image($kind, $row[$kind.'_image'], 192, 108, 'C')); ?>" alt="" width="64" loading="lazy"><?php else: ?><span class="sp-muted small">None</span><?php endif; ?></td>
						<?php endif; ?>
						<td class="sp-cell-text" data-label="<?php echo html_escape($item); ?>"><?php echo html_escape($row[$name_key]); ?></td>
						<td data-label="Products"><?php echo (int) ($usage[$id] ?? 0); ?></td>
						<td data-label="Order"><?php echo (int) $row['sort_order']; ?></td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => $base, 'id' => $id, 'page' => $page,
								'label' => strtolower($item).' '.$row[$name_key], 'active' => $row['status'] === '1',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? strtolower($item) : $plural, 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
