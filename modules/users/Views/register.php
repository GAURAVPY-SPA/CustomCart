<?php
/*
| /register (design: html/register.htm), see Users::register(). $values, $errors (field => message).
| $captcha = the form's verification code (Captcha::view_data()); its error shows under the code field.
*/
$img = theme_url().'images/';
$v   = function ($key) use ($values) { return html_escape((string) ($values[$key] ?? '')); };
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Register', 'banner' => 'Login & Register', 'crumbs' => array('Register' => NULL))));
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
<h2 class="kt-auth-side-title fw-bold">Join CustomCart</h2>
<p class="kt-auth-side-text mb-0">Track your orders, save addresses and check out faster.</p>
</div>
</aside>
</div>
<div class="col-12 col-lg-7">
<div class="kt-auth-main">
<div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary">REGISTER</div>
<h2 class="kt-auth-title fw-bold text-dark">Create Your Account</h2>
<p class="kt-auth-text text-body-secondary">Fill in your details below to get started.</p>
<?php echo view('Modules\Products\Views\partials\flash', array('errors' => array_values(array_diff_key($errors, array($captcha['field'] => TRUE))))); ?>
<?php echo form_open('register', array('class' => 'kt-validate', 'novalidate' => 'novalidate')); ?>
<input type="text" name="website" value="" class="kt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
<div class="row kt-g-ctf">
<div class="col-12">
<label for="ktRegName" class="kt-ct-label fw-bold">Full Name <span class="text-danger">*</span></label>
<div class="kt-has-ico position-relative">
<i class="bi bi-person kt-field-ico position-absolute" aria-hidden="true"></i>
<input type="text" id="ktRegName" name="full_name" required minlength="3" maxlength="100" autocomplete="name" placeholder="Your full name" class="form-control kt-input rounded-pill" value="<?php echo $v('full_name'); ?>">
</div>
</div>
<div class="col-12 col-md-6">
<label for="ktRegMobile" class="kt-ct-label fw-bold">Mobile Number <span class="text-danger">*</span></label>
<div class="kt-has-ico position-relative">
<i class="bi bi-phone kt-field-ico position-absolute" aria-hidden="true"></i>
<input type="tel" id="ktRegMobile" name="mobile_number" required maxlength="20" autocomplete="tel" placeholder="+966 5X XXX XXXX" class="form-control kt-input rounded-pill" value="<?php echo $v('mobile_number'); ?>">
</div>
</div>
<div class="col-12 col-md-6">
<label for="ktRegEmail" class="kt-ct-label fw-bold">Email Address <span class="text-danger">*</span></label>
<div class="kt-has-ico position-relative">
<i class="bi bi-envelope kt-field-ico position-absolute" aria-hidden="true"></i>
<input type="email" id="ktRegEmail" name="email" required maxlength="120" autocomplete="email" placeholder="you@example.com" class="form-control kt-input rounded-pill" value="<?php echo $v('email'); ?>">
</div>
</div>
<div class="col-12 col-md-6">
<label for="ktRegPass" class="kt-ct-label fw-bold">Password <span class="text-danger">*</span></label>
<div class="kt-pass kt-has-ico position-relative"><i class="bi bi-lock kt-field-ico position-absolute" aria-hidden="true"></i>
<input type="password" id="ktRegPass" name="password" required placeholder="Create a password" minlength="8" maxlength="72" autocomplete="new-password" class="form-control kt-input rounded-pill">
<button type="button" class="kt-pass-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-kt-pass aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
</div>
<div class="kt-form-hint">At least 8 characters, with letters and numbers.</div>
</div>
<div class="col-12 col-md-6">
<label for="ktRegPass2" class="kt-ct-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
<div class="kt-pass kt-has-ico position-relative"><i class="bi bi-shield-lock kt-field-ico position-absolute" aria-hidden="true"></i>
<input type="password" id="ktRegPass2" name="confirm_password" required maxlength="72" placeholder="Re-enter password" data-kt-match="#ktRegPass" autocomplete="new-password" class="form-control kt-input rounded-pill">
<button type="button" class="kt-pass-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-kt-pass aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
</div>
</div>
<?php echo view('includes/captcha', array('captcha' => $captcha)); /* two columns of this row: the code image, then the input */ ?>
</div>
<div class="form-check kt-side-check kt-auth-terms">
<input class="form-check-input" type="checkbox" id="ktRegNews" name="newsletter" value="1"<?php echo ($values['newsletter'] ?? '') === '1' ? ' checked' : ''; ?>>
<label class="form-check-label kt-ct-agree" for="ktRegNews">Keep me updated about CustomCart news and new products.</label>
</div>
<div class="form-check kt-side-check kt-auth-terms">
<input class="form-check-input" type="checkbox" id="ktRegTerms" name="terms" value="1" required<?php echo ($values['terms'] ?? '') !== '' ? ' checked' : ''; ?>>
<label class="form-check-label kt-ct-agree" for="ktRegTerms">I accept the <a href="<?php echo site_url('terms-conditions'); ?>" target="_blank">Terms &amp; Conditions</a> and <a href="<?php echo site_url('privacy-policy'); ?>" target="_blank">Privacy Policy</a>.</label>
</div>
<button type="submit" class="btn btn-kt-dark kt-btn-arrow kt-auth-btn rounded-pill d-flex align-items-center">REGISTER <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-right"></i></span></button>
<?php echo form_close(); ?>
<p class="kt-auth-alt text-center text-body-secondary mb-0">Already have an account? <a href="<?php echo site_url('login'); ?>" class="kt-link">Login</a></p>
</div>
</div>
</div>
</div>
</div>
</section>
<?php echo view('bottom_application'); ?>
