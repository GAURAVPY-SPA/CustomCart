<?php
/* Add / edit a certificate or brochure. $res = the row (empty values when adding),
   $image / $file = current image / PDF path ('' = none). */
use Modules\Certificates\Models\CertificateModel;

$is_edit = isset($res['certificate_id']);
$action  = $is_edit ? 'sitepanel/certificates/edit/'.(int) $res['certificate_id'] : 'sitepanel/certificates/add';
$type    = set_value('cert_type', (string) $res['cert_type']);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit entry' : 'New entry'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-md-4">
				<label class="form-label" for="cert_type">Type <span class="text-danger">*</span></label>
				<select class="form-select" id="cert_type" name="cert_type" required>
					<?php foreach (CertificateModel::TYPES as $key => $label): ?>
					<option value="<?php echo $key; ?>"<?php echo $type === $key ? ' selected' : ''; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
				<div class="form-text">Certificates are shown on the Certificates page (image required); brochures on the Brochure page (PDF required).</div>
			</div>
			<div class="col-12 col-md-8">
				<label class="form-label" for="title">Title <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="title" name="title" maxlength="150" required value="<?php echo set_value('title', (string) $res['title']); ?>">
			</div>
			<div class="col-12">
				<label class="form-label" for="description">Description</label>
				<textarea class="form-control" id="description" name="description" rows="2" maxlength="255"><?php echo set_value('description', (string) $res['description']); ?></textarea>
				<div class="form-text">Up to 255 characters. Shown on the brochure cards.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="cert_image">Image</label>
				<?php if ($is_edit && $image !== ''): ?>
				<p class="mb-2"><img class="sp-thumb" src="<?php echo html_escape(get_image(CertificateModel::IMAGE_FOLDER, basename($image), 108, 144, 'C')); ?>" alt="Current image"></p>
				<?php endif; ?>
				<input class="form-control" type="file" id="cert_image" name="cert_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
				<div class="form-text">Required for a certificate. JPG, PNG, GIF or WebP, up to 2 MB. Best <?php echo \Modules\Sitepanel\Controllers\Certificates::BEST_SIZE; ?>.<?php echo $is_edit ? ' Leave empty to keep the current image.' : ''; ?></div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="cert_file">PDF</label>
				<?php if ($is_edit && $file !== ''): ?>
				<p class="mb-2"><a href="<?php echo base_url($file); ?>" target="_blank" rel="noopener noreferrer">View the current PDF</a></p>
				<?php endif; ?>
				<input class="form-control" type="file" id="cert_file" name="cert_file" accept=".pdf,application/pdf">
				<div class="form-text">Required for a brochure (its download); optional for a certificate (linked from its title). PDF, up to 10 MB.<?php echo $is_edit ? ' Leave empty to keep the current file.' : ''; ?></div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add entry'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/certificates'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
