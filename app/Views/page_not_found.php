<?php
/* 404 page (the design has no 404 page: the shell, the inner banner and a centred
   message in the design's classes); rendered by PublicController::render_not_found()
   for every unknown address (Pages::not_found, the 404 override) and for missing records. */
echo view('top_application');
?>
<?php echo view('inner_hero', array('hero' => array('title' => 'Page Not Found', 'banner' => '404 Page Not Found', 'crumbs' => array('Page Not Found' => NULL)))); ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
<div class="kt-ty mx-auto text-center bg-white border position-relative overflow-hidden">
<div class="kt-ty-ring position-absolute rounded-circle pe-none"></div>
<div class="kt-ty-ring kt-ty-ring2 position-absolute rounded-circle pe-none"></div>
<div class="position-relative">
<div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary mb-3">ERROR 404</div>
<h2 class="kt-ty-title fw-bold text-dark">Page Not Found</h2>
<p class="kt-ty-text kt-text-2 mx-auto">Sorry, the page you are looking for doesn’t exist. It may have been moved, or the link may be incorrect.</p>
<div class="kt-ty-actions d-flex flex-wrap justify-content-center gap-2">
<a href="<?php echo site_url(); ?>" class="btn btn-kt-outline kt-ty-home rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-house-door"></i> BACK TO HOME</a>
<a href="<?php echo site_url('products'); ?>" class="btn btn-kt-dark kt-ty-home rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-grid"></i> VIEW PRODUCTS</a>
</div>
</div>
</div>
</section>
<?php echo view('bottom_application'); ?>
