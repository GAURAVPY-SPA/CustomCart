<?php
/* Add / edit a Running Marquee item. $res = the row (empty values when adding). */
$is_edit = isset($res['marquee_id']);
$action  = $is_edit ? 'sitepanel/marquee/edit/'.(int) $res['marquee_id'] : 'sitepanel/marquee/add';
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit marquee item' : 'New marquee item'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="marquee_text">Text <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="marquee_text" name="marquee_text" maxlength="255" required value="<?php echo set_value('marquee_text', (string) $res['marquee_text']); ?>">
				<div class="form-text">Up to 255 characters, e.g. <em>Free delivery on bulk orders across the Eastern Province</em>. Active items scroll above the website header.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="marquee_url">Link</label>
				<input class="form-control" type="text" id="marquee_url" name="marquee_url" maxlength="255" value="<?php echo set_value('marquee_url', (string) $res['marquee_url']); ?>">
				<div class="form-text">Optional: a full address (https://…) or a path of this site starting with /. Leave empty for plain text.</div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add item'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/marquee'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
