<?php
/*
| Footer, mobile bar and floating buttons (design: html/Library/footer.lbi).
| Contact details, social links and the copyright come from Sitepanel >
| Admin Settings; the product links are the menu's top categories.
*/
$settings = service('siteSettings');
$address  = trim((string) $settings->get('contact_address'));
$phone    = trim((string) $settings->get('contact_phone'));
$email    = trim((string) $settings->get('contact_email'));
$about    = trim((string) $settings->get('footer_description'));
$whatsapp = phone_digits((string) $settings->get('whatsapp_no'), false);
$social   = array(
	'facebook_link'  => array('facebook', 'Facebook'),
	'instagram_link' => array('instagram', 'Instagram'),
	'linkedin_link'  => array('linkedin', 'LinkedIn'),
	'twitter_link'   => array('twitter-x', 'X'),
	'youtube_link'   => array('youtube', 'YouTube'),
);
$company = array(
	'About Us'       => site_url('aboutus'),
	'Vision'         => site_url('aboutus').'#mission-vision',
	'Mission'        => site_url('aboutus').'#mission-vision',
	'Corporate Orders' => site_url('industries'),
	'News &amp; Events' => site_url('news-and-events'),
	'Images Gallery' => site_url('images-gallery'),
	'Video Gallery'  => site_url('videos-gallery'),
	'Certificates'   => site_url('certificates'),
	'Brochure'       => site_url('brochure'),
	'Teams'          => site_url('our-team'),
	'Partners'       => site_url('partners'),
);
$help = array(
	'My Account'  => site_url('dashboard'),
	'Track Order' => site_url('track-order'),
	'Returns'     => site_url('returns'),
	'Refunds'     => site_url('refunds'),
	'FAQ'         => site_url('faq'),
	'Testimonials' => site_url('testimonials'),
);
?>
<footer class="kt-footer position-relative overflow-hidden bg-light border-top">
<div class="kt-foot-ring position-absolute rounded-circle pe-none"></div>
<div class="kt-foot-disc position-absolute rounded-circle bg-primary-subtle pe-none"></div>
<div class="container-xxl kt-container kt-foot-pad position-relative">
<div class="row kt-g-foot">
  <div class="col-12 col-xl kt-grow-16">
    <a href="<?php echo site_url(); ?>" class="kt-foot-logo d-block"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo theme_url(); ?>images/customcart-footer-logo.png" alt="CustomCart logo" class="d-block w-100 h-100 object-fit-contain"></a>
    <?php if ($about !== ''): ?><p class="kt-foot-about kt-text-2"><?php echo html_escape($about); ?></p><?php endif; ?>
    <div class="d-flex gap-2">
<?php foreach ($social as $key => list($icon, $label)): $link = trim((string) $settings->get($key)); if ($link === '') { continue; } ?>
      <a href="<?php echo html_escape($link); ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="<?php echo $label; ?>"><i class="bi bi-<?php echo $icon; ?>"></i></a>
<?php endforeach; ?>
    </div>
  </div>
<div class="col-12 col-xl">
<div class="kt-mini-title kt-foot-title d-flex align-items-center gap-2 fw-bold text-dark">PRODUCTS</div>
<ul class="kt-foot-links list-unstyled d-flex flex-column mb-0">
<?php foreach (array_slice(site_mega_menu(), 0, 5) as $row): ?>
<li><a href="<?php echo html_escape($row['url']); ?>"><?php echo html_escape($row['category_name']); ?></a></li>
<?php endforeach; ?>
<li><a href="<?php echo site_url('products'); ?>">View All</a></li>
</ul>
</div>
<div class="col-6 col-md-4 col-xl">
<div class="kt-mini-title kt-foot-title d-flex align-items-center gap-2 fw-bold text-dark">COMPANY</div>
<ul class="kt-foot-links list-unstyled d-flex flex-column mb-0">
<?php foreach ($company as $label => $url): ?>
<li><a href="<?php echo $url; ?>"><?php echo $label; ?></a></li>
<?php endforeach; ?>
</ul>
</div>
<div class="col-6 col-md-4 col-xl">
<div class="kt-mini-title kt-foot-title d-flex align-items-center gap-2 fw-bold text-dark">HELP</div>
<ul class="kt-foot-links list-unstyled d-flex flex-column mb-0">
<?php foreach ($help as $label => $url): ?>
<li><a href="<?php echo $url; ?>"><?php echo $label; ?></a></li>
<?php endforeach; ?>
<li><a href="<?php echo site_url('refer-a-friend'); ?>" data-kt-refer>Refer a Friend</a></li>
<li><a href="#" data-bs-toggle="modal" data-bs-target="#ktInquiry">Send Inquiry</a></li>
</ul>
</div>
<div class="col-12 col-md-4 col-xl">
<div class="kt-mini-title kt-foot-title d-flex align-items-center gap-2 fw-bold text-dark">CONTACT</div>
<div class="kt-foot-contact d-flex flex-column kt-gap-14">
<?php if ($address !== ''): ?><span class="d-flex kt-gap-10"><i class="bi bi-geo-alt text-primary"></i><span><?php echo nl2br(html_escape(str_replace('\n', "\n", $address))); ?></span></span><?php endif; ?>
<?php if ($phone !== ''): ?><a href="tel:<?php echo html_escape(phone_digits($phone)); ?>" class="d-flex kt-gap-10"><i class="bi bi-telephone text-primary"></i><?php echo html_escape($phone); ?></a><?php endif; ?>
<?php if ($email !== ''): ?><a href="mailto:<?php echo html_escape($email); ?>" class="d-flex kt-gap-10"><i class="bi bi-envelope text-primary"></i><?php echo html_escape($email); ?></a><?php endif; ?>
</div>
</div>
</div>
</div>
<div class="kt-foot-bottom position-relative">
  <div class="container-xxl kt-container kt-foot-bottom-in d-flex flex-wrap justify-content-between align-items-center gap-3 text-body-secondary">
    <div class="kt-foot-legal d-flex flex-wrap kt-gap-20"><a href="<?php echo site_url('privacy-policy'); ?>">Privacy Policy</a><a href="<?php echo site_url('terms-conditions'); ?>">Terms &amp; Conditions</a><a href="<?php echo site_url('sitemap'); ?>">Sitemap</a></div>
    <div class="d-flex flex-wrap gap-3"><span><i class="bi bi-credit-card-2-front text-primary"></i> Secure Payment</span><span><i class="bi bi-shield-lock text-primary"></i> SSL Security</span></div>
  </div>
</div>
<div class="kt-foot-bottom position-relative">
  <div class="container-xxl kt-container kt-foot-bottom-in d-flex flex-wrap justify-content-between align-items-center gap-3 text-body-secondary">
    <span><?php echo html_escape($copyright ?? ('© '.date('Y').' CustomCart. All Rights Reserved')); ?></span>
    <div class="kt-foot-credit d-flex align-items-center gap-2"><span>Developed and Managed by <a href="https://www.weblinkindia.net" target="_blank" rel="noopener">WeblinkIndia.NET</a></span><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo theme_url(); ?>images/weblinkindia.svg" alt="WeblinkIndia.NET"></div>
  </div>
</div>
</footer>

<div class="kt-mbar position-fixed bg-white border rounded-pill d-grid d-md-none align-items-center">
  <a href="<?php echo site_url(); ?>" class="kt-mbar-item d-flex flex-column align-items-center fw-bold text-primary"><i class="bi bi-house-door-fill"></i>Home</a>
  <button type="button" class="kt-mbar-item border-0 bg-transparent p-0 d-flex flex-column align-items-center fw-bold text-dark" data-bs-toggle="offcanvas" data-bs-target="#ktDrawer" aria-controls="ktDrawer"><i class="bi bi-grid"></i>Shop</button>
  <button type="button" class="kt-mbar-item border-0 bg-transparent p-0 d-flex flex-column align-items-center fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#ktInquiry"><i class="bi bi-chat-dots"></i>Inquiry</button>
  <a href="<?php echo site_url('cart'); ?>" class="kt-mbar-cart justify-self-center rounded-circle bg-dark text-white d-flex flex-column align-items-center justify-content-center fw-bold lh-1" aria-label="Cart"><i class="bi bi-bag"></i><span class="js-cart-count"><?php echo cart_count(); ?></span></a>
</div>
<div class="kt-float position-fixed d-flex flex-column align-items-center kt-gap-10">
  <button type="button" class="btn btn-kt-prev kt-to-top rounded-circle align-items-center justify-content-center" id="ktToTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>
<?php if ($whatsapp !== ''): ?>
  <a href="https://wa.me/<?php echo html_escape($whatsapp); ?>" target="_blank" rel="noopener" class="kt-whatsapp rounded-circle text-white d-flex align-items-center justify-content-center" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
<?php endif; ?>
</div>
