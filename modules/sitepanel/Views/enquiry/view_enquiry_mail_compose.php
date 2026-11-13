<?php
/* Send Mail composer (one or more enquiries). $flow = session flow (token); $rows = the
   enquiries, from the database; $valid = how many have a valid address; $errors; $cancel_url. */
echo sp_view('includes/header'); ?>
<?php if ($errors): ?>
	<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>
<?php endif; ?>

<section class="sp-panel mb-4" aria-labelledby="enquiry-mail-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="enquiry-mail-title">Send mail to <?php echo count($rows) === 1 ? '1 enquiry' : count($rows).' enquiries'; ?></h2></header>

	<p class="form-label mb-1">To</p>
	<ul class="list-unstyled mb-2">
		<?php foreach ($rows as $row): ?>
		<?php $email = trim((string) $row['email']); $ok = filter_var(strtolower($email), FILTER_VALIDATE_EMAIL) !== FALSE; ?>
		<li>
			<?php echo html_escape(trim($row['first_name'].' '.$row['last_name'])); ?>
			&lt;<?php echo html_escape($email); ?>&gt;
			<?php if ( ! $ok): ?><span class="sp-badge sp-badge--error">Invalid address, will be skipped</span><?php endif; ?>
		</li>
		<?php endforeach; ?>
	</ul>
	<p class="form-text mb-3">
		<?php echo (int) $valid; ?> valid address(es). Each recipient gets a separate email starting "Dear &lt;name&gt;," and ending with the site signature.
		An address shared by several enquiries is emailed once. Emailed enquiries are marked as replied. Review the preview before sending.
	</p>

	<?php echo form_open($base.'/preview_mail', array('novalidate' => 'novalidate')); ?>
		<?php echo form_hidden('token', $flow['token']); ?>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="enquiry-mail-subject">Subject <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="enquiry-mail-subject" name="subject" maxlength="200" required value="<?php echo set_value('subject'); ?>">
			</div>
			<div class="col-12">
				<label class="form-label" for="enquiry-mail-message">Message <span class="text-danger">*</span></label>
				<textarea class="form-control" id="enquiry-mail-message" name="message" rows="12" required data-sp-editor data-sp-upload-url="<?php echo html_escape(site_url($base.'/upload_image')); ?>"><?php echo set_value('message'); ?></textarea>
			</div>
		</div>
		<div class="sp-form-actions mt-4">
			<button class="btn sp-btn-primary" type="submit">Preview Email</button>
			<a class="btn sp-btn-outline" href="<?php echo html_escape($cancel_url); ?>">Cancel</a>
		</div>
	<?php echo form_close(); ?>
</section>

<script src="<?php echo asset_url('assets/vendor/ckeditor4/ckeditor.js'); ?>"></script>
<script src="<?php echo asset_url('assets/sitepanel/js/editor.js'); ?>"></script>
<?php echo sp_view('includes/footer'); ?>
