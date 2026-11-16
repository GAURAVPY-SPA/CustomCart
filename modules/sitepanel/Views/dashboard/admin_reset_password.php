<?php
/**
 * Sitepanel reset password (ForgotPassword::reset()), opened from the emailed
 * link. Same card as the login page. $valid (link usable), $unavailable
 * (database error), $errors, $policy (AdminPasswordPolicy). Password fields
 * are never filled back in; the link itself is kept in the session, not here.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<title><?php echo html_escape($page_title); ?></title>
<?php if ($site_favicon !== ''): ?>
<link rel="icon" href="<?php echo asset_url($site_favicon); ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo asset_url('assets/vendor/bootstrap/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('assets/sitepanel/css/sitepanel.css'); ?>">
<?php echo sp_theme_style(); ?>
</head>
<body class="sp-login-body">
<main class="sp-login">
	<div class="sp-login-card">
		<div class="sp-login-brand">
			<img src="<?php echo str_starts_with((string) $site_logo, 'uploads/') ? base_url($site_logo) : theme_url().'images/'.$site_logo; ?>" width="182" height="44" alt="<?php echo html_escape($site_name); ?> logo">
			<p class="sp-login-site"><?php echo html_escape($site_name); ?></p>
		</div>

		<h1 class="sp-login-title">Reset password</h1>

		<?php if ($valid): ?>

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

		<?php echo form_open('sitepanel/reset_password', array('class' => 'sp-login-form', 'novalidate' => 'novalidate')); ?>
			<div class="mb-3">
				<label class="form-label" for="new_password">New password</label>
				<input class="form-control" type="password" id="new_password" name="new_password"
					minlength="<?php echo $policy->min_length(); ?>" maxlength="<?php echo $policy->max_length(); ?>" autocomplete="new-password" required autofocus>
				<div class="form-text"><?php echo html_escape($policy->summary()); ?></div>
			</div>
			<div class="mb-4">
				<label class="form-label" for="confirm_password">Confirm new password</label>
				<input class="form-control" type="password" id="confirm_password" name="confirm_password"
					maxlength="<?php echo $policy->max_length(); ?>" autocomplete="new-password" required>
			</div>
			<button class="btn sp-btn-primary w-100" type="submit">Reset password</button>
		<?php echo form_close(); ?>

		<?php elseif ($unavailable): ?>
		<div class="alert alert-danger" role="alert">Password reset is temporarily unavailable. Please try again shortly.</div>
		<?php else: ?>
		<div class="alert alert-danger" role="alert">This password reset link is invalid, has expired or has already been used.</div>
		<a class="btn sp-btn-primary w-100" href="<?php echo site_url('sitepanel/forgot_password'); ?>">Request a new link</a>
		<?php endif; ?>

		<p class="sp-login-back"><a href="<?php echo site_url('sitepanel/login'); ?>">&larr; Back to sign in</a></p>
	</div>
</main>
<script src="<?php echo asset_url('assets/sitepanel/js/sitepanel.js'); ?>"></script>
</body>
</html>
