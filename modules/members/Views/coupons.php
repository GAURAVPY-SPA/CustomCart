<?php
/*
| My Coupons (design: html/coupons.htm), see Members::coupons(). $coupons
| (valid today). Copy: customcart.js [data-kt-copy].
*/
$count = count($coupons);
echo view('top_application');
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'start', 'customer' => $customer, 'nav_active' => 'coupons', 'title' => 'My Coupons'));
?>
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Available Coupons <span class="kt-shop-count"><?php echo $count; ?> coupon<?php echo $count === 1 ? '' : 's'; ?></span></h2>
<p class="kt-acc-sub text-body-secondary mb-0">Copy a code and apply it in your cart before checkout.</p>
</div>
</div>
<?php if ($coupons): ?>
<div class="row row-cols-1 row-cols-xl-2 kt-g-cpn">
<?php foreach ($coupons as $c):
	$percent = $c['coupon_type'] === 'p';
	$value   = $percent ? rtrim(rtrim(number_format((float) $c['coupon_discount'], 2), '0'), '.').'%' : display_price($c['coupon_discount'], 0);
	$code    = (string) $c['coupon_code'];
?>
<div class="col">
<article class="kt-cpn h-100 d-flex">
<div class="kt-cpn-stub position-relative d-flex flex-column align-items-center justify-content-center text-center flex-shrink-0">
<span class="kt-cpn-stub-ico rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-<?php echo $percent ? 'percent' : 'cash-coin'; ?>"></i></span>
<b class="kt-cpn-val"><?php echo $value; ?></b>
<span class="kt-cpn-unit rounded-pill fw-bold"><?php echo $percent ? 'OFF' : 'FLAT OFF'; ?></span>
</div>
<div class="kt-cpn-body flex-grow-1 kt-min-0 d-flex flex-column">
<div class="d-flex align-items-start justify-content-between gap-2">
<h3 class="kt-cpn-title fw-bold text-dark mb-0"><?php echo html_escape($c['coupon_title']); ?></h3>
<span class="kt-status is-available rounded-pill fw-bold"><i class="bi bi-check-circle"></i> Available</span>
</div>
<div class="kt-cpn-code d-flex align-items-center justify-content-between gap-2"><b><?php echo html_escape($code); ?></b><button type="button" class="btn btn-kt-dark kt-cpn-copy rounded-pill d-inline-flex align-items-center gap-1 flex-shrink-0" data-kt-copy="<?php echo html_escape($code); ?>" aria-label="Copy coupon code <?php echo html_escape($code); ?>"><span class="is-off d-inline-flex align-items-center gap-1"><i class="bi bi-copy"></i> COPY</span><span class="is-on align-items-center gap-1"><i class="bi bi-check2"></i> COPIED</span></button></div>
<div class="kt-cpn-facts">
<div><span>Min. Order</span><b><?php echo display_price($c['minimum_order_amount']); ?></b></div>
<?php if ($percent && (float) $c['maximum_coupon_discount'] > 0): ?><div><span>Max. Discount</span><b><?php echo display_price($c['maximum_coupon_discount']); ?></b></div><?php endif; ?>
</div>
<div class="kt-cpn-date fw-bold d-flex align-items-center gap-2"><i class="bi bi-calendar-check"></i> Valid till <?php echo date('j M Y', strtotime((string) $c['end_date'])); ?></div>
<p class="kt-cpn-terms d-flex align-items-start gap-2 mb-0 mt-auto"><i class="bi bi-info-circle"></i><span><?php echo $c['coupon_usage'] === 'multiple' ? 'Can be used up to '.(int) $c['usage_count'].' times per customer.' : 'Valid once per customer.'; ?><?php echo $c['coupon_for'] === '1' ? ' For registered members.' : ''; ?></span></p>
</div>
</article>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-body-secondary mb-0">There are no coupons available right now. Please check back soon.</p>
<?php endif; ?>
</div>
<?php
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'end'));
echo view('bottom_application');
