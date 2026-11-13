<?php
/* Add / edit a video. $res = the row (empty values when adding), $image = current thumbnail path ('' = none). */
use Modules\Sitepanel\Controllers\Video;

$is_edit = isset($res['video_id']);
$action  = $is_edit ? 'sitepanel/video/edit/'.(int) $res['video_id'] : 'sitepanel/video/add';
$value   = function ($key) use ($res) {
	return set_value($key, (string) $res[$key]);
};
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit video' : 'New video'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="video_title">Title <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="video_title" name="video_title" maxlength="200" required value="<?php echo $value('video_title'); ?>">
				<div class="form-text">Up to 200 characters, e.g. <em>Introduction to CustomCart</em>.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="video_link">Video link <span class="text-danger">*</span></label>
				<input class="form-control" type="url" id="video_link" name="video_link" maxlength="255" required value="<?php echo $value('video_link'); ?>">
				<div class="form-text">The address of a YouTube or Vimeo video, e.g. <code>https://www.youtube.com/watch?v=Fc-yl9_XXtI</code> or <code>https://youtu.be/Fc-yl9_XXtI</code>. It plays in a window on the Videos Gallery page.</div>
			</div>
			<div class="col-12 col-md-6">
				<label class="form-label" for="video_category">Category</label>
				<input class="form-control" type="text" id="video_category" name="video_category" maxlength="50" value="<?php echo $value('video_category'); ?>">
				<div class="form-text">Optional, e.g. <em>Basics</em>, <em>Technique</em>, <em>Community</em>.</div>
			</div>
			<div class="col-12 col-md-6">
				<label class="form-label" for="video_duration">Duration</label>
				<input class="form-control" type="text" id="video_duration" name="video_duration" maxlength="8" inputmode="numeric" placeholder="08:24" value="<?php echo $value('video_duration'); ?>">
				<div class="form-text">Optional, minutes and seconds (<code>08:24</code>) or <code>1:02:15</code>. Shown after the category under the title.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="video_image">Thumbnail</label>
				<?php if ($is_edit && $image !== ''): ?>
				<p class="mb-2"><img class="sp-thumb" src="<?php echo html_escape(get_image('video', basename($image), 192, 108, 'C')); ?>" alt="Current thumbnail"></p>
				<div class="form-check mb-2">
					<input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1"<?php echo set_checkbox('remove_image', '1'); ?>>
					<label class="form-check-label" for="remove_image">Remove this thumbnail (YouTube videos then show their own)</label>
				</div>
				<?php endif; ?>
				<input class="form-control" type="file" id="video_image" name="video_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
				<div class="form-text">JPG, PNG, GIF or WebP, up to 2 MB. Best <?php echo Video::BEST_SIZE; ?> px (16:9). Optional for YouTube videos, which show their own thumbnail when none is uploaded; required for Vimeo videos.<?php echo $is_edit && $image !== '' ? ' Leave empty to keep the current thumbnail.' : ''; ?></div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add video'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/video'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
