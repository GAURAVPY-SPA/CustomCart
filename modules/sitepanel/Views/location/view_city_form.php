<?php
/* Add / edit a city. $res = the city (state preselected when adding), $country, $state, $states = the country's states (id => name). */
$is_edit  = isset($res['city_id']);
$action   = $is_edit ? 'sitepanel/location/city_edit/'.(int) $res['city_id'] : 'sitepanel/location/city_add/'.(int) $state['state_id'];
$selected = (string) set_value('state_id', (string) $res['state_id'], FALSE);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit city' : 'New city'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-4">
				<label class="form-label" for="country_name">Country</label>
				<input class="form-control" type="text" id="country_name" value="<?php echo html_escape($country['name']); ?>" readonly>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="state_id">State <span class="text-danger">*</span></label>
				<select class="form-select" id="state_id" name="state_id" required>
					<?php foreach ((array) $states as $id => $name): ?>
					<option value="<?php echo (int) $id; ?>"<?php echo (string) $id === $selected ? ' selected' : ''; ?>><?php echo html_escape($name); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="city_name">City name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="city_name" name="city_name" maxlength="150" required value="<?php echo set_value('city_name', (string) $res['city_name']); ?>">
				<div class="form-text">Each city name can be used once per state.</div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add city'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/location/cities/'.(int) $state['state_id']); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
