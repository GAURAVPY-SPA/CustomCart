<?php
/*
| Send Inquiry — popup page inside the header's #ktInquiry iframe
| (html/send-enquiry.htm; js/customcart-popup.js). ?product=<id> names the
| product. $product (NULL = general), $values, $errors, $sent.
*/
echo view('top_application');
$fv = function ($field) use ($values) { return html_escape($values[$field]); };
?>
<main class="kt-frame">
  <?php echo form_open(site_url('send-inquiry'), array('class' => 'kt-inq d-flex flex-column kt-gap-14 kt-validate', 'id' => 'ktInqForm', 'novalidate' => 'novalidate')); ?>
    <div class="d-flex justify-content-between align-items-center"><div class="d-flex align-items-center kt-gap-12"><span class="kt-dot-12 rounded-circle bg-warning"></span><h1 class="kt-inq-title mb-0 fw-bold text-dark" id="ktInqTitle">Send Inquiry</h1></div><button type="button" class="btn btn-kt-close kt-btn-42 rounded-circle d-flex align-items-center justify-content-center" data-kt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
<?php if ($product): ?>
    <div class="kt-inq-product text-body-secondary" id="ktInqProduct">Product: <b class="text-dark" id="ktInqProductName"><?php echo html_escape($product['product_name']); ?></b></div>
    <input type="hidden" name="product" value="<?php echo (int) $product['products_id']; ?>">
<?php endif; ?>
<?php if ($errors): ?>
    <div class="kt-alert kt-alert-error mb-0" role="alert"><ul><?php foreach ($errors as $error): ?><li><?php echo html_escape($error); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
    <div class="row row-cols-1 row-cols-sm-2 kt-g-12">
      <div class="col"><input type="text" name="name" required maxlength="61" placeholder="Full name" class="form-control kt-input rounded-pill" aria-label="Full name" value="<?php echo $fv('name'); ?>"></div>
      <div class="col"><input type="text" name="company" maxlength="60" placeholder="Company" class="form-control kt-input rounded-pill" aria-label="Company" value="<?php echo $fv('company'); ?>"></div>
      <div class="col"><input type="email" name="email" required maxlength="80" placeholder="Email" class="form-control kt-input rounded-pill" aria-label="Email" value="<?php echo $fv('email'); ?>"></div>
      <div class="col"><input type="tel" name="phone" maxlength="20" placeholder="Phone" class="form-control kt-input rounded-pill" aria-label="Phone" value="<?php echo $fv('phone'); ?>"></div>
    </div>
    <textarea rows="4" name="message" maxlength="5000" placeholder="Quantities, sizes or requirements" class="form-control kt-textarea" aria-label="Requirements"><?php echo $fv('message'); ?></textarea>
    <input type="text" name="fax_number" value="" class="kt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <button type="submit" class="btn btn-kt-dark kt-btn-56 kt-ls-14 rounded-pill">SUBMIT INQUIRY</button>
    <p class="kt-inq-done mb-0 fw-bold text-center" id="ktInqDone" role="status" data-kt-done-msg<?php echo $sent ? '' : ' hidden'; ?>><i class="bi bi-check-circle-fill"></i> Inquiry sent. Our team will contact you shortly.</p>
  <?php echo form_close(); ?>
</main>
<?php echo view('bottom_application'); ?>
