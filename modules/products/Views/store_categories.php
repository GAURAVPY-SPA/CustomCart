<?php
/*
| Category grid (design: html/category.htm for /online-store, sub-category.htm
| for a category with sub-categories). $hero [title, crumbs, image], $category
| (NULL on /online-store), $categories rows + image_url.
*/
echo view('top_application');
echo view('inner_hero', array('hero' => $hero + array('banner' => 'Online Store')));
?>
<section class="container-xxl kt-container kt-sec" data-reveal>
<?php if ($category && trim((string) $category['category_description']) !== ''): ?>
<div class="kt-cms mx-auto kt-mb-40"><p class="kt-lead kt-text-2 text-center mb-0"><?php echo nl2br(html_escape($category['category_description'])); ?></p></div>
<?php endif; ?>
<?php if ($categories): ?>
<div class="row row-cols-2 row-cols-xl-3 kt-g-cat">
<?php foreach ($categories as $row): $curl = category_url($row['friendly_url']); ?>
<div class="col">
<div class="kt-cat d-flex flex-column align-items-center text-center text-dark">
<a href="<?php echo html_escape($curl); ?>" class="position-relative w-100 ratio ratio-1x1 d-block" aria-label="<?php echo html_escape($row['category_name']); ?>">
<div>
<div class="kt-cat-ring position-absolute pe-none"></div>
<div class="kt-cat-img position-absolute top-0 start-0 w-100 h-100 rounded-5 overflow-hidden bg-white"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($row['image_url']); ?>" alt="<?php echo html_escape($row['category_alt'] ?: $row['category_name']); ?>" class="w-100 h-100 object-fit-cover"></div>
</div>
</a>
<div>
<div class="kt-cat-name fw-bold"><a href="<?php echo html_escape($curl); ?>" title="<?php echo html_escape($row['category_name']); ?>"><?php echo html_escape($row['category_name']); ?></a></div>
<a href="<?php echo html_escape($curl); ?>" class="kt-cat-link d-inline-flex align-items-center gap-2 fw-bold text-primary">EXPLORE <i class="bi bi-arrow-right"></i></a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="text-center kt-text-2 py-5">
<p class="kt-lead mb-4">Our product range is being updated. Please check back soon, or send us an inquiry for your requirements.</p>
<button type="button" class="btn btn-kt-dark rounded-pill kt-btn-56 px-4" data-bs-toggle="modal" data-bs-target="#ktInquiry">SEND INQUIRY</button>
</div>
<?php endif; ?>
</section>
<?php echo view('bottom_application'); ?>
