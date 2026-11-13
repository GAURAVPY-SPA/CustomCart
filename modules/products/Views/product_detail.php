<?php
/*
| Product page (design: html/detail.htm), see Products::detail().
| $product, $colors / $sizes (id => row), $variants ['list' => "color-size" => price/stock, 'selected'],
| $media, $reviews + $rating (product testimonials), $faqs (FaqModel::get_product_faqs()),
| $material (fabric row or NULL), $related, $in_wishlist, $forms['review'], $free_above.
| Add to Cart: form[data-kt-cart] (customcart.js, AJAX); Buy Now submits it to cart/add with
| buy_now=1 (then checkout). Colour / size changes: js/product.js (?variant=1 JSON).
*/
use Modules\Products\Models\ProductModel;

$id       = (int) $product['products_id'];
$name     = (string) $product['product_name'];
$url      = product_url($product['friendly_url']);
$selected = $variants['selected'] !== NULL ? $variants['list'][$variants['selected']] : NULL;
$prices   = $selected ? array('sale' => $selected['price'], 'regular' => $selected['regular'], 'saving' => $selected['saving']) : ProductModel::prices($product);
$stock    = $selected ? $selected['available'] : max(0, (int) $product['product_quantity']);
$low      = $selected ? $selected['low'] : FALSE;
$status   = $stock <= 0 ? 'Out of Stock' : ($low ? 'Only '.$stock.' left' : 'In Stock');
$max      = $selected ? $selected['max'] : max(1, min($stock, config(\Config\Shop::class)->maxQuantity));
list($sel_color, $sel_size) = $selected ? array($selected['color'], $selected['size']) : array(0, 0);
$images   = $media ? $media : array(array('media' => NULL));
$cat      = $path ? end($path) : NULL;
$review   = $forms['review'];
// Lead: the description's first paragraph (else all of it), tags read as spaces so list items stay apart.
$lead     = (string) $product['products_description'];
$lead     = preg_match('#<p\b[^>]*>(.*?)</p>#is', $lead, $lead_p) ? $lead_p[1] : $lead;
$lead     = trim(preg_replace('/\s+/', ' ', strip_tags(preg_replace('/<[^>]+>/', ' $0', $lead))));
echo view('top_application');
echo view('inner_hero', array('hero' => $hero + array('banner' => 'Online Store')));
?>
<section class="container-xxl kt-container kt-sec" data-reveal>
<div class="row kt-g-pd">

<div class="col-12 col-lg-6">
<div class="carousel slide kt-pd-gallery" id="ktPdCar">
<div class="kt-pd-main position-relative rounded-5 overflow-hidden bg-primary-subtle">
<div class="carousel-inner h-100">
<?php foreach ($images as $i => $m): $big = product_image_url($m['media'], NULL); $big = $big !== '' ? $big : product_image_url($m['media'], 1200, 1200); ?>
<div class="carousel-item h-100<?php echo $i === 0 ? ' active' : ''; ?>"><a href="<?php echo html_escape($big); ?>" class="kt-pd-zoomlink d-block w-100 h-100 position-relative overflow-hidden" data-kt-zoom aria-label="Zoom: <?php echo html_escape($name); ?>"><img<?php echo $i ? ' loading="lazy" fetchpriority="low" decoding="async"' : ''; ?> src="<?php echo html_escape(product_image_url($m['media'], 900, 900)); ?>" alt="<?php echo html_escape($name); ?>" class="kt-pd-img d-block w-100 h-100 object-fit-cover"></a></div>
<?php endforeach; ?>
</div>
<?php if (count($images) > 1): ?>
<button type="button" class="btn btn-kt-prev kt-pd-nav kt-pd-prev position-absolute top-50 translate-middle-y rounded-circle d-flex align-items-center justify-content-center" data-bs-target="#ktPdCar" data-bs-slide="prev" aria-label="Previous image"><i class="bi bi-arrow-left"></i></button>
<button type="button" class="btn btn-kt-next kt-pd-nav kt-pd-next position-absolute top-50 translate-middle-y rounded-circle d-flex align-items-center justify-content-center" data-bs-target="#ktPdCar" data-bs-slide="next" aria-label="Next image"><i class="bi bi-arrow-right"></i></button>
<?php endif; ?>
<button type="button" class="btn kt-pd-zoom position-absolute rounded-circle d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#ktLightbox" data-kt-pdzoom aria-label="Zoom image"><i class="bi bi-zoom-in"></i></button>
</div>
<?php if (count($images) > 1): ?>
<div class="kt-pd-thumbwrap d-flex align-items-center">
<button type="button" class="btn btn-kt-prev kt-pd-tnav rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" data-kt-slide="prev" data-kt-target="#ktPdThumbs" aria-label="Previous thumbnails"><i class="bi bi-chevron-left"></i></button>
<div class="carousel-indicators kt-pd-thumbs" id="ktPdThumbs">
<?php foreach ($images as $i => $m): ?>
<button type="button" data-bs-target="#ktPdCar" data-bs-slide-to="<?php echo $i; ?>" class="rounded-3 overflow-hidden flex-shrink-0<?php echo $i === 0 ? ' active' : ''; ?>"<?php echo $i === 0 ? ' aria-current="true"' : ''; ?> aria-label="Image <?php echo $i + 1; ?>"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape(product_image_url($m['media'], 200, 200)); ?>" alt="" class="w-100 h-100 object-fit-cover"></button>
<?php endforeach; ?>
</div>
<button type="button" class="btn btn-kt-next kt-pd-tnav rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" data-kt-slide="next" data-kt-target="#ktPdThumbs" aria-label="Next thumbnails"><i class="bi bi-chevron-right"></i></button>
</div>
<?php endif; ?>
</div>
</div>

<div class="col-12 col-lg-6">
<div class="kt-pd-info d-flex flex-column" data-product-id="<?php echo $id; ?>" data-name="<?php echo html_escape($name); ?>">
<div class="d-flex align-items-start justify-content-between gap-3">
<div class="kt-prod-cat fw-bold text-primary text-uppercase mb-0"><?php echo $cat ? html_escape($cat['category_name']) : ''; ?></div>
<button type="button" class="btn btn-kt-tool kt-wish rounded-circle d-flex align-items-center justify-content-center flex-shrink-0<?php echo $in_wishlist ? ' is-active' : ''; ?>" data-kt-wish aria-label="Wishlist" aria-pressed="<?php echo $in_wishlist ? 'true' : 'false'; ?>"><i class="bi bi-heart<?php echo $in_wishlist ? '-fill' : ''; ?>"></i></button>
</div>
<h2 class="kt-pd-name fw-bold text-dark mb-0"><?php echo html_escape($name); ?></h2>
<div class="kt-pd-meta d-flex flex-wrap align-items-center text-body-secondary">
<span>SKU: <b class="text-body"><?php echo html_escape($product['product_code']); ?></b></span>
<?php if ($material): ?><span>Fabric: <b class="text-body"><?php echo html_escape($material['material_name']); ?></b></span><?php endif; ?>
<span class="kt-qv-stock fw-bold<?php echo $stock <= 0 ? ' text-danger' : ($low ? ' is-low' : ''); ?>" id="ktPdStock"><i class="bi bi-<?php echo $stock <= 0 ? 'x-circle' : ($low ? 'exclamation-circle' : 'check-circle'); ?>-fill"></i> <span><?php echo html_escape($status); ?></span></span>
</div>
<div class="kt-pd-price d-flex flex-wrap align-items-baseline kt-gap-12"><span class="kt-qv-now fw-bold text-dark" id="ktPdPrice"><?php echo display_price($prices['sale']); ?></span><span class="kt-qv-old text-decoration-line-through text-body-tertiary" id="ktPdOld"<?php echo $prices['saving'] > 0 ? '' : ' hidden'; ?>><?php echo display_price($prices['regular']); ?></span></div>
<?php if ($lead !== ''): ?><p class="kt-pd-lead text-body-secondary mb-0"><?php echo html_escape(character_limiter($lead, 320, '…')); ?></p><?php endif; ?>

<?php echo form_open('cart/add', array('id' => 'ktPdForm', 'data-kt-cart' => '', 'data-variant-url' => $url.'?variant=1')); ?>
<input type="hidden" name="product_id" value="<?php echo $id; ?>">
<div class="kt-pd-opts d-flex flex-column">
<?php if ($colors): ?>
<div>
<div class="kt-qv-label fw-bold mb-2">COLOUR: <span class="text-body-secondary fw-normal" id="ktPdColorName"><?php echo html_escape(isset($colors[$sel_color]) ? $colors[$sel_color]['color_name'] : ''); ?></span></div>
<div class="d-flex flex-wrap kt-gap-10">
<?php foreach ($colors as $cid => $color): $ccode = (string) $color['color_code']; ?>
<input type="radio" class="btn-check" name="color_id" value="<?php echo (int) $cid; ?>" id="ktPdC<?php echo (int) $cid; ?>" data-name="<?php echo html_escape($color['color_name']); ?>"<?php echo (int) $cid === $sel_color ? ' checked' : ''; ?>><label class="kt-pd-color rounded-circle" for="ktPdC<?php echo (int) $cid; ?>" title="<?php echo html_escape($color['color_name']); ?>" style="background:<?php echo html_escape(preg_match('/^#[0-9a-f]{3,6}$/i', $ccode) ? $ccode : '#ccc'); ?>"></label>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
<div class="d-flex flex-wrap align-items-end kt-gap-12">
<?php if ($sizes): ?>
<label class="kt-qv-fabric mb-0" for="ktPdSize"><span class="kt-qv-label d-block fw-bold mb-2">SIZE</span>
<select class="form-select kt-select rounded-pill" id="ktPdSize" name="size_id">
<?php foreach ($sizes as $sid => $size): ?>
<option value="<?php echo (int) $sid; ?>"<?php echo (int) $sid === $sel_size ? ' selected' : ''; ?>><?php echo html_escape($size['size_name']); ?></option>
<?php endforeach; ?>
</select>
</label>
<?php endif; ?>
<label class="mb-0" for="ktPdQty"><span class="kt-qv-label d-block fw-bold mb-2">QTY</span><input type="number" class="form-control kt-input kt-pd-qty rounded-pill text-center fw-bold" id="ktPdQty" name="qty" value="1" min="1" max="<?php echo (int) $max; ?>"></label>
</div>
</div>

<div class="kt-pd-actions d-grid">
<button type="submit" class="btn btn-kt-dark kt-btn-qv rounded-pill" id="ktPdAdd"<?php echo $stock <= 0 ? ' disabled' : ''; ?>>ADD TO CART</button>
<button type="submit" name="buy_now" value="1" class="btn btn-kt-gold btn-kt-gold-light kt-btn-qv rounded-pill d-flex align-items-center justify-content-center" id="ktPdBuy"<?php echo $stock <= 0 ? ' disabled' : ''; ?>>BUY NOW</button>
<button type="button" class="btn btn-kt-soft kt-pd-inq rounded-circle d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#ktInquiry" aria-label="Send inquiry"><i class="bi bi-chat-dots"></i></button>
</div>
<?php echo form_close(); ?>

<div class="kt-pd-trust row row-cols-2 g-2">
<div class="col"><div class="d-flex align-items-center kt-gap-10"><span class="kt-pd-trust-ico rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-truck"></i></span><span><?php echo $free_above > 0 ? 'Free delivery over '.display_price($free_above, 0) : 'Delivery across Saudi Arabia'; ?></span></div></div>
<div class="col"><div class="d-flex align-items-center kt-gap-10"><span class="kt-pd-trust-ico rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-repeat"></i></span><span>Easy returns</span></div></div>
<div class="col"><div class="d-flex align-items-center kt-gap-10"><span class="kt-pd-trust-ico rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-shield-check"></i></span><span>Secure payment</span></div></div>
<div class="col"><div class="d-flex align-items-center kt-gap-10"><span class="kt-pd-trust-ico rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-boxes"></i></span><span>Bulk &amp; custom orders</span></div></div>
</div>

<div class="kt-pd-share d-flex flex-wrap align-items-center justify-content-between gap-3">
<div class="d-flex align-items-center gap-2">
<span class="kt-qv-label fw-bold text-dark me-1">SHARE</span>
<?php $su = rawurlencode($url); $st = rawurlencode($name.' | CustomCart'); ?>
<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $su; ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="Share on Facebook" data-kt-page-share="facebook"><i class="bi bi-facebook"></i></a>
<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $su; ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="Share on LinkedIn" data-kt-page-share="linkedin"><i class="bi bi-linkedin"></i></a>
<a href="https://twitter.com/intent/tweet?text=<?php echo $st; ?>&amp;url=<?php echo $su; ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="Share on X" data-kt-page-share="x"><i class="bi bi-twitter-x"></i></a>
<a href="https://wa.me/?text=<?php echo $st.'%20'.$su; ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="Share on WhatsApp" data-kt-page-share="whatsapp"><i class="bi bi-whatsapp"></i></a>
</div>
<a href="<?php echo site_url('refer-a-friend').'?product='.$id; ?>" class="btn btn-kt-outline kt-refer-btn rounded-pill d-inline-flex align-items-center gap-2" data-kt-pop="<?php echo site_url('refer-a-friend').'?product='.$id; ?>"><i class="bi bi-people"></i> REFER TO A FRIEND</a>
</div>
</div>
</div>
</div>
</section>

<section class="container-xxl kt-container kt-sec pt-0" id="ktPdTabs" data-reveal>
<div class="kt-pd-tabbox border">
<ul class="nav nav-pills kt-pd-tabs flex-nowrap overflow-x-auto" role="tablist">
<?php $rv_tab = $review['sent'] || $review['errors']; /* testimonial just sent, or errors to fix: TESTIMONIALS is the active tab */ ?>
<li class="nav-item" role="presentation"><button type="button" class="nav-link<?php echo $rv_tab ? '' : ' active'; ?> rounded-pill fw-bold text-nowrap" data-bs-toggle="pill" data-bs-target="#ktPdDesc" role="tab" aria-controls="ktPdDesc" aria-selected="<?php echo $rv_tab ? 'false' : 'true'; ?>">DESCRIPTION</button></li>
<?php if (trim(strip_tags((string) $product['products_specification'])) !== ''): ?>
<li class="nav-item" role="presentation"><button type="button" class="nav-link rounded-pill fw-bold text-nowrap" data-bs-toggle="pill" data-bs-target="#ktPdSpec" role="tab" aria-controls="ktPdSpec" aria-selected="false">SPECIFICATIONS</button></li>
<?php endif; ?>
<li class="nav-item" role="presentation"><button type="button" class="nav-link rounded-pill fw-bold text-nowrap" data-bs-toggle="pill" data-bs-target="#ktPdShip" role="tab" aria-controls="ktPdShip" aria-selected="false">DELIVERY &amp; RETURNS</button></li>
<?php if ($faqs): ?>
<li class="nav-item" role="presentation"><button type="button" class="nav-link rounded-pill fw-bold text-nowrap" data-bs-toggle="pill" data-bs-target="#ktPdFaq" role="tab" aria-controls="ktPdFaq" aria-selected="false">FAQS</button></li>
<?php endif; ?>
<li class="nav-item" role="presentation"><button type="button" class="nav-link rounded-pill fw-bold text-nowrap<?php echo $rv_tab ? ' active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#ktPdReviews" role="tab" aria-controls="ktPdReviews" aria-selected="<?php echo $rv_tab ? 'true' : 'false'; ?>" id="reviews"<?php echo $rv_tab ? ' data-kt-open-tab' : ''; /* product.js scrolls to it */ ?>>TESTIMONIALS<?php echo $rating['count'] ? ' ('.(int) $rating['count'].')' : ''; ?></button></li>
</ul>
<div class="tab-content kt-pd-tabbody">
<div class="tab-pane fade<?php echo $rv_tab ? '' : ' show active'; ?>" id="ktPdDesc" role="tabpanel" tabindex="0">
<div class="kt-cms"><?php echo (new \App\Libraries\HtmlSanitizer())->clean((string) $product['products_description']); ?></div>
</div>
<?php if (trim(strip_tags((string) $product['products_specification'])) !== ''): ?>
<div class="tab-pane fade" id="ktPdSpec" role="tabpanel" tabindex="0">
<div class="kt-cms kt-pd-table"><?php echo (new \App\Libraries\HtmlSanitizer())->clean((string) $product['products_specification']); ?></div>
</div>
<?php endif; ?>
<div class="tab-pane fade" id="ktPdShip" role="tabpanel" tabindex="0">
<div class="kt-cms">
<p>Orders are dispatched within 2&ndash;3 working days. Delivery across Saudi Arabia takes 3&ndash;5 working days<?php echo $free_above > 0 ? '; free delivery on orders over '.display_price($free_above, 0) : ''; ?>.</p>
<p>See our <a href="<?php echo site_url('returns'); ?>">Returns Policy</a> and <a href="<?php echo site_url('refunds'); ?>">Refund Policy</a> for details.</p>
</div>
</div>
<?php if ($faqs): ?>
<div class="tab-pane fade" id="ktPdFaq" role="tabpanel" tabindex="0">
<div class="accordion kt-faq" id="ktPdFaqAcc">
<?php foreach ($faqs as $i => $faq): $fid = 'ktPdFaq'.($i + 1); ?>
<div class="accordion-item">
<h3 class="accordion-header"><button type="button" class="accordion-button<?php echo $i ? ' collapsed' : ''; ?> fw-bold" data-bs-toggle="collapse" data-bs-target="#<?php echo $fid; ?>" aria-expanded="<?php echo $i ? 'false' : 'true'; ?>" aria-controls="<?php echo $fid; ?>"><?php echo html_escape($faq['faq_question']); ?><span class="kt-faq-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ms-auto"><i class="bi bi-plus"></i><i class="bi bi-dash"></i></span></button></h3>
<div id="<?php echo $fid; ?>" class="accordion-collapse collapse<?php echo $i ? '' : ' show'; ?>" data-bs-parent="#ktPdFaqAcc"><div class="accordion-body"><?php echo nl2br(html_escape($faq['faq_answer'])); ?></div></div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
<div class="tab-pane fade<?php echo $rv_tab ? ' show active' : ''; ?>" id="ktPdReviews" role="tabpanel" tabindex="0">
<?php if ($rating['count']): ?>
<p class="fw-bold text-dark mb-3"><?php for ($s = 1; $s <= 5; $s++): ?><i class="bi bi-star<?php echo $rating['average'] >= $s - 0.25 ? '-fill' : ($rating['average'] >= $s - 0.75 ? '-half' : ''); ?> text-warning"></i><?php endfor; ?> <?php echo number_format((float) $rating['average'], 1); ?> out of 5 &middot; <?php echo (int) $rating['count']; ?> testimonial<?php echo $rating['count'] == 1 ? '' : 's'; ?></p>
<div class="d-flex flex-column kt-gap-14 mb-4">
<?php foreach ($reviews as $row): ?>
<div class="border rounded-4 p-3">
<div class="d-flex flex-wrap justify-content-between gap-2 mb-1"><b class="text-dark"><?php echo html_escape($row['poster_name']); ?></b><span class="text-body-secondary small"><?php echo date('j M Y', strtotime((string) $row['posted_date'])); ?></span></div>
<div class="mb-2"><?php for ($s = 1; $s <= 5; $s++): ?><i class="bi bi-star<?php echo (int) $row['rate'] >= $s ? '-fill' : ''; ?> text-warning"></i><?php endfor; ?></div>
<p class="mb-0 text-body-secondary"><?php echo nl2br(html_escape($row['comment'])); ?></p>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-body-secondary">No testimonials yet. Share your experience with this product.</p>
<?php endif; ?>
<?php if ($review['sent']): ?>
<div class="kt-alert kt-alert-ok" role="status">Thank you. Your testimonial will appear once it is approved.</div>
<?php else: ?>
<?php echo view('Modules\Products\Views\partials\form_errors', array('form_errors' => $review['errors'])); ?>
<?php echo form_open($url.'#reviews', array('class' => 'row kt-g-ctf')); ?>
<input type="hidden" name="action" value="review">
<input type="text" name="fax_number" value="" class="kt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="col-12 col-md-6"><label for="ktRvName" class="kt-ct-label fw-bold">Your Name <span class="text-danger">*</span></label><input type="text" id="ktRvName" name="poster_name" required maxlength="60" class="form-control kt-input rounded-pill" value="<?php echo html_escape($review['values']['poster_name']); ?>"></div>
<div class="col-12 col-md-6"><label for="ktRvEmail" class="kt-ct-label fw-bold">Your Email <span class="text-danger">*</span></label><input type="email" id="ktRvEmail" name="email" required maxlength="120" class="form-control kt-input rounded-pill" value="<?php echo html_escape($review['values']['email']); ?>"></div>
<div class="col-12 col-md-6"><label for="ktRvRate" class="kt-ct-label fw-bold">Rating <span class="text-danger">*</span></label>
<select id="ktRvRate" name="rate" required class="form-select kt-select rounded-pill"><option value="">Choose a rating</option><?php for ($s = 5; $s >= 1; $s--): ?><option value="<?php echo $s; ?>"<?php echo $review['values']['rate'] === (string) $s ? ' selected' : ''; ?>><?php echo $s; ?> star<?php echo $s > 1 ? 's' : ''; ?></option><?php endfor; ?></select></div>
<div class="col-12"><label for="ktRvText" class="kt-ct-label fw-bold">Your Testimonial <span class="text-danger">*</span></label><textarea id="ktRvText" name="comment" rows="4" required maxlength="2000" class="form-control kt-textarea"><?php echo html_escape($review['values']['comment']); ?></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">SUBMIT TESTIMONIAL <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-send"></i></span></button></div>
<?php echo form_close(); ?>
<?php endif; ?>
</div>
</div>
</div>
</section>

<?php if ($related): ?>
<section class="container-xxl kt-container kt-sec pt-0" data-reveal>
<div class="d-flex flex-wrap align-items-end justify-content-between kt-gap-20 kt-mb-40">
<div>
<div class="kt-eyebrow d-flex align-items-center fw-bold text-primary mb-3">RELATED</div>
<h2 class="kt-h2 mb-0 fw-bold text-dark">You May Also Like</h2>
</div>
<div class="d-flex kt-gap-10">
<button type="button" class="btn btn-kt-prev kt-btn-52 rounded-circle d-flex align-items-center justify-content-center" data-kt-slide="prev" data-kt-target="#ktRelTrack" aria-label="Previous"><i class="bi bi-arrow-left"></i></button>
<button type="button" class="btn btn-kt-next kt-btn-52 rounded-circle d-flex align-items-center justify-content-center" data-kt-slide="next" data-kt-target="#ktRelTrack" aria-label="Next"><i class="bi bi-arrow-right"></i></button>
</div>
</div>
<div class="kt-track d-flex overflow-x-auto" id="ktRelTrack">
<?php foreach (product_cards($related) as $card): ?>
<?php echo view('Modules\Products\Views\partials\product_card', array('card' => $card)); ?>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<div class="modal fade kt-lightbox" id="ktLightbox" tabindex="-1" aria-label="Product image viewer" aria-hidden="true">
<div class="modal-dialog modal-fullscreen">
<div class="modal-content kt-lb position-relative border-0 rounded-0 d-flex align-items-center justify-content-center">
<button type="button" class="btn btn-kt-prev kt-lb-close position-absolute rounded-circle d-flex align-items-center justify-content-center" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
<button type="button" class="btn btn-kt-prev kt-btn-52 kt-lb-prev position-absolute top-50 translate-middle-y rounded-circle d-flex align-items-center justify-content-center" id="ktLbPrev" aria-label="Previous"><i class="bi bi-arrow-left"></i></button>
<button type="button" class="btn btn-kt-next kt-btn-52 kt-lb-next position-absolute top-50 translate-middle-y rounded-circle d-flex align-items-center justify-content-center" id="ktLbNext" aria-label="Next"><i class="bi bi-arrow-right"></i></button>
<div class="kt-lb-frame kt-lb-pd position-relative overflow-hidden bg-white"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape(product_image_url($images[0]['media'], 1200, 1200)); ?>" alt="<?php echo html_escape($name); ?>" class="d-block w-100 h-100 object-fit-contain" id="ktLbImg"></div>
</div>
</div>
</div>
<script type="application/json" id="ktPdVariants"><?php echo json_encode(array_map(static fn ($v) => array('available' => $v['available'], 'max' => $v['max'], 'price' => $v['price_text'], 'regular' => $v['regular_text'], 'saving' => $v['saving'], 'status' => $v['status_text'], 'low' => $v['low']), $variants['list']), JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<?php echo view('bottom_application'); ?>
