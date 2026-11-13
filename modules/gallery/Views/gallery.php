<?php
/*
| /images-gallery — html/images-gallery.htm. $photos = [title, src, full, width,
| height] (Gallery::index); $unavailable = the photos could not be read.
| The site script (customcart.js) opens #ktLightbox on a .kt-gal-item button.
*/
echo view('top_application');
?>
<?php echo view('inner_hero', array('hero' => array('title' => 'Images Gallery', 'banner' => 'Images Gallery', 'crumbs' => array('Images Gallery' => NULL)))); ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
<?php if ( ! $photos): ?>
  <p class="kt-text-2 text-center mb-0"><?php echo $unavailable ? 'The gallery is not available right now. Please try again in a few minutes.' : 'Photos will be added here soon.'; ?></p>
<?php else: ?>
  <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 kt-g-gal">
<?php foreach ($photos as $photo): ?>
        <div class="col"><div class="kt-gal-item position-relative ratio ratio-1x1 rounded-5 overflow-hidden bg-primary-subtle"><div><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($photo['src']); ?>" alt="<?php echo html_escape($photo['title']); ?>" class="w-100 h-100 object-fit-cover"><button type="button" class="btn kt-gal-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#ktLightbox" aria-label="View photo"><i class="bi bi-arrows-angle-expand"></i></button></div></div></div>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>

<div class="modal fade kt-lightbox" id="ktLightbox" tabindex="-1" aria-label="Gallery viewer" aria-hidden="true">
<div class="modal-dialog modal-fullscreen">
<div class="modal-content kt-lb kt-lb-clear position-relative border-0 rounded-0 d-flex align-items-center justify-content-center">
<button type="button" class="btn btn-kt-prev kt-lb-close position-absolute rounded-circle d-flex align-items-center justify-content-center" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
<button type="button" class="btn btn-kt-prev kt-btn-52 kt-lb-prev position-absolute top-50 translate-middle-y rounded-circle d-flex align-items-center justify-content-center" id="ktLbPrev" aria-label="Previous"><i class="bi bi-arrow-left"></i></button>
<button type="button" class="btn btn-kt-next kt-btn-52 kt-lb-next position-absolute top-50 translate-middle-y rounded-circle d-flex align-items-center justify-content-center" id="ktLbNext" aria-label="Next"><i class="bi bi-arrow-right"></i></button>
<div class="kt-lb-frame position-relative overflow-hidden"><img src="data:," alt="" class="d-block w-100 h-100 object-fit-contain" id="ktLbImg"></div>
</div>
</div>
</div>
<?php echo view('bottom_application'); ?>
