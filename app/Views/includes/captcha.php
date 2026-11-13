<?php
/*
| Verification code field of a public form (App\Libraries\Captcha::view_data()),
| in the design's markup (html/contact-us.htm, detail.htm): two .col-12.col-md-6
| columns, the code image with its "new code" button, then the input.
| $captcha: form, id (element id prefix), field (input name), image_url, width,
| height, length, label, error (message under the field, '' = none).
| The button only reloads the image (assets/designer/resources/js/captcha.js,
| added with Captcha::page_script()).
*/
$c       = $captcha;
$id      = html_escape($c['id']);
$invalid = $c['error'] !== '';
?>
<div class="col-12 col-md-6">
<span class="kt-ct-label d-block fw-bold"><?php echo html_escape($c['label']); ?></span>
<div class="d-flex align-items-center kt-gap-10" data-captcha data-captcha-src="<?php echo html_escape($c['image_url']); ?>" data-captcha-input="<?php echo $id; ?>Code">
<span class="kt-captcha rounded-pill bg-light d-flex align-items-center justify-content-center flex-grow-1 overflow-hidden"><img src="<?php echo html_escape($c['image_url']); ?>" id="<?php echo $id; ?>Image" alt="Captcha code" width="<?php echo (int) $c['width']; ?>" height="<?php echo (int) $c['height']; ?>" style="width:auto;height:auto;max-height:44px" data-captcha-image></span>
<button type="button" class="btn btn-kt-close kt-btn-44 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" aria-label="New captcha code" aria-controls="<?php echo $id; ?>Image" data-captcha-refresh><i class="bi bi-arrow-clockwise"></i></button>
</div>
<span class="visually-hidden" role="status" aria-live="polite" data-captcha-status></span>
</div>
<div class="col-12 col-md-6"><label for="<?php echo $id; ?>Code" class="kt-ct-label fw-bold">Enter Code <span class="text-danger">*</span></label><input type="text" id="<?php echo $id; ?>Code" name="<?php echo html_escape($c['field']); ?>" required placeholder="Type the code" maxlength="<?php echo (int) $c['length']; ?>" class="form-control kt-input rounded-pill<?php echo $invalid ? ' is-invalid' : ''; ?>" autocomplete="off"<?php echo $invalid ? ' aria-invalid="true"' : ''; ?>>
<?php if ($invalid): ?><div class="kt-field-error"><?php echo html_escape($c['error']); ?></div><?php endif; ?>
</div>
