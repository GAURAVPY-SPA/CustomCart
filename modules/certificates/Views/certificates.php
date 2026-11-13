<?php
/*
| /certificates (Certificates::index), built from html/certificate.htm: image
| cards; the zoom button opens the lightbox (#ktLightbox, customcart.js shows
| the card's image and pages through the others). $items = rows + thumb_url,
| full_url, pdf_url ('' = none); $unavailable.
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Certificates', 'banner' => 'Certificates', 'crumbs' => array('Certificates' => NULL))));
?>
<!-- CERTIFICATES -->
<section class="container-xxl kt-container kt-sec" data-reveal>
<?php if ( ! $items): ?>
  <p class="text-center text-body-secondary mb-0"><?php echo $unavailable ? 'Certificates could not be loaded. Please try again in a few minutes.' : 'Our certificates will be added soon.'; ?></p>
<?php else: ?>
  <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 kt-g-gal">
<?php foreach ($items as $item): ?>
    <div class="col"><div class="kt-gal-item position-relative ratio kt-cert-ratio rounded-5 overflow-hidden bg-primary-subtle"><div><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($item['thumb_url']); ?>" alt="<?php echo html_escape($item['title']); ?>" class="w-100 h-100 object-fit-cover"><a href="<?php echo html_escape($item['full_url']); ?>" data-kt-zoom hidden></a><button type="button" class="btn kt-gal-btn kt-cert-zoom position-absolute rounded-circle d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#ktLightbox" aria-label="View <?php echo html_escape($item['title']); ?>"><i class="bi bi-zoom-in"></i></button></div></div>
<div class="kt-cert-name fw-bold text-dark text-center"><?php if ($item['pdf_url'] !== ''): ?><a href="<?php echo html_escape($item['pdf_url']); ?>" target="_blank" rel="noopener" class="text-dark"><?php echo html_escape($item['title']); ?></a><?php else: ?><?php echo html_escape($item['title']); ?><?php endif; ?></div></div>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>

<!-- LIGHTBOX (certificates) -->
<div class="modal fade kt-lightbox" id="ktLightbox" tabindex="-1" aria-label="Certificate viewer" aria-hidden="true">
<div class="modal-dialog modal-fullscreen">
<div class="modal-content kt-lb position-relative border-0 rounded-0 d-flex align-items-center justify-content-center">
<button type="button" class="btn btn-kt-prev kt-lb-close position-absolute rounded-circle d-flex align-items-center justify-content-center" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
<button type="button" class="btn btn-kt-prev kt-btn-52 kt-lb-prev position-absolute top-50 translate-middle-y rounded-circle d-flex align-items-center justify-content-center" id="ktLbPrev" aria-label="Previous"><i class="bi bi-arrow-left"></i></button>
<button type="button" class="btn btn-kt-next kt-btn-52 kt-lb-next position-absolute top-50 translate-middle-y rounded-circle d-flex align-items-center justify-content-center" id="ktLbNext" aria-label="Next"><i class="bi bi-arrow-right"></i></button>
<div class="kt-lb-frame kt-lb-cert position-relative overflow-hidden bg-primary-subtle"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="" class="d-block w-100 h-100 object-fit-contain" id="ktLbImg"></div>
</div>
</div>
</div>
<?php echo view('bottom_application'); ?>
