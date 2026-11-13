<?php
/* Send Mail preview. $rows = enquiries; $valid = those with a valid address; $subject;
   $message = sanitized HTML; $sample = the first recipient's full email. */
echo sp_view('includes/header');

?>

<section class="sp-panel mb-4" aria-labelledby="enquiry-preview-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="enquiry-preview-title">Review before sending</h2></header>
	<dl class="row mb-4">
		<dt class="col-sm-3">To</dt>
		<dd class="col-sm-9">
			<?php $names = array(); foreach ($valid as $row) { $names[] = html_escape(trim($row['first_name'].' '.$row['last_name']).' <'.trim($row['email']).'>'); } ?>
			<?php echo implode('<br>', $names); ?>
			<?php if (count($rows) > count($valid)): ?><span class="sp-muted d-block small"><?php echo count($rows) - count($valid); ?> invalid address(es) will be skipped.</span><?php endif; ?>
		</dd>
		<dt class="col-sm-3">Subject</dt>
		<dd class="col-sm-9"><?php echo html_escape($subject); ?></dd>
	</dl>
	<p class="form-label mb-1">Message<?php echo count($valid) > 1 ? ' (as the first recipient will see it)' : ''; ?></p>
	<div class="sp-panel mb-4" aria-label="Email message preview"><?php echo $sample; ?></div>

	<div class="sp-form-actions">
		<?php echo form_open($base.'/deliver_mail'); ?>
			<?php echo form_hidden('token', $flow['token']); ?>
			<?php echo form_hidden('subject', $subject); ?>
			<?php echo form_hidden('message', $message); ?>
			<button class="btn sp-btn-primary" type="submit">Send Mail</button>
		<?php echo form_close(); ?>
		<?php echo form_open($base.'/compose_mail'); ?>
			<?php echo form_hidden('token', $flow['token']); ?>
			<?php echo form_hidden('subject', $subject); ?>
			<?php echo form_hidden('message', $message); ?>
			<button class="btn sp-btn-outline" type="submit">Edit Message</button>
		<?php echo form_close(); ?>
		<a class="btn sp-btn-outline" href="<?php echo html_escape($cancel_url); ?>">Cancel</a>
	</div>
</section>

<?php echo sp_view('includes/footer'); ?>
