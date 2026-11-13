<?php
/*
| /sitemap — html/sitemap.htm: the footer's link groups (store categories from
| Manage Categories: site_mega_menu()) with the real addresses; the help
| strip opens Contact Us and the Send Inquiry popup. $banner = banner positions.
*/
echo view('top_application');
$products = array();
foreach (site_mega_menu() as $row)
{
	$products[$row['category_name']] = $row['url'];
}
$products['View All'] = site_url('products');
$groups = array(
	'PRODUCTS' => $products,
	'COMPANY'  => array(
		'About Us'          => site_url('aboutus'),
		'Vision'            => site_url('aboutus').'#mission-vision',
		'Mission'           => site_url('aboutus').'#mission-vision',
		'Industries'        => site_url('industries'),
		'News & Events'     => site_url('news-and-events'),
		'Images Gallery'    => site_url('images-gallery'),
		'Video Gallery'     => site_url('videos-gallery'),
		'Certificates'      => site_url('certificates'),
		'Brochure'          => site_url('brochure'),
		'Teams'             => site_url('our-team'),
		'Partners'          => site_url('partners'),
	),
	'HELP' => array(
		'My Account'         => site_url('dashboard'),
		'Track Order'        => site_url('track-order'),
		'Returns'            => site_url('returns'),
		'Refunds'            => site_url('refunds'),
		'FAQ'                => site_url('faq'),
		'Testimonials'       => site_url('testimonials'),
		'Refer a Friend'     => 'refer',
		'Send Inquiry'       => 'inquiry',
		'Privacy Policy'     => site_url('privacy-policy'),
		'Terms & Conditions' => site_url('terms-conditions'),
	),
);
?>
<?php echo view('inner_hero', array('hero' => array('title' => 'Sitemap', 'banner' => $banner, 'crumbs' => array('Sitemap' => NULL)))); ?>
<section class="container-xxl kt-container kt-pt-sec" data-reveal>
<div class="kt-eyebrow d-flex align-items-center fw-bold text-primary mb-3">SITEMAP</div>
<h2 class="kt-h2 mb-3 fw-bold text-dark text-balance">All CustomCart links, in one view</h2>
<p class="kt-lead kt-text-2 mb-0">Find our products, company information and help pages quickly. Each group links straight to the page you need.</p>
</section>

<section class="container-xxl kt-container kt-sec pt-5">
<div class="kt-sm-feature position-relative overflow-hidden text-white" data-reveal>
<div class="kt-sm-feature-ring position-absolute rounded-circle pe-none"></div>
<div class="row kt-g-doc position-relative">
<?php foreach ($groups as $title => $links): ?>
<div class="col-12 col-md-4">
<div class="kt-sm-box h-100">
<div class="kt-mini-title kt-foot-title d-flex align-items-center gap-2 fw-bold text-warning"><?php echo $title; ?></div>
<ul class="kt-sm-plain list-unstyled d-flex flex-column mb-0">
<?php foreach ($links as $label => $url): ?>
<?php if ($url === 'refer'): ?>
<li><a href="<?php echo site_url('refer-a-friend'); ?>" data-kt-refer><?php echo html_escape($label); ?></a></li>
<?php elseif ($url === 'inquiry'): ?>
<li><a href="#" data-bs-toggle="modal" data-bs-target="#ktInquiry"><?php echo html_escape($label); ?></a></li>
<?php else: ?>
<li><a href="<?php echo html_escape($url); ?>"><?php echo html_escape($label); ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
</ul>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="kt-sm-help bg-light border d-flex flex-column flex-md-row align-items-md-center kt-gap-20" data-reveal>
<span class="kt-ico-48 rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-search"></i></span>
<div class="flex-grow-1">
<h3 class="kt-sm-help-title fw-bold text-dark mb-1">Can't find what you need?</h3>
<p class="kt-text-2 mb-0">Search the catalogue from the header, or ask our team directly.</p>
</div>
<div class="d-flex flex-wrap gap-2">
<a href="<?php echo site_url('contactus'); ?>" class="btn btn-kt-outline kt-btn-56 rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-telephone"></i> CONTACT US</a>
<button type="button" class="btn btn-kt-gold kt-btn-56 rounded-pill d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#ktInquiry"><i class="bi bi-chat-dots"></i> SEND INQUIRY</button>
</div>
</div>
</section>
<?php echo view('bottom_application'); ?>
