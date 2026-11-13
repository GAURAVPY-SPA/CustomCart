<?php
/*
| /testimonials — html/testimonial.htm. $testimonials = active rows
| (Testimonials::index); $form = Post a Testimonial state (values, errors,
| submitted); $captcha = the form's verification code (Captcha::view_data()).
| A long comment shows its first lines, the rest opens with Read more.
*/
echo view('top_application');
$fv      = function ($field) use ($form) { return html_escape($form['values'][$field]); };
$c       = $captcha;
$initials = function ($name) {
	$parts = preg_split('/\s+/u', trim((string) $name));
	return mb_strtoupper(mb_substr($parts[0], 0, 1).(isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
};
?>
<?php echo view('inner_hero', array('hero' => array('title' => 'Testimonials', 'banner' => 'Testimonials', 'crumbs' => array('Testimonials' => NULL)))); ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
  <div class="text-center kt-mb-56">
    <div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary mb-3">TESTIMONIALS</div>
    <h2 class="kt-h2 mb-0 fw-bold text-dark">Customer Feedback</h2>
  </div>
  <div class="row align-items-start kt-g-doc">
    <div class="col-12 col-xl-8">
      <div class="kt-testi-list d-flex flex-column">
<?php if ( ! $testimonials): ?>
      <p class="kt-text-2 mb-0">No testimonials have been published yet. Be the first to share your experience.</p>
<?php endif; ?>
<?php foreach ($testimonials as $i => $row): ?>
<?php
	$text  = trim((string) $row['testimonial_description']);
	$short = $text;
	$more  = '';
	if (mb_strlen($text) > 220)
	{
		$cut   = mb_strrpos(mb_substr($text, 0, 220), ' ');
		$cut   = $cut ? $cut : 220;
		$short = mb_substr($text, 0, $cut);
		$more  = mb_substr($text, $cut);
	}
	$id    = 'ktTesti'.(int) $row['testimonial_id'];
	$photo = (string) $row['photo'] !== '' ? get_image('testimonial', $row['photo'], 116, 116, 'C') : '';
?>
      <article class="kt-testi-row bg-white border d-flex flex-column flex-md-row kt-gap-28">
        <div class="kt-testi-who d-flex flex-md-column align-items-center align-items-md-start kt-gap-14 flex-shrink-0">
<?php if ($photo !== ''): ?>
          <span class="kt-testi-avatar rounded-circle bg-primary-subtle overflow-hidden d-flex flex-shrink-0"><img loading="lazy" decoding="async" src="<?php echo html_escape($photo); ?>" alt="<?php echo html_escape($row['poster_name']); ?>" width="58" height="58" class="w-100 h-100 object-fit-cover"></span>
<?php else: ?>
          <span class="kt-testi-avatar rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0"><?php echo html_escape($initials($row['poster_name'])); ?></span>
<?php endif; ?>
          <div>
            <div class="kt-fs-18 fw-bold text-dark"><?php echo html_escape($row['poster_name']); ?></div>
<?php if ((string) $row['poster_role'] !== ''): ?>
            <div class="kt-fs-15 text-body-secondary"><?php echo html_escape($row['poster_role']); ?></div>
<?php endif; ?>
          </div>
        </div>
        <div class="flex-grow-1">
          <span class="kt-testi-mark rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center"><i class="bi bi-quote"></i></span>
          <p class="kt-testi-txt mb-3"><?php echo nl2br(html_escape($short)); ?><?php if ($more !== ''): ?><span class="collapse kt-testi-more" id="<?php echo $id; ?>"><?php echo nl2br(html_escape($more)); ?></span><span class="kt-testi-dots">&hellip;</span><?php endif; ?></p>
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
<?php if ($more !== ''): ?>
            <button type="button" class="btn btn-link kt-read p-0 collapsed" data-bs-toggle="collapse" data-bs-target="#<?php echo $id; ?>" aria-expanded="false" aria-controls="<?php echo $id; ?>"><span class="kt-read-more">READ MORE</span><span class="kt-read-less">READ LESS</span> <i class="bi bi-chevron-down"></i></button>
<?php else: ?>
            <span></span>
<?php endif; ?>
            <span class="kt-fs-14 text-body-tertiary"><i class="bi bi-calendar3"></i> <?php echo date('j M Y', strtotime((string) $row['posted_date'])); ?></span>
          </div>
        </div>
      </article>
<?php endforeach; ?>
      </div>
    </div>
    <div class="col-12 col-xl-4">
      <aside class="kt-testi-form bg-light border" id="post-testimonial">
        <div class="kt-eyebrow d-flex align-items-center fw-bold text-primary mb-2">SHARE YOUR EXPERIENCE</div>
        <h2 class="kt-inq-title fw-bold text-dark kt-mb-22">Post a Testimonial</h2>
<?php if ($form['submitted']): ?>
        <div class="kt-alert kt-alert-ok" role="status">Thank you! Your testimonial has been received and will appear here once it is approved.</div>
<?php endif; ?>
<?php if ($form['errors']): ?>
        <div class="kt-alert kt-alert-error" role="alert"><ul><?php foreach ($form['errors'] as $error): ?><li><?php echo html_escape($error); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
        <?php echo form_open_multipart(site_url('testimonials').'#post-testimonial', array('class' => 'd-flex flex-column kt-gap-14')); ?>
          <input type="text" name="name" required maxlength="30" placeholder="Name" class="form-control kt-input rounded-pill" aria-label="Name" value="<?php echo $fv('name'); ?>">
          <input type="email" name="email" required maxlength="80" placeholder="Email" class="form-control kt-input rounded-pill" aria-label="Email" value="<?php echo $fv('email'); ?>">
          <textarea rows="5" name="message" required maxlength="2000" placeholder="Comment" class="form-control kt-textarea" aria-label="Comment"><?php echo $fv('message'); ?></textarea>
          <div>
            <label for="testiPhoto" class="form-label kt-fs-14 fw-bold text-dark mb-1">Photo <span class="fw-normal text-body-secondary">(optional)</span></label>
            <input type="file" id="testiPhoto" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control kt-input rounded-pill" aria-describedby="testiPhotoHelp">
            <div id="testiPhotoHelp" class="kt-fs-14 text-body-secondary mt-1">JPG, PNG, GIF or WEBP, at most 2 MB.<?php echo $form['errors'] || $c['error'] !== '' ? ' Please choose your photo again.' : ''; ?></div>
          </div>
          <input type="text" name="fax_number" value="" class="kt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
          <div class="d-flex align-items-center kt-gap-10" data-captcha data-captcha-src="<?php echo html_escape($c['image_url']); ?>" data-captcha-input="<?php echo html_escape($c['id']); ?>Code">
            <span class="kt-captcha rounded-pill bg-white d-flex align-items-center justify-content-center flex-grow-1 overflow-hidden"><img src="<?php echo html_escape($c['image_url']); ?>" alt="Captcha code" width="<?php echo (int) $c['width']; ?>" height="<?php echo (int) $c['height']; ?>" style="width:auto;height:auto;max-height:44px" data-captcha-image></span>
            <button type="button" class="btn btn-kt-close kt-btn-44 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" aria-label="New captcha code" data-captcha-refresh><i class="bi bi-arrow-clockwise"></i></button>
          </div>
          <input type="text" id="<?php echo html_escape($c['id']); ?>Code" name="<?php echo html_escape($c['field']); ?>" required maxlength="<?php echo (int) $c['length']; ?>" placeholder="Enter captcha code" class="form-control kt-input rounded-pill<?php echo $c['error'] !== '' ? ' is-invalid' : ''; ?>" aria-label="Captcha code" autocomplete="off">
<?php if ($c['error'] !== ''): ?>
          <div class="kt-field-error"><?php echo html_escape($c['error']); ?></div>
<?php endif; ?>
          <button type="submit" class="btn btn-kt-dark kt-btn-56 kt-ls-14 rounded-pill">SUBMIT</button>
        <?php echo form_close(); ?>
      </aside>
    </div>
  </div>
</section>
<?php echo view('bottom_application'); ?>
