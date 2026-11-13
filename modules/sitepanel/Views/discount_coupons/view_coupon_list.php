<?php
/* Discount coupons: code / title search, status filter, bulk actions. */
$filtered = $keyword !== '' || $status !== '';
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Discount Coupons</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/coupons/add'); ?>"><?php echo sp_icon('plus'); ?> Add coupon</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/coupons'); ?>" role="search" aria-label="Search coupons">
		<div>
			<label class="form-label" for="filter-keyword">Coupon code or title</label>
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
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/coupons'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Coupons could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No coupons on this page.' : ($filtered ? 'No coupons match your search.' : 'No coupons yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'coupons', 'base' => 'sitepanel/coupons', 'page' => $page, 'noun' => 'coupons', 'filters' => array('keyword' => $keyword, 'status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Discount coupons, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all coupons" data-sp-select-all="coupons" form="sp-bulk-coupons"></th><?php endif; ?>
						<th scope="col">Coupon</th>
						<th scope="col">Discount</th>
						<th scope="col">Usage</th>
						<th scope="col">Validity</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['coupon_id']; ?>" aria-label="Select coupon <?php echo html_escape($row['coupon_code']); ?>" data-sp-select="coupons" form="sp-bulk-coupons"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Coupon">
							<?php echo html_escape($row['coupon_title']); ?>
							<span class="d-block"><strong><code><?php echo html_escape($row['coupon_code']); ?></code></strong></span>
							<span class="sp-muted d-block small">Minimum order: <?php echo (float) $row['minimum_order_amount'] > 0 ? display_price($row['minimum_order_amount']) : 'any amount'; ?></span>
						</td>
						<td data-label="Discount">
							<?php if ($row['coupon_type'] === 'p'): ?>
							<?php echo rtrim(rtrim($row['coupon_discount'], '0'), '.'); ?>%
							<?php if ((float) $row['maximum_coupon_discount'] > 0): ?><span class="sp-muted d-block small">up to <?php echo display_price($row['maximum_coupon_discount']); ?></span><?php endif; ?>
							<?php else: ?>
							<?php echo display_price($row['coupon_discount']); ?>
							<?php endif; ?>
						</td>
						<td data-label="Usage"><?php echo $row['coupon_usage'] === 'multiple' ? (int) $row['usage_count'].' uses per customer' : 'Once per customer'; ?></td>
						<td data-label="Validity">
							<?php echo html_escape(date('d M Y', strtotime($row['start_date']))); ?> – <?php echo html_escape(date('d M Y', strtotime($row['end_date']))); ?>
							<?php if ($row['end_date'] < $today): ?><span class="sp-muted d-block small">Expired</span><?php elseif ($row['start_date'] > $today): ?><span class="sp-muted d-block small">Not started yet</span><?php endif; ?>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/coupons', 'id' => $row['coupon_id'], 'page' => $page,
								'label' => 'coupon '.$row['coupon_code'], 'active' => $row['status'] === '1',
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'coupon' : 'coupons', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
