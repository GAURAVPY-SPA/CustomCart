<?php
/*
 * Related products of $product: $options = products_id => "name (code)" of
 * every other product (FALSE = not loaded), $related_ids = ticked ids in
 * their order, $max_related. A small inline script filters the list.
 */
$picked = array_flip($related_ids);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open('sitepanel/products/related/'.(int) $product['products_id'], array('novalidate' => 'novalidate')); ?>
<section class="sp-panel mb-4" aria-labelledby="related-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="related-title">Related products of <?php echo html_escape($product['product_name']); ?></h2>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/edit/'.(int) $product['products_id']); ?>">Edit product</a>
	</header>
	<p class="sp-muted">Shown as "You may also like" on the product page (active products only), at most <?php echo (int) $max_related; ?>. With none picked, the newest products of the same category are shown.</p>

	<?php if ($options === FALSE): ?>
		<p class="sp-empty mb-0">Products could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($options)): ?>
		<p class="sp-empty mb-0">There are no other products yet.</p>
	<?php else: ?>
		<div class="mb-3" style="max-width:24rem">
			<label class="form-label" for="related-search">Find a product</label>
			<input class="form-control" type="search" id="related-search" autocomplete="off" placeholder="Name or code">
		</div>
		<div class="row g-2" id="related-options">
			<?php foreach ($options as $id => $label): ?>
			<div class="col-12 col-md-6 col-xl-4" data-related-option="<?php echo html_escape(strtolower($label)); ?>">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="related-<?php echo (int) $id; ?>" name="related_ids[]" value="<?php echo (int) $id; ?>"<?php echo isset($picked[$id]) ? ' checked' : ''; ?>>
					<label class="form-check-label" for="related-<?php echo (int) $id; ?>"><?php echo html_escape($label); ?></label>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>

<?php if ($editPrvg && $options): ?>
<div class="sp-form-actions mb-4">
	<button class="btn sp-btn-primary" type="submit">Save related products</button>
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products'); ?>">Back to products</a>
</div>
<?php endif; ?>
<?php echo form_close(); ?>
<script>
(function () {
	'use strict';
	var search = document.getElementById('related-search');
	if (!search) { return; }
	search.addEventListener('input', function () {
		var term = search.value.trim().toLowerCase();
		Array.prototype.forEach.call(document.querySelectorAll('[data-related-option]'), function (item) {
			item.hidden = term !== '' && item.getAttribute('data-related-option').indexOf(term) === -1;
		});
	});
}());
</script>
<?php echo sp_view('includes/footer'); ?>
