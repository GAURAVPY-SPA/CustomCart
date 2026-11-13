<?php
/* Add / edit a gallery photo. $res = the row (empty values when adding), $image = current image path ('' = none). */
$is_edit = isset($res['gallery_id']);
$action  = $is_edit ? 'sitepanel/gallery/edit/'.(int) $res['gallery_id'] : 'sitepanel/gallery/add';
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit photo' : 'New photo'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="gallery_title">Title <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="gallery_title" name="gallery_title" maxlength="200" required value="<?php echo set_value('gallery_title', (string) $res['gallery_title']); ?>">
				<div class="form-text">Describe the photo, e.g. <em>FR coveralls on display at the CustomCart showroom</em>. Shown to screen readers and search engines (alt text). Up to 200 characters.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="gallery_image">Image<?php if ( ! $is_edit): ?> <span class="text-danger">*</span><?php endif; ?></label>
				<?php if ($is_edit && $image !== ''): ?>
				<p class="mb-2"><img class="sp-thumb" src="<?php echo html_escape(get_image('gallery', basename($image), 192, 108, 'C')); ?>" alt="Current image"></p>
				<?php endif; ?>
				<input class="form-control" type="file" id="gallery_image" name="gallery_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"<?php echo $is_edit ? '' : ' required'; ?>>
				<div class="form-text">JPG, PNG, GIF or WebP, up to 2 MB, portrait or landscape (the gallery keeps each photo's proportions). Best <?php echo \Modules\Sitepanel\Controllers\Gallery::BEST_SIZE; ?>.<?php echo $is_edit ? ' Leave empty to keep the current image.' : ''; ?></div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add photo'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/gallery'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
