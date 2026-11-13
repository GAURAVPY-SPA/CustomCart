<?php
/* Add / edit a FAQ. $res = the FAQ (empty values when adding). */
$is_edit = isset($res['faq_id']);
$action  = $is_edit ? 'sitepanel/faq/edit/'.(int) $res['faq_id'] : 'sitepanel/faq/add';
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit FAQ' : 'New FAQ'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label" for="faq_question">Question <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="faq_question" name="faq_question" maxlength="220" required value="<?php echo set_value('faq_question', (string) $res['faq_question']); ?>">
				<div class="form-text">Up to 220 characters. Each question can be used once.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="faq_answer">Answer <span class="text-danger">*</span></label>
				<textarea class="form-control" id="faq_answer" name="faq_answer" rows="10" maxlength="8500" required><?php echo set_value('faq_answer', (string) $res['faq_answer']); ?></textarea>
				<div class="form-text">Plain text, up to 8500 characters. Line breaks are kept on the website.</div>
			</div>
			<div class="col-12">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="is_general" name="is_general" value="1"<?php echo set_checkbox('is_general', '1', (string) ($res['is_general'] ?? '1') === '1'); ?>>
					<label class="form-check-label" for="is_general">Show on the website's FAQ page (and the home page FAQ)</label>
				</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="products">Show on these product pages</label>
				<?php $chosen = array_map('intval', (array) (service('request')->getPost('products') ?? $assigned)); ?>
				<select class="form-select" id="products" name="products[]" multiple size="8">
					<?php foreach ($products as $pid => $pname): ?>
					<option value="<?php echo (int) $pid; ?>"<?php echo in_array((int) $pid, $chosen, TRUE) ? ' selected' : ''; ?>><?php echo html_escape($pname); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="form-text">Hold Ctrl (Cmd on a Mac) to choose several products. The FAQ appears in the FAQs tab of each product page chosen.</div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add FAQ'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/faq'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
