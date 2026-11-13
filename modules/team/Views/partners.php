<?php
/*
| /partners (Team::partners), built from html/partners.htm: intro, the active
| partner logos and the Become a Partner call to action (Send Inquiry popup).
| $partners = active partner rows + image_url, $unavailable.
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Partners', 'banner' => 'Partners', 'crumbs' => array('Partners' => NULL))));
?>
<!-- PARTNERS: intro + logo grid -->
<section class="container-xxl kt-container kt-sec" data-reveal>
<div class="kt-prt-intro text-center mx-auto">
<div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary mb-3">OUR PARTNERS</div>
<h2 class="kt-h2 fw-bold text-dark text-balance">Trusted by Leading Companies</h2>
<p class="kt-prt-lead kt-text-2 mb-0">From cotton mills to couriers, these partners help CustomCart deliver quality you can feel. We are proud to work alongside them.</p>
</div>
<?php if ( ! $partners): ?>
<p class="text-center text-body-secondary mb-0"><?php echo $unavailable ? 'Our partners could not be loaded. Please try again in a few minutes.' : 'Partner logos will be added soon.'; ?></p>
<?php else: ?>
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 kt-g-prt">
<?php foreach ($partners as $row): ?>
<div class="col"><div class="kt-prt bg-white border d-flex align-items-center justify-content-center"><?php if ($row['image_url'] !== ''): ?><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($row['image_url']); ?>" alt="<?php echo html_escape($row['name']); ?>" class="d-block w-100 h-100 object-fit-contain"><?php else: ?><span class="fw-bold text-dark text-center"><?php echo html_escape($row['name']); ?></span><?php endif; ?></div></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<!-- BECOME A PARTNER -->
<section class="kt-cta-wrap kt-prt-cta-sec" data-reveal>
<div class="kt-cta position-relative mx-auto bg-primary-subtle overflow-hidden">
<div class="kt-cta-ring position-absolute rounded-circle border border-primary-subtle pe-none"></div>
<div class="kt-cta-pad position-relative">
<div class="row g-4 align-items-center">
<div class="col-12 col-lg">
<h2 class="kt-cta-title fw-bold text-dark text-balance">Become a CustomCart Partner</h2>
<p class="kt-cta-text kt-text-2 mb-0">Want to work with CustomCart or order for your team? Share your requirements and our team will get in touch with a supply plan.</p>
</div>
<div class="col-12 col-lg-auto">
<div class="d-flex flex-wrap kt-gap-12">
<button type="button" class="btn btn-kt-gold kt-btn-56 rounded-pill d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#ktInquiry">SEND AN INQUIRY</button>
<a href="<?php echo site_url('contactus'); ?>" class="btn btn-kt-outline btn-kt-outline-dark kt-btn-56 rounded-pill d-inline-flex align-items-center">CONTACT US</a>
</div>
</div>
</div>
</div>
</div>
</section>
<?php echo view('bottom_application'); ?>
