<?php
/*
| Homepage (CustomCart storefront), see Modules\Home\Controllers\Home.
| $slides [image, small, eyebrow, title, accent, text, url, external] (Manage Header Images; none: one default slide)
| $categories category rows + image_url; $featured / $new product_cards(); $partners [name, logo];
| $testimonials [name, role, text, photo]; $faqs [faq_id, faq_question, faq_answer];
| $newsletter NULL or [error, message, email?]
| Sections: hero, store promises, categories, featured, promotion, new arrivals,
| why choose us, testimonials, partners, FAQ, newsletter.
*/
$img = theme_url().'images/';
echo view('top_application');
if ( ! $slides)
{
	$slides = array(array(
		'image' => $img.'why-choose-us.jpg', 'small' => '',
		'eyebrow' => 'YOUR STORE, YOUR WAY', 'title' => 'Everyday Essentials', 'accent' => 'Made to Last',
		'text' => 'Premium clothing and accessories, delivered across Saudi Arabia.',
		'url' => '', 'external' => FALSE,
	));
}
$cards = function ($cards) {
	$out = '';
	foreach ($cards as $card)
	{
		$out .= '<div class="col">'.view('Modules\Products\Views\partials\product_card', array('card' => $card, 'card_class' => 'h-100')).'</div>';
	}
	return $out;
};
$promises = array(
	array('truck', 'Free Delivery', 'On orders over SAR 250'),
	array('arrow-repeat', 'Easy Returns', '14-day return policy'),
	array('shield-lock', 'Secure Payments', 'Encrypted card checkout'),
	array('headset', 'Customer Care', 'Real people, fast replies'),
);
?>
<section class="cc-hero" data-screen-label="Hero">
<div id="ktHero" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6500" data-bs-pause="hover">
<div class="carousel-inner">
<?php foreach ($slides as $i => $slide): $tag = $i === 0 ? 'h1' : 'h2'; $href = $slide['url'] !== '' ? $slide['url'] : store_url(); ?>
<div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
<div class="container-xxl kt-container">
<div class="row align-items-center cc-hero-row">
<div class="col-12 col-lg-6 order-2 order-lg-1">
<?php if ($slide['eyebrow'] !== ''): ?><span class="cc-pill"><?php echo html_escape(mb_strtoupper($slide['eyebrow'])); ?></span><?php endif; ?>
<<?php echo $tag; ?> class="cc-hero-title"><?php echo html_escape($slide['title']); ?><?php if ($slide['accent'] !== ''): ?> <span><?php echo html_escape($slide['accent']); ?></span><?php endif; ?></<?php echo $tag; ?>>
<?php if ($slide['text'] !== ''): ?><p class="cc-hero-text"><?php echo html_escape($slide['text']); ?></p><?php endif; ?>
<div class="d-flex flex-wrap gap-3">
<a href="<?php echo html_escape($href); ?>" class="btn cc-btn cc-btn-primary"<?php echo $slide['external'] ? ' target="_blank" rel="noopener"' : ''; ?>>Shop Now <i class="bi bi-arrow-right"></i></a>
<a href="<?php echo store_url(); ?>" class="btn cc-btn cc-btn-ghost">View All Products</a>
</div>
</div>
<div class="col-12 col-lg-6 order-1 order-lg-2">
<div class="cc-hero-media">
<img<?php echo $i ? ' loading="lazy" decoding="async"' : ''; ?> src="<?php echo html_escape($slide['image']); ?>" alt="<?php echo html_escape(trim($slide['title'].' '.$slide['accent'])); ?>">
<span class="cc-hero-chip"><i class="bi bi-stars"></i> New Season</span>
</div>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php if (count($slides) > 1): ?>
<div class="container-xxl kt-container">
<div class="carousel-indicators cc-hero-dots">
<?php foreach ($slides as $i => $slide): ?>
<button type="button" data-bs-target="#ktHero" data-bs-slide-to="<?php echo $i; ?>"<?php echo $i === 0 ? ' class="active" aria-current="true"' : ''; ?> aria-label="Slide <?php echo $i + 1; ?>"></button>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
</div>
</section>

<section class="cc-promises" aria-label="Why shop with us">
<div class="container-xxl kt-container">
<div class="row row-cols-2 row-cols-lg-4 g-3">
<?php foreach ($promises as list($icon, $title, $text)): ?>
<div class="col"><div class="cc-promise"><span class="cc-promise-ico"><i class="bi bi-<?php echo $icon; ?>"></i></span><div><strong><?php echo $title; ?></strong><span><?php echo $text; ?></span></div></div></div>
<?php endforeach; ?>
</div>
</div>
</section>

<?php if ($categories): ?>
<section id="categories" class="cc-sec" data-reveal>
<div class="container-xxl kt-container">
<div class="cc-head">
<div><span class="cc-kicker">Collections</span><h2 class="cc-h2">Shop by Category</h2></div>
<a href="<?php echo store_url(); ?>" class="cc-link">All products <i class="bi bi-arrow-right"></i></a>
</div>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
<?php foreach ($categories as $row): $curl = category_url($row['friendly_url']); ?>
<div class="col">
<a href="<?php echo html_escape($curl); ?>" class="cc-cat">
<span class="cc-cat-img"><img loading="lazy" decoding="async" src="<?php echo html_escape($row['image_url']); ?>" alt="<?php echo html_escape($row['category_alt'] ?: $row['category_name']); ?>"></span>
<span class="cc-cat-body"><span class="cc-cat-name"><?php echo html_escape($row['category_name']); ?></span><span class="cc-cat-go">Shop now <i class="bi bi-arrow-right"></i></span></span>
</a>
</div>
<?php endforeach; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<section id="featured" class="cc-sec cc-sec-soft" data-reveal>
<div class="container-xxl kt-container">
<div class="cc-head">
<div><span class="cc-kicker">Hand-picked</span><h2 class="cc-h2">Featured Products</h2></div>
<a href="<?php echo store_url(); ?>" class="cc-link">Shop all <i class="bi bi-arrow-right"></i></a>
</div>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 cc-grid">
<?php echo $cards($featured); ?>
</div>
</div>
</section>
<?php endif; ?>

<section class="cc-sec pb-0" data-reveal>
<div class="container-xxl kt-container">
<div class="cc-promo">
<div class="cc-promo-copy">
<span class="cc-pill cc-pill-dark">LIMITED OFFER</span>
<h2 class="cc-promo-title">10% off your first order</h2>
<p>Use code <strong>WELCOME10</strong> at checkout on orders over SAR 200. Free delivery on orders over SAR 250.</p>
<a href="<?php echo store_url(); ?>" class="btn cc-btn cc-btn-accent">Start Shopping <i class="bi bi-arrow-right"></i></a>
</div>
<img loading="lazy" decoding="async" src="<?php echo $img; ?>promo-banner.jpg" alt="Denim jacket and cotton T-shirt" class="cc-promo-img">
</div>
</div>
</section>

<?php if ($new): ?>
<section class="cc-sec" data-reveal>
<div class="container-xxl kt-container">
<div class="cc-head">
<div><span class="cc-kicker">Just landed</span><h2 class="cc-h2">New Arrivals</h2></div>
<a href="<?php echo store_url(); ?>" class="cc-link">See what&rsquo;s new <i class="bi bi-arrow-right"></i></a>
</div>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 cc-grid">
<?php echo $cards($new); ?>
</div>
</div>
</section>
<?php endif; ?>

<section class="cc-sec cc-sec-soft" data-reveal>
<div class="container-xxl kt-container">
<div class="row align-items-center g-5">
<div class="col-12 col-lg-6"><img loading="lazy" decoding="async" src="<?php echo $img; ?>why-choose-us.jpg" alt="CustomCart gift box with a shirt and a leather wallet" class="cc-why-img"></div>
<div class="col-12 col-lg-6">
<span class="cc-kicker">Why CustomCart</span>
<h2 class="cc-h2 mb-3">Quality you can feel, service you can count on</h2>
<p class="cc-lead">We choose every fabric and fit ourselves, keep prices honest and deliver across Saudi Arabia.</p>
<div class="row row-cols-1 row-cols-sm-2 g-3 mt-2">
<?php foreach (array(array('patch-check', 'Premium fabrics', 'Combed cotton, organic denim and full-grain leather.'), array('rulers', 'Fits that work', 'Clear size guides on every product page.'), array('box-seam', 'Gift-ready packaging', 'Every order arrives beautifully packed.'), array('chat-dots', 'Here to help', 'Message us any time; we reply fast.')) as list($icon, $title, $text)): ?>
<div class="col"><div class="cc-feature"><i class="bi bi-<?php echo $icon; ?>"></i><strong><?php echo $title; ?></strong><span><?php echo $text; ?></span></div></div>
<?php endforeach; ?>
</div>
</div>
</div>
</div>
</section>

<?php if ($testimonials): ?>
<section class="cc-sec" data-reveal>
<div class="container-xxl kt-container">
<div class="cc-head">
<div><span class="cc-kicker">Reviews</span><h2 class="cc-h2">What Our Customers Say</h2></div>
<a href="<?php echo site_url('testimonials'); ?>" class="cc-link">All reviews <i class="bi bi-arrow-right"></i></a>
</div>
<div class="row row-cols-1 row-cols-md-3 g-4">
<?php foreach ($testimonials as $t): $initials = mb_strtoupper(mb_substr($t['name'], 0, 1)); ?>
<div class="col">
<figure class="cc-quote">
<div class="cc-stars" aria-label="5 out of 5"><?php echo str_repeat('<i class="bi bi-star-fill"></i>', 5); ?></div>
<blockquote><?php echo html_escape($t['text']); ?></blockquote>
<figcaption>
<?php if ($t['photo'] !== ''): ?><img loading="lazy" decoding="async" src="<?php echo html_escape($t['photo']); ?>" alt=""><?php else: ?><span class="cc-avatar"><?php echo html_escape($initials); ?></span><?php endif; ?>
<span><strong><?php echo html_escape($t['name']); ?></strong><?php if ($t['role'] !== ''): ?><small><?php echo html_escape($t['role']); ?></small><?php endif; ?></span>
</figcaption>
</figure>
</div>
<?php endforeach; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php if ($partners): ?>
<section class="cc-partners" aria-label="Our partners">
<div class="container-xxl kt-container">
<p class="cc-partners-label">Trusted partners</p>
<div class="cc-partner-row">
<?php foreach ($partners as $partner): ?>
<img loading="lazy" decoding="async" src="<?php echo html_escape($partner['logo']); ?>" alt="<?php echo html_escape($partner['name']); ?>">
<?php endforeach; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php if ($faqs): ?>
<section id="faq" class="cc-sec" data-reveal>
<div class="container-xxl kt-container">
<div class="row g-5">
<div class="col-12 col-lg-4">
<span class="cc-kicker">Help centre</span>
<h2 class="cc-h2 mb-3">Frequently Asked Questions</h2>
<p class="cc-lead">Can&rsquo;t find your answer? Our team is happy to help.</p>
<a href="<?php echo site_url('faq'); ?>" class="btn cc-btn cc-btn-ghost">View all FAQs</a>
</div>
<div class="col-12 col-lg-8">
<div class="accordion cc-faq" id="ktHomeFaq">
<?php foreach ($faqs as $i => $faq): $fid = 'ktHomeFaq'.($i + 1); ?>
<div class="accordion-item">
<h3 class="accordion-header"><button type="button" class="accordion-button<?php echo $i ? ' collapsed' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#<?php echo $fid; ?>" aria-expanded="<?php echo $i ? 'false' : 'true'; ?>" aria-controls="<?php echo $fid; ?>"><?php echo html_escape($faq['faq_question']); ?></button></h3>
<div id="<?php echo $fid; ?>" class="accordion-collapse collapse<?php echo $i ? '' : ' show'; ?>" data-bs-parent="#ktHomeFaq"><div class="accordion-body"><?php echo nl2br(html_escape($faq['faq_answer'])); ?></div></div>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
</div>
</section>
<?php endif; ?>

<section class="cc-sec pt-0" id="newsletter">
<div class="container-xxl kt-container">
<div class="cc-news">
<div>
<h2 class="cc-news-title">Join the CustomCart list</h2>
<p class="mb-0">New arrivals, members-only offers and style notes. No spam, unsubscribe any time.</p>
</div>
<div class="cc-news-form">
<?php if ($newsletter && empty($newsletter['error'])): ?>
<p class="cc-news-done mb-0" role="status"><i class="bi bi-check-circle-fill"></i> <?php echo html_escape($newsletter['message']); ?></p>
<?php else: ?>
<?php echo form_open(site_url(), array('class' => 'd-flex gap-2', 'id' => 'ktNewsletter')); ?>
<input type="email" name="email" required maxlength="80" class="form-control" placeholder="Your email address" aria-label="Your email address" value="<?php echo html_escape($newsletter['email'] ?? ''); ?>"<?php echo $newsletter ? ' aria-describedby="ktNlError" aria-invalid="true"' : ''; ?>>
<input type="text" name="website" value="" class="kt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
<button type="submit" class="btn cc-btn cc-btn-accent flex-shrink-0">Subscribe</button>
<?php echo form_close(); ?>
<?php if ($newsletter): ?><p class="kt-field-error mb-0" id="ktNlError" role="alert"><?php echo html_escape($newsletter['message']); ?></p><?php endif; ?>
<?php endif; ?>
</div>
</div>
</div>
</section>
<?php echo view('bottom_application'); ?>
