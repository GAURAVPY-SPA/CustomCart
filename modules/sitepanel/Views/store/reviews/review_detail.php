<?php
/*
| One product testimonial ($res, with product_name and friendly_url): its
| details, the testimonial text, and Activate / Deactivate, Delete and Back.
| Laid out like the other detail pages (e.g. enquiry/view_enquiry_detail):
| .sp-details pairs are each wrapped in a <div>. Visitor input: always escaped.
*/
$rate    = max(0, min(5, (int) $res['rate']));
$active  = $res['status'] === '1';
$label   = 'testimonial by '.$res['poster_name'];
$product = website_url(\Modules\Products\Models\ProductModel::URL_PREFIX.$res['friendly_url']);
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="review-info">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="review-info">Testimonial #<?php echo (int) $res['review_id']; ?></h2>
		<?php if ($active): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
	</header>
	<dl class="sp-details">
		<div><dt>Product</dt><dd><a class="sp-link sp-link--external" href="<?php echo html_escape($product); ?>" target="_blank" rel="noopener"><?php echo html_escape($res['product_name']); ?><span class="visually-hidden"> (opens the product page in a new tab)</span></a></dd></div>
		<div><dt>Name</dt><dd><?php echo html_escape($res['poster_name']); ?></dd></div>
		<div><dt>Email</dt><dd><a class="sp-link" href="mailto:<?php echo html_escape($res['email']); ?>"><?php echo html_escape($res['email']); ?></a></dd></div>
		<div><dt>Rating</dt><dd><span class="text-warning" aria-hidden="true"><?php echo str_repeat('★', $rate).str_repeat('☆', 5 - $rate); ?></span> <?php echo $rate; ?> / 5</dd></div>
		<div><dt>Customer</dt><dd><?php if ((int) $res['mem_id'] > 0): ?><a class="sp-link" href="<?php echo site_url('sitepanel/members/view/'.(int) $res['mem_id']); ?>">Registered member #<?php echo (int) $res['mem_id']; ?></a><?php else: ?>Guest<?php endif; ?></dd></div>
		<div><dt>Posted</dt><dd><?php echo html_escape(sp_datetime($res['posted_date'])); ?></dd></div>
		<?php if ((string) $res['ip_address'] !== ''): ?><div><dt>IP address</dt><dd><?php echo html_escape($res['ip_address']); ?></dd></div><?php endif; ?>
	</dl>
</section>

<section class="sp-panel mb-4" aria-labelledby="review-text">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="review-text">Testimonial</h2></header>
	<p class="sp-text mb-0"><?php echo nl2br(html_escape((string) $res['comment'])); ?></p>
</section>

<div class="sp-form-actions mb-4">
	<?php if ($active ? $deactivatePrvg : $activatePrvg): ?>
	<?php echo form_open('sitepanel/reviews/status/'.(int) $res['review_id']); ?>
		<input type="hidden" name="status" value="<?php echo $active ? '0' : '1'; ?>">
		<button class="btn sp-btn-primary" type="submit"><?php echo $active ? 'Deactivate' : 'Activate'; ?><span class="visually-hidden"> <?php echo html_escape($label); ?></span></button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<?php if ($deletePrvg): ?>
	<?php echo form_open('sitepanel/reviews/delete/'.(int) $res['review_id'], array('data-sp-confirm' => 'Delete this testimonial? This cannot be undone.')); ?>
		<button class="btn sp-btn-outline sp-btn-danger" type="submit">Delete testimonial</button>
	<?php echo form_close(); ?>
	<?php endif; ?>
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/reviews'); ?>">Back to testimonials</a>
</div>
<?php echo sp_view('includes/footer'); ?>
