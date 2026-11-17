<?php
/* FAQ list: question search, status filter, display order, bulk actions. */
$filtered = $keyword !== '' || $status !== '';
$filters  = array('keyword' => $keyword, 'status_filter' => $status);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">FAQs</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/faq/add'); ?>"><?php echo sp_icon('plus'); ?> Add FAQ</a>
		<?php endif; ?>
	</header>

	<?php echo sp_view('includes/status_filter', array('filter_form' => array('url' => 'sitepanel/faq', 'aria_label' => 'Search FAQs', 'label' => 'Question', 'keyword' => $keyword, 'status' => $status))); ?>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">FAQs could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No FAQs on this page.' : ($filtered ? 'No FAQs match your search.' : 'No FAQs yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'faq', 'base' => 'sitepanel/faq', 'page' => $page, 'noun' => 'FAQs', 'filters' => $filters, 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<?php if ($editPrvg): ?>
		<?php echo form_open('sitepanel/faq/sort', array('id' => 'sp-sort-faq')); ?>
			<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
			<?php foreach ($filters as $name => $value): ?>
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo html_escape($value); ?>">
			<?php endforeach; ?>
		<?php echo form_close(); ?>
		<?php endif; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">FAQs in display order</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all FAQs" data-sp-select-all="faq" form="sp-bulk-faq"></th><?php endif; ?>
						<th scope="col">Question / Answer</th>
						<th scope="col">Display order</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['faq_id']; ?>" aria-label="Select FAQ <?php echo (int) $row['faq_id']; ?>" data-sp-select="faq" form="sp-bulk-faq"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Question / Answer">
							<strong><?php echo html_escape($row['faq_question']); ?></strong>
							<span class="sp-muted d-block small"><?php echo html_escape(character_limiter($row['faq_answer'], 160)); ?></span>
						</td>
						<td data-label="Display order">
							<?php if ($editPrvg): ?>
							<input class="form-control form-control-sm" type="number" min="0" max="9999" style="width: 6rem" name="ord[<?php echo (int) $row['faq_id']; ?>]" value="<?php echo (int) $row['sort_order']; ?>" aria-label="Display order of FAQ <?php echo (int) $row['faq_id']; ?>" form="sp-sort-faq">
							<?php else: ?>
							<?php echo (int) $row['sort_order']; ?>
							<?php endif; ?>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/faq', 'id' => $row['faq_id'], 'page' => $page,
								'label' => 'FAQ #'.$row['faq_id'], 'active' => $row['status'] === '1',
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
			<button class="btn sp-btn-outline" type="submit" form="sp-sort-faq">Update order</button>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'FAQ' : 'FAQs', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
