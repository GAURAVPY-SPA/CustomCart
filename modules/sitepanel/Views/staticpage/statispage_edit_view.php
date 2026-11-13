<?php
/* Add / edit a static page: Page Title, page address (when adding) and Description (CKEditor). */
$is_edit = isset($res['page_id']);
$action  = $is_edit ? 'sitepanel/staticpages/edit/'.(int) $res['page_id'] : 'sitepanel/staticpages/add';
echo sp_view('includes/header');
?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-page">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-page"><?php echo $is_edit ? 'Edit page' : 'New page'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="page_name">Page Title <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="page_name" name="page_name" maxlength="50" required value="<?php echo set_value('page_name', (string) $res['page_name']); ?>">
			</div>
			<div class="col-12">
				<label class="form-label" for="friendly_url">Page address <span class="text-danger">*</span></label>
				<div class="input-group">
					<span class="input-group-text"><?php echo html_escape(website_url()); ?></span>
					<input class="form-control" type="text" id="friendly_url" name="friendly_url" maxlength="50" required value="<?php echo set_value('friendly_url', (string) $res['friendly_url']); ?>">
				</div>
				<div class="form-text">Small letters, digits and hyphens, e.g. <code>mission-and-vision</code>. The page is shown at this address while it is active.<?php echo $is_edit ? ' Changing it moves the page (the old address stops working).' : ''; ?></div>
			</div>
			<div class="col-12">
				<?php $designed = $layout !== \Modules\Pages\Models\PagesModel::TEXT_LAYOUT; ?>
				<?php $shown = \Modules\Pages\Controllers\Pages::shows_description($layout); ?>
				<label class="form-label" for="page_description">Description<?php if ($shown): ?> <span class="text-danger">*</span><?php endif; ?></label>
				<?php if ($layout === 'contact_us'): ?>
				<div class="form-text mt-0 mb-2">This is the built-in Contact Us page: the Description is the text of the contact information panel next to the message form. The address, phone, email, hours, social links and map come from Admin Settings.</div>
				<?php elseif ($layout === 'about_us'): ?>
				<div class="form-text mt-0 mb-2">This is the built-in About Us page: the Description is the text at the top of the page. Mission &amp; Vision and the Our Team slider (Manage Team &amp; Partners) follow it.</div>
				<?php elseif ($designed): ?>
				<div class="form-text mt-0 mb-2">This is a built-in page of the website with its own design; the Description is optional and not shown on it.</div>
				<?php endif; ?>
				<textarea class="form-control" id="page_description" name="page_description" rows="14" data-sp-editor data-sp-upload-url="<?php echo html_escape(site_url('sitepanel/staticpages/upload_image')); ?>"><?php echo set_value('page_description', (string) $res['page_description']); ?></textarea>
			</div>
		</div>
	</section>

	<section class="sp-panel mb-4" aria-labelledby="form-seo">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-seo">SEO</h2></header>
		<p class="form-text mt-0">Saved as this page's Meta Tags record. Leave a field empty to use the page: the Page Title for the title and keywords, the start of the Description for the description.</p>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="meta_title">Meta title</label>
				<textarea class="form-control" id="meta_title" name="meta_title" rows="2" maxlength="450"><?php echo set_value('meta_title', (string) $seo['meta_title']); ?></textarea>
			</div>
			<div class="col-12">
				<label class="form-label" for="meta_keyword">Meta keywords</label>
				<textarea class="form-control" id="meta_keyword" name="meta_keyword" rows="2" maxlength="450"><?php echo set_value('meta_keyword', (string) $seo['meta_keyword']); ?></textarea>
			</div>
			<div class="col-12">
				<label class="form-label" for="meta_description">Meta description</label>
				<textarea class="form-control" id="meta_description" name="meta_description" rows="3" maxlength="650"><?php echo set_value('meta_description', (string) $seo['meta_description']); ?></textarea>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Update' : sp_icon('plus').' '.'Add page'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/staticpages'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>

<script src="<?php echo asset_url('assets/vendor/ckeditor4/ckeditor.js'); ?>"></script>
<script src="<?php echo asset_url('assets/sitepanel/js/editor.js'); ?>"></script>
<?php echo sp_view('includes/footer'); ?>
