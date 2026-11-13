<?php
/* Team & Partners list: name search, type and status filters, display order, bulk actions. */
use Modules\Team\Models\TeamModel;

$filtered = $keyword !== '' || $status !== '' || $type !== '';
$filters  = array('keyword' => $keyword, 'status_filter' => $status, 'type_filter' => $type);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Team &amp; Partners</h2>
		<?php if ($addPrvg): ?>
		<div class="d-flex flex-wrap gap-2">
			<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/team/add?type='.TeamModel::TYPE_TEAM); ?>"><?php echo sp_icon('plus'); ?> Add team member</a>
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/team/add?type='.TeamModel::TYPE_PARTNER); ?>"><?php echo sp_icon('plus'); ?> Add partner</a>
		</div>
		<?php endif; ?>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/team'); ?>" role="search" aria-label="Search team and partners">
		<div>
			<label class="form-label" for="filter-keyword">Name or designation</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($keyword); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-type">Type</label>
			<select class="form-select" id="filter-type" name="type">
				<option value="">All</option>
				<?php foreach (TeamModel::TYPES as $key => $label): ?>
				<option value="<?php echo $key; ?>"<?php echo $type === $key ? ' selected' : ''; ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
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
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/team'); ?>">Clear</a><?php endif; ?>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Entries could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No entries on this page.' : ($filtered ? 'No entries match your search.' : 'No team members or partners yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'team', 'base' => 'sitepanel/team', 'page' => $page, 'noun' => 'entries', 'filters' => $filters, 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<?php if ($editPrvg): ?>
		<?php echo form_open('sitepanel/team/sort', array('id' => 'sp-sort-team')); ?>
			<input type="hidden" name="page" value="<?php echo (int) $page; ?>">
			<?php foreach ($filters as $name => $value): ?>
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo html_escape($value); ?>">
			<?php endforeach; ?>
		<?php echo form_close(); ?>
		<?php endif; ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Team members and partners in display order</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all entries" data-sp-select-all="team" form="sp-bulk-team"></th><?php endif; ?>
						<th scope="col">Photo / logo</th>
						<th scope="col">Name</th>
						<th scope="col">Type</th>
						<th scope="col">Display order</th>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $thumb = get_image('team', $row['photo'], 96, 96, $row['team_type'] === TeamModel::TYPE_PARTNER ? 'R' : 'C'); ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo (int) $row['team_id']; ?>" aria-label="Select <?php echo html_escape($row['name']); ?>" data-sp-select="team" form="sp-bulk-team"></td><?php endif; ?>
						<td data-label="Photo / logo"><?php if ($thumb !== ''): ?><img class="sp-thumb" src="<?php echo html_escape($thumb); ?>" alt="" loading="lazy"><?php else: ?><span class="sp-muted">—</span><?php endif; ?></td>
						<td class="sp-cell-text" data-label="Name">
							<strong><?php echo html_escape($row['name']); ?></strong>
							<?php if ((string) $row['designation'] !== ''): ?><span class="sp-muted d-block small"><?php echo html_escape($row['designation']); ?></span><?php endif; ?>
						</td>
						<td data-label="Type"><?php echo html_escape(TeamModel::TYPES[$row['team_type']] ?? '—'); ?></td>
						<td data-label="Display order">
							<?php if ($editPrvg): ?>
							<input class="form-control form-control-sm" type="number" min="0" max="9999" style="width: 6rem" name="ord[<?php echo (int) $row['team_id']; ?>]" value="<?php echo (int) $row['sort_order']; ?>" aria-label="Display order of <?php echo html_escape($row['name']); ?>" form="sp-sort-team">
							<?php else: ?>
							<?php echo (int) $row['sort_order']; ?>
							<?php endif; ?>
						</td>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/team', 'id' => $row['team_id'], 'page' => $page,
								'label' => $row['name'], 'active' => $row['status'] === '1',
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
			<button class="btn sp-btn-outline" type="submit" form="sp-sort-team">Update order</button>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'entry' : 'entries', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
