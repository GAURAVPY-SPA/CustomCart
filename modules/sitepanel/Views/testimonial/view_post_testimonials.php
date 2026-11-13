<?php
/* Add a testimonial (the reference's "post testimonial" view). $max_size = [w, h]. */
$value = function ($key) {
	return set_value($key, '');
};
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart('sitepanel/testimonial/add', array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title">New testimonial</h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="poster_name">Name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="poster_name" name="poster_name" maxlength="30" required value="<?php echo $value('poster_name'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="email">Email <span class="text-danger">*</span></label>
				<input class="form-control" type="email" id="email" name="email" maxlength="80" required value="<?php echo $value('email'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="poster_role">Company / Role</label>
				<input class="form-control" type="text" id="poster_role" name="poster_role" maxlength="100" value="<?php echo $value('poster_role'); ?>">
				<div class="form-text">Optional, e.g. Operations Director, Retail Group.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="testimonial_description">Comment <span class="text-danger">*</span></label>
				<textarea class="form-control" id="testimonial_description" name="testimonial_description" rows="5" maxlength="5000" required><?php echo $value('testimonial_description'); ?></textarea>
				<div class="form-text">Plain text, up to 5000 characters.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="photo">Photo</label>
				<input class="form-control" type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
				<div class="form-text">Optional. JPG, PNG, GIF or WebP, up to 2 MB and <?php echo (int) $max_size[0].' × '.(int) $max_size[1]; ?> pixels.</div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo sp_icon('plus'); ?> Add testimonial</button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/testimonial'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
