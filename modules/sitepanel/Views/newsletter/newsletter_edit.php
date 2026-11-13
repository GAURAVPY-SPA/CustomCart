<?php
/* Edit a newsletter subscriber. $res = the row. */
$value = function ($key) use ($res) {
	return set_value($key, (string) $res[$key]);
};
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open('sitepanel/newsletter/edit/'.(int) $res['subscriber_id'], array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo 'Subscriber #'.(int) $res['subscriber_id']; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="subscriber_name">Name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="subscriber_name" name="subscriber_name" maxlength="32" required value="<?php echo $value('subscriber_name'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="subscriber_email">Email <span class="text-danger">*</span></label>
				<input class="form-control" type="email" id="subscriber_email" name="subscriber_email" maxlength="80" required value="<?php echo $value('subscriber_email'); ?>">
				<div class="form-text">Each email address can be subscribed once.</div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit">Save changes</button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/newsletter'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
