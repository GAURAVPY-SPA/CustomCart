<?php
/* SEO tags for a website page's <head>, rendered by seo_head() from App\Libraries\PageMeta::head()
   (wl_meta_tags, Sitepanel > Meta Tags). Empty values are left out. */
?>
<title><?php echo html_escape($seo['title']); ?></title>
<?php if ($seo['description'] !== ''): ?>
<meta name="description" content="<?php echo html_escape($seo['description']); ?>">
<?php endif; ?>
<?php if ($seo['keywords'] !== ''): ?>
<meta name="keywords" content="<?php echo html_escape($seo['keywords']); ?>">
<?php endif; ?>
<link rel="canonical" href="<?php echo html_escape($seo['canonical']); ?>">
<meta property="og:type" content="website">
<?php if ($seo['site_name'] !== ''): ?>
<meta property="og:site_name" content="<?php echo html_escape($seo['site_name']); ?>">
<?php endif; ?>
<meta property="og:title" content="<?php echo html_escape($seo['title']); ?>">
<?php if ($seo['description'] !== ''): ?>
<meta property="og:description" content="<?php echo html_escape($seo['description']); ?>">
<?php endif; ?>
<meta property="og:url" content="<?php echo html_escape($seo['canonical']); ?>">
<?php if ($seo['og_image'] !== ''): ?>
<meta property="og:image" content="<?php echo html_escape($seo['og_image']); ?>">
<?php endif; ?>
