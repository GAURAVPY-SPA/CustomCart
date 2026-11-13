<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
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

		<h1 class="sp-login-title">Sitepanel sign in</h1>

		<?php if ($notice): ?>
		<div class="alert alert-success" role="status"><?php echo html_escape($notice); ?></div>
		<?php endif; ?>

		<?php if ($error): ?>
		<div class="alert alert-danger" role="alert"><?php echo html_escape($error); ?></div>
		<?php endif; ?>

		<?php echo form_open('sitepanel/login', array('class' => 'sp-login-form', 'autocomplete' => 'off')); ?>
			<div class="mb-3">
				<label class="form-label" for="username">Username</label>
				<input class="form-control<?php echo form_error('username') ? ' is-invalid' : ''; ?>" type="text" id="username" name="username"
					value="<?php echo set_value('username'); ?>" maxlength="50" autocomplete="off" autocapitalize="none" spellcheck="false" required readonly data-sp-no-autofill>
				<?php echo form_error('username', '<div class="invalid-feedback">', '</div>'); ?>
			</div>
			<div class="mb-4">
				<label class="form-label" for="password">Password</label>
				<input class="form-control<?php echo form_error('password') ? ' is-invalid' : ''; ?>" type="password" id="password" name="password"
					maxlength="255" autocomplete="current-password" required>
				<?php echo form_error('password', '<div class="invalid-feedback">', '</div>'); ?>
			</div>
			<button class="btn sp-btn-primary w-100" type="submit">Sign in</button>
		<?php echo form_close(); ?>

		<p class="sp-login-back"><a href="<?php echo website_url(); ?>">&larr; Back to website</a></p>
	</div>
</main>
<script>
/* Browsers ignore autocomplete="off" on login forms but never offer saved
   usernames for a read-only field: the username stays read-only until it is
   focused, then becomes editable. */
(function () {
	var field = document.querySelector("[data-sp-no-autofill]");
	if (!field) { return; }
	field.addEventListener("focus", function () {
		setTimeout(function () { field.removeAttribute("readonly"); }, 50);
	});
	field.addEventListener("blur", function () { field.setAttribute("readonly", ""); });
})();
</script>
<script src="<?php echo asset_url('assets/sitepanel/js/sitepanel.js'); ?>"></script>
</body>
</html>
