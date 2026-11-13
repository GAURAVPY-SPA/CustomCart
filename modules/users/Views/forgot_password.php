<?php
/*
| /forgot-password (the login page's Forgot Password popup posts here), see
| Users::forgot_password(). $errors, $sent, $email. Same card as html/login.htm.
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Forgot Password', 'banner' => 'Login & Register', 'crumbs' => array('Login' => site_url('login'), 'Forgot Password' => NULL))));
?>
<section class="container-xxl kt-container kt-sec kt-auth-sec">
<div class="kt-shop-card bg-white border mx-auto" style="max-width:560px">
<div class="d-flex align-items-center kt-gap-12 mb-3"><span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-key"></i></span><h2 class="kt-shop-title fw-bold text-dark mb-0">Forgot Password</h2></div>
<?php if ($sent): ?>
<div class="alert alert-success" role="status">If an account uses this email address, a link to reset its password is on its way. Please check your inbox.</div>
<a href="<?php echo site_url('login'); ?>" class="btn btn-kt-dark kt-shop-btn rounded-pill">BACK TO LOGIN</a>
<?php else: ?>
<?php echo view('Modules\Products\Views\partials\flash', array('errors' => $errors)); ?>
<?php echo form_open('forgot-password', array('class' => 'kt-validate', 'novalidate' => 'novalidate')); ?>
<p class="kt-shop-text text-body-secondary">Enter the email address registered with your account and we will send you a link to reset your password.</p>
<label for="ktForgotEmail" class="kt-ct-label fw-bold">Email Address <span class="text-danger">*</span></label>
<input type="email" id="ktForgotEmail" name="email" required maxlength="120" placeholder="you@example.com" class="form-control kt-input rounded-pill mb-3" value="<?php echo html_escape($email); ?>">
<button type="submit" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-check-lg"></i> SEND RESET LINK</button>
<?php echo form_close(); ?>
<?php endif; ?>
</div>
</section>
<?php echo view('bottom_application'); ?>
