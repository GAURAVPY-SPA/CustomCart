<?php 
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Newsletter subscribers</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/newsletter/add'); ?>"><?php echo sp_icon('plus'); ?> Add subscriber</a>
		<?php endif; ?>
	</header>
<div style="width:400px !important">
	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/newsletter'); ?>" role="search" aria-label="Search subscribers">
		<div>
			<label class="form-label" for="filter-keyword">Name or email</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($keyword); ?>">
		</div>
		
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($keyword !== '' || $status !== ''): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/newsletter'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>
    </div>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Subscribers could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No subscribers on this page.' : (($keyword !== '' || $status !== '') ? 'No subscribers match this search.' : 'No subscribers yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $deletePrvg || $editPrvg; ?>
		<?php $bulk = array('key' => 'newsletter', 'base' => 'sitepanel/newsletter', 'page' => $page, 'noun' => 'subscribers', 'filters' => array('keyword' => $keyword, 'status' => $status), 'can_delete' => $deletePrvg, 'can_send_email' => $editPrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Newsletter subscribers, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all subscribers" data-sp-select-all="newsletter" form="sp-bulk-newsletter"></th><?php endif; ?>
						<th scope="col">Name</th>
						<th scope="col">Email</th>						
						<th scope="col">Subscribed</th>
						<th scope="col">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['subscriber_id']; ?>" aria-label="Select subscriber <?php echo html_escape($row['subscriber_email']); ?>" data-sp-select="newsletter" form="sp-bulk-newsletter"></td><?php endif; ?>
						<td data-label="Name"><?php echo html_escape((string) $row['subscriber_name']); ?></td>
						<td class="sp-cell-text" data-label="Email"><?php echo html_escape($row['subscriber_email']); ?></td>
						
						<td class="text-nowrap" data-label="Subscribed"><?php echo html_escape(sp_datetime($row['subscribe_date'])); ?></td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/newsletter', 'id' => $row['subscriber_id'], 'page' => $page,
								'label' => $row['subscriber_email'], 
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'subscriber' : 'subscribers', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
