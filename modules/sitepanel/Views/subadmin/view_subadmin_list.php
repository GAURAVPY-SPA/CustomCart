<?php
/* Sub-admins: search (name, username, email), status filter, bulk actions. */
$filtered = $keyword !== '' || $status !== '';
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Sub-admins</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/subadmin/add'); ?>"><?php echo sp_icon('plus'); ?> Add sub-admin</a>
		<?php endif; ?>
	</header>

	<?php echo sp_view('includes/status_filter', array('filter_form' => array('url' => 'sitepanel/subadmin', 'aria_label' => 'Search sub-admins', 'label' => 'Name, username or email', 'keyword' => $keyword, 'status' => $status))); ?>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Sub-admins could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No sub-admins on this page.' : ($filtered ? 'No sub-admins match your search.' : 'No sub-admins yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'subadmins', 'base' => 'sitepanel/subadmin', 'page' => $page, 'noun' => 'sub-admins', 'filters' => array('keyword' => $keyword, 'status_filter' => $status), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Sub-admins, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all sub-admins" data-sp-select-all="subadmins" form="sp-bulk-subadmins"></th><?php endif; ?>
						<th scope="col">Name</th>
						<th scope="col">Username</th>
						<th scope="col">Contact</th>
						<th scope="col">Sections</th>
						<th scope="col">Added · last login</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $active = (int) $row['status'] === 1; ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['admin_id']; ?>" aria-label="Select <?php echo html_escape($row['admin_username']); ?>" data-sp-select="subadmins" form="sp-bulk-subadmins"></td><?php endif; ?>
						<td class="sp-cell-text" data-label="Name"><?php echo html_escape($row['admin_name']); ?></td>
						<td class="sp-cell-text" data-label="Username"><?php echo html_escape($row['admin_username']); ?></td>
						<td class="sp-cell-text" data-label="Contact">
							<?php echo html_escape($row['admin_email']); ?>
							<?php if ((string) $row['phone'] !== ''): ?><span class="sp-muted d-block small"><?php echo html_escape($row['phone']); ?></span><?php endif; ?>
						</td>
						<td data-label="Sections"><?php echo (int) $row['section_count']; ?></td>
						<td data-label="Added · last login">
							<?php echo html_escape(sp_datetime($row['created_at'])); ?>
							<span class="sp-muted d-block small"><?php echo $row['last_login_at'] ? html_escape(sp_datetime($row['last_login_at'])) : 'Never signed in'; ?></span>
						</td>
						<td data-label="Status">
							<?php if ($active): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/subadmin', 'id' => $row['admin_id'], 'page' => $page,
								'label' => $row['admin_username'], 'active' => $active,
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
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'sub-admin' : 'sub-admins', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
