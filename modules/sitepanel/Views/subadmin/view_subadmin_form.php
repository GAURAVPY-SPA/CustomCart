<?php
/*
| Add / edit a sub-admin. $res = the sub-admin (empty values when adding),
| $sections = heading => grantable sections (each with its 'privileges'),
| $grants = section id => granted privilege codes.
*/
$is_edit    = isset($res['admin_id']);
$action     = $is_edit ? 'sitepanel/subadmin/edit/'.(int) $res['admin_id'] : 'sitepanel/subadmin/add';
$privileges = \Modules\Sitepanel\Controllers\Subadmin::PRIVILEGES;
$value      = function ($key) use ($res) {
	return set_value($key, (string) $res[$key]);
};
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate', 'autocomplete' => 'off')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit sub-admin' : 'New sub-admin'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="admin_name">Name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="admin_name" name="admin_name" maxlength="50" required value="<?php echo $value('admin_name'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="admin_email">Email <span class="text-danger">*</span></label>
				<input class="form-control" type="email" id="admin_email" name="admin_email" maxlength="191" required value="<?php echo $value('admin_email'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="phone">Phone no. <span class="text-danger">*</span></label>
				<input class="form-control" type="tel" id="phone" name="phone" maxlength="15" required value="<?php echo $value('phone'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="admin_username">Username <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="admin_username" name="admin_username" maxlength="50" required autocomplete="off" value="<?php echo $value('admin_username'); ?>">
				<div class="form-text">Letters, digits, . _ and -. Used to sign in to the sitepanel.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="password">Password<?php if ( ! $is_edit): ?> <span class="text-danger">*</span><?php endif; ?></label>
				<?php $policy = new \Modules\Sitepanel\Libraries\AdminPasswordPolicy(); ?>
				<input class="form-control" type="password" id="password" name="password" maxlength="<?php echo $policy->max_length(); ?>" autocomplete="new-password"<?php echo $is_edit ? '' : ' required'; ?>>
				<div class="form-text"><?php echo html_escape($policy->summary()); ?><?php echo $is_edit ? ' Leave empty to keep the current password.' : ''; ?></div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="cpassword">Confirm password<?php if ( ! $is_edit): ?> <span class="text-danger">*</span><?php endif; ?></label>
				<input class="form-control" type="password" id="cpassword" name="cpassword" maxlength="<?php echo $policy->max_length(); ?>" autocomplete="new-password"<?php echo $is_edit ? '' : ' required'; ?>>
			</div>
		</div>
	</section>

	<section class="sp-panel mb-4" aria-labelledby="sections-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="sections-title">Allowed sections <span class="text-danger">*</span></h2></header>
		<p class="form-text mt-0">Tick a section to allow it (View is always included), then the actions allowed there. The Dashboard and Change Password are always available.</p>
		<?php foreach ($sections as $heading => $group): ?>
		<div class="sp-table-wrap mb-3">
			<table class="sp-table sp-table--stack">
				<caption class="sp-stat-label"><?php echo html_escape($heading); ?></caption>
				<thead>
					<tr>
						<th scope="col">Section</th>
						<th scope="col">Allowed actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($group as $section): ?>
					<?php $id = (int) $section['id']; $granted = isset($grants[$id]) ? $grants[$id] : NULL; ?>
					<tr>
						<td data-label="Section">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="sec-<?php echo $id; ?>" name="sec_id[]" value="<?php echo $id; ?>"<?php echo $granted !== NULL ? ' checked' : ''; ?>>
								<label class="form-check-label" for="sec-<?php echo $id; ?>"><?php echo html_escape($section['section_title']); ?></label>
							</div>
						</td>
						<td data-label="Allowed actions">
							<div class="d-flex flex-wrap gap-3">
								<?php foreach ($section['privileges'] as $code): ?>
								<?php if ( ! isset($privileges[$code]) || $code === \Modules\Sitepanel\Libraries\AdminAccess::PRIV_VIEW) { continue; } ?>
								<div class="form-check">
									<input class="form-check-input" type="checkbox" id="perm-<?php echo $id.'-'.$code; ?>" name="permissions[<?php echo $id; ?>][]" value="<?php echo $code; ?>"<?php echo $granted !== NULL && in_array($code, $granted, TRUE) ? ' checked' : ''; ?>>
									<label class="form-check-label" for="perm-<?php echo $id.'-'.$code; ?>"><?php echo $privileges[$code]; ?><span class="visually-hidden"> in <?php echo html_escape($section['section_title']); ?></span></label>
								</div>
								<?php endforeach; ?>
								<?php if (count($section['privileges']) === 1): ?><span class="sp-muted">View only</span><?php endif; ?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endforeach; ?>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add sub-admin'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/subadmin'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
