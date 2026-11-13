<?php
/* Add / edit a cancel or return reason. $res = the reason (defaults when adding). */
$is_edit = isset($res['reason_id']);
$action  = $is_edit ? 'sitepanel/reasons/edit/'.(int) $res['reason_id'] : 'sitepanel/reasons/add';
$type    = (string) set_value('reason_type', (string) $res['reason_type'], FALSE);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit reason' : 'New reason'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-8">
				<label class="form-label" for="reason_name">Reason <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="reason_name" name="reason_name" maxlength="255" required value="<?php echo set_value('reason_name', (string) $res['reason_name']); ?>">
				<div class="form-text">Shown to customers when they ask to cancel or return an item, e.g. Ordered by mistake.</div>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="reason_type">Type <span class="text-danger">*</span></label>
				<select class="form-select" id="reason_type" name="reason_type" required>
					<?php foreach (\Modules\Cart\Models\ReasonModel::TYPES as $key => $label): ?>
					<option value="<?php echo $key; ?>"<?php echo (string) $key === $type ? ' selected' : ''; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="sort_order">Display order</label>
				<input class="form-control" type="number" min="0" max="9999" id="sort_order" name="sort_order" value="<?php echo set_value('sort_order', (string) $res['sort_order']); ?>">
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="status">Status <span class="text-danger">*</span></label>
				<select class="form-select" id="status" name="status" required>
					<option value="1"<?php echo set_select('status', '1', $res['status'] === '1'); ?>>Active</option>
					<option value="0"<?php echo set_select('status', '0', $res['status'] !== '1'); ?>>Inactive</option>
				</select>
			</div>
			<div class="col-12">
				<p class="form-text mb-0"><span class="text-danger">*</span> Required. Requests already made keep the reason text they were made with.</p>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add reason'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/reasons'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
