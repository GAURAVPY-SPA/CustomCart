<?php 
echo sp_view('includes/header'); ?>
<?php if ($errors): ?>
	<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>
<?php endif; ?>

<section class="sp-panel mb-4" aria-labelledby="newsletter-compose-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="newsletter-compose-title">Compose newsletter email</h2></header>
	<p class="mb-3">
		Selected: <strong><?php echo (int) $selected; ?></strong>
		&middot; Valid addresses: <strong><?php echo (int) $valid; ?></strong>
		<?php if ($invalid): ?>&middot; Invalid addresses to skip: <strong><?php echo (int) $invalid; ?></strong><?php endif; ?>
	</p>
	<p class="form-text mb-3">The message will be sent only to the selected subscribers. Invalid addresses are skipped. Review the preview before sending.</p>

	<?php echo form_open('sitepanel/newsletter/preview_email', array('novalidate' => 'novalidate')); ?>
		<?php echo form_hidden('token', $flow['token']); ?>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="newsletter-subject">Subject <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="newsletter-subject" name="subject" maxlength="200" required value="<?php echo set_value('subject'); ?>">
			</div>
			<div class="col-12">
				<label class="form-label" for="newsletter-message">Message <span class="text-danger">*</span></label>
				<textarea class="form-control" id="newsletter-message" name="message" rows="14" required data-sp-editor data-sp-upload-url="<?php echo html_escape(site_url('sitepanel/newsletter/upload_image')); ?>"><?php echo set_value('message'); ?></textarea>
			</div>
		</div>
		<div class="sp-form-actions mt-4">
			<button class="btn sp-btn-primary" type="submit">Preview Email</button>
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/newsletter'); ?>">Cancel</a>
		</div>
	<?php echo form_close(); ?>
</section>

<script src="<?php echo asset_url('assets/vendor/ckeditor4/ckeditor.js'); ?>"></script>
<script src="<?php echo asset_url('assets/sitepanel/js/editor.js'); ?>"></script>
<?php echo sp_view('includes/footer'); ?>
