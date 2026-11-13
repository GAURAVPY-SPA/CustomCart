<?php
/* Refer a Friend messages (Referrals::index()): name / email search, bulk delete. */
$filters = array('keyword' => $keyword);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Refer a Friend</h2>
	</header>

	<form class="sp-filters sp-filters--single" method="get" action="<?php echo site_url('sitepanel/referrals'); ?>" role="search" aria-label="Search referrals">
		<div>
			<label class="form-label" for="filter-keyword">Name or email</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($keyword); ?>">
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($keyword !== ''): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/referrals'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Referrals could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No referrals on this page.' : ($keyword !== '' ? 'No referrals match your search.' : 'No referrals yet.'); ?></p>
	<?php else: ?>
		<?php $bulk = array('key' => 'referrals', 'base' => 'sitepanel/referrals', 'page' => $page, 'noun' => 'referrals', 'filters' => $filters, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Refer a Friend messages, newest first</caption>
				<thead>
					<tr>
						<?php if ($deletePrvg): ?><th scope="col"><input type="checkbox" aria-label="Select all referrals" data-sp-select-all="referrals" form="sp-bulk-referrals"></th><?php endif; ?>
						<th scope="col">From</th>
						<th scope="col">To friend</th>
						<th scope="col">Product / page</th>
						<th scope="col">Sent</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($deletePrvg): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['referral_id']; ?>" aria-label="Select referral <?php echo (int) $row['referral_id']; ?>" data-sp-select="referrals" form="sp-bulk-referrals"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="From"><?php echo html_escape($row['sender_name']); ?><span class="sp-muted d-block small"><?php echo html_escape($row['sender_email']); ?></span></td>
						<td class="sp-cell-text" data-label="To friend"><?php echo html_escape($row['friend_name']); ?><span class="sp-muted d-block small"><?php echo html_escape($row['friend_email']); ?></span></td>
						<td class="sp-cell-text" data-label="Product / page"><?php echo $row['product_name'] !== NULL ? html_escape($row['product_name']) : '<span class="sp-muted">'.html_escape((string) $row['page_url'] !== '' ? $row['page_url'] : '—').'</span>'; ?></td>
						<td data-label="Sent"><?php echo html_escape(sp_datetime($row['referral_date'])); ?></td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/referrals', 'id' => $row['referral_id'], 'page' => $page,
								'label' => 'referral #'.$row['referral_id'], 'view' => TRUE, 'edit' => FALSE,
							))); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => array('position' => 'bottom') + $bulk)); ?>
		<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'referral' : 'referrals', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
