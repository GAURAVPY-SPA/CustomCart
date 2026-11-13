<?php
/* Sitepanel page header: <head>, sidebar (dashboard/admin_left_view), topbar, flash messages.
   Loaded by each page view; data comes from AdminController (shared view data) + the page. */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo html_escape($heading_title.' | '.$site_title); ?></title>
<?php if ($site_favicon !== ''): ?>
<link rel="icon" href="<?php echo asset_url($site_favicon); ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo asset_url('assets/vendor/bootstrap/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('assets/sitepanel/css/sitepanel.css'); ?>">
<?php echo sp_theme_style(); ?>
</head>
<body class="sp-body">
<a class="sp-skip-link" href="#sp-main">Skip to content</a>

<div class="sp-shell" id="sp-shell">
	<?php echo sp_view('dashboard/admin_left_view'); ?>
	<div class="sp-overlay" data-sp-close-sidebar hidden></div>

	<div class="sp-content">
		<header class="sp-topbar">
			<button class="sp-icon-btn" type="button" data-sp-toggle-sidebar aria-controls="sp-sidebar" aria-expanded="true" aria-label="Toggle navigation">
				<?php echo sp_icon('menu'); ?>
			</button>

			<div class="sp-heading">
				<nav class="sp-breadcrumb" aria-label="Breadcrumb">
					<ol>
						<li><a href="<?php echo site_url('sitepanel/dashboard'); ?>">Sitepanel</a></li>
						<?php foreach ($breadcrumbs as $crumb => $url): ?>
						<li<?php echo $url ? '' : ' aria-current="page"'; ?>>
							<?php if ($url): ?><a href="<?php echo $url; ?>"><?php echo html_escape($crumb); ?></a><?php else: ?><?php echo html_escape($crumb); ?><?php endif; ?>
						</li>
						<?php endforeach; ?>
					</ol>
				</nav>
				<h1 class="sp-page-title"><?php echo html_escape($heading_title); ?></h1>
			</div>

			<?php
			// Previous successful sign-in, captured at login before last_login_at was updated (AdminAuth::previous_login()).
			$last_login_text = sp_datetime($previous_login['at']);
			$last_login_text = $last_login_text !== '' ? $last_login_text : 'First login';
			?>
			<p class="sp-last-login"<?php echo ! empty($previous_login['ip']) ? ' title="From IP '.html_escape($previous_login['ip']).'"' : ''; ?>>
				<span class="sp-last-login-label">Last logged in</span>
				<span class="sp-last-login-value"><?php echo html_escape($last_login_text); ?></span>
			</p>

			<a class="sp-account-btn sp-header-link" href="<?php echo website_url(); ?>" target="_blank" rel="noopener" title="View Website" aria-label="View Website (opens in a new tab)">
				<?php echo sp_icon('globe'); ?>
				<span class="sp-account-name">View Website</span>
				<span class="visually-hidden">(opens in a new tab)</span>
			</a>

			<div class="sp-account">
				<button class="sp-account-btn" type="button" data-sp-toggle-account aria-haspopup="true" aria-expanded="false" aria-controls="sp-account-menu">
					<?php echo sp_icon('user'); ?>
					<span class="sp-account-name"><?php echo html_escape($admin['admin_name']); ?></span>
					<?php echo sp_icon('chevron'); ?>
				</button>
				<div class="sp-account-menu" id="sp-account-menu" hidden>
					<p class="sp-account-meta">
						<strong><?php echo html_escape($admin['admin_name']); ?></strong>
						<span><?php echo html_escape($admin['admin_email']); ?></span>
						<span class="sp-account-last-login">Last logged in: <?php echo html_escape($last_login_text); ?></span>
					</p>
					<?php echo form_open('sitepanel/dashboard/clear_cache'); ?>
						<button class="sp-account-item" type="submit"><?php echo sp_icon('database'); ?> Clear Cache</button>
					<?php echo form_close(); ?>
					<?php echo form_open('sitepanel/logout'); ?>
						<button class="sp-account-item" type="submit"><?php echo sp_icon('logout'); ?> Sign out</button>
					<?php echo form_close(); ?>
				</div>
			</div>
		</header>

		<main class="sp-main" id="sp-main">
			<?php if ( ! empty($flash_error)): ?>
			<div class="alert alert-danger" role="alert"><?php echo html_escape($flash_error); ?></div>
			<?php endif; ?>
			<?php if ( ! empty($flash_success)): ?>
			<div class="alert alert-success" role="status"><?php echo html_escape($flash_success); ?></div>
			<?php endif; ?>
