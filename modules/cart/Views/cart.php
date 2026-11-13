<?php
/*
| Shopping cart (design: html/cart.htm), see Cart::index(). One form posts the
| quantity steps (action=update, qty[rowid]) and Remove (remove=rowid); the
| coupon has its own form (action=coupon / remove_coupon). Totals: ShopCart::summary().
*/
$lines    = $summary['lines'];
$count    = (int) $summary['count'];
$max      = config(\Config\Shop::class)->maxQuantity;
$logged   = ! empty($customer);
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Shopping Cart', 'banner' => 'Online Store', 'crumbs' => array('Shopping Cart' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-shop">
<?php echo view('Modules\Cart\Views\partials\steps', array('step' => 1)); ?>
<?php echo view('Modules\Products\Views\partials\flash'); ?>
<?php if ( ! empty($coupon_notice)): ?><div class="alert alert-info" role="status"><?php echo html_escape($coupon_notice); ?></div><?php endif; ?>
<?php if ($lines): ?>
<div class="row kt-g-shop">
<div class="col-12 col-xl-8">
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Shopping Cart <span class="kt-shop-count"><?php echo $count; ?> item<?php echo $count === 1 ? '' : 's'; ?></span></h2>
<a href="<?php echo site_url('products'); ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-left"></i> CONTINUE SHOPPING</a>
</div>
<?php echo form_open('cart', array('id' => 'ktCartForm')); ?>
<input type="hidden" name="action" value="update">
<div class="kt-ci-list">
<?php foreach ($lines as $line):
	$rowid   = $line['rowid'];
	$percent = $line['regular'] > $line['price'] ? (int) round(($line['regular'] - $line['price']) / $line['regular'] * 100) : 0;
	$save    = round(($line['regular'] - $line['price']) * $line['qty'], 2);
	$low     = $line['available'] > 0 && $line['available'] <= service('siteSettings')->low_stock_threshold();
	$top     = max(1, min($max, (int) $line['available']));
?>
<article class="kt-ci d-flex">
<a href="<?php echo html_escape($line['url']); ?>" class="kt-ci-img rounded-4 overflow-hidden bg-primary-subtle flex-shrink-0"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape(product_image_url($line['image'], 240, 240)); ?>" alt="<?php echo html_escape($line['name']); ?>" class="w-100 h-100 object-fit-cover"></a>
<div class="kt-ci-body flex-grow-1 kt-min-0">
<div class="d-flex align-items-start justify-content-between gap-3">
<div class="kt-min-0">
<?php if ((string) $line['category_name'] !== ''): ?><div class="kt-prod-cat fw-bold text-primary text-uppercase"><?php echo html_escape($line['category_name']); ?></div><?php endif; ?>
<h3 class="kt-ci-name fw-bold mb-0"><a href="<?php echo html_escape($line['url']); ?>" class="text-dark"><?php echo html_escape($line['name']); ?></a></h3>
<div class="kt-ci-meta text-body-secondary d-flex flex-wrap"><?php if ($line['code'] !== ''): ?><span>SKU: <b class="text-body"><?php echo html_escape($line['code']); ?></b></span><?php endif; ?><?php if ($line['color_name']): ?><span>Colour: <b class="text-body"><?php echo html_escape($line['color_name']); ?></b></span><?php endif; ?><?php if ($line['size_name']): ?><span>Size: <b class="text-body"><?php echo html_escape($line['size_name']); ?></b></span><?php endif; ?></div>
<?php if ($line['problem']): ?>
<div class="kt-ci-stock fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo html_escape($line['problem']); ?></div>
<?php elseif ($low): ?>
<div class="kt-ci-stock fw-bold is-low"><i class="bi bi-exclamation-circle-fill"></i> Only <?php echo (int) $line['available']; ?> left</div>
<?php else: ?>
<div class="kt-ci-stock fw-bold is-in"><i class="bi bi-check-circle-fill"></i> In Stock</div>
<?php endif; ?>
</div>
<button type="submit" name="remove" value="<?php echo html_escape($rowid); ?>" class="btn kt-ci-remove rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" aria-label="Remove <?php echo html_escape($line['name']); ?>"><i class="bi bi-trash3"></i></button>
</div>
<div class="kt-ci-foot d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-ci-price d-flex flex-wrap align-items-baseline kt-gap-10"><span class="kt-ci-now fw-bold text-dark"><?php echo display_price($line['price']); ?></span><?php if ($percent > 0): ?><span class="kt-ci-old text-decoration-line-through"><?php echo display_price($line['regular']); ?></span><span class="kt-ci-off rounded-pill fw-bold"><?php echo $percent; ?>% OFF</span><?php endif; ?></div>
<div class="kt-qty d-flex align-items-center kt-gap-6 rounded-pill"><button type="submit" name="qty[<?php echo html_escape($rowid); ?>]" value="<?php echo max(0, $line['qty'] - 1); ?>" class="btn btn-kt-close kt-btn-36 rounded-circle d-flex align-items-center justify-content-center" aria-label="Decrease quantity"><i class="bi bi-<?php echo $line['qty'] > 1 ? 'dash' : 'trash3'; ?>"></i></button><span class="kt-qty-val text-center fw-bold" aria-live="polite"><?php echo (int) $line['qty']; ?></span><button type="submit" name="qty[<?php echo html_escape($rowid); ?>]" value="<?php echo $line['qty'] + 1; ?>" class="btn btn-kt-close kt-btn-36 rounded-circle d-flex align-items-center justify-content-center" aria-label="Increase quantity"<?php echo $line['qty'] >= $top ? ' disabled' : ''; ?>><i class="bi bi-plus"></i></button></div>
<div class="kt-ci-total text-end"><span class="kt-ci-total-label d-block">Item Total</span><b class="kt-ci-total-val d-block text-dark"><?php echo display_price($line['line_total']); ?></b><span class="kt-ci-save d-block fw-bold<?php echo $save > 0 ? '' : ' d-none'; ?>">You save <?php echo display_price($save); ?></span></div>
</div>
</div>
</article>
<?php endforeach; ?>
</div>
<?php echo form_close(); ?>
</div>
</div>
<div class="col-12 col-xl-4">
<aside class="kt-shop-side d-flex flex-column">
<div class="kt-shop-card kt-coupon bg-white border">
<div class="kt-sum-title fw-bold text-dark d-flex align-items-center gap-2"><i class="bi bi-ticket-perforated text-primary"></i> Apply Coupon</div>
<?php if ($summary['coupon_code']): ?>
<?php echo form_open('cart', array('class' => 'kt-coupon-applied d-flex align-items-center')); ?>
<input type="hidden" name="action" value="remove_coupon">
<span class="kt-coupon-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-check-lg"></i></span>
<span class="flex-grow-1"><b class="d-block text-dark"><?php echo html_escape($summary['coupon_code']); ?></b><span class="kt-coupon-off">You saved <?php echo display_price($summary['discount']); ?></span></span>
<button type="submit" class="btn kt-coupon-remove fw-bold">REMOVE</button>
<?php echo form_close(); ?>
<?php else: ?>
<?php echo form_open('cart', array('class' => 'kt-coupon-form d-flex gap-2')); ?>
<input type="hidden" name="action" value="coupon">
<input type="text" name="coupon_code" maxlength="20" required class="form-control kt-input kt-coupon-input rounded-pill" placeholder="Enter coupon code" aria-label="Coupon code" autocomplete="off">
<button type="submit" class="btn btn-kt-dark kt-coupon-btn rounded-pill flex-shrink-0">APPLY</button>
<?php echo form_close(); ?>
<?php endif; ?>
</div>
<?php
$actions = '<div class="kt-sum-actions d-grid">';
if ($summary['has_problems'])
{
	$actions .= '<p class="text-danger fw-bold mb-0 text-center">Please fix the items marked above to continue.</p>';
}
else
{
	$actions .= '<a href="'.site_url('checkout').'" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-flex align-items-center justify-content-between">PROCEED TO CHECKOUT <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-right"></i></span></a>';
	if ( ! $logged)
	{
		$actions .= '<a href="'.site_url('guest-checkout').'" class="btn btn-kt-outline kt-btn-56 rounded-pill d-flex align-items-center justify-content-center gap-2"><i class="bi bi-person"></i> GUEST CHECKOUT</a>';
	}
}
$actions .= '</div>';
echo view('Modules\Cart\Views\partials\summary', array('summary' => $summary, 'actions' => $actions, 'choose_shipping' => TRUE));
?>
</aside>
</div>
</div>
<?php else: ?>
<div class="kt-cart-empty kt-shop-card bg-white border text-center">
<span class="kt-cart-empty-ico rounded-circle d-inline-flex align-items-center justify-content-center"><i class="bi bi-bag-x"></i></span>
<h2 class="kt-shop-title fw-bold text-dark">Your Cart Is Empty</h2>
<p class="text-body-secondary">Looks like you have not added anything yet. Explore our latest collection to get started.</p>
<a href="<?php echo store_url(); ?>" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">START SHOPPING <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-bag"></i></span></a>
</div>
<?php endif; ?>
</section>
<?php echo view('bottom_application'); ?>
