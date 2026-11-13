<?php
/*
| /login (design: html/login.htm), see Users::login(). $errors, $email, $ref
| (page to return to). The Forgot Password popup posts to /forgot-password.
| Guest checkout is offered while the visitor has a cart.
*/
$img = theme_url().'images/';
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Login', 'banner' => 'Login & Register', 'crumbs' => array('Login' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-auth-sec">
<div class="kt-auth-wrap position-relative">
<div class="kt-auth-box kt-shop-card bg-white border overflow-hidden">
<div class="row g-0">
<div class="col-12 col-lg-5">
<aside class="kt-auth-side h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
<img src="<?php echo $img; ?>login-register.jpg" alt="" class="kt-auth-bg position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
<a href="<?php echo site_url(); ?>" class="kt-auth-logo d-inline-block align-self-start bg-white" aria-label="CustomCart home"><img src="<?php echo $img; ?>customcart-logo.png" alt="CustomCart logo" class="d-block w-100 h-100 object-fit-contain"></a>
<div class="kt-auth-side-copy">
<h2 class="kt-auth-side-title fw-bold">Welcome Back to CustomCart</h2>
<p class="kt-auth-side-text mb-0">Your Store, Your Way: track orders, save addresses and check out faster.</p>
</div>
</aside>
</div>
<div class="col-12 col-lg-7">
<div class="kt-auth-main">
<div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary">WELCOME BACK</div>
<h2 class="kt-auth-title fw-bold text-dark">Login to Your Account</h2>
<p class="kt-auth-text text-body-secondary">Enter your email address and password to continue.</p>
<?php echo view('Modules\Products\Views\partials\flash', array('errors' => $errors)); ?>
<?php echo form_open('login', array('class' => 'kt-validate', 'novalidate' => 'novalidate')); ?>
<?php if ($ref !== ''): ?><input type="hidden" name="ref" value="<?php echo html_escape($ref); ?>"><?php endif; ?>
<div class="row kt-g-ctf">
<div class="col-12">
<label for="ktLoginEmail" class="kt-ct-label fw-bold">Email Address <span class="text-danger">*</span></label>
<div class="kt-has-ico position-relative">
<i class="bi bi-envelope kt-field-ico position-absolute" aria-hidden="true"></i>
<input type="email" id="ktLoginEmail" name="email" required maxlength="120" autocomplete="email" placeholder="you@example.com" class="form-control kt-input rounded-pill" value="<?php echo html_escape($email); ?>">
</div>
</div>
<div class="col-12">
<label for="ktLoginPass" class="kt-ct-label fw-bold">Password <span class="text-danger">*</span></label>
<div class="kt-pass kt-has-ico position-relative"><i class="bi bi-lock kt-field-ico position-absolute" aria-hidden="true"></i>
<input type="password" id="ktLoginPass" name="password" required maxlength="72" placeholder="Enter your password" autocomplete="current-password" class="form-control kt-input rounded-pill">
<button type="button" class="kt-pass-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-kt-pass aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
</div>
</div>
</div>
<div class="kt-auth-row d-flex flex-wrap align-items-center justify-content-end">
<button type="button" class="btn p-0 border-0 kt-link" data-bs-toggle="modal" data-bs-target="#ktForgot">Forgot Password?</button>
</div>
<button type="submit" class="btn btn-kt-dark kt-btn-arrow kt-auth-btn rounded-pill d-flex align-items-center">LOGIN <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-right"></i></span></button>
<?php echo form_close(); ?>
<p class="kt-auth-alt text-center text-body-secondary mb-0">New to CustomCart? <a href="<?php echo site_url('register'); ?>" class="kt-link">Create an account</a></p>
<?php if (cart_count() > 0): ?>
<p class="kt-auth-alt text-center text-body-secondary mb-0">Prefer not to sign in? <a href="<?php echo site_url('guest-checkout'); ?>" class="kt-link">Checkout as a guest</a></p>
<?php endif; ?>
</div>
</div>
</div>
</div>
</div>
</section>

<div class="modal fade kt-form-modal" id="ktForgot" tabindex="-1" aria-labelledby="ktForgotTitle" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ">
<div class="modal-content border-0">
<div class="kt-form-modal-head d-flex align-items-center justify-content-between">
<div class="d-flex align-items-center kt-gap-12">
<span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-key"></i></span>
<h2 class="kt-shop-title fw-bold text-dark mb-0" id="ktForgotTitle">Forgot Password</h2>
</div>
<button type="button" class="btn btn-kt-close kt-btn-42 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
</div>
<?php echo form_open('forgot-password', array('class' => 'd-flex flex-column kt-min-0 kt-validate', 'novalidate' => 'novalidate')); ?>
<div class="modal-body kt-form-modal-body">
<p class="kt-shop-text text-body-secondary">Enter the email address registered with your account and we will send you a link to reset your password.</p>
<label for="ktForgotEmail" class="kt-ct-label fw-bold">Email Address <span class="text-danger">*</span></label>
<input type="email" id="ktForgotEmail" name="email" required maxlength="120" placeholder="you@example.com" class="form-control kt-input rounded-pill">
</div>
<div class="kt-form-modal-foot d-flex flex-wrap justify-content-end gap-2">
<button type="button" class="btn btn-kt-outline kt-shop-btn rounded-pill" data-bs-dismiss="modal">CANCEL</button>
<button type="submit" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-check-lg"></i> SEND RESET LINK</button>
</div>
<?php echo form_close(); ?>
</div>
</div>
</div>
<?php echo view('bottom_application'); ?>
