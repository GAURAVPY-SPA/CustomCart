<?php
/*
 * Add / edit a news item. $res = the row (empty values when adding) plus
 * meta_title/meta_keyword/meta_description; $image = current image path
 * ('' = none); $meta = the page's wl_meta_tags row (NULL = none);
 * $page_url = address of the website's News & Events page.
 * Content uses the full CKEditor (editor.js) with image upload.
 */
use Modules\Sitepanel\Controllers\News;

$is_edit = isset($res['news_id']);
$action  = $is_edit ? 'sitepanel/news/edit/'.(int) $res['news_id'] : 'sitepanel/news/add';
$value   = function ($key) use ($res) {
	return set_value($key, (string) $res[$key]);
};
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit news' : 'New news'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="news_title">Title <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="news_title" name="news_title" maxlength="200" required value="<?php echo $value('news_title'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="friendly_url">URL slug</label>
				<input class="form-control" type="text" id="friendly_url" name="friendly_url" maxlength="150" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?php echo $value('friendly_url'); ?>">
				<div class="form-text">Page address: <?php echo html_escape(website_url($page_url !== '' ? $page_url.'/' : '')); ?>&lt;slug&gt;. Leave empty to build it from the title.</div>
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="news_category">Type <span class="text-danger">*</span></label>
				<select class="form-select" id="news_category" name="news_category" required>
					<?php $type = set_value('news_category', (string) $res['news_category'] !== '' ? (string) $res['news_category'] : 'News'); ?>
					<?php foreach (\Modules\News\Controllers\News::CATEGORIES as $option): ?>
					<option value="<?php echo $option; ?>"<?php echo $type === $option ? ' selected' : ''; ?>><?php echo $option; ?></option>
					<?php endforeach; ?>
				</select>
				<div class="form-text">Shown on the news card and the item page.</div>
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="publish_date">Publication date <span class="text-danger">*</span></label>
				<input class="form-control" type="date" id="publish_date" name="publish_date" required value="<?php echo $value('publish_date'); ?>">
			</div>
			<div class="col-12">
				<label class="form-label" for="short_description">Short description</label>
				<textarea class="form-control" id="short_description" name="short_description" rows="2" maxlength="500"><?php echo $value('short_description'); ?></textarea>
				<div class="form-text">The 2–3 line excerpt on the news cards; also the default meta description.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="description">Content</label>
				<textarea class="form-control" id="description" name="description" rows="14" data-sp-editor data-sp-upload-url="<?php echo html_escape(site_url('sitepanel/news/upload_image')); ?>"><?php echo set_value('description', (string) $res['description']); ?></textarea>
				<div class="form-text">The full article. The first paragraph is shown as the lead; Heading 2 / Heading 3 and lists take the design's styles.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="news_image">Featured image</label>
				<?php if ($is_edit && $image !== ''): ?>
				<p class="mb-2"><img class="sp-thumb" src="<?php echo html_escape(get_image('news', basename($image), 192, 108, 'C')); ?>" alt="Current image"></p>
				<div class="form-check mb-2">
					<input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1"<?php echo set_checkbox('remove_image', '1'); ?>>
					<label class="form-check-label" for="remove_image">Remove this image</label>
				</div>
				<?php endif; ?>
				<input class="form-control" type="file" id="news_image" name="news_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
				<div class="form-text">JPG, PNG, GIF or WebP, up to 2 MB. Best <?php echo News::BEST_SIZE; ?>.<?php echo $is_edit && $image !== '' ? ' Leave empty to keep the current image.' : ''; ?></div>
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="sort_order">Display order</label>
				<input class="form-control" type="number" id="sort_order" name="sort_order" min="0" max="9999" value="<?php echo $value('sort_order'); ?>">
				<div class="form-text">0 = by date, newest first.</div>
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="status">Status <span class="text-danger">*</span></label>
				<select class="form-select" id="status" name="status" required>
					<option value="1"<?php echo set_select('status', '1', $res['status'] === '1'); ?>>Active</option>
					<option value="0"<?php echo set_select('status', '0', $res['status'] !== '1'); ?>>Inactive</option>
				</select>
			</div>
		</div>
	</section>

	<section class="sp-panel mb-4" aria-labelledby="news-seo-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="news-seo-title">SEO</h2></header>
		<p class="sp-muted small">Optional. Empty fields use the title and short description. Stored with the Meta Tags<?php if ( ! empty($meta['meta_id'])): ?> (<a class="sp-link" href="<?php echo site_url('sitepanel/meta/edit/'.(int) $meta['meta_id']); ?>">open in Meta Tags</a>)<?php endif; ?>.</p>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="meta_title">Meta title</label>
				<input class="form-control" type="text" id="meta_title" name="meta_title" maxlength="450" value="<?php echo $value('meta_title'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="meta_keyword">Meta keywords</label>
				<input class="form-control" type="text" id="meta_keyword" name="meta_keyword" maxlength="450" value="<?php echo $value('meta_keyword'); ?>">
			</div>
			<div class="col-12">
				<label class="form-label" for="meta_description">Meta description</label>
				<textarea class="form-control" id="meta_description" name="meta_description" rows="2" maxlength="650"><?php echo $value('meta_description'); ?></textarea>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add news'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/news'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<script src="<?php echo asset_url('assets/vendor/ckeditor4/ckeditor.js'); ?>"></script>
<script src="<?php echo asset_url('assets/sitepanel/js/editor.js'); ?>"></script>
<?php echo sp_view('includes/footer'); ?>
