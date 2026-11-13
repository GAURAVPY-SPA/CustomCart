<?php
/* Add / edit a header image. $res = the row (empty values when adding), $image = current image path ('' = none). */
$is_edit = isset($res['id']);
$action  = $is_edit ? 'sitepanel/header_images/edit/'.(int) $res['id'] : 'sitepanel/header_images/add';
$value   = function ($key) use ($res) {
	return set_value($key, (string) $res[$key]);
};
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit header image' : 'New header image'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="header_image">Header image<?php if ( ! $is_edit): ?> <span class="text-danger">*</span><?php endif; ?></label>
				<?php if ($is_edit && $image !== ''): ?>
				<p class="mb-2"><img class="sp-thumb" src="<?php echo html_escape(get_image('header_images', basename($image), 192, 108, 'C')); ?>" alt="Current header image"></p>
				<?php endif; ?>
				<input class="form-control" type="file" id="header_image" name="header_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"<?php echo $is_edit ? '' : ' required'; ?>>
				<div class="form-text">JPG, PNG, GIF or WebP, up to 2 MB. Best size <?php echo \Modules\Sitepanel\Controllers\HeaderImages::BEST_SIZE; ?> px.<?php echo $is_edit ? ' Leave empty to keep the current image.' : ''; ?></div>
			</div>
			<div class="col-12">
				<label class="form-label" for="header_url">Link URL</label>
				<input class="form-control" type="text" id="header_url" name="header_url" maxlength="255" value="<?php echo $value('header_url'); ?>">
				<div class="form-text">Optional, e.g. <code>https://example.com/page</code>. The slide's Learn More button opens it (without a link: Explore Our Programs).</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="line_one">First line</label>
				<input class="form-control" type="text" id="line_one" name="line_one" maxlength="80" value="<?php echo $value('line_one'); ?>">
				<div class="form-text">The small heading above the title. Up to 80 characters.</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="line_two">Second line</label>
				<input class="form-control" type="text" id="line_two" name="line_two" maxlength="255" value="<?php echo $value('line_two'); ?>">
				<div class="form-text">The slide's title. Up to 255 characters.</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="line_three">Third line</label>
				<input class="form-control" type="text" id="line_three" name="line_three" maxlength="255" value="<?php echo $value('line_three'); ?>">
				<div class="form-text">The text under the title. Up to 255 characters.</div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add header image'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/header_images'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
