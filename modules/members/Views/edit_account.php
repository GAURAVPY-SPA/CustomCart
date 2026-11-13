<?php
/*
| Edit Profile (design: html/edit-profile.htm), see Members::edit_account():
| the profile (Edit Profile popup: full name, mobile, newsletter) and the
| Change Password form (posts to change-password, back here).
| $customer, $values, $errors, $password_errors.
*/
$full_name = trim($customer['first_name'].' '.$customer['last_name']);
$initials  = strtoupper(mb_substr($customer['first_name'], 0, 1).mb_substr((string) $customer['last_name'], 0, 1));
echo view('top_application');
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'start', 'customer' => $customer, 'nav_active' => 'edit', 'title' => 'Edit Profile'));
?>
<div class="kt-shop-card bg-white border">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Profile Information</h2>
<p class="kt-acc-sub text-body-secondary mb-0">Your personal details used for orders and invoices.</p>
</div>
<button type="button" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#ktProfileEdit"><i class="bi bi-pencil"></i> EDIT PROFILE</button>
</div>
<div class="kt-prof d-flex flex-wrap align-items-center">
<span class="kt-prof-avatar rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"><?php echo html_escape($initials); ?></span>
<div class="kt-min-0"><b class="kt-prof-name d-block text-dark"><?php echo html_escape($full_name); ?></b><span class="text-body-secondary">Customer since <?php echo date('M Y', strtotime((string) $customer['account_created_date'])); ?></span></div>
</div>
<dl class="kt-prof-dl row row-cols-1 row-cols-md-2 g-0 mb-0">
<div class="col"><div class="kt-prof-cell"><dt>Full Name</dt><dd><?php echo html_escape($full_name); ?></dd></div></div>
<div class="col"><div class="kt-prof-cell"><dt>Email Address</dt><dd><?php echo html_escape($customer['user_name']); ?></dd></div></div>
<div class="col"><div class="kt-prof-cell"><dt>Mobile Number</dt><dd><?php echo html_escape($customer['mobile_number']); ?></dd></div></div>
<div class="col"><div class="kt-prof-cell"><dt>Newsletter</dt><dd><?php echo $customer['newsletter'] === '1' ? 'Subscribed' : 'Not subscribed'; ?></dd></div></div>
</dl>
</div>
<div class="kt-shop-card bg-white border" id="ktPassword">
<div class="kt-shop-head d-flex flex-wrap align-items-center justify-content-between">
<div class="kt-min-0">
<h2 class="kt-shop-title fw-bold text-dark mb-0">Change Password</h2>
<p class="kt-acc-sub text-body-secondary mb-0">Use a strong password that you do not use on other websites.</p>
</div>
</div>
<?php if ($password_errors): ?><?php echo view('Modules\Products\Views\partials\form_errors', array('form_errors' => $password_errors)); ?><?php endif; ?>
<?php echo form_open('change-password', array('class' => 'kt-validate', 'novalidate' => 'novalidate')); ?>
<div class="row kt-g-ctf">
<div class="col-12 col-md-6">
<label for="ktPwdCurrent" class="kt-ct-label fw-bold">Current Password <span class="text-danger">*</span></label>
<div class="kt-pass position-relative"><input type="password" id="ktPwdCurrent" name="current_password" required maxlength="72" placeholder="Enter current password" autocomplete="current-password" class="form-control kt-input rounded-pill"><button type="button" class="kt-pass-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-kt-pass aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button><div class="invalid-feedback">Please enter your current password.</div></div>
</div>
<div class="w-100 d-none d-md-block m-0 p-0"></div>
<div class="col-12 col-md-6">
<label for="ktPwdNew" class="kt-ct-label fw-bold">New Password <span class="text-danger">*</span></label>
<div class="kt-pass position-relative"><input type="password" id="ktPwdNew" name="new_password" required minlength="8" maxlength="72" placeholder="Create a new password" autocomplete="new-password" class="form-control kt-input rounded-pill"><button type="button" class="kt-pass-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-kt-pass aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button><div class="invalid-feedback">Use at least 8 characters with letters and numbers.</div></div>
</div>
<div class="col-12 col-md-6">
<label for="ktPwdConfirm" class="kt-ct-label fw-bold">Confirm New Password <span class="text-danger">*</span></label>
<div class="kt-pass position-relative"><input type="password" id="ktPwdConfirm" name="confirm_password" required maxlength="72" data-kt-match="#ktPwdNew" placeholder="Re-enter new password" autocomplete="new-password" class="form-control kt-input rounded-pill"><button type="button" class="kt-pass-btn position-absolute rounded-circle d-flex align-items-center justify-content-center" data-kt-pass aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button><div class="invalid-feedback">The passwords do not match.</div></div>
</div>
</div>
<button type="submit" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2 mt-3"><i class="bi bi-shield-check"></i> UPDATE PASSWORD</button>
<?php echo form_close(); ?>
</div>

<div class="modal fade kt-form-modal" id="ktProfileEdit" tabindex="-1" aria-labelledby="ktProfileEditTitle" aria-hidden="true"<?php echo $errors ? ' data-kt-open' : ''; ?>>
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
<div class="modal-content border-0">
<div class="kt-form-modal-head d-flex align-items-center justify-content-between">
<div class="d-flex align-items-center kt-gap-12"><span class="kt-shop-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-person"></i></span><h2 class="kt-shop-title fw-bold text-dark mb-0" id="ktProfileEditTitle">Edit Profile</h2></div>
<button type="button" class="btn btn-kt-close kt-btn-42 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
</div>
<?php echo form_open('acc-edit', array('class' => 'd-flex flex-column kt-min-0 kt-validate', 'novalidate' => 'novalidate')); ?>
<div class="modal-body kt-form-modal-body">
<?php if ($errors): ?><?php echo view('Modules\Products\Views\partials\form_errors', array('form_errors' => $errors)); ?><?php endif; ?>
<div class="row kt-g-ctf">
<div class="col-12 col-md-6"><label for="ktPfName" class="kt-ct-label fw-bold">Full Name <span class="text-danger">*</span></label><input type="text" id="ktPfName" name="full_name" required minlength="3" maxlength="100" class="form-control kt-input rounded-pill" value="<?php echo html_escape($values['full_name']); ?>"></div>
<div class="col-12 col-md-6"><label for="ktPfEmail" class="kt-ct-label fw-bold">Email Address</label><input type="email" id="ktPfEmail" readonly class="form-control kt-input rounded-pill" value="<?php echo html_escape($customer['user_name']); ?>"></div>
<div class="col-12 col-md-6"><label for="ktPfMobile" class="kt-ct-label fw-bold">Mobile Number <span class="text-danger">*</span></label><input type="tel" id="ktPfMobile" name="mobile_number" required maxlength="20" placeholder="+966 5X XXX XXXX" class="form-control kt-input rounded-pill" value="<?php echo html_escape($values['mobile_number']); ?>"></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="newsletter" value="1" id="ktPfNews"<?php echo $values['newsletter'] === '1' ? ' checked' : ''; ?>><label class="form-check-label" for="ktPfNews">Keep me updated about CustomCart news and new products.</label></div></div>
</div>
</div>
<div class="kt-form-modal-foot d-flex flex-wrap justify-content-end gap-2">
<button type="button" class="btn btn-kt-outline kt-shop-btn rounded-pill" data-bs-dismiss="modal">CANCEL</button>
<button type="submit" class="btn btn-kt-dark kt-shop-btn rounded-pill d-inline-flex align-items-center gap-2"><i class="bi bi-check-lg"></i> SAVE CHANGES</button>
</div>
<?php echo form_close(); ?>
</div>
</div>
</div>
<?php
echo view('Modules\Members\Views\partials\account_nav', array('part' => 'end'));
echo view('bottom_application');
