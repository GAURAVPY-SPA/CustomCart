<?php
/* Add / edit a state. $res = the state (country preselected when adding), $countries = id => name. */
$is_edit  = isset($res['state_id']);
$action   = $is_edit ? 'sitepanel/location/state_edit/'.(int) $res['state_id'] : 'sitepanel/location/state_add/'.(int) $country['country_id'];
$selected = (string) set_value('country_id', (string) $res['country_id'], FALSE);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit state' : 'New state'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="country_id">Country <span class="text-danger">*</span></label>
				<select class="form-select" id="country_id" name="country_id" required>
					<?php foreach ((array) $countries as $id => $name): ?>
					<option value="<?php echo (int) $id; ?>"<?php echo (string) $id === $selected ? ' selected' : ''; ?>><?php echo html_escape($name); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="state_name">State name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="state_name" name="state_name" maxlength="150" required value="<?php echo set_value('state_name', (string) $res['state_name']); ?>">
				<div class="form-text">Each state name can be used once per country.</div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add state'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/location/states/'.(int) $country['country_id']); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
