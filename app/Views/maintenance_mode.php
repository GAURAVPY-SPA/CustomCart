<?php
/* Maintenance page (Sitepanel > Settings > Maintenance mode), in the card design of
   page_not_found.php; rendered by PublicController::maintenance_page(). */
echo view('top_application');
?>
<section class="container-xxl kt-container kt-sec" id="maintenance" aria-labelledby="statusTitle">
<div class="kt-ty mx-auto text-center bg-white border position-relative overflow-hidden">
<div class="kt-ty-ring position-absolute rounded-circle pe-none"></div>
<div class="kt-ty-ring kt-ty-ring2 position-absolute rounded-circle pe-none"></div>
<div class="position-relative">
<div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary mb-3"><i class="bi bi-tools me-2" aria-hidden="true"></i>MAINTENANCE</div>
<h1 class="kt-ty-title fw-bold text-dark" id="statusTitle">We&rsquo;ll be back soon</h1>
<p class="kt-ty-text kt-text-2 mx-auto mb-0">The website is undergoing scheduled maintenance. Please check back shortly.</p>
</div>
</div>
</section>
<?php echo view('bottom_application'); ?>
