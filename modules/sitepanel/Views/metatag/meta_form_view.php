<?php 
$is_edit = isset($res['meta_id']);
$action = $is_edit ? 'sitepanel/meta/edit/'.(int) $res['meta_id'] : 'sitepanel/meta/add';
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="meta-form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="meta-form-title"><?php echo $is_edit ? 'Edit Meta Tag' : 'Add Meta Tag'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="page_url">URL <span class="text-danger">*</span></label>
				<div class="input-group">
					<span class="input-group-text"><?php echo html_escape(website_url()); ?></span>
					<input class="form-control" type="text" id="page_url" name="page_url" maxlength="200" required value="<?php echo set_value('page_url', (string) $res['page_url']); ?>"<?php echo $is_edit ? ' readonly' : ''; ?>>
				</div>
					<div class="form-text">Enter the page slug used after the domain, such as <code>aboutus</code>. The public URL will be <code><?php echo html_escape(website_url()); ?>aboutus</code>.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="meta_title">Title <span class="text-danger">*</span></label>
				<textarea class="form-control" id="meta_title" name="meta_title" rows="3" maxlength="450" required><?php echo set_value('meta_title', (string) $res['meta_title']); ?></textarea>
			</div>
			<div class="col-12">
				<label class="form-label" for="meta_keyword">Keywords <span class="text-danger">*</span></label>
				<textarea class="form-control" id="meta_keyword" name="meta_keyword" rows="3" maxlength="450" required><?php echo set_value('meta_keyword', (string) $res['meta_keyword']); ?></textarea>
			</div>
			<div class="col-12">
				<label class="form-label" for="meta_description">Description <span class="text-danger">*</span></label>
				<textarea class="form-control" id="meta_description" name="meta_description" rows="5" maxlength="650" required><?php echo set_value('meta_description', (string) $res['meta_description']); ?></textarea>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add Meta Tag'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/meta'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
