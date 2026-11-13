<?php
/* Add / edit a discount coupon. $res = the coupon (defaults when adding). */
$is_edit = isset($res['coupon_id']);
$action  = $is_edit ? 'sitepanel/coupons/edit/'.(int) $res['coupon_id'] : 'sitepanel/coupons/add';
$value   = function ($key) use ($res) {
	$stored = (string) $res[$key];
	if (in_array($key, array('coupon_discount', 'maximum_coupon_discount', 'minimum_order_amount'), TRUE) && (float) $stored === 0.0) {
		$stored = $key === 'coupon_discount' ? $stored : '';
	}
	return set_value($key, $stored);
};
$type  = (string) set_value('coupon_type', (string) $res['coupon_type'], FALSE);
$usage = (string) set_value('coupon_usage', (string) $res['coupon_usage'], FALSE);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit coupon' : 'New coupon'; ?></h2></header>
		<div class="row g-3">
			<?php if ($is_edit): ?>
			<div class="col-12">
				<span class="form-label d-block">Coupon code</span>
				<strong><code><?php echo html_escape($res['coupon_code']); ?></code></strong>
				<div class="form-text">Generated when the coupon was added; it can't be changed.</div>
			</div>
			<?php endif; ?>
			<div class="col-12 col-lg-8">
				<label class="form-label" for="coupon_title">Coupon title <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="coupon_title" name="coupon_title" maxlength="150" required value="<?php echo $value('coupon_title'); ?>">
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="coupon_type">Coupon type <span class="text-danger">*</span></label>
				<select class="form-select" id="coupon_type" name="coupon_type" required>
					<?php foreach (\Modules\Sitepanel\Controllers\Coupons::TYPES as $key => $label): ?>
					<option value="<?php echo $key; ?>"<?php echo $key === $type ? ' selected' : ''; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="coupon_discount">Coupon discount <span class="text-danger">*</span></label>
				<input class="form-control" type="text" inputmode="decimal" id="coupon_discount" name="coupon_discount" maxlength="11" required value="<?php echo $value('coupon_discount'); ?>">
				<div class="form-text">Percentage (less than 99), e.g. 5, or amount, e.g. 120.00.</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="maximum_coupon_discount">Maximum coupon discount <span class="text-danger">**</span></label>
				<input class="form-control" type="text" inputmode="decimal" id="maximum_coupon_discount" name="maximum_coupon_discount" maxlength="11" value="<?php echo $value('maximum_coupon_discount'); ?>">
				<div class="form-text">Percentage coupons only: the most a percentage coupon can take off, e.g. 250.00.</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="minimum_order_amount">Minimum order amount</label>
				<input class="form-control" type="text" inputmode="decimal" id="minimum_order_amount" name="minimum_order_amount" maxlength="11" value="<?php echo $value('minimum_order_amount'); ?>">
				<div class="form-text">Optional, e.g. 1520.00. Empty = any order amount.</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="coupon_usage">Coupon usage <span class="text-danger">*</span></label>
				<select class="form-select" id="coupon_usage" name="coupon_usage" required>
					<?php foreach (\Modules\Sitepanel\Controllers\Coupons::USAGES as $key => $label): ?>
					<option value="<?php echo $key; ?>"<?php echo $key === $usage ? ' selected' : ''; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="usage_count">Number of times coupon can be used <span class="text-danger">**</span></label>
				<input class="form-control" type="number" min="1" max="9999" id="usage_count" name="usage_count" value="<?php echo $value('usage_count'); ?>">
				<div class="form-text">Multiple use only: uses per customer.</div>
			</div>
			<div class="col-12 col-lg-4"></div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="start_date">Coupon start date <span class="text-danger">*</span></label>
				<input class="form-control" type="date" id="start_date" name="start_date" required value="<?php echo $value('start_date'); ?>"<?php echo $is_edit ? '' : ' min="'.date('Y-m-d').'"'; ?>>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="end_date">Coupon end date <span class="text-danger">*</span></label>
				<input class="form-control" type="date" id="end_date" name="end_date" required value="<?php echo $value('end_date'); ?>">
			</div>
			<div class="col-12">
				<p class="form-text mb-0"><span class="text-danger">*</span> Required. <span class="text-danger">**</span> Required for the coupon type or usage noted. The coupon is available to all customers.</p>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add coupon'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/coupons'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
