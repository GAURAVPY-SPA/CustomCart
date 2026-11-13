<?php
/*
| Payment step (design: html/payment.htm), see Cart::payment(): the delivery
| address, the payment card tiles (Config\Shop::$paymentCards), terms and
| Complete My Order (one-time order_token). The payment gateway is not
| connected yet: the order is placed Unpaid (see Cart::place_order()).
*/
$change = site_url(empty($customer) && ! (new \Modules\Users\Libraries\CustomerAuth())->current() ? 'guest-checkout' : 'checkout');
$card   = $card !== '' ? $card : (string) array_key_first($shop->paymentCards);
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Payment', 'banner' => 'Online Store', 'crumbs' => array('Shopping Cart' => site_url('cart'), 'Checkout' => $change, 'Payment' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-shop">
<?php echo view('Modules\Cart\Views\partials\steps', array('step' => 3)); ?>
<?php echo view('Modules\Products\Views\partials\flash', array('errors' => $errors)); ?>
<div class="row kt-g-shop">
<div class="col-12 col-xl-8">
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Delivery Address</h2>
<a href="<?php echo $change; ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2"><i class="bi bi-pencil"></i> CHANGE</a>
</div>
<div class="kt-pay-addr d-flex align-items-start kt-gap-14">
<span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-geo-alt"></i></span>
<div class="kt-min-0">
<b class="kt-addr-name d-block text-dark"><?php echo html_escape($delivery['full_name']); ?></b>
<div class="kt-addr-phone text-body-secondary"><i class="bi bi-telephone"></i> <span><?php echo html_escape($delivery['mobile']); ?></span><?php echo $delivery['alt_mobile'] !== '' ? ' / <span>'.html_escape($delivery['alt_mobile']).'</span>' : ''; ?></div>
<p class="kt-addr-text mb-0"><span><?php echo html_escape($delivery['address']); ?></span><?php if ($delivery['landmark'] !== ''): ?><span>, <span><?php echo html_escape($delivery['landmark']); ?></span></span><?php endif; ?><br><span><?php echo html_escape($delivery['city']); ?></span>, <span><?php echo html_escape($delivery['state']); ?></span> <span><?php echo html_escape($delivery['zipcode']); ?></span>, <span><?php echo html_escape($delivery['country']); ?></span></p>
</div>
</div>
</div>
<?php echo form_open('payment', array('class' => 'kt-shop-card bg-white border kt-validate', 'novalidate' => 'novalidate')); ?>
<input type="hidden" name="order_token" value="<?php echo html_escape($token); ?>">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Payment Method</h2>
</div>
<div class="kt-pay-amount text-dark">Payable Amount : <b data-kt-grand-total><?php echo display_price($summary['grand_total']); ?></b></div>
<div class="kt-pay-opts">
<div class="row row-cols-2 row-cols-md-4 kt-g-pay">
<?php foreach ($shop->paymentCards as $key => $label): $pid = 'ktPay'.ucfirst($key); ?>
<div class="col"><label class="kt-pay-opt h-100 d-flex align-items-center" for="<?php echo $pid; ?>"><input type="radio" name="payment" id="<?php echo $pid; ?>" value="<?php echo $key; ?>" class="form-check-input kt-pay-radio flex-shrink-0"<?php echo $card === $key ? ' checked' : ''; ?> required><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo theme_url(); ?>images/<?php echo $key; ?>.svg" alt="<?php echo html_escape($label); ?>" class="kt-pay-logo"></label></div>
<?php endforeach; ?>
</div>
</div>
<div class="kt-pay-foot">
<div class="form-check kt-side-check kt-pay-terms">
<input class="form-check-input" type="checkbox" name="terms" value="1" id="ktPayTerms" required>
<label class="form-check-label kt-ct-agree" for="ktPayTerms">I agree to the <a href="<?php echo site_url('terms-conditions'); ?>" target="_blank">Terms &amp; Conditions</a>, <a href="<?php echo site_url('returns'); ?>" target="_blank">Returns Policy</a> and <a href="<?php echo site_url('refunds'); ?>" target="_blank">Refund Policy</a>.</label>
<div class="invalid-feedback">Please accept the terms to continue.</div>
</div>
<button type="submit" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">COMPLETE MY ORDER <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-right"></i></span></button>
<p class="kt-pay-note mb-0"><b class="text-dark">Note :</b> Your order information will be sent to your email id <b class="text-dark">(<?php echo html_escape($email); ?>)</b></p>
</div>
<?php echo form_close(); ?>
<div class="kt-shop-nav d-flex flex-wrap align-items-center justify-content-between">
<a href="<?php echo $change; ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-left"></i> BACK TO ADDRESS</a>
</div>
</div>
<div class="col-12 col-xl-4">
<aside class="kt-shop-side d-flex flex-column">
<?php echo view('Modules\Cart\Views\partials\summary', array('summary' => $summary, 'items' => TRUE)); ?>
</aside>
</div>
</div>
</section>
<?php echo view('bottom_application'); ?>
