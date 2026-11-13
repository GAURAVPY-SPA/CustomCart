<?php 
echo sp_view('includes/header'); ?>

<section class="sp-panel mb-4" aria-labelledby="newsletter-preview-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="newsletter-preview-title">Review before sending</h2></header>
	<dl class="row mb-4">
		<dt class="col-sm-3">Recipients</dt>
		<dd class="col-sm-9"><?php echo (int) count($rows); ?> selected subscriber(s).</dd>
		<dt class="col-sm-3">Subject</dt>
		<dd class="col-sm-9"><?php echo html_escape($subject); ?></dd>
	</dl>
	<div class="sp-panel mb-4" aria-label="Email message preview"><?php echo $message; ?></div>

	<div class="sp-form-actions">
		<?php echo form_open('sitepanel/newsletter/send_email'); ?>
			<?php echo form_hidden('token', $flow['token']); ?>
			<?php echo form_hidden('subject', $subject); ?>
			<?php echo form_hidden('message', $message); ?>
			<button class="btn sp-btn-primary" type="submit">Send Email</button>
		<?php echo form_close(); ?>
		<?php echo form_open('sitepanel/newsletter/compose_email'); ?>
			<?php echo form_hidden('token', $flow['token']); ?>
			<?php echo form_hidden('subject', $subject); ?>
			<?php echo form_hidden('message', $message); ?>
			<button class="btn sp-btn-outline" type="submit">Edit Message</button>
		<?php echo form_close(); ?>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/newsletter'); ?>">Cancel</a>
	</div>
</section>

<?php echo sp_view('includes/footer'); ?>
