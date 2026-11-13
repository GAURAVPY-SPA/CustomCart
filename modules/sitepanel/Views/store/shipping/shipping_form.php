<?php
/* Add / edit a delivery method. $res = the method (defaults when adding). */
$is_edit = isset($res['shipping_id']);
$action  = $is_edit ? 'sitepanel/shipping/edit/'.(int) $res['shipping_id'] : 'sitepanel/shipping/add';
$amount  = function ($key) use ($res) {
	$stored = (string) $res[$key];
	return set_value($key, $key === 'free_above' && (float) $stored === 0.0 ? '' : $stored);
};
echo sp_view('includes/header'); ?>
<div class="alert alert-info" role="status">The online store charges the <strong>default active</strong> delivery method (or the first active one): its rate, free when the cart subtotal reaches its <strong>Free above</strong> amount. With no active method, delivery is free. VAT is set in <a href="<?php echo site_url('sitepanel/settings'); ?>">Admin Settings</a>.</div>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit delivery method' : 'New delivery method'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-8">
				<label class="form-label" for="shipping_type">Delivery method name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="shipping_type" name="shipping_type" maxlength="100" required value="<?php echo set_value('shipping_type', (string) $res['shipping_type']); ?>">
				<div class="form-text">Shown to customers in the cart, e.g. Island-wide Delivery (3–5 working days).</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="status">Status <span class="text-danger">*</span></label>
				<select class="form-select" id="status" name="status" required>
					<option value="1"<?php echo set_select('status', '1', $res['status'] === '1'); ?>>Active</option>
					<option value="0"<?php echo set_select('status', '0', $res['status'] !== '1'); ?>>Inactive</option>
				</select>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="shipment_rate">Rate <span class="text-danger">*</span></label>
				<input class="form-control" type="text" inputmode="decimal" id="shipment_rate" name="shipment_rate" maxlength="11" required value="<?php echo $amount('shipment_rate'); ?>">
				<div class="form-text">Flat charge per order, e.g. 6.00. Use 0 for a free method such as self collection.</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="free_above">Free delivery from</label>
				<input class="form-control" type="text" inputmode="decimal" id="free_above" name="free_above" maxlength="11" value="<?php echo $amount('free_above'); ?>">
				<div class="form-text">Optional: the method costs nothing once the cart reaches this amount, e.g. 150.00.</div>
			</div>
			<div class="col-12 col-lg-4">
				<span class="form-label d-block" id="delivery_days_label">Delivery days <span class="text-danger">*</span></span>
				<div class="input-group" role="group" aria-labelledby="delivery_days_label">
					<input class="form-control" type="number" min="0" max="365" id="delivery_min_days" name="delivery_min_days" aria-label="Delivery days from" required value="<?php echo set_value('delivery_min_days', (string) $res['delivery_min_days']); ?>">
					<span class="input-group-text">to</span>
					<input class="form-control" type="number" min="0" max="365" id="delivery_max_days" name="delivery_max_days" aria-label="Delivery days to" required value="<?php echo set_value('delivery_max_days', (string) $res['delivery_max_days']); ?>">
					<span class="input-group-text">days</span>
				</div>
				<div class="form-text">Days after the order is placed, e.g. 3 to 5 (Standard) or 1 to 2 (Express). Shown to customers as the expected delivery.</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="sort_order">Display order</label>
				<input class="form-control" type="number" min="0" max="9999" id="sort_order" name="sort_order" value="<?php echo set_value('sort_order', (string) $res['sort_order']); ?>">
			</div>
			<div class="col-12">
				<p class="form-text mb-0"><span class="text-danger">*</span> Required. Prices include GST. Orders keep the name, rate and delivery days they were placed with.</p>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add delivery method'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/shipping'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
