<?php
/* Add / edit a country. $res = the country (empty values when adding). */
$is_edit = isset($res['country_id']);
$action  = $is_edit ? 'sitepanel/location/edit/'.(int) $res['country_id'] : 'sitepanel/location/add';
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit country' : 'New country'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="name">Country name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="name" name="name" maxlength="128" required value="<?php echo set_value('name', (string) $res['name']); ?>">
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="iso_code_2">ISO code (2 letters)</label>
				<input class="form-control" type="text" id="iso_code_2" name="iso_code_2" maxlength="2" value="<?php echo set_value('iso_code_2', (string) $res['iso_code_2']); ?>">
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="iso_code_3">ISO code (3 letters)</label>
				<input class="form-control" type="text" id="iso_code_3" name="iso_code_3" maxlength="3" value="<?php echo set_value('iso_code_3', (string) $res['iso_code_3']); ?>">
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add country'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/location'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
