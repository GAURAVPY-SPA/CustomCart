<?php
/*
| Guest checkout (design: html/guest-checkout.htm), see Cart::guest():
| contact email and mobile, the delivery address (partials/address_fields).
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Guest Checkout', 'banner' => 'Online Store', 'crumbs' => array('Shopping Cart' => site_url('cart'), 'Guest Checkout' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-shop">
<?php echo view('Modules\Cart\Views\partials\steps', array('step' => 2)); ?>
<?php echo view('Modules\Products\Views\partials\flash', array('errors' => $errors)); ?>
<?php echo form_open('guest-checkout', array('class' => 'kt-validate', 'novalidate' => 'novalidate')); ?>
<div class="row kt-g-shop">
<div class="col-12 col-xl-8">
<div class="kt-shop-card kt-guest bg-white border">
<div class="d-flex align-items-start kt-gap-14">
<span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-person"></i></span>
<div class="flex-grow-1 kt-min-0">
<h2 class="kt-shop-title fw-bold text-dark mb-1">Checking Out as a Guest</h2>
<p class="kt-shop-text text-body-secondary">Order updates and your invoice will be sent to this email address. Already have an account? <a href="<?php echo site_url('login'); ?>?ref=checkout" class="fw-bold">Log in</a></p>
<div class="row kt-g-ctf">
<div class="col-12 col-md-6"><label for="ktGuestEmail" class="kt-ct-label fw-bold">Email Address <span class="text-danger">*</span></label><input type="email" id="ktGuestEmail" name="email" required maxlength="120" autocomplete="email" placeholder="you@example.com" class="form-control kt-input rounded-pill" value="<?php echo html_escape($values['email']); ?>"></div>
<div class="col-12 col-md-6"><label for="ktGuestMobile" class="kt-ct-label fw-bold">Mobile Number <span class="text-danger">*</span></label><input type="tel" id="ktGuestMobile" name="contact_mobile" required maxlength="20" autocomplete="tel" placeholder="+966 5X XXX XXXX" class="form-control kt-input rounded-pill" value="<?php echo html_escape($values['contact_mobile']); ?>"></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="newsletter" value="1" id="ktGuestNews"<?php echo $values['newsletter'] === '1' ? ' checked' : ''; ?>><label class="form-check-label text-body-secondary" for="ktGuestNews">Keep me updated about CustomCart news and new products.</label></div></div>
</div>
</div>
</div>
</div>
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Delivery Address</h2>
</div>
<?php echo view('Modules\Cart\Views\partials\address_fields', array('values' => $values, 'locations' => $locations, 'shop' => $shop, 'id_prefix' => 'ktGa')); ?>
</div>
<div class="kt-shop-nav d-flex flex-wrap align-items-center justify-content-between">
<a href="<?php echo site_url('cart'); ?>" class="kt-shop-link fw-bold d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-left"></i> BACK TO CART</a>
<button type="submit" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">PROCEED TO PAYMENT <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-right"></i></span></button>
</div>
</div>
<div class="col-12 col-xl-4">
<aside class="kt-shop-side d-flex flex-column">
<?php echo view('Modules\Cart\Views\partials\summary', array('summary' => $summary, 'items' => TRUE, 'choose_shipping' => TRUE)); ?>
</aside>
</div>
</div>
<?php echo form_close(); ?>
</section>
<?php echo view('bottom_application'); ?>
