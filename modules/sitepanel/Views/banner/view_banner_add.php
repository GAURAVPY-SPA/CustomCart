<?php
/* Add a banner. $positions = position => best size hint. */
$value = function ($key) {
	return set_value($key, '');
};
$selected = set_value('banner_position', '', FALSE);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart('sitepanel/banners/add', array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title">New banner</h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="banner_position">Banner position <span class="text-danger">*</span></label>
				<select class="form-select" id="banner_position" name="banner_position" required>
					<option value="">Select position</option>
					<?php foreach ($positions as $name => $size): ?>
					<option value="<?php echo html_escape($name); ?>"<?php echo $name === $selected ? ' selected' : ''; ?>><?php echo html_escape($name.($size !== '' ? ' — best size '.$size.' px' : '')); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="category_id">Category <span class="sp-muted">(Category Pages only)</span></label>
				<select class="form-select" id="category_id" name="category_id">
					<option value="">—</option>
					<?php foreach ($categories as $cid => $cname): ?>
					<option value="<?php echo (int) $cid; ?>"<?php echo (string) $cid === (string) set_value('category_id', (string) ($res['category_id'] ?? ''), FALSE) ? ' selected' : ''; ?>><?php echo html_escape($cname); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="form-text">The banner shown at the top of this category's page (and of its sub-categories without one of their own).</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="sort_order">Display order</label>
				<input class="form-control" type="number" min="0" max="9999" id="sort_order" name="sort_order" value="<?php echo (int) set_value('sort_order', (string) ($res['sort_order'] ?? '0'), FALSE); ?>">
				<div class="form-text">Lowest first, when a category has more than one active banner.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="banner_title">Banner text</label>
				<input class="form-control" type="text" id="banner_title" name="banner_title" maxlength="30" value="<?php echo $value('banner_title'); ?>">
				<div class="form-text">Up to 30 characters.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="banner_image">Banner image <span class="text-danger">*</span></label>
				<input class="form-control" type="file" id="banner_image" name="banner_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" required>
				<div class="form-text">JPG, PNG, GIF or WebP, up to 2 MB.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="banner_url">Link URL</label>
				<input class="form-control" type="url" id="banner_url" name="banner_url" maxlength="255" value="<?php echo $value('banner_url'); ?>">
				<div class="form-text">Optional, e.g. <code>https://example.com/offer</code>.</div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo sp_icon('plus'); ?> Add banner</button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/banners'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
