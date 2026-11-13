<?php
/* Category add/edit form. */
$is_edit = isset($res['category_id']);
$value = static function (string $field, string $default = '') use ($res): string {
	return (string) set_value($field, (string) ($res[$field] ?? $default));
};
$parent_id = (int) ($res['parent_id'] ?? 0);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', ['errors' => $errors]); ?>
<section class="sp-panel mb-4" aria-labelledby="category-form-title">
	<header class="sp-panel-head sp-panel-head--wrap"><h2 class="sp-panel-title" id="category-form-title"><?php echo html_escape($heading_title); ?></h2><a class="btn sp-btn-outline" href="<?php echo site_url($back_link); ?>">Cancel</a></header>
	<?php echo form_open_multipart($action, ['id' => 'category-form']); ?>
	<div class="row g-3">
		<div class="col-12 col-lg-6"><label class="form-label" for="category_name"><?php echo html_escape($heading_title); ?> Name <span class="text-danger">*</span></label><input class="form-control" type="text" id="category_name" name="category_name" maxlength="100" required placeholder="Your Category Name" value="<?php echo html_escape($value('category_name')); ?>"></div>
		<?php /* Page URL: made by the server from the name (Category::page_url_of()); shown read-only and not submitted (no name attribute). */ ?>
		<div class="col-12 col-lg-6"><label class="form-label" for="friendly_url">Page URL</label><?php if ($parent): ?><div class="input-group"><span class="input-group-text"><?php echo html_escape(rtrim($parent['friendly_url'], '/')); ?>/</span><?php endif; ?><input class="form-control sp-input-readonly" type="text" id="friendly_url" readonly tabindex="-1" aria-describedby="friendly-url-help" data-slug-source="category_name" data-slug-max="<?php echo \Modules\Sitepanel\Controllers\Category::SLUG_MAX; ?>" placeholder="Made from the name" value="<?php echo html_escape((string) ($res['friendly_url'] ?? '')); ?>" data-saved-name="<?php echo html_escape($is_edit ? (string) $res['category_name'] : ''); ?>" data-saved-url="<?php echo html_escape($is_edit ? (string) ($saved_segment ?? $res['friendly_url']) : ''); ?>"><?php if ($parent): ?></div><?php endif; ?><div class="form-text" id="friendly-url-help">Made automatically from the name when saved: lowercase letters, numbers and hyphens. If it is already used, -2, -3… is added. It stays the same while the name is unchanged.</div></div>
<div class="col-12 col-lg-6"><label class="form-label" for="category_image"><?php echo $is_edit ? 'Upload Image' : 'Image'; ?></label><?php if ($image !== ''): ?><img class="sp-thumb d-block mb-2" src="<?php echo html_escape(get_image('category', basename($image), 192, 108, 'C')); ?>" alt="Current category image" width="100" onerror="this.classList.remove('d-block');this.style.display='none';this.nextElementSibling.style.display='block';"><span class="sp-muted small mb-2" style="display:none">No Image</span><div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="cat_img_delete" name="cat_img_delete" value="Y"><label class="form-check-label" for="cat_img_delete">Delete current image</label></div><?php endif; ?><input class="form-control" type="file" id="category_image" name="category_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"><div class="form-text"><?php echo html_escape($note); ?></div></div>
		<div class="col-12 col-lg-6"><label class="form-label" for="category_alt">Alt</label><input class="form-control" type="text" id="category_alt" name="category_alt" maxlength="100" value="<?php echo html_escape($value('category_alt')); ?>"></div>
	</div>
	<div class="sp-form-actions mt-4"><button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Update' : sp_icon('plus').' '.'Add'; ?></button><a class="btn sp-btn-outline" href="<?php echo site_url($back_link); ?>">Cancel</a></div>
	<?php echo form_close(); ?>
</section>
<script src="<?php echo asset_url('assets/sitepanel/js/page-slug.js'); ?>"></script>
<?php echo sp_view('includes/footer'); ?>
