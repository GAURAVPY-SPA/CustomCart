<?php
/*
| Contact Us (layout contact_us) — html/contact-us.htm; after a message is
| sent (?sent=1) the design's thank-you (html/thankyou.htm) replaces the form.
| $content = the page's Description (text of the contact panel), $contact =
| Admin Settings details (Pages::contact_details()), $contact_values /
| $contact_errors / $contact_error / $contact_sent / $contact_url, $captcha.
*/
echo view('top_application');
$c   = $contact;
$fv  = function ($field) use ($contact_values) { return html_escape($contact_values[$field]); };
$err = function ($field) use ($contact_errors) { return isset($contact_errors[$field]) ? '<div class="kt-field-error">'.html_escape($contact_errors[$field]).'</div>' : ''; };
$inv = function ($field) use ($contact_errors) { return isset($contact_errors[$field]) ? ' is-invalid' : ''; };
$hours = trim((string) $c['office_hours']);
?>
<?php echo view('inner_hero', array('hero' => array('title' => $contact_sent ? 'Thank You' : $page['page_name'], 'banner' => $contact_sent ? array('Thank You', $page['page_name']) : $banner, 'crumbs' => $contact_sent ? array($page['page_name'] => site_url($contact_url), 'Thank You' => NULL) : array($page['page_name'] => NULL)))); ?>
<?php if ($contact_sent): ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
<div class="kt-ty mx-auto text-center bg-white border position-relative overflow-hidden">
<div class="kt-ty-ring position-absolute rounded-circle pe-none"></div>
<div class="kt-ty-ring kt-ty-ring2 position-absolute rounded-circle pe-none"></div>
<div class="position-relative">
<div class="kt-ty-check mx-auto position-relative d-flex align-items-center justify-content-center">
<span class="kt-ty-pulse position-absolute rounded-circle pe-none"></span>
<span class="kt-ty-ico rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-check-lg"></i></span>
</div>
<div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary mb-3">MESSAGE SENT</div>
<h2 class="kt-ty-title fw-bold text-dark">Thank You for Contacting Us!</h2>
<p class="kt-ty-text kt-text-2 mx-auto">Your message has been received. Our customer care team will get back to you within one working day.</p>
<div class="kt-ty-actions d-flex flex-wrap justify-content-center">
<a href="<?php echo site_url(); ?>" class="btn btn-kt-outline kt-ty-home rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-house-door"></i> BACK TO HOME</a>
</div>
<?php if ($c['phone'] !== '' || $c['email'] !== ''): ?>
<div class="kt-ty-help d-flex flex-wrap align-items-center justify-content-center">
<span class="text-body-secondary">Need urgent help?</span>
<?php if ($c['phone_href'] !== ''): ?><a href="<?php echo html_escape($c['phone_href']); ?>" class="fw-bold"><i class="bi bi-telephone"></i> <?php echo html_escape($c['phone']); ?></a><?php endif; ?>
<?php if ($c['email'] !== ''): ?><a href="mailto:<?php echo html_escape($c['email']); ?>" class="fw-bold"><i class="bi bi-envelope"></i> <?php echo html_escape($c['email']); ?></a><?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
</section>
<?php else: ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
<div class="kt-ct-box row g-0 overflow-hidden">
<div class="col-12 col-lg-5">
<div class="kt-ct-panel position-relative overflow-hidden h-100 text-white d-flex flex-column">
<div class="kt-ct-panel-ring position-absolute rounded-circle pe-none"></div>
<div class="kt-ct-panel-ring kt-ct-panel-ring2 position-absolute rounded-circle pe-none"></div>
<div class="position-relative">
<div class="kt-ct-kicker fw-bold">CONTACT INFORMATION</div>
<h3 class="kt-ct-panel-title fw-bold text-white">Get in Touch With Us</h3>
<?php if (trim(strip_tags($content)) !== ''): ?><div class="kt-ct-panel-text mb-0"><?php echo $content; ?></div><?php endif; ?>
</div>
<ul class="kt-ct-list list-unstyled position-relative d-flex flex-column mb-0">
<?php if ($c['address'] !== ''): ?>
<li class="d-flex"><span class="kt-ct-li-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-geo-alt"></i></span><span><span class="kt-ct-li-label d-block">Address</span><?php echo nl2br(html_escape($c['address'])); ?></span></li>
<?php endif; ?>
<?php if ($c['phone_href'] !== ''): ?>
<li class="d-flex"><span class="kt-ct-li-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-telephone"></i></span><span><span class="kt-ct-li-label d-block">Call Us On</span><a href="<?php echo html_escape($c['phone_href']); ?>"><?php echo html_escape($c['phone']); ?></a></span></li>
<?php endif; ?>
<?php if ($c['email'] !== ''): ?>
<li class="d-flex"><span class="kt-ct-li-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-envelope"></i></span><span><span class="kt-ct-li-label d-block">Email Us</span><a href="mailto:<?php echo html_escape($c['email']); ?>"><?php echo html_escape($c['email']); ?></a></span></li>
<?php endif; ?>
<?php if ($hours !== ''): ?>
<li class="d-flex"><span class="kt-ct-li-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-clock"></i></span><span><span class="kt-ct-li-label d-block">Business Hours</span><?php echo nl2br(html_escape(str_replace(', ', "\n", $hours))); ?></span></li>
<?php endif; ?>
<li class="d-flex"><span class="kt-ct-li-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-reply"></i></span><span><span class="kt-ct-li-label d-block">Response Time</span>Within one working day</span></li>
</ul>
<?php if ($c['social']): ?>
<div class="kt-ct-follow position-relative mt-auto d-flex align-items-center justify-content-between flex-wrap gap-3">
<span class="kt-ct-li-label">Follow us</span>
<div class="d-flex gap-2">
<?php foreach ($c['social'] as $icon => $url): ?>
<a href="<?php echo html_escape($url); ?>" target="_blank" rel="noopener" class="kt-ct-soc rounded-circle d-flex align-items-center justify-content-center" aria-label="<?php echo html_escape(ucfirst(str_replace('twitter-x', 'X', $icon))); ?>"><i class="bi bi-<?php echo $icon; ?>"></i></a>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
</div>
</div>

<div class="col-12 col-lg-7">
<div class="kt-ct-form bg-white h-100" id="contact-form">
<h3 class="kt-inq-title fw-bold text-dark mb-2">Send Us a Message</h3>
<p class="kt-ct-intro text-body-secondary">Have a question about an order? Add your order number so we can help faster.</p>
<?php if ($contact_error !== ''): ?>
<div class="kt-alert kt-alert-error" role="alert"><?php echo html_escape($contact_error); ?></div>
<?php elseif ($contact_errors): ?>
<div class="kt-alert kt-alert-error" role="alert">Please check the highlighted fields.</div>
<?php endif; ?>
<?php echo form_open(site_url($contact_url).'#contact-form', array('class' => 'row kt-g-ctf kt-validate', 'novalidate' => 'novalidate')); /* checked like the register form (customcart.js form.kt-validate) */ ?>
<div class="col-12 col-md-6"><label for="ktCtName" class="kt-ct-label fw-bold">Full Name <span class="text-danger">*</span></label><input type="text" id="ktCtName" name="name" required maxlength="61" placeholder="John Smith" class="form-control kt-input rounded-pill<?php echo $inv('name'); ?>" value="<?php echo $fv('name'); ?>"><?php echo $err('name'); ?></div>
<div class="col-12 col-md-6"><label for="ktCtEmail" class="kt-ct-label fw-bold">Email <span class="text-danger">*</span></label><input type="email" id="ktCtEmail" name="email" required maxlength="80" placeholder="you@example.com" class="form-control kt-input rounded-pill<?php echo $inv('email'); ?>" value="<?php echo $fv('email'); ?>"><?php echo $err('email'); ?></div>
<div class="col-12 col-md-6"><label for="ktCtPhone" class="kt-ct-label fw-bold">Phone <span class="text-danger">*</span></label><input type="tel" id="ktCtPhone" name="phone" required maxlength="20" placeholder="+966 5X XXX XXXX" class="form-control kt-input rounded-pill<?php echo $inv('phone'); ?>" value="<?php echo $fv('phone'); ?>"><?php echo $err('phone'); ?></div>
<div class="col-12 col-md-6"><label for="ktCtsubject" class="kt-ct-label fw-bold">Subject <span class="kt-ct-opt">(optional)</span></label><input type="text" class="form-control kt-input rounded-pill<?php echo $inv('subject'); ?>" id="ktCtsubject" name="subject" maxlength="255" placeholder="How can we help?" value="<?php echo $fv('subject'); ?>"><?php echo $err('subject'); ?></div>
<div class="col-12"><label for="ktCtMsg" class="kt-ct-label fw-bold">Comment <span class="text-danger">*</span></label><textarea id="ktCtMsg" rows="3" name="message" required maxlength="5000" placeholder="How can we help you?" class="form-control kt-textarea<?php echo $inv('message'); ?>"><?php echo $fv('message'); ?></textarea><?php echo $err('message'); ?></div>
<input type="text" name="fax_number" value="" class="kt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
<?php echo view('includes/captcha', array('captcha' => $captcha)); ?>
<div class="col-12">
<div class="form-check kt-side-check mb-0"><input class="form-check-input" type="checkbox" id="ktCtAgree" required><label class="form-check-label kt-ct-agree" for="ktCtAgree">I agree to the <a href="<?php echo site_url('privacy-policy'); ?>">Privacy Policy</a> and <a href="<?php echo site_url('terms-conditions'); ?>">Terms &amp; Conditions</a>.</label></div>
</div>
<div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
<button type="submit" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">Submit <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-send"></i></span></button>
<span class="kt-ct-safe text-body-secondary d-flex align-items-center gap-2"><i class="bi bi-shield-lock text-primary"></i> Your details are kept confidential.</span>
</div>
<?php echo form_close(); ?>
</div>
</div>
</div>
</section>
<?php endif; ?>

<?php if ($c['map_src'] !== ''): ?>
<section class="container-xxl kt-container kt-sec pt-0" data-reveal>
<div class="kt-ct-mapbox position-relative rounded-5 overflow-hidden border">
<iframe src="<?php echo html_escape($c['map_src']); ?>" title="CustomCart store location map" class="d-block w-100 h-100 border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
<div class="kt-ct-office bg-white">
<div class="kt-ct-kicker fw-bold text-primary">STORE &amp; WAREHOUSE</div>
<div class="kt-ct-office-name fw-bold text-dark"><?php echo html_escape((string) service('siteSettings')->get('site_name')); ?></div>
<p class="kt-ct-office-text text-body-secondary"><?php echo nl2br(html_escape($c['address'])); ?><?php if ($hours !== ''): ?><br><?php echo html_escape(explode(',', $hours)[0]); ?><?php endif; ?></p>
<?php if ($c['map_url'] !== ''): ?>
<a href="<?php echo html_escape($c['map_url']); ?>" target="_blank" rel="noopener" class="btn btn-kt-dark kt-ct-dir rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-sign-turn-right"></i> GET DIRECTIONS</a>
<?php endif; ?>
</div>
</div>
</section>
<?php endif; ?>
<?php echo view('bottom_application'); ?>
