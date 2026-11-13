<?php
/*
| Refer a Friend — popup page (iframe; js/customcart-popup.js). Footer:
| html/refer-a-friend.htm (share links) + the "Refer to a Friend" form of
| html/detail.htm #ktRefer for the website; ?product=<id>: the form about that
| product. $product (NULL = website), $values, $errors, $sent, $captcha.
*/
echo view('top_application');
$fv = function ($field) use ($values) { return html_escape($values[$field]); };
$c  = $captcha;
?>
<main class="kt-frame">
  <div class="kt-inq d-flex flex-column kt-gap-14">
    <div class="d-flex justify-content-between align-items-center"><div class="d-flex align-items-center kt-gap-12"><span class="kt-dot-12 rounded-circle bg-warning"></span><h1 class="kt-inq-title mb-0 fw-bold text-dark"><?php echo $product ? 'Refer to a Friend' : 'Refer a Friend'; ?></h1></div><button type="button" class="btn btn-kt-close kt-btn-42 rounded-circle d-flex align-items-center justify-content-center" data-kt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
<?php if ($product): ?>
    <p class="kt-refer-text text-body-secondary mb-0">Send a link to the <b class="text-dark"><?php echo html_escape($product['product_name']); ?></b> with a personal note.</p>
<?php else: ?>
    <p class="kt-shop-text text-body-secondary mb-0">Know someone who would love our collection? Share CustomCart with your friends and family.</p>
    <div class="kt-site-share">
      <a href="https://wa.me/" target="_blank" rel="noopener" class="kt-site-share-btn d-flex flex-column align-items-center" data-kt-share="whatsapp"><i class="bi bi-whatsapp is-wa rounded-circle d-flex align-items-center justify-content-center"></i>WhatsApp</a>
      <a href="mailto:" target="_top" class="kt-site-share-btn d-flex flex-column align-items-center" data-kt-share="email"><i class="bi bi-envelope is-mail rounded-circle d-flex align-items-center justify-content-center"></i>Email</a>
      <a href="https://www.facebook.com/" target="_blank" rel="noopener" class="kt-site-share-btn d-flex flex-column align-items-center" data-kt-share="facebook"><i class="bi bi-facebook is-fb rounded-circle d-flex align-items-center justify-content-center"></i>Facebook</a>
      <a href="https://twitter.com/" target="_blank" rel="noopener" class="kt-site-share-btn d-flex flex-column align-items-center" data-kt-share="x"><i class="bi bi-twitter-x is-x rounded-circle d-flex align-items-center justify-content-center"></i>X</a>
      <a href="https://www.linkedin.com/" target="_blank" rel="noopener" class="kt-site-share-btn d-flex flex-column align-items-center" data-kt-share="linkedin"><i class="bi bi-linkedin is-in rounded-circle d-flex align-items-center justify-content-center"></i>LinkedIn</a>
    </div>
    <p class="kt-shop-text text-body-secondary mb-0">Or send your friend a link with a personal note:</p>
<?php endif; ?>
<?php if ($sent): ?>
    <div class="kt-alert kt-alert-ok mb-0" role="status" data-kt-done-msg>Thank you! Your message has been sent to your friend.</div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="kt-alert kt-alert-error mb-0" role="alert"><ul><?php foreach ($errors as $error): ?><li><?php echo html_escape($error); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
    <?php echo form_open(site_url('refer-a-friend'), array('class' => 'row kt-g-ctf kt-validate', 'novalidate' => 'novalidate')); ?>
<?php if ($product): ?>
    <input type="hidden" name="product" value="<?php echo (int) $product['products_id']; ?>">
<?php endif; ?>
    <div class="col-12 col-md-6"><label for="ktRfName" class="kt-ct-label fw-bold">Your Name <span class="text-danger">*</span></label><input type="text" id="ktRfName" name="your_name" required maxlength="100" placeholder="Your name" class="form-control kt-input rounded-pill" value="<?php echo $fv('your_name'); ?>"></div>
    <div class="col-12 col-md-6"><label for="ktRfEmail" class="kt-ct-label fw-bold">Your Email <span class="text-danger">*</span></label><input type="email" id="ktRfEmail" name="your_email" required maxlength="120" placeholder="you@example.com" class="form-control kt-input rounded-pill" value="<?php echo $fv('your_email'); ?>"></div>
    <div class="col-12 col-md-6"><label for="ktRfFName" class="kt-ct-label fw-bold">Friend&rsquo;s Name <span class="text-danger">*</span></label><input type="text" id="ktRfFName" name="friend_name" required maxlength="100" placeholder="Friend's name" class="form-control kt-input rounded-pill" value="<?php echo $fv('friend_name'); ?>"></div>
    <div class="col-12 col-md-6"><label for="ktRfFEmail" class="kt-ct-label fw-bold">Friend&rsquo;s Email <span class="text-danger">*</span></label><input type="email" id="ktRfFEmail" name="friend_email" required maxlength="120" placeholder="friend@example.com" class="form-control kt-input rounded-pill" value="<?php echo $fv('friend_email'); ?>"></div>
    <div class="col-12"><label for="ktRfMsg" class="kt-ct-label fw-bold">Message</label><textarea id="ktRfMsg" rows="3" name="message" maxlength="1000" placeholder="<?php echo $product ? 'I thought you might like this product.' : 'I thought you might like CustomCart.'; ?>" class="form-control kt-textarea"><?php echo $fv('message'); ?></textarea></div>
    <input type="text" name="fax_number" value="" class="kt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
<?php echo view('includes/captcha', array('captcha' => $c)); ?>
    <div class="col-12"><button type="submit" class="btn btn-kt-dark kt-btn-arrow rounded-pill d-inline-flex align-items-center">SEND TO FRIEND <span class="kt-arrow-dot rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-send"></i></span></button></div>
    <?php echo form_close(); ?>
  </div>
</main>
<?php echo view('bottom_application'); ?>
