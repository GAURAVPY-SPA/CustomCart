<?php
echo sp_view('includes/header');
$filtered = ($keyword !== '' || $from !== '' || $to !== '' || $replied !== '');
?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head">
		<h2 class="sp-panel-title" id="list-title"><?php echo html_escape($list_title); ?></h2>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url($base); ?>" role="search" aria-label="Search enquiries">
		<div>
			<label class="form-label" for="filter-keyword">Search</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($keyword); ?>" placeholder="Name, email, company, city">
		</div>
		<div>
			<label class="form-label" for="filter-from">Received from</label>
			<input class="form-control" type="date" id="filter-from" name="from" value="<?php echo html_escape($from); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-to">Received to</label>
			<input class="form-control" type="date" id="filter-to" name="to" value="<?php echo html_escape($to); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-replied">Reply status</label>
			<select class="form-select" id="filter-replied" name="replied">
				<option value="">All</option>
				<option value="N"<?php echo $replied === 'N' ? ' selected' : ''; ?>>Not replied</option>
				<option value="Y"<?php echo $replied === 'Y' ? ' selected' : ''; ?>>Replied</option>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url($base); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Enquiries could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No enquiries on this page.' : ($filtered ? 'No enquiries match this search.' : 'No enquiries have been received yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $deletePrvg || $editPrvg; ?>
		<?php $bulk = array('key' => 'enquiries', 'base' => $base, 'page' => $page, 'noun' => 'enquiries', 'filters' => array('keyword' => $keyword, 'from' => $from, 'to' => $to, 'replied' => $replied), 'can_delete' => $deletePrvg, 'can_send_email' => $editPrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Enquiries, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all enquiries" data-sp-select-all="enquiries" form="sp-bulk-enquiries"></th><?php endif; ?>
						<th scope="col">From</th>
						<th scope="col">Type</th>
						<th scope="col">Message</th>
						<th scope="col">Received</th>
						<th scope="col">Reply</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $name = trim($row['first_name'].' '.$row['last_name']); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['id']; ?>" aria-label="Select enquiry from <?php echo html_escape($name); ?>" data-sp-select="enquiries" form="sp-bulk-enquiries"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="From">
							<?php echo html_escape($name); ?>
							<span class="sp-muted d-block small"><?php echo html_escape($row['email']); ?></span>
						</td>
						<td data-label="Type">
							<?php echo html_escape(isset($types[$row['type']]) ? $types[$row['type']] : $row['type']); ?>
							<?php if ((string) $row['product_service'] !== ''): ?><span class="sp-muted d-block small"><?php echo html_escape($row['product_service']); ?></span><?php endif; ?>
						</td>
						<td class="sp-cell-text" data-label="Message"><?php echo html_escape(character_limiter((string) $row['message'], 120)); ?></td>
						<td class="text-nowrap" data-label="Received"><?php echo html_escape(sp_datetime($row['receive_date'])); ?></td>
						<td data-label="Reply">
							<?php if ($row['reply_status'] === 'Y'): ?><span class="sp-badge sp-badge--ok">Replied</span><?php else: ?><span class="sp-badge sp-badge--muted">Not replied</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => $base, 'id' => $row['id'], 'page' => $page,
								'label' => 'enquiry from '.$name, 'view' => TRUE, 'edit' => FALSE,
								'send_mail' => $bulk['filters'],
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'enquiry' : 'enquiries', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
