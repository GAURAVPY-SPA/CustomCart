<?php
/*
| My Wishlist (design: html/wishlist.htm), see Members::wishlist(). $products
| (public product rows), $variants (ids that need a colour / size: Add to Cart
| opens the product page). Add to Cart / remove: customcart.js.
*/
$products = is_array($products) ? $products : array();
$count    = count($products);
echo view('top_application');
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'start', 'customer' => $customer, 'nav_active' => 'wishlist', 'title' => 'Wishlist'));
?>
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0"><h2 class="kt-shop-title fw-bold text-dark mb-0">My Wishlist <span class="kt-shop-count"><?php echo $count; ?> item<?php echo $count === 1 ? '' : 's'; ?></span></h2></div>
<a href="<?php echo site_url('products'); ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-left"></i> CONTINUE SHOPPING</a>
</div>
<?php if ($products): ?>
<div class="kt-wl-list">
<?php foreach ($products as $p):
	$url    = product_url($p['friendly_url']);
	$prices = \Modules\Products\Models\ProductModel::prices($p);
	$qty    = (int) $p['product_quantity'];
	$low    = $qty > 0 && $qty <= service('siteSettings')->low_stock_threshold();
?>
<article class="kt-wl" data-kt-wish-item data-product-id="<?php echo (int) $p['products_id']; ?>" data-name="<?php echo html_escape($p['product_name']); ?>" data-url="<?php echo html_escape($url); ?>" data-kt-options="<?php echo isset($variants[(int) $p['products_id']]) ? '1' : '0'; ?>">
<a href="<?php echo html_escape($url); ?>" class="kt-wl-img rounded-4 overflow-hidden bg-primary-subtle"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape(product_image_url($p['image'], 300, 300)); ?>" alt="<?php echo html_escape($p['product_name']); ?>" class="w-100 h-100 object-fit-cover"></a>
<div class="kt-min-0">
<?php if ((string) ($p['category_name'] ?? '') !== ''): ?><div class="kt-prod-cat fw-bold text-primary text-uppercase"><?php echo html_escape($p['category_name']); ?></div><?php endif; ?>
<h3 class="kt-wl-name fw-bold"><a href="<?php echo html_escape($url); ?>" class="text-dark"><?php echo html_escape($p['product_name']); ?></a></h3>
<div class="kt-ci-price d-flex flex-wrap align-items-baseline kt-gap-10"><span class="kt-ci-now fw-bold text-dark"><?php echo display_price($prices['sale']); ?></span><?php if ($prices['percent'] > 0): ?><span class="kt-ci-old"><?php echo display_price($prices['regular']); ?></span><span class="kt-ci-off rounded-pill fw-bold"><?php echo $prices['percent']; ?>% OFF</span><?php endif; ?></div>
<?php if ($qty <= 0): ?><div class="kt-ci-stock fw-bold text-danger"><i class="bi bi-x-circle-fill"></i> Out of Stock</div><?php elseif ($low): ?><div class="kt-ci-stock fw-bold is-low"><i class="bi bi-exclamation-circle-fill"></i> Only <?php echo $qty; ?> left</div><?php else: ?><div class="kt-ci-stock fw-bold is-in"><i class="bi bi-check-circle-fill"></i> In Stock</div><?php endif; ?>
</div>
<div class="kt-wl-acts d-flex align-items-center">
<?php if ($qty > 0): ?><button type="button" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center justify-content-center gap-2" data-kt-add><i class="bi bi-bag-plus"></i> ADD TO CART</button><?php endif; ?>
<button type="button" class="btn kt-ci-remove is-active rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" data-kt-wish data-kt-wish-remove aria-label="Remove <?php echo html_escape($p['product_name']); ?> from wishlist"><i class="bi bi-trash3"></i></button>
</div>
</article>
<?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-body-secondary mb-0">Your wishlist is empty. Tap the heart on a product to save it here. <a href="<?php echo store_url(); ?>" class="kt-link fw-bold">Browse products</a></p>
<?php endif; ?>
</div>
<?php
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'end'));
echo view('bottom_application');
