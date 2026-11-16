<?php
/**
 * Sitepanel "Forgot password?" (ForgotPassword::index()). Same card as the
 * login page. $errors, $notice, $email.
 */
?>
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

		<h1 class="sp-login-title">Forgot password</h1>

		<?php if ($notice): ?>
		<div class="alert alert-success" role="status"><?php echo html_escape($notice); ?></div>
		<?php else: ?>
		<p class="text-muted">Enter the email address of your Sitepanel account and we will send you a link to reset your password.</p>
		<?php endif; ?>

		<?php if ($errors): ?>
		<div class="alert alert-danger" role="alert">
			<?php foreach ($errors as $error): ?>
			<div><?php echo html_escape($error); ?></div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php echo form_open('sitepanel/forgot_password', array('class' => 'sp-login-form', 'novalidate' => 'novalidate')); ?>
			<div class="mb-4">
				<label class="form-label" for="email">Email address</label>
				<input class="form-control<?php echo $errors ? ' is-invalid' : ''; ?>" type="email" id="email" name="email"
					value="<?php echo html_escape($email); ?>" maxlength="191" autocomplete="email" autocapitalize="none" spellcheck="false" required autofocus>
			</div>
			<button class="btn sp-btn-primary w-100" type="submit">Send reset link</button>
		<?php echo form_close(); ?>

		<p class="sp-login-back"><a href="<?php echo site_url('sitepanel/login'); ?>">&larr; Back to sign in</a></p>
	</div>
</main>
<script src="<?php echo asset_url('assets/sitepanel/js/sitepanel.js'); ?>"></script>
</body>
</html>
