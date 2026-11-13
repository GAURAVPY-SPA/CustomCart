<?php
/**
 * Change Password form. Password fields are never filled back in.
 */
echo sp_view('includes/header');
?>
<?php if ($errors): ?>
<div class="alert alert-danger" role="alert">
	<p class="mb-1 fw-semibold">Password was not changed:</p>
	<ul class="mb-0">
		<?php foreach ($errors as $error): ?>
		<li><?php echo html_escape($error); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<?php echo form_open('sitepanel/change_password', array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="pw-heading">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="pw-heading">Change password</h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="current_password">Current password <span class="text-danger">*</span></label>
				<input class="form-control" type="password" id="current_password" name="current_password" maxlength="255" autocomplete="current-password" required>
			</div>
			<div class="w-100"></div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="new_password">New password <span class="text-danger">*</span></label>
				<input class="form-control" type="password" id="new_password" name="new_password" minlength="<?php echo $policy->min_length(); ?>" maxlength="<?php echo $policy->max_length(); ?>" autocomplete="new-password" required>
				<div class="form-text"><?php echo html_escape($policy->summary()); ?></div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="confirm_password">Confirm new password <span class="text-danger">*</span></label>
				<input class="form-control" type="password" id="confirm_password" name="confirm_password" maxlength="<?php echo $policy->max_length(); ?>" autocomplete="new-password" required>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit">Change password</button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/dashboard'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
