<?php
/* Page information + content preview. $content is sanitised HTML (Html_sanitizer). */
echo sp_view('includes/header');
$active = ($res['status'] === '1');
?>
<section class="sp-panel mb-4" aria-labelledby="page-info">
	<header class="sp-panel-head">
		<h2 class="sp-panel-title" id="page-info">Page information</h2>
		<?php if ($editPrvg): ?>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/staticpages/edit/'.(int) $res['page_id']); ?>">Edit page</a>
		<?php endif; ?>
	</header>
	<dl class="sp-details">
		<div><dt>Page name</dt><dd><?php echo html_escape($res['page_name']); ?></dd></div>
		<div><dt>Status</dt><dd><?php echo $active ? '<span class="sp-badge sp-badge--ok">Active</span>' : '<span class="sp-badge sp-badge--muted">Inactive</span>'; ?></dd></div>
		<div><dt>Public address</dt><dd>
			<?php if ($active): ?>
			<a class="sp-link sp-link--external" href="<?php echo html_escape(website_url($res['friendly_url'])); ?>" target="_blank" rel="noopener"><?php echo html_escape(website_url($res['friendly_url'])); ?><span class="visually-hidden"> (opens in a new tab)</span></a>
			<?php else: ?>
			<?php echo html_escape(website_url($res['friendly_url'])); ?> <span class="sp-muted">(not visible while inactive)</span>
			<?php endif; ?>
		</dd></div>
		<div><dt>Heading</dt><dd><?php echo (string) $res['page_short_description'] !== '' ? html_escape($res['page_short_description']) : '<span class="sp-muted">—</span>'; ?></dd></div>
		<div><dt>Last updated</dt><dd><?php echo html_escape(sp_datetime($res['page_updated_date'])); ?></dd></div>
	</dl>
</section>

<section class="sp-panel mb-4" aria-labelledby="page-content">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="page-content">Content</h2></header>
	<div class="sp-page-preview"><?php echo $content; ?></div>
</section>

<div class="sp-form-actions mb-4">
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/staticpages'); ?>">Back to static pages</a>
</div>
<?php echo sp_view('includes/footer'); ?>
