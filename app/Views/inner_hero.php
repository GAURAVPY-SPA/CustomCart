<?php
/*
| Inner page banner + breadcrumb (design: the kt-inner-banner section of
| every inner page).
|   $hero = ['title'  => page heading,
|            'banner' => banner position(s) of the page, most specific first
|                        (Config\Sitepanel::$bannerPositions; inner_banner()),
|            'image'  => an image URL that overrides the banner (e.g. a category banner),
|            'crumbs' => [label => url, ..., current label => NULL] ([] = only the title)]
*/
$hero  += array('banner' => array(), 'crumbs' => array(), 'image' => '');
$image  = $hero['image'] !== '' ? $hero['image'] : inner_banner($hero['banner'])['src'];
?>
<section class="kt-inner-banner position-relative overflow-hidden text-white">
<img src="<?php echo html_escape($image); ?>" alt="" class="kt-inner-img position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
<div class="container-xxl kt-container position-relative">
<h1 class="kt-inner-title fw-bold text-white"><?php echo html_escape($hero['title']); ?></h1>
<?php if ($hero['crumbs']): ?>
<nav aria-label="breadcrumb">
<ol class="breadcrumb kt-breadcrumb mb-0">
<li class="breadcrumb-item"><a href="<?php echo site_url(); ?>">Home</a></li>
<?php foreach ($hero['crumbs'] as $label => $url): ?>
<?php if ($url === NULL): ?>
<li class="breadcrumb-item active" aria-current="page"><?php echo html_escape($label); ?></li>
<?php else: ?>
<li class="breadcrumb-item"><a href="<?php echo html_escape($url); ?>"><?php echo html_escape($label); ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
</ol>
</nav>
<?php endif; ?>
</div>
</section>
