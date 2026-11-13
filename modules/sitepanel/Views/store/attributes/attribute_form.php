<?php
/*
 * Add / edit a colour, size, brand or material (StoreLookup). $res = id
 * (edit), name, color_code, sort_order, status; $image = current logo path.
 */
$is_edit = isset($res['id']);
$action  = $is_edit ? $base.'/edit/'.(int) $res['id'] : $base.'/add';
$value   = function ($key) use ($res) {
	return set_value($key, (string) $res[$key]);
};
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo $has_image ? form_open_multipart($action, array('novalidate' => 'novalidate')) : form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo ($is_edit ? 'Edit ' : 'New ').html_escape(strtolower($item)); ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="name"><?php echo html_escape($item); ?> name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="name" name="name" maxlength="<?php echo (int) $name_max; ?>" required value="<?php echo $value('name'); ?>">
			</div>
			<?php if ($kind === 'color'): ?>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="color_code">Colour code <span class="text-danger">*</span></label>
				<div class="d-flex gap-2 align-items-center">
					<input class="form-control form-control-color" type="color" id="color_picker" value="<?php echo preg_match('/^#[0-9a-fA-F]{6}$/', (string) set_value('color_code', $res['color_code'], FALSE)) ? html_escape(set_value('color_code', $res['color_code'], FALSE)) : '#000000'; ?>" aria-label="Pick the colour" oninput="document.getElementById('color_code').value=this.value">
					<input class="form-control" type="text" id="color_code" name="color_code" maxlength="7" pattern="#[0-9a-fA-F]{6}" required value="<?php echo $value('color_code'); ?>" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){document.getElementById('color_picker').value=this.value}">
				</div>
				<div class="form-text">The swatch shown on the product page, as #rrggbb.</div>
			</div>
			<?php endif; ?>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="sort_order">Display order <span class="text-danger">*</span></label>
				<input class="form-control" type="number" min="0" max="999999" id="sort_order" name="sort_order" required value="<?php echo $value('sort_order'); ?>">
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="status">Status <span class="text-danger">*</span></label>
				<select class="form-select" id="status" name="status" required>
					<option value="1"<?php echo set_select('status', '1', $res['status'] === '1'); ?>>Active</option>
					<option value="0"<?php echo set_select('status', '0', $res['status'] !== '1'); ?>>Inactive</option>
				</select>
			</div>
			<?php if ($has_image): ?>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="image">Logo</label>
				<?php if ($image !== ''): ?>
				<img class="sp-thumb mb-2 d-block" src="<?php echo html_escape(get_image($kind, basename($image), 192, 108, 'C')); ?>" alt="Current logo">
				<?php endif; ?>
				<input class="form-control" type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
				<div class="form-text">Optional. JPG, PNG, GIF or WebP, up to 2 MB and 2000 × 2000 pixels.</div>
				<?php if ($image !== ''): ?>
				<div class="form-check mt-2">
					<input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1"<?php echo set_checkbox('remove_image', '1'); ?>>
					<label class="form-check-label" for="remove_image">Remove the current logo</label>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add '.html_escape(strtolower($item)); ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url($base); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
