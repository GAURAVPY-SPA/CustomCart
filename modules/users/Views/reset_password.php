<?php
/*
| /reset-password?token= (link of the reset email), see Users::reset_password().
| $valid, $unavailable, $errors, $token.
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Reset Password', 'banner' => 'Login & Register', 'crumbs' => array('Login' => site_url('login'), 'Reset Password' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-auth-sec">
<div class="kt-shop-card bg-white border mx-auto" style="max-width:560px">
<div class="d-flex align-items-center kt-gap-12 mb-3"><span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-shield-lock"></i></span><h2 class="kt-shop-title fw-bold text-dark mb-0">Reset Password</h2></div>
<?php if ( ! $valid): ?>
<div class="alert alert-<?php echo $unavailable ? 'warning' : 'danger'; ?>" role="alert"><?php echo $unavailable ? 'Password reset is not available right now. Please try again shortly.' : 'This reset link is invalid or has expired. Please ask for a new one.'; ?></div>
<a href="<?php echo site_url('forgot-password'); ?>" class="btn btn-kt-dark kt-shop-btn rounded-pill">REQUEST A NEW LINK</a>
<?php else: ?>
<?php echo view('Modules\Products\Views\partials\flash', array('errors' => $errors)); ?>
<?php echo form_open('reset-password', array('class' => 'kt-validate', 'novalidate' => 'novalidate')); ?>
<input type="hidden" name="token" value="<?php echo html_escape($token); ?>">
<div class="row kt-g-ctf">
<div class="col-12"><label for="ktNewPass" class="kt-ct-label fw-bold">New Password <span class="text-danger">*</span></label>
<div class="kt-pass kt-has-ico position-relative"><i class="bi bi-lock kt-field-ico position-absolute" aria-hidden="true"></i><input type="password" id="ktNewPass" name="password" required minlength="8" maxlength="72" autocomplete="new-password" class="form-control kt-input rounded-pill"><button type="button" class="kt-pass-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-kt-pass aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button></div>
<div class="kt-form-hint">At least 8 characters, with letters and numbers.</div></div>
<div class="col-12"><label for="ktNewPass2" class="kt-ct-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
<div class="kt-pass kt-has-ico position-relative"><i class="bi bi-shield-lock kt-field-ico position-absolute" aria-hidden="true"></i><input type="password" id="ktNewPass2" name="confirm_password" required maxlength="72" data-kt-match="#ktNewPass" autocomplete="new-password" class="form-control kt-input rounded-pill"><button type="button" class="kt-pass-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-kt-pass aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button></div></div>
</div>
<button type="submit" class="btn btn-kt-dark kt-btn-arrow kt-auth-btn rounded-pill d-flex align-items-center mt-3">SAVE NEW PASSWORD <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-arrow-right"></i></span></button>
<?php echo form_close(); ?>
<?php endif; ?>
</div>
</section>
<?php echo view('bottom_application'); ?>
